<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Validator;
use Stripe;
use App\Http\Controllers\MailController;

class StripeController extends Controller
{
    public function payWithStripe(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;


            $validator = Validator::make($request->all(),[ 
                'card_number' => 'required',
                'expiry_month' => 'required',
                'expiry_year' => 'required',
                'cvc' => 'required',
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  


            $course_id = check_if_null_or_empty($request->course_id);

            //get all the information about the course including amount
            $course_result = DB::select('select c.course_name, c.price, c.is_discounted, c.discount_price 
                                        from courses c where c.is_deleted = 0 and c.id = :id', ['id' => $course_id]);
        
            if(!empty($course_result)){
                $amount_to_be_paid = $course_result[0]->price;

                $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

                $customer = $stripe->customers->create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone_number
                ]);

                $stripe_token_result = $stripe->tokens->create([
                    'card' => [
                        'number' => $request->card_number,
                        'exp_month' => $request->expiry_month,
                        'exp_year' => $request->expiry_year,
                        'cvc' => $request->cvc
                    ]
                ]);


                $admin_revenue = (floatval(get_property_value('ADMIN_SHARE_PERCENTAGE')) * $amount_to_be_paid) / 100;
                $instructor_revenue = (floatval(get_property_value('INSTRUCTOR_SHARE_PERCENTAGE')) * $amount_to_be_paid) / 100;

                $data = array(
                    'payment_type' => 'stripe',
                    'course_id' => $course_id,
                    'user_id' => $user_id,
                    'amount' => $amount_to_be_paid,
                    'details' => 'Stripe token: ' . $stripe_token_result->id,
                    'is_instructor_payed' => Config::get('constants.false'),
                    'admin_revenue' => $admin_revenue,
                    'instructor_revenue' => $instructor_revenue,
                    'channel' => 'stripe',
                    'status' => Config::get('constants.pending'),
                    'created_at' => get_current_date_time(),
                    'ip_address' => $stripe_token_result->client_ip,
                    'currency' => 'GBP'
                );
        
                $transaction_id = DB::table('transaction')->insertGetId($data);
    
                Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

                $stripe_charge_response = $stripe->charges->create([
                    'amount' => (float)($amount_to_be_paid * 100),
                    'currency' => 'gbp',
                    'source' => $stripe_token_result->id,
                    'description' => $request->description
                ]);

                if($stripe_charge_response->status == 'succeeded'){
                    // Approve payment transaction
                    $data = array(
                        'transaction_reference' => $stripe_charge_response->id,
                        'payer_id' => $customer->id,
                        'status' => $stripe_charge_response->status,
                        'updated_at' => get_current_date_time()
                    );

                    DB::table('transaction')->where('id', $transaction_id)->update($data);

                    // Enroll user into course
                    $enrolment_data = array(
                        'course_id' => $course_id,
                        'user_id' => $user_id,
                        'is_completed' => Config::get('constants.false'),
                        'date_started' => get_current_date_time()
                    );

                    $enrolment_id = DB::table('enrolment_history')->insertGetId($enrolment_data);


                    // Enrolment and payment successful emails 
                    $user_fullName = $user->name;
                    $user_email = $user->email;
                    $user_phoneNumber = $user->phone_number;
                    $course_name = $course_result[0]->course_name;
 
 
                    // Enrolment and payment successful emails
                    MailController::send_payment_success_mail($user_fullName, $user_email, $user_phoneNumber, $course_id, $course_name, $stripe_charge_response->id,
                                                                'GBP', $amount_to_be_paid, 'stripe', get_current_date_time());
 
                    MailController::send_course_enrolment_mail($user_fullName, $user_email, $course_id, $course_name);


                    //Save activity trail
                    save_activity_trail($user_id, 'Course payment', 'User ('.$user_id.') payed and enroled for a course with id ('.$course_id.')',
                        get_current_date_time());

                    return response()->json([
                        'message' => 'Payment successful',
                        'transaction_reference' => $stripe_charge_response->id,
                        'course_id' => $course_id,
                        'transaction_state' => $stripe_charge_response->status
                    ], 200);

                } else{
                    return response()->json([
                        'message' => 'Payment not successful',
                        'transaction_state' => $stripe_charge_response->status
                    ], 402);
                }
            }

        } catch(\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            return response()->json([
                'message' => $e->getError()->message,
                'state' => $e->getError()->code
            ], $e->getHttpStatus());
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return response()->json([
                'message' => 'Stripe API Authentication Failed',
                'state' => 'error'
            ], 500);
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            return response()->json([
                'message' => 'Cannot connect to Stripe',
                'state' => 'error'
            ], 500);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            return response()->json([
                'message' => 'Stripe payment failed.',
                'state' => 'error'
            ], 500);
        }
    }
}
