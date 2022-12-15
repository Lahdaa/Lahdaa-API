<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Http\Controllers\MailController;

use Illuminate\Support\Str;

class PassportAuthController extends Controller
{
    /**
     * Registration
     */
    public function registerStudent(Request $request){
        try{
            $this->validate($request, [
                'name' => 'required|min:4',
                'email' => 'required|email',
                'phone_number' => 'required',
                'password' => 'required|min:6',
                'country_id' => 'required|numeric',
            ]);
     
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => bcrypt($request->password),
                'country' => $request->country_id,
                'is_email_notification_checked' => Config::get('constants.true'),
                'is_sms_notification_checked' => Config::get('constants.true'),
                'is_verified' => Config::get('constants.true')
            ]);
    
            DB::insert('insert into user_role (role_id, user_id) values (?, ?)', [Config::get('constants.student_role'), $user->id]);
           
            $token = $user->createToken('LaravelAuthApp')->accessToken;

            $activation_code = Str::random(32);
            $activation_url = Config::get('constants.api_prod_url') . 'verify/' . $user->id . '/' . $activation_code;

            DB::update('update users set activation_code = :activation_code, auth_token = :token 
                        where id = :id', ['activation_code' => $activation_code, 'token' => $token, 'id' => $user->id]);

            
            //Send welcome mail
            $student_name = trim($request->name);
            $student_email = trim($request->email);

            MailController::send_student_welcome_mail($student_name, $student_email, $activation_url);

            $activation_url_for_frontend = Config::get('constants.prod_url') . $activation_code;

            $date_created = get_current_date_time();

            save_activity_trail($user->id, 'Student registered', 'Student registered', $date_created);
     
            return response()->json([
                'message' => 'Registration Successful',
                'user_id' => $user->id,
                'token' => $token,
                'email' => $user->email,
                'activation_code' => $activation_code,
                'activation_url' => $activation_url_for_frontend
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function registerInstructor(Request $request){
        try{
            $this->validate($request, [
                'name' => 'required|min:4',
                'email' => 'required|email',
                'phone_number' => 'required',
                'password' => 'required|min:6',
                'linkedin_profile_url' => 'required',
                'country_id' => 'required',
            ]);
     
            $user = User::create([
                'name' => trim($request->name),
                'email' => trim($request->email),
                'phone_number' => trim($request->phone_number),
                'password' => bcrypt(trim($request->password)),
                'country' => $request->country_id,
                'is_email_notification_checked' => Config::get('constants.true'),
                'is_sms_notification_checked' => Config::get('constants.true'),
                'is_verified' => Config::get('constants.true')
            ]);
    
            DB::insert('insert into user_role (role_id, user_id) values (?, ?)', [Config::get('constants.instructor_role'), $user->id]);
    
            $profile_url = generate_random_string(7);
    
            Instructor::create([
                'user_id' => $user->id,
                'is_deleted' => Config::get('constants.false'),
                'is_approved' => Config::get('constants.true'),
                'linkedin_profile_url' => $request->linkedin_profile_url,
                'profile_url' => $profile_url,
                'country' => $request->country_id
            ]);
           
            $token = $user->createToken('LaravelAuthApp')->accessToken;
            DB::update('update users set auth_token = :token where id = :id', ['token' => $token, 'id' => $user->id]);

            //Send welcome mail
            $instructor_name = trim($request->name);
            $instructor_email = trim($request->email);

            MailController::send_instructor_welcome_mail($instructor_name, $instructor_email);

            $date_created = get_current_date_time();

            save_activity_trail($user->id, 'Instructor registered', 'Instructor registered', $date_created);
     
            return response()->json([
                'message' => 'Registration Successful',
                'user_id' => $user->id,
                'token' => $token
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }
 
    /**
     * Login
     */
    public function login(Request $request){
        try{
            $data = [
                'email' => $request->email,
                'password' => $request->password
            ];
     
            if (Auth::attempt($data)) {
                //Get and save new token to user
                $token = Auth::user()->createToken('LaravelAuthApp')->accessToken;
                DB::update('update users set auth_token = :token where id = :id', ['token' => $token, 'id' => Auth::user()->id]);
    
                //Get user role
                $result = DB::select('select role_id from user_role where user_id = :id', ['id' => Auth::user()->id]);
    
                $is_verified = Auth::user()->is_verified;

                if($is_verified == 1){
                    $user_data = [
                        'id' => Auth::user()->id,
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email,
                        'phone_number' => Auth::user()->phone_number,
                        'role_id' => $result[0]->role_id,
                        'lang' => Auth::user()->lang,
                    ];

                    $date_created = get_current_date_time();

                    save_activity_trail(Auth::user()->id, 'User Login', 'User logged in', $date_created);
        
                    return response()->json([
                        'message' => 'Login Successful',
                        'userData' => $user_data,
                        'token' => $token
                    ], 200);
                } else {
                    return response()->json([
                        'message' => 'This user is not verified',
                    ], 401);
                }
                
            } else {
                return response()->json(['message' => 'Incorrect email or password'], 401);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
    }   

    public function forgotPassword(Request $request){
        try{
            $data = [
                'email' => $request->email
            ];
     
            if (Auth::attempt($data)) {
                
                //$token = auth()->user()->createToken('LaravelAuthApp')->accessToken;
                //return response()->json(['token' => $token], 200);
            } else {
                return response()->json(['error' => 'Unauthorised'], 401);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function verifyActivationLink(Request $request, $user_id, $token){
        try{
            $user_data = DB::select('select * from users where id = :id and activation_code = :activation_code', 
                        ['id' => $user_id, 'activation_code' => $token]);

            //$user_data = DB::table('users')->where([['id', '=', $user_id],['activation_code', '=', $token],])->pluck('name', 'email');

            if(count($user_data) > 0){
                $data_for_user_table = array(
                    'is_verified' => Config::get('constants.true')
                );

                User::where('id', $user_id)->update($data_for_user_table);

                //Send verification success mail
                $student_name = trim($user_data[0]->name);
                $student_email = trim($user_data[0]->email);

                MailController::send_success_verification_mail($student_name, $student_email);

                return response()->json([
                    'message' => 'Verification successful',
                    'user_id' => $user_id
                ], 200);

            } else{
                return response()->json(['message' => 'Invalid activation link'], 401);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }
}