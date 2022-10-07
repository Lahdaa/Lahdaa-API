<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;
use Illuminate\Support\Str;

use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Card;

use App\Http\Controllers\MailController;

class PaymentController extends Controller
{
    public function createPayoutRequest(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
    
            //Get Params
            $request_amount = check_if_null_or_empty($request->request_amount);
            $request_detail = check_if_null_or_empty($request->request_detail);
            $created_by = check_if_null_or_empty($user_id);
            $date_created = get_current_date_time();
            $is_approved = Config::get('constants.false');
            $is_deleted = Config::get('constants.false');
            //$request_reference = (string) Str::orderedUuid();
            $request_reference = generate_unique_code(10);
            $status = Config::get('constants.pending');
    
            $data = array(
                'request_amount' => $request_amount,
                'request_detail' => $request_detail,
                'created_by' => $created_by,
                'date_created' => $date_created,
                'is_approved' => $is_approved,
                'is_deleted' => $is_deleted,
                'request_reference' => $request_reference,
                'status' => $status
            );
    
            $payout_request_id = DB::table('payout_requests')->insertGetId($data);
    
            $instructor_name = $user->name;
            $instructor_email = $user->email;
            $request_reference = strtoupper($request_reference);

            MailController::send_request_payout_mail($instructor_name, $instructor_email, $request_amount, $request_detail, 
            $request_reference, $status);

            $date_created = get_current_date_time();

            save_activity_trail($user_id, 'Payout request initiated', 'Instructor with id ('. $user_id .') initiated a payout request', 
                        $date_created);
    
            return response()->json([
                'message' => 'Payout request created',
                'payout_request_id' => $payout_request_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getSettlementDashboardStats(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
    
            $available_funds_for_withdrawal_result = DB::select('select SUM(instructor_revenue) as available_funds_for_withdrawal 
                from transaction where course_id in (select course_id from courses where created_by = :id)', ['id' => $user_id]); 
    
            $total_amount_earned_result = DB::select('select SUM(instructor_revenue) as total_amount_earned from transaction 
                where course_id in (select course_id from courses where created_by = :id)', ['id' => $user_id]);
    
            $total_courses_booked_result = DB::select('select count(*) as total_courses_booked from enrolment_history where 
                course_id in (select course_id from courses where created_by = :id)', ['id' => $user_id]);
    
    
            $available_funds_for_withdrawal = $available_funds_for_withdrawal_result[0]->available_funds_for_withdrawal != null ?
                $available_funds_for_withdrawal_result[0]->available_funds_for_withdrawal : 0;
    
            $total_amount_earned = $total_amount_earned_result[0]->total_amount_earned != null ? $total_amount_earned_result[0]->total_amount_earned : 0;
    
            $total_courses_booked = $total_courses_booked_result[0]->total_courses_booked != null ? $total_courses_booked_result[0]->total_courses_booked : 0;
    
            
            return response()->json([
                'message' => 'Settlement dashboard stats gotten',
                'available_funds_for_withdrawal' => $available_funds_for_withdrawal,
                'total_amount_earned' => $total_amount_earned,
                'total_courses_booked' => $total_courses_booked
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllSettlementTransactions(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
    
            $settlement_transactions_result = DB::select('select * from payout_requests where is_deleted = 0 and created_by = :id', ['id' => $user_id]); 
        
            return response()->json([
                'message' => 'All Settlement Transactions',
                'settlement_transactions' => $settlement_transactions_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getSettlementRequestById(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
    
            $payout_request_id = check_if_null_or_empty($request->payout_request_id);
                
            $payout_request_result = DB::select('select * from payout_requests where is_deleted = 0 
                    and payout_request_id = :payout_request_id', ['payout_request_id' => (int)$payout_request_id]); 
        
            return response()->json([
                'message' => 'Payout Request',
                'payout_request' => $payout_request_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function payAndEnrollForCourse(Request $request){
        try {
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            Log::info(print_r($user, true));

            $validator = Validator::make($request->all(),[ 
                'course_id' => 'required',
                'course_id' => 'required',
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  
    
            // //Get Params
            $course_id = check_if_null_or_empty($request->course_id);
            $amount = check_if_null_or_empty($request->amount);
            $created_by = check_if_null_or_empty($user_id);
            $date_created = get_current_date_time();
            // $is_approved = Config::get('constants.false');
            // $is_deleted = Config::get('constants.false');
            // //$request_reference = (string) Str::orderedUuid();
            // $request_reference = generate_unique_code(10);
            // $status = Config::get('constants.pending');



            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
       
            //check id customer exists if yes skip...no create
            $customer = $stripe->customers->create([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number
            ]);
            echo $customer;

            $chargeResult = $stripe->charges->create([
                'amount' => (int)($amount * 100),
                'currency' => 'gbp',
                'source' => 'tok_amex',
                'description' => 'Enrolment payment for course: ',
            ]);

  
                               
            // Insert into the database
            \App\PaymentLogs::create([                                         
                'amount'=> $input['amount'],
                'plan'=> $input['plan'],
                'charge_id'=>$charge->id,
                'stripe_id'=>$unique_id,                     
                'quantity'=>1
            ]);


            
           // INSERT INTO `transaction`(`id`, `transaction_reference`, `payment_type`, `course_id`, `user_id`, 
           // `amount`, `details`, `is_instructor_payed`, `admin_revenue`, `instructor_revenue`, `ip_address`, 
           // `channel`, `bank_name`, `bank_account_number`, `status`, `created_at`, `updated_at`) VALUES ([value-1],[value-2],[value-3],[value-4],[value-5],[value-6],[value-7],[value-8],[value-9],[value-10],[value-11],[value-12],[value-13],[value-14],[value-15],[value-16],[value-17])




            $data = array(
                'transaction_reference' => $title,
                'course_id' => $description,
                'user_id' => $user_id,
                'channel' => $channel 
            );
    
            $activity_trail_id = DB::table('activity_trail')->insertGetId($data);

            return response()->json([
                'message' => 'Charge successful, Thank you for payment!',
                'state' => 'success'
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'There were some issue with the payment. Please try again later.',
                'state' => 'error'
            ], 500);
        }             
    }
}
