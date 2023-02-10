<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Instructor;
use App\Models\User;
use Validator;
use Storage;
use App\Http\Controllers\MailController;

class InstructorController extends Controller
{
    //
    public function createInstructor(Request $request){

    }

    public function updateInstructor(Request $request){

    }

    public function deleteInstructor(Request $request){

    }

    public function getInstructorInfo(Request $request, $user_id){
        try{           
            $instructor_info_result = DB::select('select u.id, i.professional_title, i.profile_picture_url, i.about_you, 
                                                i.linkedin_profile_url, i.professional_portfolio, i.other_platforms, 
                                                i.rating, u.name AS instructor_name, i.country, 
                                                co.country_name, co.iso2, co.emoji AS country_flag, 
                                                i.state, s.state_name
                                                from instructors i
                                                inner join users u ON u.id = i.user_id 
                                                inner join country co ON co.country_id = i.country 
                                                inner join state s ON s.state_id = i.state 
                                                where i.user_id = :id and i.is_approved = :isApproved 
                                                and i.is_deleted = :isDeleted', ['id' => $user_id, 
                                                'isApproved' => Config::get('constants.true'), 'isDeleted' => Config::get('constants.false')]); 
    
            if(count($instructor_info_result) > 0){

                $instructor_info = $instructor_info_result[0];

                $all_instructor_courses = DB::select('select c.id, c.course_name, c.thumbnail_file_url, c.price, 
                                                    cc.name as course_category_name, u.name as instructor_name, 
                                                    i.professional_title, i.profile_picture_url
                                                    from courses c inner join course_category cc on c.course_category = cc.id 
                                                    inner join users u on c.created_by = u.id 
                                                    inner join instructors i on c.created_by = i.user_id 
                                                    where c.created_by = :id and c.is_deleted = :isDeleted 
                                                    and c.is_published = :isPublished', ['id' => $user_id, 
                                                    'isDeleted' => Config::get('constants.false'), 'isPublished' => Config::get('constants.true')]); 

                $instructor_info->instructor_courses = $all_instructor_courses;

                return response()->json([
                    'message' => 'Instructor data gotten successfully',
                    'instructor_info' => $instructor_info
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No instructor found',
                ], 200);
            }                                    
            
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getInstructorDashboardStats(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $enrolment_count_result = DB::select('select count(*) as enrolment_count from enrolment_history where course_id in 
                                        (select course_id from courses where created_by = :id)', ['id' => $user_id]); 

            $courses_count_result = DB::select('select count(*) as courses_count from courses where is_deleted = 0 and
                                        created_by = :id', ['id' => $user_id]); 

            $total_instructor_reveue_result = DB::select('select sum(instructor_revenue) as amount from transaction where 
                                        course_id in (select course_id from courses where created_by = :id)', ['id' => $user_id]);

            $instructor_info_result = DB::select('select profile_picture_url, rating, profile_url from instructors where user_id = :id', ['id' => $user_id]);                                        
            

            $total_instructor_reveue = $total_instructor_reveue_result[0]->amount != null ?
                $total_instructor_reveue_result[0]->amount : 0;

            return response()->json([
                'message' => 'Instructor dashboard stats gotten',
                'enrolment_count' => $enrolment_count_result[0]->enrolment_count,
                'courses_count' => $courses_count_result[0]->courses_count,
                'total_instructor_reveue' => $total_instructor_reveue,
                'instructor_profile_url' => $instructor_info_result[0]->profile_url,
                'instructor_profile_picture_url' => $instructor_info_result[0]->profile_picture_url,
                'instructor_rating' => $instructor_info_result[0]->rating
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateInstructorPersonalProfile(Request $request){
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
            ]);

            //Get Params
            $name = check_if_null_or_empty($request->name);
            $email = check_if_null_or_empty($request->email);
            $phone_number = check_if_null_or_empty($request->phone_number);
            $professional_title = check_if_null_or_empty($request->professional_title);
            $about_you = check_if_null_or_empty($request->about_you);
            $linkedin_profile_url = check_if_null_or_empty($request->linkedin_profile_url);
            $country = check_if_null_or_empty($request->country);
            $state = check_if_null_or_empty($request->state);
            $date_updated = get_current_date_time();

            $data_for_user_table = array(
                'name' => $name,
                'email' => $email,
                'phone_number' => $phone_number,
                'country' => $country,
            );

            $data_for_instructor_table = array(
                'email' => $email,
                'phone_number' => $phone_number,
                'professional_title' => $professional_title,
                'about_you' => $about_you,
                'linkedin_profile_url' => $linkedin_profile_url,
                'country' => $country,
                'state' => $state,
            );

            User::where('id', $user_id)->update($data_for_user_table);

            Instructor::where('user_id', $user_id)->update($data_for_instructor_table);

            save_activity_trail($user_id, 'Instructor profile updated', 'Instructor with id ('. $user_id .') profile updated',
                                $date_updated);

            return response()->json([
                'message' => 'Instructor profile update successful',
                'user_id' => $user_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateInstructorBankInformation(Request $request){
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
                'bank_account_number' => 'required|min:10',
                'bank_name' => 'required',
                'bank_account_name' => 'required'
            ]);

            //Get Params
            $bank_account_number = check_if_null_or_empty($request->bank_account_number);
            $bank_name = check_if_null_or_empty($request->bank_name);
            $bank_account_name = check_if_null_or_empty($request->bank_account_name);
            $date_updated = get_current_date_time();

            $data_for_instructor_table = array(
                'bank_account_number' => $bank_account_number,
                'bank_name' => $bank_name,
                'bank_account_name' => $bank_account_name
            );

            Instructor::where('user_id', $user_id)->update($data_for_instructor_table);

            save_activity_trail($user_id, 'Instructor bank info updated', 'Instructor with id ('. $user_id .') bank information updated',
                                $date_updated);

            return response()->json([
                'message' => 'Instructor bank information update successful',
                'user_id' => $user_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateInstructorPassword(Request $request){
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

                save_activity_trail($user_id, 'Instructor password updated', 'Instructor with id ('. $user_id .') password updated',
                                    $date_updated);
    
                return response()->json([
                    'message' => 'Instructor password update successful',
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

    public function updateInstructorNotificationSetting(Request $request){
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

            $data_for_instructor_table = array(
                'is_email_notification_checked' => $is_email_notification_checked,
                'is_sms_notification_checked' => $is_sms_notification_checked
            );

            Instructor::where('user_id', $user_id)->update($data_for_instructor_table);

            save_activity_trail($user_id, 'Instructor notification setting updated', 'Instructor with id ('. $user_id .') notification setting updated',
                        $date_updated);

            return response()->json([
                'message' => 'Instructor notification update successful',
                'user_id' => $user_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }


    public function getInstructorPersonalProfile(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $user_info_result = DB::select('select name, email, phone_number from users where id = :id', ['id' => $user_id]); 

            $instructor_info_result = DB::select('select state, country, profile_picture_url, profile_url, professional_title, about_you, linkedin_profile_url from instructors where user_id = :id', ['id' => $user_id]); 

            $instructor_personal_profile = array(
                'name' => $user_info_result[0]->name,
                'email' => $user_info_result[0]->email,
                'phone_number' => $user_info_result[0]->phone_number,
                'professional_title' => $instructor_info_result[0]->professional_title,
                'about_you' => $instructor_info_result[0]->about_you,
                'linkedin_profile_url' => $instructor_info_result[0]->linkedin_profile_url,
                'profile_url' => $instructor_info_result[0]->profile_url,
                'profile_picture_url' => $instructor_info_result[0]->profile_picture_url,
                'country' => $instructor_info_result[0]->country,
                'state' => $instructor_info_result[0]->state
            );

            return response()->json([
                'message' => 'Instructor personal profile successfully gotten',
                'instructor_personal_profile' => $instructor_personal_profile,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getInstructorBankInformation(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $instructor_bank_info_result = DB::select('select bank_name, bank_account_number, bank_account_name from instructors where user_id = :id', ['id' => $user_id]); 

            $instructor_bank_info = array(
                'bank_account_number' => $instructor_bank_info_result[0]->bank_account_number,
                'bank_account_name' => $instructor_bank_info_result[0]->bank_account_name,
                'bank_name' => $instructor_bank_info_result[0]->bank_name
            );

            return response()->json([
                'message' => 'Instructor bank information successfully gotten',
                'instructor_bank_info' => $instructor_bank_info,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getInstructorNotificationSetting(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $instructor_notification_setting_result = DB::select('select is_email_notification_checked, is_sms_notification_checked from instructors where user_id = :id', ['id' => $user_id]); 

            $instructor_notification_setting = array(
                'is_email_notification_checked' => $instructor_notification_setting_result[0]->is_email_notification_checked,
                'is_sms_notification_checked' => $instructor_notification_setting_result[0]->is_sms_notification_checked
            );

            return response()->json([
                'message' => 'Instructor notification setting successfully gotten',
                'instructor_notification_setting' => $instructor_notification_setting,
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
                $user = $auth_check_result['data'];
            }
                  
            $user_id = $user->id;
            $date_updated = get_current_date_time();

            //Validation
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
                Instructor::where('user_id', $user_id)->update($data_for_user_table);

                save_activity_trail($user_id, 'Instructor profile picture updated', 'Instructor with id ('. $user_id .') profile picture updated',
                        $date_updated);
                
            }else{
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

    public function getAllEnrolledStudentsForCourse(Request $request){
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

            $all_enrolled_students_result = DB::select('select u.id, u.name, u.profile_picture_url from enrolment_history e 
                                inner join users u on u.id = e.user_id where e.course_id = :course_id and u.is_verified = 1', 
                                ['course_id' => $request->course_id]); 

            return response()->json([
                'message' => 'All enrolled students for course gotten',
                'all_enrolled_students' => $all_enrolled_students_result,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function checkIfInstructorBankInformationIsFilled(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $instructor_bank_info_result = DB::select('select bank_name, bank_account_number, bank_account_name from instructors where user_id = :id', ['id' => $user_id]); 

            $bank_account_number = check_if_null_or_empty($instructor_bank_info_result[0]->bank_account_number);
            $bank_account_name = $instructor_bank_info_result[0]->bank_account_name;
            $bank_name = check_if_null_or_empty($instructor_bank_info_result[0]->bank_name);

            $is_bank_information_set = false;
            $is_bank_information_set_message = '';

            if((!isset($bank_account_number) || trim($bank_account_number) === '') && (!isset($bank_name) || trim($bank_name) === '')){
                $is_bank_information_set = false;
                $is_bank_information_set_message = 'Instructors bank information is not set';
            } else{
                $is_bank_information_set = true;
                $is_bank_information_set_message = 'Instructors bank information is set';
            }

            
            return response()->json([
                'message' => $is_bank_information_set_message,
                'is_bank_information_set' => $is_bank_information_set,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllInstructorUpcomingClasses(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $all_instructor_live_classes_result = DB::select('select l.*, t.abbr as timezone_name, lt.name as live_class_type_name, 
                                            c.topic, c.description, lp.name as platform_name from live_class l 
                                            inner join live_class_platform lp on lp.id = l.preferred_platform 
                                            inner join timezones t on t.id = l.time_zone 
                                            inner join live_class_type lt on lt.live_class_type_id = l.live_class_type 
                                            inner join curriculum c on c.curriculum_id = l.curriculum_id 
                                            where l.created_by = :id and l.is_deleted = :isDeleted order by l.date desc limit 5', 
                                            ['id' => $user_id, 'isDeleted' => Config::get('constants.false')]);
                    

            $upcoming_classes = array();

            foreach($all_instructor_live_classes_result as $all_instructor_live_classes){
                $data = [
                            'live_class_id' => $all_instructor_live_classes->id,
                            'course_id' => $all_instructor_live_classes->course_id,
                            'live_class_name' => $all_instructor_live_classes->live_class_name,
                            'date' => format_date($all_instructor_live_classes->date),
                            'start_time' => $all_instructor_live_classes->start_time,
                            'end_time' => $all_instructor_live_classes->end_time,
                            'link_to_live_class' => $all_instructor_live_classes->link_to_live_class,
                            'platform_name' => $all_instructor_live_classes->platform_name,

                            'live_class_type_id' => $all_instructor_live_classes->live_class_type,
                            'live_class_type_name' => $all_instructor_live_classes->live_class_type_name,

                            'curriculum_id' => $all_instructor_live_classes->curriculum_id,
                            'topic' => $all_instructor_live_classes->topic,
                            'description' => $all_instructor_live_classes->description,
                           
                            'timezone_id' => (int)$all_instructor_live_classes->time_zone,
                            'timezone_name' => $all_instructor_live_classes->timezone_name,
                        ];

                array_push($upcoming_classes, $data);
            }
           

            return response()->json([
                'message' => 'Upcoming instructor live classes gotten',
                'all_instructor_live_classes' => $upcoming_classes
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllInstructorCompletedCourses(Request $request){
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
                                       order by e.created_at desc)', ['id' => $user_id, 'isCompleted' => Config::get('constants.true')]); 
    
            return response()->json([
                'message' => 'All completed courses',
                'completed_courses' => $completed_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllInstructorPublishedCourses(Request $request){
        try{           
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                
            $user_id = $user->id;

            $published_courses_result = DB::select('select c.id, c.course_name, 
                                    (select cc.name from course_category cc where cc.id = c.course_category) as course_category_name, 
                                    c.thumbnail_file_url, c.course_rating from courses c 
                                    where c.created_by = :id and c.is_deleted = 0 order by c.id desc', 
                                    ['id' => $user_id]); 
    
            return response()->json([
                'message' => 'All published courses',
                'published_courses' => $published_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function processInstructorApplication(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'resume' => 'required|mimes:docx,doc,pdf',
                'full_name' => 'required|min:4',
                'email' => 'required|email',
                'phone_number' => 'required',
                'about_me' => 'required',
                'professional_portfolio' => 'required',
                'linkedin_profile_url' => 'required',
                'country_id' => 'required|numeric',
                'course_category_id' => 'required|numeric',
                'availability' => 'required',
                'why_you_want_to_be_an_instructor' => 'required',
                'why_do_you_want_to_teach' => 'required',
                'speak_english_frequently' => 'required',
                'where_did_you_hear_about_stevia' => 'required'
            ]);


            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            if($request->file('resume')) {
                $file = $request->file('resume');
                $filename = time().'_'.$file->getClientOriginalName();

                // Upload file
                //$file->move($location, $filename);
                $file = $request->resume->storeAs(Config::get('constants.instructor_resume_file_path'), $filename);
                logger('file uploaded');

                //$path = Storage::disk('public')->url($filename);
                $path = \Storage::url($file);
                $url = asset($path);

                //Get Params
                $full_name = check_if_null_or_empty($request->full_name);
                $email = check_if_null_or_empty($request->email);
                $phone_number = check_if_null_or_empty($request->phone_number);
                $about_me = check_if_null_or_empty($request->about_me);
                $professional_portfolio = check_if_null_or_empty($request->professional_portfolio);
                $other_platforms = check_if_null_or_empty($request->other_platforms);
                $linkedin_profile_url = check_if_null_or_empty($request->linkedin_profile_url);
                $country_id = check_if_null_or_empty($request->country_id);
                $course_category = check_if_null_or_empty($request->course_category_id);
                $availability = check_if_null_or_empty($request->availability);
                $why_you_want_to_be_an_instructor = check_if_null_or_empty($request->why_you_want_to_be_an_instructor);
                $why_do_you_want_to_teach = check_if_null_or_empty($request->why_do_you_want_to_teach);
                $speak_english_frequently = check_if_null_or_empty($request->speak_english_frequently);
                $where_did_you_hear_about_stevia = check_if_null_or_empty($request->where_did_you_hear_about_stevia);


                $user = User::create([
                    'name' => $full_name,
                    'email' => $email,
                    'phone_number' => $phone_number,
                    'country' => $country_id,
                    'about_me' => $about_me,
                    'is_email_notification_checked' => Config::get('constants.true'),
                    'is_sms_notification_checked' => Config::get('constants.true'),
                    'is_verified' => Config::get('constants.true'),
                    'password' => bcrypt(generate_random_string(7)),
                ]);

                DB::insert('insert into user_role (role_id, user_id) values (?, ?)', [Config::get('constants.instructor_role'), $user->id]);

                $profile_url = generate_random_string(7);
    
                Instructor::create([
                    'user_id' => $user->id,
                    'is_deleted' => Config::get('constants.false'),
                    'is_approved' => Config::get('constants.true'),
                    'linkedin_profile_url' => $linkedin_profile_url,
                    'profile_url' => $profile_url,
                    'country' => $country_id,
                    'about_you' => $about_me,
                    'professional_portfolio' => $professional_portfolio,
                    'other_platforms' => $other_platforms,
                    'course_category' => $course_category,
                    'availability' => $availability,
                    'why_you_want_to_be_an_instructor' => $why_you_want_to_be_an_instructor,
                    'why_do_you_want_to_teach' => $why_do_you_want_to_teach,
                    'speak_english_frequently' => $speak_english_frequently,
                    'where_did_you_hear_about_stevia' => $where_did_you_hear_about_stevia
                ]);

                MailController::send_instructor_application_submission_mail($full_name, $email);

                $date_created = get_current_date_time();

                save_activity_trail($user->id, 'Instructor application', 'Instructor application submitted', $date_created);

            }else{
                logger('file not uploaded');
            }

            return response()->json([
                'message' => 'Instructor application submitted and mail sent successfully',
                'user_id' => $user->id
            ], 200);
        
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllInstructors(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $page_number = check_if_null_or_empty($request->page_number);

            // $all_instructors_result = DB::select('select u.id, u.name, u.professional_title, u.profile_picture_url, u.country,
            //                                     i.professional_title, i.rating, i.profile_url, i.country, i.course_category
            //                                     FROM instructors i
            //                                     INNER JOIN users u ON u.id = i.user_id
            //                                     WHERE i.is_deleted = :isDeleted AND i.is_approved = :isApproved AND u.is_verified = :isVerified', 
            //                                 ['isDeleted' => Config::get('constants.false'), 'isApproved' => Config::get('constants.true'), 'isVerified' => Config::get('constants.true')]);

            $all_instructors_result = DB::table("instructors")
                                        ->join("users", function($join){
                                            $join->on("users.id", "=", "instructors.user_id");
                                        })
                                        ->select("users.id", "users.name", "users.professional_title", "users.profile_picture_url", "users.country", "instructors.professional_title", "instructors.rating", "instructors.profile_url", "instructors.country", "instructors.course_category")
                                        ->where("instructors.is_deleted", "=", Config::get('constants.false'))
                                        ->where("instructors.is_approved", "=", Config::get('constants.true'))
                                        ->where("users.is_verified", "=", Config::get('constants.true'))
                                        ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);

            return response()->json([
                'message' => 'All instructors gotten',
                'all_instructors' => $all_instructors_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function searchForInstructor(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $search_keyword = check_if_null_or_empty($request->search_keyword);
            $page_number = check_if_null_or_empty($request->page_number);

            $search_keyword = '%' . $search_keyword . '%';

            $instructor_search_result = DB::table("instructors")
                                        ->join("users", function($join){
                                            $join->on("instructors.user_id", "=", "users.id");
                                        })
                                        ->select("users.id", "users.name", "instructors.professional_title", "instructors.profile_picture_url")
                                        ->where("users.name", "like", "$search_keyword")
                                        ->where("instructors.is_deleted", "=", Config::get('constants.false'))
                                        ->where("instructors.is_approved", "=", Config::get('constants.true'))
                                        ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);

            if(count($instructor_search_result) > 0){
                return response()->json([
                    'message' => 'Instructors found',
                    'courses' => $instructor_search_result
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No instructor found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function filterInstructor(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $course_category = check_if_null_or_empty($request->course_category);
            $page_number = check_if_null_or_empty($request->page_number);

            $instructor_search_result = DB::table("instructors")
                                        ->join("course_category", function($join){
                                            $join->on("instructors.course_category", "=", "course_category.id");
                                        })
                                        ->join("users", function($join){
                                            $join->on("instructors.user_id", "=", "users.id");
                                        })
                                        ->select("users.id", "users.name", "instructors.professional_title", "instructors.profile_picture_url")
                                        ->where("instructors.course_category", "=", $course_category)
                                        ->where("instructors.is_deleted", "=", Config::get('constants.false'))
                                        ->where("instructors.is_approved", "=", Config::get('constants.true'))
                                        ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);
            
            if(count($instructor_search_result) > 0){
                return response()->json([
                    'message' => 'Instructors found',
                    'courses' => $instructor_search_result
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No instructor found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function searchForInstructorNew(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $search_keyword = check_if_null_or_empty($request->search_keyword);
            $page_number = check_if_null_or_empty($request->page_number);
            $course_category = check_if_null_or_empty($request->course_category);


            if(is_null($search_keyword) && is_null($course_category)){
                $instructor_search_result = DB::table("instructors")
                                            ->join("course_category", function($join){
                                                $join->on("instructors.course_category", "=", "course_category.id");
                                            })
                                            ->join("users", function($join){
                                                $join->on("instructors.user_id", "=", "users.id");
                                            })
                                            ->select("users.id", "users.name", "instructors.professional_title", "instructors.profile_picture_url")
                                            ->where("instructors.is_deleted", "=", Config::get('constants.false'))
                                            ->where("instructors.is_approved", "=", Config::get('constants.true'))
                                            ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);

            } else {

                $instructor_search = DB::table("instructors")
                                        ->join("course_category", function($join){
                                            $join->on("instructors.course_category", "=", "course_category.id");
                                        })
                                        ->join("users", function($join){
                                            $join->on("instructors.user_id", "=", "users.id");
                                        });
                                            
                $instructor_search->select("users.id", "users.name", "instructors.professional_title", "instructors.profile_picture_url");

                if(!is_null($search_keyword) ){
                    //echo 'in search_keyword - ' . $search_keyword;
                    $search_keyword = '%' . $search_keyword . '%';

                    $instructor_search->where("users.name", "like", "$search_keyword");
                }

                if(!is_null($course_category) ){
                    //echo 'in course_category - ' . $course_category;
                    $instructor_search->where("instructors.course_category", "=", $course_category);
                }                             
                                            
                $instructor_search->where("instructors.is_deleted", "=", Config::get('constants.false'));
                $instructor_search->where("instructors.is_approved", "=", Config::get('constants.true'));
                $instructor_search_result = $instructor_search->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);
            }

            if(count($instructor_search_result) > 0){
                return response()->json([
                    'message' => 'Instructors found',
                    'courses' => $instructor_search_result
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No instructor found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function approveInstructor(Request $request){
        try{
            $this->validate($request, [
                'instructor_id' => 'required|numeric'
            ]);

            //Get Params
            $instructor_id = check_if_null_or_empty($request->instructor_id);
            $date_updated = get_current_date_time();

            $data_for_instructor_table = array(
                'is_approved' => Config::get('constants.true')
            );

            Instructor::where('user_id', $instructor_id)->update($data_for_instructor_table);

            save_activity_trail($instructor_id, 'Instructor Approved', 'Instructor with id ('. $instructor_id .') has been approved',
                                $date_updated);

            return response()->json([
                'message' => 'Instructor approved',
                'user_id' => $instructor_id,
            ], 200);

        } catch(Exception $e){
            return $e->getMessage();
        }
    }
}