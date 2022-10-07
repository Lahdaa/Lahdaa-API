<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;


use App\Http\Requests;
use App\Http\Controllers\Controller;

use Exception;

class MailController extends Controller{
   public static function send_instructor_welcome_mail($user_fullName, $user_email) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email
         );
           
         Mail::send('emails.instructor_welcome_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Welcome to Stevia!');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public static function send_instructor_application_submission_mail($user_fullName, $user_email) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email
         );
           
         Mail::send('emails.instructor_welcome_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Welcome to Stevia!');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public static function send_student_welcome_mail($user_fullName, $user_email, $activation_url) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email,
            'activation_url' => $activation_url
         );
           
         Mail::send('emails.new_student_welcome_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Welcome to Stevia!');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public static function send_success_verification_mail($user_fullName, $user_email) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email,
         );
           
         Mail::send('emails.student_account_verification_success_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Account Verified');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public static function send_course_creation_mail($user_fullName, $user_email, $course_id, $course_name) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email,
            'course_id' => $course_id,
            'course_name' => $course_name,
         );
           
         Mail::send('emails.course_creation_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Course created');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public static function send_request_payout_mail($user_fullName, $user_email, $request_amount, $request_detail, 
      $request_reference, $status) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email,
            'request_amount' => $request_amount,
            'request_detail' => $request_detail,
            'request_reference' => $request_reference,
            'status' => $status,
         );
           
         Mail::send('emails.request_payout_creation_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Payout request successful');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public static function send_course_completion_mail($user_fullName, $user_email, $course_id, $course_name) {
      try{
         $data = array(
            'user_fullName' => $user_fullName,
            'user_email' => $user_email,
            'course_id' => $course_id,
            'course_name' => $course_name,
         );
           
         Mail::send('emails.course_completion_mail', $data, function($message) use ($data){
            $message->to($data['user_email'])->subject('Course completed');
            $message->from(Config::get('constants.email_from'), Config::get('constants.email_from_name'));
         });
      } catch(Exception $e){
         return $e->getMessage();
      }
   }

   public function basic_email() {
      $data = array('name'=>"Virat Gandhi");
     
      Mail::send(['text'=>'mail'], $data, function($message) {
         $message->to('abc@gmail.com', 'Tutorials Point')->subject
            ('Laravel Basic Testing Mail');
         $message->from('xyz@gmail.com','Virat Gandhi');
      });
      echo "Basic Email Sent. Check your inbox.";
   }

     public function html_email() {
        $data = array('name'=>"Virat Gandhi");

        Mail::send('mail', $data, function($message) {
           $message->to('abc@gmail.com', 'Tutorials Point')->subject
              ('Laravel HTML Testing Mail');
           $message->from('xyz@gmail.com','Virat Gandhi');
        });
        echo "HTML Email Sent. Check your inbox.";
     }

     public function attachment_email() {
        $data = array('name'=>"Virat Gandhi");
        Mail::send('mail', $data, function($message) {
           $message->to('abc@gmail.com', 'Tutorials Point')->subject
              ('Laravel Testing Mail with Attachment');
           $message->attach('C:\laravel-master\laravel\public\uploads\image.png');
           $message->attach('C:\laravel-master\laravel\public\uploads\test.txt');
           $message->from('xyz@gmail.com','Virat Gandhi');
        });
        echo "Email Sent with attachment. Check your inbox.";
     }
}
