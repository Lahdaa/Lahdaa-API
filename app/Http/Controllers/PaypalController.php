<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use URL;
use Illuminate\Support\Facades\Config;
use App\Http\Controllers\MailController;

use Omnipay\Omnipay;

class PaypalController extends Controller
{
    private $gateway;

    public function __construct() {
        $this->gateway = Omnipay::create('PayPal_Rest');
        $this->gateway->setClientId(env('PAYPAL_CLIENT_ID'));
        $this->gateway->setSecret(env('PAYPAL_SECRET'));
        $this->gateway->setTestMode(true);
    }

    public function payWithPaypal(Request $request){
        try {
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
                    
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                          
            $user_id = $user->id;
        
            $course_id = check_if_null_or_empty($request->course_id);
        
            //get all the information about the course including amount
            $course_result = DB::select('select c.course_name, c.price, c.is_discounted, c.discount_price 
                                        from courses c where c.is_deleted = 0 and c.id = :id', ['id' => $course_id]);
        
            if(!empty($course_result)){
                $amount_to_be_paid = $course_result[0]->price;

                $omni_response = $this->gateway->purchase(array(
                    'amount' => $amount_to_be_paid,
                    'currency' => 'GBP',
                    'returnUrl' => url('api/paypal/success'),
                    'cancelUrl' => url('api/paypal/error')
                ))->send();
            
                $admin_revenue = (floatval(get_property_value('ADMIN_SHARE_PERCENTAGE')) * $amount_to_be_paid) / 100;
                $instructor_revenue = (floatval(get_property_value('INSTRUCTOR_SHARE_PERCENTAGE')) * $amount_to_be_paid) / 100;

                $data = array(
                    'transaction_reference' => $omni_response->getTransactionReference(),
                    'payment_type' => 'paypal',
                    'course_id' => $course_id,
                    'user_id' => $user_id,
                    'amount' => $amount_to_be_paid,
                    'details' => $omni_response->getRedirectUrl(),
                    'is_instructor_payed' => Config::get('constants.false'),
                    'admin_revenue' => $admin_revenue,
                    'instructor_revenue' => $instructor_revenue,
                    'channel' => 'paypal',
                    'status' => Config::get('constants.pending'),
                    'created_at' => get_current_date_time(),
                    'currency' => 'GBP'
                );
        
                $transaction_id = DB::table('transaction')->insertGetId($data);
        
                return response()->json([
                    'message' => 'Link generated for payment via PayPal',
                    'payment_link' => $omni_response->getRedirectUrl(),
                    'state' => 'success'
                ], 200);
            }

        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Paypal payment link generation failed.',
                'state' => 'error'
            ], 500);
        }
    }

    public function success(Request $request){
        try{
            if ($request->input('paymentId') && $request->input('PayerID')) {
                $transaction = $this->gateway->completePurchase(array(
                    'payer_id' => $request->input('PayerID'),
                    'transactionReference' => $request->input('paymentId')
                ));

                $transaction_reference = $request->input('paymentId');

                $response = $transaction->send();

                if ($response->isSuccessful()) {
                    $arr = $response->getData();
                    
                    //$transaction_reference = $arr['id'];

                    // Approve payment transaction
                    $data = array(
                        'payer_id' => $arr['payer']['payer_info']['payer_id'],
                        'status' => $arr['state'],
                        'updated_at' => get_current_date_time()
                    );

                    DB::table('transaction')->where('transaction_reference', $transaction_reference)->update($data);


                    // Enroll user into course
                    $transaction_result = DB::select('select course_id, user_id, currency, amount, channel, created_at from transaction 
                                        where transaction_reference = :ref', ['ref' => $transaction_reference]);

                    $course_id = $transaction_result[0]->course_id;
                    $user_id = $transaction_result[0]->user_id;
                    $amount_currency = $transaction_result[0]->currency;
                    $amount = $transaction_result[0]->amount;
                    $channel = $transaction_result[0]->channel;
                    $tnx_date = $transaction_result[0]->created_at;

                    $enrolment_data = array(
                        'course_id' => $course_id,
                        'user_id' => $user_id,
                        'is_completed' => Config::get('constants.false'),
                        'date_started' => get_current_date_time()
                    );
            
                    $enrolment_id = DB::table('enrolment_history')->insertGetId($enrolment_data);


                    //get course information
                    $course_info_result = DB::select('select course_name from courses where id = :id', ['id' => $course_id]);
                    $course_name = $course_info_result[0]->course_name;

                    //get user info
                    $user_info_result = DB::select('select name, email, phone_number from users where id = :id', ['id' => $user_id]);
                    $user_fullName = $user_info_result[0]->name;
                    $user_email = $user_info_result[0]->email;
                    $user_phoneNumber = $user_info_result[0]->phone_number;


                    // Enrolment and payment successful emails
                    MailController::send_payment_success_mail($user_fullName, $user_email, $user_phoneNumber, $course_id, $course_name, $transaction_reference,
                                                                $amount_currency, $amount, $channel, $tnx_date);

                    MailController::send_course_enrolment_mail($user_fullName, $user_email, $course_id, $course_name);


                    //Save activity trail
                    save_activity_trail($user_id, 'Course payment', 'User ('.$user_id.') payed and enroled for a course with id ('.$course_id.')',
                        get_current_date_time());

                    return response()->json([
                        'message' => 'Payment successful',
                        'transaction_reference' => $transaction_reference,
                        'course_id' => $course_id,
                        'transaction_state' => $arr['state']
                    ], 200);

                }
                else{
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
            else{
                // $data = array(
                //     'status' => Config::get('constants.declined'),
                //     'updated_at' => get_current_date_time()
                // );

                // DB::table('transaction')->where('transaction_reference', $course_id)->update($data);

                return response()->json([
                    'message' => 'Payment declined.',
                    'state' => 'error'
                ], 400);
            }

        } catch (Exception $ex) {
            return response()->json([
                'message' => 'There were some issue with the payment. Please try again later.',
                'state' => 'error'
            ], 500);
        }
    }

    public function error(){
        return response()->json([
            'message' => 'User declined the payment!',
            'state' => 'error'
        ], 400);  
    }
}
