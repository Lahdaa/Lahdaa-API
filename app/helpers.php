<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\ActivityTrail;

if (!function_exists('check_if_null_or_empty')){
    function check_if_null_or_empty($value){
        if(!isset($value) || trim($value) === ''){
            return null;
        } else{
            return $value;
        }
    }
}

if (!function_exists('get_current_date_time')){
    function get_current_date_time(){
        return Carbon::now()->toDateTimeString();
    }
}

if (!function_exists('format_date')){
    function format_date($date){
        return Carbon::parse($date)->isoFormat('ll'); 
    }
}

if (!function_exists('get_property_value')){
    function get_property_value($key){
        $propertyData = DB::select('select property_value from app_properties WHERE property_key = :key', ['key' => $key]);

        return $propertyData[0]->property_value; 
    }
}

if (!function_exists('check_authentication')){
    function check_authentication($header_auth_token){
        if(isset($header_auth_token)){
            $getuserData = DB::select('select * from users WHERE auth_token = :auth_token limit 1', ['auth_token' => $header_auth_token]);
           
            if(count($getuserData) > 0){
                foreach($getuserData as $userdata){
                    $user = $userdata;                   
                }
                return array('status' => true, 'data' => $user, 'message' => 'User does not exist with this authentication', 'error' => 'Unauthorised');
            }
            else{
                return array('status' => false, 'data' => 'No User', 'message' => 'User does not exist with this authentication', 'error' => 'Unauthorised');
            }           
        }
        else{
            return array('status' => false, 'data' => 'No Authentication', 'message' => 'No Authentication. Please login again', 'error' => 'Unauthorised'); 
        }
    }
}

if (!function_exists('logger')){
    function logger($data){
        return Log::info($data);
    }
}

if (!function_exists('generate_random_string')){
    function generate_random_string($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
}

if(!function_exists('generate_unique_code')){
    function generate_unique_code($limit){
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }
}

if(!function_exists('save_notification')){
    function save_notification($user_id, $notification_type, $message, $date_created){

        $notification = Notification::create([
            'user_id' => $user_id,
            'notification_type' => $notification_type,
            'message' => $message,
            'created_at' => $date_created,
            'is_deleted' => Config::get('constants.false')
        ]);

        return $notification->notification_id;
    }
}

if(!function_exists('save_activity_trail')){
    function save_activity_trail($user_id, $title, $description, $date_created){
        $data = array(
            'title' => $title,
            'description' => $description,
            'user_id' => $user_id,
            'created_at' => $date_created
        );

        $activity_trail_id = DB::table('activity_trail')->insertGetId($data);

        return $activity_trail_id;
    }
}