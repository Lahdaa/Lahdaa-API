<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Validator;
use Storage;

class StudentController extends Controller
{
    public function dashboardStats(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;


            $total_enrolment_result = DB::select('select count(*) as total_enrolment from enrolment_history where user_id = :id', 
                                ['id' => $user_id]);

            $total_active_enrolment_result = DB::select('select count(*) as total_active_enrolment from enrolment_history where 
                                        is_completed = :isCompleted and user_id = :id', 
                                        ['id' => $user_id, 'isCompleted' => Config::get('constants.false')]);

            $total_courses_completed_result = DB::select('select count(*) as total_courses_completed from enrolment_history where 
                                        is_completed = :isCompleted and user_id = :id', 
                                        ['id' => $user_id, 'isCompleted' => Config::get('constants.true')]);

            return response()->json([
                'message' => 'Student dashboard stats gotten',
                'total_enrolment' => (int)$total_enrolment_result[0]->total_enrolment,
                'active_enrolment' => (int)$total_active_enrolment_result[0]->total_active_enrolment,
                'courses_completed' => (int)$total_courses_completed_result[0]->total_courses_completed
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStudentUpcomingClasses(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $all_student_live_classes_result = DB::select('select l.*, lp.name as platform_name,
                                        (select u.name from users u where u.id = l.created_by) as instructor_name 
                                        from live_class l 
                                        inner join live_class_platform lp on lp.id = l.preferred_platform where l.course_id in 
                                        (select course_id from enrolment_history where is_completed = :isCompleted and 
                                        user_id = :id) and l.is_completed = 0 and l.is_deleted = 0 order by l.date desc', 
                                        ['id' => $user_id, 'isCompleted' => Config::get('constants.false')]);

            $upcoming_classes = array();

            foreach($all_student_live_classes_result as $all_student_live_classes){
                $data = [
                            'live_class_id' => $all_student_live_classes->id,
                            'course_id' => $all_student_live_classes->course_id,
                            'live_class_name' => $all_student_live_classes->live_class_name,
                            'date' => format_date($all_student_live_classes->date),
                            'start_time' => $all_student_live_classes->start_time,
                            'end_time' => $all_student_live_classes->end_time,
                            'link_to_live_class' => $all_student_live_classes->link_to_live_class,
                            'platform_name' => $all_student_live_classes->platform_name,
                            'instructor_name' => $all_student_live_classes->instructor_name,
                        ];

                array_push($upcoming_classes, $data);
            }
           

            return response()->json([
                'message' => 'Upcoming student live classes gotten',
                'all_student_live_classes' => $upcoming_classes
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStudentEnrolledCourses(Request $request){
        try{       
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $enrolled_courses_result = DB::select('select c.id, c.course_name, (select cc.name from course_category cc where cc.id = c.course_category) 
                                                as course_category_name, c.thumbnail_file_url, c.course_rating from courses c where c.id in 
                                                (select e.course_id from enrolment_history e where e.user_id = :id order by e.date_started desc)', 
                                            ['id' => $user_id]); 
    
            return response()->json([
                'message' => 'All student enrolled courses',
                'enrolled_courses' => $enrolled_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStudentOngoingCourses(Request $request){
        try{       
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;
    
            $ongoing_courses_result = DB::select('select c.id, c.course_name, (select cc.name from course_category cc where cc.id = c.course_category) 
                                                 as course_category_name, c.thumbnail_file_url, c.course_rating from courses c where c.id in 
                                                (select e.course_id from enrolment_history e where e.user_id = :id and e.is_completed = :isCompleted 
                                                order by e.date_started desc)', ['id' => $user_id, 'isCompleted' => Config::get('constants.false')]); 
    
            return response()->json([
                'message' => 'All ongoing courses',
                'ongoing_courses' => $ongoing_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStudentCompletedCourses(Request $request){
        try{           
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $completed_courses_result = DB::select('select c.id, c.course_name, (select cc.name from course_category cc where cc.id = c.course_category) 
                                        as course_category_name, c.thumbnail_file_url, c.course_rating from courses c where c.id in 
                                       (select e.course_id from enrolment_history e where e.user_id = :id and e.is_completed = :isCompleted 
                                       order by e.date_started desc)', ['id' => $user_id, 'isCompleted' => Config::get('constants.true')]); 
    
            return response()->json([
                'message' => 'All completed courses',
                'completed_courses' => $completed_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStudentCourseWishlist(Request $request){
        try{       
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $wishlist_courses_result = DB::select('select c.id, c.course_name, (select cc.name from course_category cc where 
                                            cc.id = c.course_category) as course_category_name, c.thumbnail_file_url, 
                                            c.course_rating from courses c where c.id in (select w.course_id from wishlist w 
                                            where w.user_id = :id and w.is_deleted = 0 order by w.id desc)', ['id' => $user_id]); 
    
            return response()->json([
                'message' => 'Students wishlist',
                'wishlist_courses' => $wishlist_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }




    public function updateStudentPersonalProfileOld(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $this->validate($request, [
                'name' => 'required|min:4',
                'email' => 'required|email',
                'phone_number' => 'required',
                'country' => 'required',
            ]);

            //Get Params
            $name = check_if_null_or_empty($request->name);
            $email = check_if_null_or_empty($request->email);
            $phone_number = check_if_null_or_empty($request->phone_number);
            $country = check_if_null_or_empty($request->country);

            $data_for_user_table = array(
                'name' => $name,
                'email' => $email,
                'phone_number' => $phone_number,
                'country' => $country,
            );

            User::where('id', $user_id)->update($data_for_user_table);

            return response()->json([
                'message' => 'Student profile update successful',
                'user_id' => $user_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateStudentPersonalProfile(Request $request){
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
                'name' => 'required|min:4',
                'email' => 'required|email',
                'phone_number' => 'required',
                'country' => 'required',
                'professional_title' => 'required',
                'about_me' => 'required',
                'profile_picture_file' => 'required|mimes:png,jpg,jpeg'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            if($request->file('profile_picture_file')) {
                $file = $request->file('profile_picture_file');
                $filename = time().'_'.$file->getClientOriginalName();

                // Upload file
                //$file->move($location, $filename);
                $file = $request->profile_picture_file->storeAs(Config::get('constants.profile_picture_upload_file_path'), $filename);
                logger('file uploaded');

                //$path = Storage::disk('public')->url($filename);
                $path = \Storage::url($file);
                $url = asset($path);

                //Get other params
                $name = check_if_null_or_empty($request->name);
                $email = check_if_null_or_empty($request->email);
                $phone_number = check_if_null_or_empty($request->phone_number);
                $country = check_if_null_or_empty($request->country);
                $professional_title = check_if_null_or_empty($request->professional_title);
                $about_me = check_if_null_or_empty($request->about_me);
                $date_updated = get_current_date_time();

                $data_for_user_table = array(
                    'name' => $name,
                    'email' => $email,
                    'phone_number' => $phone_number,
                    'country' => $country,
                    'profile_picture_url' => $url,
                    'professional_title' => $professional_title,
                    'about_me' => $about_me
                );

                User::where('id', $user_id)->update($data_for_user_table);

                save_activity_trail($user_id, 'Student profile updated', 'Student with id ('. $user_id .') profile updated',
                                $date_updated);
                    
            } else{
                logger('file not uploaded');
            }

            return response()->json([
                'message' => 'Student profile update successful',
                'user_id' => $user_id,
                'profile_picture_url' => $url
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateStudentPassword(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            $date_updated = get_current_date_time();

            $this->validate($request, [
                'current_password' => 'required|min:6',
                'new_password' => 'required|min:6',
            ]);

            //check current password by authenticating user
            $data = [
                'email' => $user->email,
                'password' => $request->current_password
            ];
     
            if (Auth::attempt($data)) {
                $data_for_user_table = array(
                    'password' => bcrypt($request->new_password)
                );
    
                User::where('id', $user_id)->update($data_for_user_table);

                save_activity_trail($user_id, 'Student password updated', 'Student with id ('. $user_id .') password updated',
                                    $date_updated);
    
                return response()->json([
                    'message' => 'Student password update successful',
                    'user_id' => $user_id,
                ], 200);
            } else{
                return response()->json([
                    'message' => 'Current password does not match with records in database',
                    'user_id' => $user_id,
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateStudentNotificationSetting(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            $date_updated = get_current_date_time();

            //Get Params
            $is_email_notification_checked = check_if_null_or_empty($request->is_email_notification_checked);
            $is_sms_notification_checked = check_if_null_or_empty($request->is_sms_notification_checked);

            $data_for_student_table = array(
                'is_email_notification_checked' => $is_email_notification_checked,
                'is_sms_notification_checked' => $is_sms_notification_checked
            );

            User::where('id', $user_id)->update($data_for_student_table);

            save_activity_trail($user_id, 'Student notification setting updated', 'Student with id ('. $user_id .') notification setting updated',
                        $date_updated);

            return response()->json([
                'message' => 'Student notification update successful',
                'user_id' => $user_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateStudentBillingAddress(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            $date_updated = get_current_date_time();

            $this->validate($request, [
                'billing_country' => 'required',
                'billing_address' => 'required',
                'billing_state' => 'required',
            ]);

            //Get Params
            $billing_country = check_if_null_or_empty($request->billing_country);
            $billing_address = check_if_null_or_empty($request->billing_address);
            $billing_postal_code = check_if_null_or_empty($request->billing_postal_code);
            $billing_state = check_if_null_or_empty($request->billing_state);
            $billing_city = check_if_null_or_empty($request->billing_city);
            $billing_organization = check_if_null_or_empty($request->billing_organization);
            $billing_tax_id = check_if_null_or_empty($request->billing_tax_id);

            $data_for_user_table = array(
                'billing_country' => $billing_country,
                'billing_address' => $billing_address,
                'billing_postal_code' => $billing_postal_code,
                'billing_state' => $billing_state,
                'billing_city' => $billing_city,
                'billing_organization' => $billing_organization,
                'billing_tax_id' => $billing_tax_id,
            );

            User::where('id', $user_id)->update($data_for_user_table);

            save_activity_trail($user_id, 'Student billing address updated', 'Student with id ('. $user_id .') billing address updated',
                        $date_updated);

            return response()->json([
                'message' => 'Student billing info update successful',
                'user_id' => $user_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }


    public function getStudentPersonalProfile(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $user_info_result = DB::select('select name, email, phone_number, country, profile_picture_url, about_me, professional_title 
                        from users where id = :id', ['id' => $user_id]); 


            $student_personal_profile = array(
                'name' => $user_info_result[0]->name,
                'email' => $user_info_result[0]->email,
                'phone_number' => $user_info_result[0]->phone_number,
                'country' => $user_info_result[0]->country,
                'profile_picture_url' => $user_info_result[0]->profile_picture_url,
                'about_me' => $user_info_result[0]->about_me,
                'professional_title' => $user_info_result[0]->professional_title,
            );

            return response()->json([
                'message' => 'Student personal profile successfully gotten',
                'student_personal_profile' => $student_personal_profile,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getStudentNotificationSetting(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $student_notification_setting_result = DB::select('select is_email_notification_checked, is_sms_notification_checked from users where id = :id', ['id' => $user_id]); 

            $student_notification_setting = array(
                'is_email_notification_checked' => $student_notification_setting_result[0]->is_email_notification_checked,
                'is_sms_notification_checked' => $student_notification_setting_result[0]->is_sms_notification_checked
            );

            return response()->json([
                'message' => 'Student notification setting successfully gotten',
                'student_notification_setting' => $student_notification_setting,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getStudentBillingAddress(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $user_info_result = DB::select('select billing_country, billing_address, billing_postal_code, billing_state, 
                                billing_city, billing_organization, billing_tax_id from users where id = :id', ['id' => $user_id]); 

            $student_bililng_info = array(
                'billing_country' => $user_info_result[0]->billing_country,
                'billing_address' => $user_info_result[0]->billing_address,
                'billing_postal_code' => $user_info_result[0]->billing_postal_code,
                'billing_state' => $user_info_result[0]->billing_state,
                'billing_city' => $user_info_result[0]->billing_city,
                'billing_organization' => $user_info_result[0]->billing_organization,
                'billing_tax_id' => $user_info_result[0]->billing_tax_id,
            );

            return response()->json([
                'message' => 'Student billing information successfully gotten',
                'student_bililng_info' => $student_bililng_info,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function saveCourseToWishlist(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            $date_created = get_current_date_time();

            $this->validate($request, [
                'course_id' => 'required'
            ]);

            $course_id = check_if_null_or_empty($request->course_id);
            $is_deleted = Config::get('constants.false');

            DB::table('wishlist')->insert([
                'course_id' => $course_id,
                'user_id' => $user_id,
                'is_deleted' => $is_deleted
            ]);

            save_activity_trail($user_id, 'Course saved to wishlist', 'Student with id ('. $user_id .') saved a course to wishlist',
                        $date_created);

            return response()->json([
                'message' => 'Course saved to wishlist'
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function uploadProfilePicture(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            $date_updated = get_current_date_time();

            // Validation
            //$request->validate([
            //    'file' => 'required|mimes:png,jpg,jpeg|max:5120'
            //]);

            $validator = Validator::make($request->all(),[ 
                'profile_picture_file' => 'required|mimes:png,jpg,jpeg|max:5120'
            ]); 

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            if($request->file('profile_picture_file')) {
                $file = $request->file('profile_picture_file');
                $filename = time().'_'.$file->getClientOriginalName();

                // Upload file
                //$file->move($location, $filename);
                $file = $request->profile_picture_file->storeAs(Config::get('constants.profile_picture_upload_file_path'), $filename);
                logger('file uploaded');

                //$path = Storage::disk('public')->url($filename);
                $path = \Storage::url($file);
                $url = asset($path);

                //Update Users table
                $data_for_user_table = array(
                    'profile_picture_url' => $url
                );

                User::where('id', $user_id)->update($data_for_user_table);

                save_activity_trail($user_id, 'Student profile picture updated', 'Student with id ('. $user_id .') profile picture updated',
                        $date_updated);
                
            } else{
                logger('file not uploaded');
            }

    
            return response()->json([
                'message' => 'File uploaded successfully',
                'file_url' => $url
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function saveCourseReview(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $this->validate($request, [
                'course_id' => 'required',
                'rating' => 'required'
            ]);

            $course_id = check_if_null_or_empty($request->course_id);
            $rating = check_if_null_or_empty($request->rating);
            $review_text = check_if_null_or_empty($request->review_text);
            $is_deleted = Config::get('constants.false');
            $date_created = get_current_date_time();

            DB::table('reviews')->insert([
                'course_id' => $course_id,
                'user_id' => $user_id,
                'rating' => $rating,
                'review_text' => $review_text,
                'is_deleted' => $is_deleted,
                'created_at' => $date_created
            ]);

            save_activity_trail($user_id, 'Course reviewed', 'Student with id ('. $user_id .') reviewed a course with id ('. $course_id .')',
                        $date_created);

            return response()->json([
                'message' => 'Course review saved',
                'course_id' => $course_id,
                'rating' => $rating,
                'review_text' => $review_text,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getRecentCourses(Request $request){
        try {
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $enrolled_courses_result = DB::select('select distinct course_id from enrolment_history where user_id = :id order by date_started desc', 
                                                ['id' => $user_id]);

            $enrolled_courses_data_result = array();

            if(!empty($enrolled_courses_result)){
                if(count($enrolled_courses_result) > 0){

                    for($i = 0; $i < sizeof($enrolled_courses_result); $i++){

                        $data = (object)[];

                        $course_id = $enrolled_courses_result[$i]->course_id;

                        $courses_result = DB::select('select id, course_name, thumbnail_file_url from courses where id = :id', ['id' => $course_id]);

                        $course_content_count_result = DB::select('select count(*) as count from course_content where is_deleted = 0 and course_id = :course_id', 
                                                    ['course_id' => $course_id]);

                        $courses_result[0]->total_course_content = $course_content_count_result[0]->count;


                        $completed_course_contents = DB::select('select count(*) as count from course_content_tracker where course_id = :course_id and user_id = :user_id and is_completed = 1',
                                                    ['course_id' => $course_id, 'user_id' => $user_id]);


                        $courses_result[0]->completed_course_content = $completed_course_contents[0]->count;

                        $data->course_id = $course_id;
                        $data->course_name = $courses_result[0]->course_name;
                        $data->thumbnail_file_url = $courses_result[0]->thumbnail_file_url;
                        $data->total_course_content = $course_content_count_result[0]->count;
                        $data->completed_course_content = $completed_course_contents[0]->count;

                        array_push($enrolled_courses_data_result, $data);  
                    }

                    return response()->json([
                        'message' => 'Recent courses gotten successfully',
                        'recent_courses' => $enrolled_courses_data_result
                    ], 200);
                }

            } else {
                return response()->json([
                    'message' => 'Recent courses gotten successfully',
                    'recent_courses' => $enrolled_courses_data_result
                ], 200);
            }
                                                
        } catch(Exception $e){
            return response()->json([
                'message' => 'Error fetching student recent courses'
            ], 500);
        }
    }
}
