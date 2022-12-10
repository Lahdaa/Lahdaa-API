<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Paystack;// Paystack package
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\MailController;

class PaystackController extends Controller
{
    public function getPaymentLink(Request $request){
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
                'course_id' => 'required'
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

                $url = "https://api.paystack.co/transaction/initialize";

                $fields = [
                    'email' => $user->email,
                    'currency' => "NGN",
                    'amount' => $amount_to_be_paid * 100,
                    'first_name' => $user->email,
                    'phone_number' => $user->phone_number,
                ];

                $fields_string = http_build_query($fields);

                //open connection
                $ch = curl_init();
                
                //set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Authorization: Bearer " . env('PAYSTACK_SECRET_KEY'),
                    "Cache-Control: no-cache",
                ));
                
                //So that curl_exec returns the contents of the cURL; rather than echoing it
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                
                //execute post
                $result = curl_exec($ch);

                $result_array = get_object_vars(json_decode($result));

                if($result_array['status']){
                    $admin_revenue = (floatval(get_property_value('ADMIN_SHARE_PERCENTAGE')) * $amount_to_be_paid) / 100;
                    $instructor_revenue = (floatval(get_property_value('INSTRUCTOR_SHARE_PERCENTAGE')) * $amount_to_be_paid) / 100;
    
                    $data = array(
                        'transaction_reference' => $result_array['data']->reference,
                        'payment_type' => 'paystack',
                        'course_id' => $course_id,
                        'user_id' => $user_id,
                        'amount' => $amount_to_be_paid,
                        'details' => $result_array['data']->authorization_url,
                        'is_instructor_payed' => Config::get('constants.false'),
                        'admin_revenue' => $admin_revenue,
                        'instructor_revenue' => $instructor_revenue,
                        'status' => Config::get('constants.pending'),
                        'created_at' => get_current_date_time(),
                    );
            
                    $transaction_id = DB::table('transaction')->insertGetId($data);
    
                    return response()->json([
                        'payment_link_data' => json_decode($result)
                    ], 200);                
                }                
            } else{
                return response()->json([
                    'message' => 'No course with id ' . $course_id,
                    'state' => 'error'
                ], 400);
            }
            
        }catch(\Exception $e) {
            echo $e;
            return response()->json([
                'message' => 'Paystack link generation failed.',
                'state' => 'error'
            ], 500);
        }        
    }

    public function verifyTransaction(Request $request){
        try {
            $validator = Validator::make($request->all(),[ 
                'reference' => 'required'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }

            $transaction_reference = $request->reference;

            $curl = curl_init();
  
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $transaction_reference,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . env('PAYSTACK_SECRET_KEY'),
                "Cache-Control: no-cache",
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            
            if ($err) {
                echo "cURL Error #:" . $err;

                $data = array(
                    'status' => Config::get('constants.failed'),
                    'updated_at' => get_current_date_time()
                );

                DB::table('transaction')->where('transaction_reference', $transaction_reference)->update($data);
            } else {
                $response_array = get_object_vars(json_decode($response));

                if($response_array['data']->status == 'success'){

                    // Approve payment transaction
                    $data = array(
                        'payer_id' => $response_array['data']->customer->id,
                        'status' => $response_array['data']->status,
                        'updated_at' => get_current_date_time(),
                        'ip_address' => $response_array['data']->ip_address,
                        'currency' => $response_array['data']->currency,
                        'channel' => $response_array['data']->channel
                    );

                    DB::table('transaction')->where('transaction_reference', $transaction_reference)->update($data);


                    // Enroll user into course
                    $transaction_result = DB::select('select course_id, user_id, currency, amount, channel, created_at from transaction 
                                        where transaction_reference = :ref', ['ref' => $transaction_reference]);

                    $course_id = $transaction_result[0]->course_id;
                    $user_id = $transaction_result[0]->user_id;
                    $amount = $transaction_result[0]->amount;
                    $tnx_date = $transaction_result[0]->created_at;

                    $enrolment_data = array(
                        'course_id' => $course_id,
                        'user_id' => $user_id,
                        'is_completed' => Config::get('constants.false'),
                        'date_started' => get_current_date_time()
                    );

                    $enrolment_id = DB::table('enrolment_history')->insertGetId($enrolment_data);

                    // Enrolment and payment successful emails
                    //get course information
                    $course_info_result = DB::select('select course_name from courses where id = :id', ['id' => $course_id]);
                    $course_name = $course_info_result[0]->course_name;

                    //get user info
                    $user_info_result = DB::select('select name, email, phone_number from users where id = :id', ['id' => $user_id]);
                    $user_fullName = $user_info_result[0]->name;
                    $user_email = $user_info_result[0]->email;
                    $user_phoneNumber = $user_info_result[0]->phone_number;

                    MailController::send_payment_success_mail($user_fullName, $user_email, $user_phoneNumber, $course_id, $course_name, $transaction_reference,
                        $response_array['data']->currency, $amount, $response_array['data']->channel, get_current_date_time());

                    MailController::send_course_enrolment_mail($user_fullName, $user_email, $course_id, $course_name);

                    //Save activity trail
                    save_activity_trail($user_id, 'Course payment', 'User ('.$user_id.') payed via paystack and enroled for a course with id ('.$course_id.')',
                        get_current_date_time());

                    return response()->json([
                        'message' => 'Payment successful',
                        'transaction_reference' => $transaction_reference,
                        'course_id' => $course_id,
                        'transaction_state' => $response_array['data']->status
                    ], 200);
                } else {
                    $data = array(
                        'status' => Config::get('constants.failed'),
                        'updated_at' => get_current_date_time()
                    );

                    DB::table('transaction')->where('transaction_reference', $transaction_reference)->update($data);

                    return response()->json([
                        'message' => 'Payment failed.',
                        'state' => 'error'
                    ], 402);
                }
            }
        } catch(Exception $e){
            return response()->json([
                'message' => 'There were some issue with the payment. Please try again later.',
                'state' => 'error',
                'error' => $e
            ], 500);
        }
    }
}
