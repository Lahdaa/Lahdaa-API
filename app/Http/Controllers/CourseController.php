<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;
use App\Http\Controllers\MailController;
use App\Models\Course;
use Illuminate\Pagination\Paginator;
use Storage;


class CourseController extends Controller
{
    public function createCourse(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'];
            }
                
            $user_id = $user->id;

            $validator = Validator::make($request->all(),[ 
                //'thumbnail_file' => 'required|mimes:png,jpg,jpeg'
                //'course_name' => 'required'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }

            $url = '';

            if($request->file('thumbnail_file')) {
                $file = $request->file('thumbnail_file');
                $filename = time().'_'.$file->getClientOriginalName();

                // Upload file
                //$file->move($location, $filename);
                $file = $request->thumbnail_file->storeAs(Config::get('constants.thumbnail_upload_file_path'), $filename);
                logger('file uploaded');

                //$path = Storage::disk('public')->url($filename);
                $path = \Storage::url($file);
                $url = asset($path);

            }else{
                logger('thumbnail_file does not exist. Not uploaded');
            }


            //Get Params
            $course_name = check_if_null_or_empty($request->course_name);
            $sub_title = check_if_null_or_empty($request->sub_title);
            $course_overview = check_if_null_or_empty($request->course_overview);
            $class_size = check_if_null_or_empty($request->class_size);
            $course_category = check_if_null_or_empty($request->course_category);
            $outcomes = check_if_null_or_empty($request->outcomes);
            $course_requirements = check_if_null_or_empty($request->course_requirements);
            $about_course = check_if_null_or_empty($request->about_course);
            $course_availability = check_if_null_or_empty($request->course_availability);
            $promo_video_url = check_if_null_or_empty($request->promo_video_url);
            $price = check_if_null_or_empty($request->price);
            $is_discounted = Config::get('constants.false');
            $discount_price = check_if_null_or_empty($request->discount_price);
            $is_published = check_if_null_or_empty($request->is_published);
            $created_by = check_if_null_or_empty($user_id);
            $is_deleted = Config::get('constants.false');
            $date_created = get_current_date_time();
            $is_featured_course = Config::get('constants.false');
            $start_date = check_if_null_or_empty($request->start_date);
            $end_date = check_if_null_or_empty($request->end_date);
            $level_of_competence = check_if_null_or_empty($request->level_of_competence);


            $data = array(
                'course_name' => $course_name,
                'sub_title' => $sub_title,
                'course_overview' => $course_overview,
                'class_size' => $class_size,
                'course_category' => $course_category,
                'outcomes' => $outcomes,
                'course_requirements' => $course_requirements,
                'about_course' => $about_course,
                'course_availability' => $course_availability,
                'thumbnail_file_url' => $url,
                'promo_video_url' => $promo_video_url,
                'price' => $price,
                'is_discounted' => $is_discounted,
                'discount_price' => $discount_price,
                'is_published' => $is_published,
                'created_by' => $created_by,
                'is_deleted' => $is_deleted,
                'created_at' => $date_created,
                'is_featured_course' => $is_featured_course,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'level_of_competence' => $level_of_competence
            );

            $course_id = DB::table('courses')->insertGetId($data);

            $instructor_name = $user->name;
            $instructor_email = $user->email;

            MailController::send_course_creation_mail($instructor_name, $instructor_email, $course_id, $course_name);

            save_activity_trail($user_id, 'Course created', 'Course with id ('.$course_id.') created',
                            $date_created);


            return response()->json([
                'message' => 'Course created',
                'course_id' => $course_id,
                'course_name' => $course_name,
                'class_size' => $class_size,
                'course_category' => $course_category,
                'level_of_competence' => $level_of_competence,
                'thumbnail_file_url' => $url,
                'outcomes' => $outcomes,
                'course_requirements' => $course_requirements,
                'about_course' => $about_course,
                'start_date' => $start_date,
                'end_date' => $end_date
            ], 200);
        
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateCourse(Request $request){
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
                //'thumbnail_file' => 'required|mimes:png,jpg,jpeg'
                //'course_name' => 'required'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  


            //Get Params
            $course_id = check_if_null_or_empty($request->course_id);
            $course_name = check_if_null_or_empty($request->course_name);
            $sub_title = check_if_null_or_empty($request->sub_title);
            $course_overview = check_if_null_or_empty($request->course_overview);
            $class_size = check_if_null_or_empty($request->class_size);
            $course_category = check_if_null_or_empty($request->course_category);
            $outcomes = check_if_null_or_empty($request->outcomes);
            $course_requirements = check_if_null_or_empty($request->course_requirements);
            $about_course = check_if_null_or_empty($request->about_course);
            $course_availability = check_if_null_or_empty($request->course_availability);
            $is_discounted = Config::get('constants.false');
            $discount_price = check_if_null_or_empty($request->discount_price);
            $created_by = check_if_null_or_empty($user_id);
            $date_updated = get_current_date_time();
            $is_featured_course = Config::get('constants.false');
            $start_date = check_if_null_or_empty($request->start_date);
            $end_date = check_if_null_or_empty($request->end_date);
            $level_of_competence = check_if_null_or_empty($request->level_of_competence);

            $url = '';

            if($request->file('thumbnail_file')) {
                $file = $request->file('thumbnail_file');
                $filename = time().'_'.$file->getClientOriginalName();

                // Upload file
                //$file->move($location, $filename);
                $file = $request->thumbnail_file->storeAs(Config::get('constants.thumbnail_upload_file_path'), $filename);
                logger('file uploaded');

                //$path = Storage::disk('public')->url($filename);
                $path = \Storage::url($file);
                $url = asset($path);

                $data = array(
                    'thumbnail_file_url' => $url
                );
        
                DB::table('courses')->where('id', $course_id)->update($data);

            } else{
                logger('thumbnail_file does not exist. Not uploaded');
            }
    
            $data = array(
                'course_name' => $course_name,
                'sub_title' => $sub_title,
                'course_overview' => $course_overview,
                'class_size' => $class_size,
                'course_category' => $course_category,
                'outcomes' => $outcomes,
                'course_requirements' => $course_requirements,
                'about_course' => $about_course,
                'course_availability' => $course_availability,
                'is_discounted' => $is_discounted,
                'discount_price' => $discount_price,
                'updated_at' => $date_updated,
                'is_featured_course' => $is_featured_course,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'level_of_competence' => $level_of_competence
            );
    
            DB::table('courses')->where('id', $course_id)->update($data);

            save_activity_trail($user_id, 'Course updated', 'Course with id ('.$course_id.') updated',
                            $date_updated);
    
            if($url != ''){
                return response()->json([
                    'message' => 'Course updated',
                    'course_name' => $course_name,
                    'sub_title' => $sub_title,
                    'course_overview' => $course_overview,
                    'class_size' => $class_size,
                    'course_category' => $course_category,
                    'outcomes' => $outcomes,
                    'course_requirements' => $course_requirements,
                    'about_course' => $about_course,
                    'course_availability' => $course_availability,
                    'thumbnail_file_url' => $url,
                    'is_discounted' => $is_discounted,
                    'discount_price' => $discount_price,
                    'updated_at' => $date_updated,
                    'is_featured_course' => $is_featured_course,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'level_of_competence' => $level_of_competence
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Course updated',
                    'course_name' => $course_name,
                    'sub_title' => $sub_title,
                    'course_overview' => $course_overview,
                    'class_size' => $class_size,
                    'course_category' => $course_category,
                    'outcomes' => $outcomes,
                    'course_requirements' => $course_requirements,
                    'about_course' => $about_course,
                    'course_availability' => $course_availability,
                    'is_discounted' => $is_discounted,
                    'discount_price' => $discount_price,
                    'updated_at' => $date_updated,
                    'is_featured_course' => $is_featured_course,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'level_of_competence' => $level_of_competence
                ], 200);
            }
          
        } catch(Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
        
    }

    public function deleteCourse(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            $is_deleted = Config::get('constants.true');
            $date_updated = get_current_date_time();

            $data = array(
                'is_deleted' => $is_deleted,
                'updated_at' => $date_updated
            );
    
            DB::table('courses')->where('id', $course_id)->update($data);

            save_activity_trail($user_id, 'Course deleted', 'Course with id ('.$course_id.') deleted',
                                $date_updated);
    
            return response()->json([
                'message' => 'Course deleted',
                'course_id' => $course_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getInstructorCourses(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            
            $courses_result = DB::select('select * from courses where is_deleted = 0 and created_by = :id', ['id' => $user_id]); 
    
            return response()->json([
                'message' => 'Instructors courses',
                'courses' => $courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getCourseById(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $course_result = DB::select('select * from courses where is_deleted = 0 and id = :id', ['id' => $course_id]); 
    
            if(!empty($course_result)){
                return response()->json([
                    'message' => 'Course gotten',
                    'courses' => $course_result[0]
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                    'courses' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getCourseByIdFromURLOld(Request $request, $course_id){
        try{
            /*$header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id; */
            
            $course_result = DB::select('select c.id, c.course_name, c.class_size, c.outcome, c.about_course, c.course_availability,
                                c.thumbnail_file_url, c.promo_video_url, c.course_rating, c.price, c.is_discounted, c.discount_price,
                                c.is_featured_course, c.created_by, cat.name as course_category_name from courses c 
                                inner join course_category cat on c.course_category = cat.id 
                                where c.is_deleted = 0 and c.id = :id', ['id' => $course_id]); 


            if(!empty($course_result)){
                $instructors_user_id = $course_result[0]->created_by;

                $instructor_details = DB::select('select u.name, i.rating, i.professional_title, i.profile_picture_url, 
                                    about_you, linkedin_profile_url from users u right join instructors i on u.id = i.user_id 
                                    where u.id = :id', ['id' => $instructors_user_id]); 

                $no_of_courses_created_by_instructor = DB::select('select count(*) as no_of_courses_created from courses 
                                    where created_by = :id', ['id' => $instructors_user_id]);

                return response()->json([
                    'message' => 'Course gotten',
                    'course' => $course_result[0],
                    'course_category' => $course_result[0]->course_category_name,
                    'course_rating' => $course_result[0]->course_rating == null || $course_result[0]->course_rating === 'null' 
                                        ? 0 : $course_result[0]->course_rating,
                    'instructor_details' => $instructor_details[0],
                    'no_of_courses_created_by_instructor' => $no_of_courses_created_by_instructor[0]->no_of_courses_created

                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                    'course' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        } 
    }

    public function getCourseByIdFromURL(Request $request, $course_id){
        try{
            /*$header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id; */

            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == true){
                $user = $auth_check_result['data'];
                $user_id = $user->id;

                $check_if_loggedin_student_is_enroled_result = DB::select('select count(*) as count from enrolment_history where 
                    course_id = :course_id and user_id = :user_id', ['course_id' => (int)$course_id, 'user_id' => $user_id]);
            }
                
            
            $course_result = DB::select('select c.id, c.course_name, c.sub_title, c.course_overview, c.outcomes, c.course_requirements, c.class_size, c.level_of_competence, 
                                        c.about_course, c.course_availability, c.thumbnail_file_url, c.promo_video_url, c.course_rating, c.price, c.is_discounted, 
                                        c.discount_price, c.is_featured_course, c.start_date, c.end_date, c.created_by, c.created_at, cat.name as course_category_name, 
                                        l.name as level_of_competence_name 
                                        from courses c 
                                        inner join course_category cat on c.course_category = cat.id 
                                        inner join level_of_competence l on c.level_of_competence = l.level_of_competence_id 
                                        where c.is_deleted = 0 and c.id = :id', ['id' => $course_id]); 


            if(!empty($course_result)){
                $instructors_user_id = $course_result[0]->created_by;
                $course_result[0]->course_duration = self::getDaysBetweenTwoDates($course_result[0]->start_date, $course_result[0]->end_date);

                $instructor_details = DB::select('select u.name, i.rating, i.professional_title, i.profile_picture_url, 
                                    about_you, linkedin_profile_url from users u right join instructors i on u.id = i.user_id 
                                    where u.id = :id', ['id' => $instructors_user_id]);

                $no_of_courses_created_by_instructor = DB::select('select count(*) as no_of_courses_created from courses 
                                    where created_by = :id', ['id' => $instructors_user_id]);

                $course_contents_result = DB::select('select * from course_content where is_deleted = 0 
                                    and course_id = :course_id', ['course_id' => (int)$course_id]); 

                $course_reviews_result = DB::select('select * from reviews where is_deleted = 0 
                                    and course_id = :course_id', ['course_id' => (int)$course_id]); 

                $enrolment_count_result = DB::select('select count(*) as enrolment_count from enrolment_history where course_id = :course_id', 
                                    ['course_id' => (int)$course_id]);

                $course_curriculum_result = DB::select('select * from curriculum where is_deleted = 0 
                                    and course_id = :course_id', ['course_id' => $course_id]);

                $get_last_3_enroled_users_picture_result = DB::select('select u.profile_picture_url from enrolment_history e 
                                    inner join users u on e.user_id = u.id 
                                    where e.course_id = :course_id
                                    order by e.date_started desc
                                    limit 3', ['course_id' => (int)$course_id]);

                $who_is_this_course_for_result = DB::select('select w.*, wc.name as category_name from who_is_this_course_for w 
                                    inner join who_is_this_course_for_category wc on wc.category_id = w.category
                                    where w.is_deleted = 0 and w.course_id = :course_id', ['course_id' => $course_id]);

                return response()->json([
                    'message' => 'Course gotten',
                    'course' => $course_result[0],
                    'course_category' => $course_result[0]->course_category_name,
                    'course_rating' => $course_result[0]->course_rating == null || $course_result[0]->course_rating === 'null' 
                                        ? 0 : $course_result[0]->course_rating,
                    'instructor_details' => $instructor_details[0],
                    'no_of_courses_created_by_instructor' => $no_of_courses_created_by_instructor[0]->no_of_courses_created,
                    'course_contents' => $course_contents_result,
                    'course_reviews' => $course_reviews_result,
                    'enrolment_count' => $enrolment_count_result[0]->enrolment_count,
                    'last_3_enroled_users_picture' => $get_last_3_enroled_users_picture_result,
                    'curriculums' => $course_curriculum_result,
                    'who_is_this_course_for' => $who_is_this_course_for_result,
                    //'is_logged_in_student_enrolled' => isset($check_if_loggedin_student_is_enroled_result) ? $check_if_loggedin_student_is_enroled_result[0]->count : 0,
                    'is_logged_in_student_enrolled' => isset($check_if_loggedin_student_is_enroled_result) ? 1 : 0
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                    'course' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        } 
    }

    public static function getDaysBetweenTwoDates($start_date, $end_date){
        try {
            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);
            
            $diff = $start_date->diffInWeeks($end_date);

            return $diff;
        } catch(Exception $e){
            return $e->getMessage();
        }

        return null;
    }

    public function getCourseByIdForEdit(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $course_result = DB::select('select * from courses where is_deleted = 0 and id = :id', ['id' => $course_id]);

            $course_live_classes_result = DB::select('select l.id as live_class_id, l.live_class_name, l.date, l.start_time, l.end_time, 
                                    l.live_class_type, lt.name as live_class_type_name, l.curriculum_id, l.link_to_live_class, 
                                    l.note_to_students, l.preferred_platform as preferred_platform_id, p.name as preferred_platform_name, 
                                    c.topic as curriculum_topic, c.description as curriculum_description, 
                                    l.time_zone as time_zone_id, t.abbr as time_zone_name FROM live_class l 
                                    inner join live_class_platform p on p.id = l.preferred_platform 
                                    inner join timezones t on t.id = l.time_zone 
                                    inner join curriculum c on c.curriculum_id = l.curriculum_id 
                                    inner join live_class_type lt on lt.live_class_type_id = l.live_class_type  
                                    where l.is_deleted = 0 and l.course_id = :id', ['id' => $course_id]);

            $course_contents_result = DB::select('select * from course_content where is_deleted = 0 
                                                and course_id = :course_id', ['course_id' => $course_id]);

            $course_curriculum_result = DB::select('select * from curriculum where is_deleted = 0 
                                                and course_id = :course_id', ['course_id' => $course_id]);

            $who_is_this_course_for_result = DB::select('select w.id, w.course_id, w.category as category_id, w.description, w.is_deleted, 
                                                wc.name as category_name from who_is_this_course_for w
                                                inner join who_is_this_course_for_category wc on wc.category_id = w.category
                                                where w.is_deleted = 0 and w.course_id = :course_id', ['course_id' => $course_id]);
     
            if(!empty($course_result)){
                return response()->json([
                    'message' => 'Course gotten',
                    'courses' => $course_result[0],
                    'live_classes' => $course_live_classes_result,
                    'course_contents' => $course_contents_result,
                    'curriculums' => $course_curriculum_result,
                    'who_is_this_course_for' => $who_is_this_course_for_result
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                    'courses' => [],
                    'live_classes' => [],
                    'course_contents' => [],
                    'curriculums' => [],
                    'who_is_this_course_for' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getCourseProfile(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $course_result = DB::select('select c.*, u.name as instructor_name from courses c 
                                inner join users u on u.id = c.created_by 
                                where c.is_deleted = 0 and c.id = :id', ['id' => $course_id]);
     

            if(!empty($course_result)){
                $instructors_user_id = $course_result[0]->created_by;

                $instructor_details = DB::select('select u.name, i.rating, i.professional_title, i.profile_picture_url, 
                                    about_you, linkedin_profile_url from users u right join instructors i on u.id = i.user_id 
                                    where u.id = :id', ['id' => $instructors_user_id]); 

                $course_contents_result = DB::select('select * from course_content where is_deleted = 0 
                                            and course_id = :course_id', ['course_id' => $course_id]);

                $class_size = (int)$course_result[0]->class_size;

                $no_of_enrolled_students_query = DB::select('select count(*) as enrolment_count from enrolment_history where course_id = :id', ['id' => $course_id]);
                $no_of_enrolled_students = (int)$no_of_enrolled_students_query[0]->enrolment_count;

                $percentage_class_size = 0;

                if($class_size !== null && $class_size !== 'null' && $class_size > 0){
                    $percentage_class_size = ($no_of_enrolled_students / $class_size) * 100;
                }

                $course_curriculum_result = DB::select('select * from curriculum where is_deleted = 0 
                                                and course_id = :course_id', ['course_id' => $course_id]);

                $who_is_this_course_for_result = DB::select('select * from who_is_this_course_for where is_deleted = 0 
                                                and course_id = :course_id', ['course_id' => $course_id]);

                $course_live_classes_result = DB::select('select l.id, l.live_class_name, l.date, l.start_time, 
                                            l.end_time, l.time_zone, t.abbr as timezone_name_abbr, t.value as timezone_name, 
                                            p.name, l.link_to_live_class, l.link_to_recording, l.note_to_students, l.is_completed, 
                                            l.live_class_type as live_class_type_id, lt.name as live_class_type_name, c.topic as curriculum_name
                                            FROM live_class l 
                                            inner join live_class_platform p on p.id = l.preferred_platform 
                                            inner join live_class_type lt on lt.live_class_type_id = l.live_class_type 
                                            inner join curriculum c on c.curriculum_id = l.curriculum_id 
                                            inner join timezones t on t.id = l.time_zone 
                                            where l.is_deleted = 0 and l.course_id = :id', ['id' => $course_id]);

                $reviews_result = DB::select('select * from reviews where is_deleted = 0 
                                    and course_id = :course_id', ['course_id' => $course_id]);

                $is_course_reviewed = 0;

                $has_student_reviewed_course_result = DB::select('select count(*) as count from reviews where is_deleted = 0 
                                    and user_id = :user_id', ['user_id' => $user_id]);  

                if($has_student_reviewed_course_result[0]->count > 0){
                    $is_course_reviewed = 1;
                }

                $enrolment_result = DB::select('select count(*) as count from enrolment_history where course_id = :course_id and user_id = :user_id', 
                                    ['course_id' => (int)$course_id, 'user_id' => $user_id]);
                
                $is_student_enroled = $enrolment_result[0]->count > 0 ? 1: 0;

                $all_enroled_students = DB::select('select u.id, u.name, u.profile_picture_url from enrolment_history e right join users u on u.id = e.user_id 
                                        where e.course_id = :course_id', ['course_id' => (int)$course_id]);

                return response()->json([
                    'message' => 'Course gotten',
                    'courses' => $course_result[0],
                    'course_contents' => $course_contents_result,
                    'no_of_enrolled_students' => $no_of_enrolled_students,
                    'class_size' => $class_size,
                    'percentage_class_size' => $percentage_class_size,
                    'live_classes' => $course_live_classes_result,
                    'curriculums' => $course_curriculum_result,
                    'who_is_this_course_for' => $who_is_this_course_for_result,
                    'reviews' => $reviews_result,
                    'is_course_reviewed' => $is_course_reviewed,
                    'instructor_details' => $instructor_details[0],
                    'is_student_enroled' => $is_student_enroled,
                    'all_enroled_students' => $all_enroled_students
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                    'courses' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function updateCourseDuration(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            
            $start_date = check_if_null_or_empty($request->start_date);
            $end_date = check_if_null_or_empty($request->end_date);
            $date_updated = get_current_date_time();
    
            $data = array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'updated_at' => $date_updated
            );
    
            DB::table('courses')->where('id', $course_id)->update($data);

            save_activity_trail($user_id, 'Course duration changed', 
                                'Course duration with course id ('.$course_id.') updated',
                                $date_updated);
    
            return response()->json([
                'message' => 'Course duration updated',
                'course_id' => $course_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ], 200);
          
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function uploadPromoVideo(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            
            $promo_video_url = check_if_null_or_empty($request->promo_video_url);
    
            $data = array(
                'promo_video_url' => $promo_video_url
            );
    
            DB::table('courses')->where('id', $course_id)->update($data);   
    
            return response()->json([
                'message' => 'Promo video url updated',
                'course_id' => $course_id,
                'promo_video_url' => $promo_video_url
            ], 200);
          
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }
    
    public function getLiveClassByLiveClassId(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $live_class_id = check_if_null_or_empty($request->live_class_id);
            
            $live_class_result = DB::select('select l.*, lp.name as platform_name FROM live_class l inner join live_class_platform lp on lp.id = l.preferred_platform where l.id = :id', ['id' => $live_class_id]); 
    
            return response()->json([
                'message' => 'Live class gotten',
                'courses' => $live_class_result[0]
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getLiveClassesByCourseId(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $live_class_results = DB::select('select l.*, lp.name as platform_name FROM live_class l inner join live_class_platform lp on lp.id = l.preferred_platform where l.course_id = :id', ['id' => $course_id]); 
    
            return response()->json([
                'message' => 'Live classes gotten',
                'courses' => $live_class_results
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getInstructorPastLiveClasses(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            
            $past_live_classes_result = DB::select('select * FROM live_class WHERE date < curdate() and created_by = :id', ['id' => $user_id]); 
    
            return response()->json([
                'message' => 'Past live classes gotten',
                'past_courses' => $past_live_classes_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getInstructorFutureLiveClasses(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            
            $future_live_classes_result = DB::select('select * FROM live_class WHERE date > curdate() and created_by = :id', ['id' => $user_id]); 
    
            return response()->json([
                'message' => 'Future live classes gotten',
                'future_courses' => $future_live_classes_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function updatePriceForCourse(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            $price = check_if_null_or_empty($request->price);
            $date_updated = get_current_date_time();
    
    
            $data = array(
                'price' => $price,
                'updated_at' => $date_updated
            );
    
            DB::table('courses')->where('id', $course_id)->update($data);

            save_activity_trail($user_id, 'Course price updated', 
                                'Course price with course id ('.$course_id.') updated',
                                $date_updated);
    
            return response()->json([
                'message' => 'Course price updated',
                'price' => $price
            ], 200);
          
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }




    public static function date_sort($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    }

    public static function get_next_live_class_date($live_classes, $current_date) {
        try {
            $live_class_dates = array();
            $live_class_ids_and_dates = array();

            for($i = 0; $i < sizeof($live_classes); $i++){
                //Carbon::parse($live_class['date']);
                $x = [
                    'id' => $live_classes[$i]->id,
                    'date' => $live_classes[$i]->date,
                ];

                array_push($live_class_dates, $live_classes[$i]->date);
                array_push($live_class_ids_and_dates, $x);
            }

            usort($live_class_ids_and_dates, "static::date_sort");

            foreach ($live_class_ids_and_dates as $count => $live_class_id_and_date) {
                if (strtotime($current_date) < strtotime($live_class_id_and_date['date']))  {
                    $nextDate = date('Y-m-d', strtotime($live_class_id_and_date['date']));
                    $next_live_class_id = $live_class_id_and_date['id'];
                    break;
                } 
            }

            return $next_live_class_id;
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public static function getNextLiveClass(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
            
            $live_classes = DB::select('select * from live_class where created_by = :id', ['id' => $user_id]); 

            if(count($live_classes) > 0){

                $current_date = get_current_date_time();

                $next_live_class_id = self::get_next_live_class_date($live_classes, $current_date);

                $next_live_class = DB::select('select * from live_class where id = :id', ['id' => $next_live_class_id]); 
            }
    
            return response()->json([
                'message' => 'Instructors courses',
                'next_meeting' => $next_live_class
                
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function createCourseContentOld(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;
    
            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:mp4,mkv,mov,avi,png,jpg,jpeg,csv,txt,pdf,doc,docx|max:2048'
            ]);
    
            if($validator->fails()){
                return response()->json(['error' => $validator->errors()]);
            }
    
            if($files = $request->file('file')){
    
                //store file into upload folder
                $file = $request->file->store('public/documents');
    
                $filename = time() . '_' . $file->getClientOriginalName();
    
                //Get Params
                $course_id = check_if_null_or_empty($request->course_id);
                $course_content_name = check_if_null_or_empty($request->course_content_name);
                $estimated_time = check_if_null_or_empty($request->estimated_time);
                $created_by = check_if_null_or_empty($user_id);
                $is_approved = check_if_null_or_empty(Config::get('constants.true'));
                $is_deleted = check_if_null_or_empty(Config::get('constants.false'));
                $date_created = get_current_date_time();
    
                $data = array(
                    'course_id' => $course_id,
                    'course_content_name' => $course_content_name,
                    'estimated_time' => $estimated_time,
                    'created_by' => $created_by,
                    'is_approved' => $is_approved,
                    'is_deleted' => $is_deleted,
                    'created_at' => $date_created,
                    'attachment_file_url' => $filename,
                    'attachment_link' => $filename
                );
    
                $course_content_id = DB::table('course_content')->insertGetId($data);
    
                return response()->json([
                    'message' => 'Course content created',
                    'course_content_id' => $course_content_id,
                    'course_id' => (int)$course_id,
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function createCourseContent(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            $resource_name = check_if_null_or_empty($request->resource_name);
            $estimated_time = check_if_null_or_empty($request->estimated_time);
            $created_by = check_if_null_or_empty($user_id);
            $is_approved = Config::get('constants.true');
            $is_deleted = Config::get('constants.false');
            $date_created = get_current_date_time();
            $attachment_link = check_if_null_or_empty($request->attachment_link);
            $resource_type = check_if_null_or_empty($request->resource_type);
            $description = check_if_null_or_empty($request->description);
            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
    
            $data = array(
                'course_id' => $course_id,
                'resource_name' => $resource_name,
                'estimated_time' => $estimated_time,
                'created_by' => $created_by,
                'is_approved' => $is_approved,
                'is_deleted' => $is_deleted,
                'created_at' => $date_created,
                'attachment_file_url' => '',
                'attachment_link' => $attachment_link,
                'resource_type' => $resource_type,
                'description' => $description,
                'curriculum_id' => $curriculum_id
            );
    
            $course_content_id = DB::table('course_content')->insertGetId($data);

            $resource_type_result = DB::select('select name from resource_type where 
                                resource_type_id = :resource_type_id', ['resource_type_id' => (int)$resource_type]); 
    
            save_activity_trail($user_id, 'Course content created', 
                                'Course content with id ('.$course_content_id.') created',
                                $date_created);
    
            return response()->json([
                'message' => 'Course content created',
                'course_content_id' => $course_content_id,
                'course_id' => (int)$course_id,
                'resource_name' => $resource_name,
                'resource_type_id' => $resource_type,
                'resource_type_name' => $resource_type_result[0]->name,
                'attachment_link' => $attachment_link,
                'curriculum_id' => $curriculum_id,
                'estimated_time' => $estimated_time,
                'description' => $description,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateCourseContent(Request $request){
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
            //$course_id = check_if_null_or_empty($request->course_id);
            $course_content_id = check_if_null_or_empty($request->course_content_id);
            $resource_name = check_if_null_or_empty($request->resource_name);
            $estimated_time = check_if_null_or_empty($request->estimated_time);
            $is_approved = Config::get('constants.true');
            $is_deleted = Config::get('constants.false');
            $updated_at = get_current_date_time();
            $attachment_link = check_if_null_or_empty($request->attachment_link);
            $resource_type = check_if_null_or_empty($request->resource_type);
            $description = check_if_null_or_empty($request->description);
    
            $data = array(
                'resource_name' => $resource_name,
                'estimated_time' => $estimated_time,
                'is_approved' => $is_approved,
                'is_deleted' => $is_deleted,
                'updated_at' => $updated_at,
                'attachment_file_url' => '',             
                'attachment_link' => $attachment_link,
                'resource_type' => $resource_type,
                'description' => $description,
            );
    
            DB::table('course_content')->where('id', $course_content_id)->update($data); 
            
            save_activity_trail($user_id, 'Course content updated', 
                                'Course content with id ('.$course_content_id.') updated',
                                $updated_at);
    
            return response()->json([
                'message' => 'Course content updated',
                'course_content_id' => $course_content_id,
                'resource_name' => $resource_name,
                'estimated_time' => $estimated_time,        
                'attachment_link' => $attachment_link,
                'resource_type' => $resource_type,
                'description' => $description,
            ], 200);
          
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function deleteCourseContent(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_content_id = check_if_null_or_empty($request->course_content_id);
            $is_deleted = Config::get('constants.true');
            $date_updated = get_current_date_time();

            $data = array(
                'is_deleted' => $is_deleted,
                'updated_at' => $date_updated
            );
    
            DB::table('course_content')->where('id', $course_content_id)->update($data);   

            save_activity_trail($user_id, 'Course content deleted', 
                                'Course content with id ('.$course_content_id.') deleted',
                                $date_updated);
    
            return response()->json([
                'message' => 'Course content deleted',
                'course_content_id' => $course_content_id
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllCourseContentsForCourse(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $course_contents_result = DB::select('select * from course_content where is_deleted = 0 
                                        and course_id = :course_id', ['course_id' => (int)$course_id]); 
    
            return response()->json([
                'message' => 'Course contents',
                'course_contents' => $course_contents_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getCourseContentByCourseContentId(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_content_id = check_if_null_or_empty($request->course_content_id);
            
            $course_content_result = DB::select('select * from course_content where is_deleted = 0 
                                        and id = :id', ['id' => (int)$course_content_id]); 
    
            return response()->json([
                'message' => 'Course content',
                'course_content' => $course_content_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function uploadCourseContentAttachment(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            // Validation
            $request->validate([
                'file' => 'required|mimes:mp4,mkv,mov,avi,png,jpg,jpeg,csv,txt,pdf|max:2048'
            ]); 

            if($request->file('file')) {
                $file = $request->file('file');
                $filename = time().'_'.$file->getClientOriginalName();

                $course_content_id = check_if_null_or_empty($request->course_content_id);
                $course_id = check_if_null_or_empty($request->course_id);
                $file_type = check_if_null_or_empty($request->file_type);

                // File upload location
                $location = 'files';

                // Upload file
                $file->move($location, $filename);
                logger('file uploaded');
                
            }else{
                logger('file not uploaded');
            }

    
            return response()->json([
                'message' => 'File uploaded successfully'
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function createLiveClass(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            $live_class_name = check_if_null_or_empty($request->live_class_name);
            $live_class_type = check_if_null_or_empty($request->live_class_type);
            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
            $date = check_if_null_or_empty($request->date);
            $start_time = check_if_null_or_empty($request->start_time);
            $end_time = check_if_null_or_empty($request->end_time);
            $time_zone_id = check_if_null_or_empty($request->time_zone_id);
            $preferred_platform = check_if_null_or_empty($request->preferred_platform);
            $link_to_live_class = check_if_null_or_empty($request->link_to_live_class);
            $created_by = check_if_null_or_empty($user_id);
            $is_completed = Config::get('constants.false');
            $is_deleted = Config::get('constants.false');
            $date_created = get_current_date_time();
    
            $data = array(
                'course_id' => $course_id,
                'live_class_name' => $live_class_name,
                'live_class_type' => $live_class_type,
                'curriculum_id' => $curriculum_id,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_zone' => $time_zone_id,
                'preferred_platform' => $preferred_platform,
                'link_to_live_class' => $link_to_live_class,
                'created_by' => $created_by,
                'is_deleted' => $is_deleted,
                'created_at' => $date_created,
                'is_completed' => $is_completed,
            );
    
            $live_class_id = DB::table('live_class')->insertGetId($data);

            $live_class_type_result = DB::select('select name from live_class_type where 
                            live_class_type_id = :live_class_type_id', ['live_class_type_id' => (int)$live_class_type]);

            $curriculum_result = DB::select('select topic, description from curriculum where 
                             curriculum_id = :curriculum_id', ['curriculum_id' => (int)$curriculum_id]);

            $time_zone_result = DB::select('select abbr from timezones where 
                             id = :id', ['id' => (int)$time_zone_id]);

            save_activity_trail($user_id, 'Live class created', 
                                'Live class with id ('.$live_class_id.') created',
                                $date_created);
    
            return response()->json([
                'message' => 'Live class created',
                'live_class_id' => $live_class_id,
                'course_id' => (int)$course_id,
                'live_class_type' => $live_class_type,
                'live_class_type_name' => $live_class_type_result[0]->name,
                'curriculum_id' => $curriculum_id,
                'curriculum_topic' => $curriculum_result[0]->topic,
                'curriculum_description' => $curriculum_result[0]->description,
                'live_class_name' => $live_class_name,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_zone_id' => $time_zone_id,
                'time_zone_name' => $time_zone_result[0]->abbr,
                'preferred_platform' => $preferred_platform,
                'link_to_live_class' => $link_to_live_class,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateLiveClass(Request $request){
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
            $live_class_id = check_if_null_or_empty($request->live_class_id);
            //$live_class_name = check_if_null_or_empty($request->live_class_name);
            $live_class_type = check_if_null_or_empty($request->live_class_type);
            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
            $date = check_if_null_or_empty($request->date);
            $start_time = check_if_null_or_empty($request->start_time);
            $end_time = check_if_null_or_empty($request->end_time);
            $time_zone_id = check_if_null_or_empty($request->time_zone_id);
            $preferred_platform = check_if_null_or_empty($request->preferred_platform);
            $link_to_live_class = check_if_null_or_empty($request->link_to_live_class);
            $date_updated = get_current_date_time();

            self::removeUploadedRecordingIfNewer($live_class_id, $date);
    
            $data = array(
                //'live_class_name' => $live_class_name,
                'live_class_type' => $live_class_type,
                'curriculum_id' => $curriculum_id,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_zone' => $time_zone_id,
                'preferred_platform' => $preferred_platform,
                'link_to_live_class' => $link_to_live_class,
                'updated_at' => $date_updated
            );
    
            DB::table('live_class')->where('id', $live_class_id)->update($data);

            $time_zone_result = DB::select('select abbr from timezones where 
                            id = :id', ['id' => (int)$time_zone_id]);

            $live_class_type_result = DB::select('select name from live_class_type where 
                            live_class_type_id = :live_class_type_id', ['live_class_type_id' => (int)$live_class_type]); 

            $curriculum_result = DB::select('select topic, description from curriculum where 
                            curriculum_id = :curriculum_id', ['curriculum_id' => (int)$curriculum_id]);

            save_activity_trail($user_id, 'Live class updated', 
                                'Live class with id ('.$live_class_id.') updated',
                                $date_updated);
    
            return response()->json([
                'message' => 'Live class updated',
                'live_class_id' => $live_class_id,
                //'live_class_name' => $live_class_name,
                'live_class_type' => $live_class_type,
                'live_class_type_name' => $live_class_type_result ? $live_class_type_result[0]->name : '',
                'curriculum_id' => $curriculum_id,
                'curriculum_topic' => $curriculum_result ? $curriculum_result[0]->topic : '',
                'curriculum_description' => $curriculum_result ? $curriculum_result[0]->description : '',
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'time_zone_id' => $time_zone_id,
                'time_zone_name' => $time_zone_result ? $time_zone_result[0]->abbr : '',
                'preferred_platform' => $preferred_platform,
                'link_to_live_class' => $link_to_live_class,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public static function removeUploadedRecordingIfNewer($live_class_id, $updated_date){
        try {
            //get last date in db
            $date_result = DB::select('select date from live_class where id = :id', ['id' => (int)$live_class_id]);

            // check if greater than updated date
            $date_in_db = strtotime($date_result[0]->date);
            $updated_date = strtotime($updated_date);

            if($updated_date > $date_in_db){
                // delete link_to_recording
                $data = array(
                    'link_to_recording' => null
                );
        
                DB::table('live_class')->where('id', $live_class_id)->update($data);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function deleteLiveClass(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $live_class_id = check_if_null_or_empty($request->live_class_id);
            $is_deleted = Config::get('constants.true');
            $date_updated = get_current_date_time();

            $data = array(
                'is_deleted' => $is_deleted,
                'updated_at' => $date_updated
            );
    
            DB::table('live_class')->where('id', $live_class_id)->update($data);

            save_activity_trail($user_id, 'Live class deleted', 
                                'Live class with id ('.$live_class_id.') deleted',
                                $date_updated);
    
            return response()->json([
                'message' => 'Live class deleted',
                'live_class_id' => $live_class_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function uploadLiveClassRecording(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $live_class_id = check_if_null_or_empty($request->live_class_id);
            $link_to_recording = check_if_null_or_empty($request->link_to_recording);
            $date_updated = get_current_date_time();

            $data = array(
                'link_to_recording' => $link_to_recording,
                'updated_at' => $date_updated
            );
    
            DB::table('live_class')->where('id', $live_class_id)->update($data);

            save_activity_trail($user_id, 'Live class recording uploaded', 
                                'Recording uploaded for live class with id ('.$live_class_id.')',
                                $date_updated);
    
            return response()->json([
                'message' => 'Live class recording updated successfully',
                'live_class_id' => $live_class_id,
                'link_to_recording' => $link_to_recording
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllCourses(Request $request){
        try{           
            $courses_result = DB::select('select c.*, u.name as instructor_name from courses c
                                            inner join users u on c.created_by = u.id
                                            where c.is_deleted = 0 and c.is_published = 1');

            $courses_array = array();

            if(count($courses_result) > 0){
                foreach ($courses_result as $course) {
                    $enrolment_count_result = DB::select('select count(*) as enrolment_count from enrolment_history where course_id = :course_id', 
                                    ['course_id' => (int)$course->id]);

                    $no_of_students_enroled = (int)$enrolment_count_result[0]->enrolment_count;
                    $class_size = (int)$course->class_size;

                    $percentage_booked = ($no_of_students_enroled/$class_size) * 100;

                    $course->percentage_booked = $percentage_booked;
                    $course->no_of_students_enroled = $no_of_students_enroled;

                    array_push($courses_array, $course);
                }

                return response()->json([
                    'message' => 'All courses',
                    'courses' => $courses_array
                ], 200);

            } else{
                return response()->json([
                    'message' => 'All courses',
                    'courses' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllCoursesPaginated(Request $request){
        try{     
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $page_number = check_if_null_or_empty($request->page_number);

            $courses_result =  DB::table("courses")
                                    ->join("users", function($join){
                                        $join->on("users.id", "=", "courses.created_by");
                                    })
                                    ->join("instructors", function($join){
                                        $join->on("instructors.user_id", "=", "users.id");
                                    })
                                    ->join("course_category", function($join){
                                        $join->on("course_category.id", "=", "courses.course_category");
                                    })
                                    ->select("courses.id", "courses.course_name", "courses.price", 
                                    "courses.thumbnail_file_url", "users.name as instructor_name", 
                                    "instructors.professional_title", "instructors.profile_picture_url", 
                                    "instructors.profile_url", "course_category.name as course_category_name")
                                    ->where("courses.is_deleted", "=", Config::get('constants.false'))
                                    ->where("courses.is_published", "=", Config::get('constants.true'))
                                    ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);

            if(count($courses_result) > 0){
                return response()->json([
                    'message' => 'All courses',
                    'courses' => $courses_result
                ], 200);

            } else{
                return response()->json([
                    'message' => 'All courses',
                    'courses' => []
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllFeaturedCourses(Request $request){
        try{           
            $featured_courses_result = DB::select('select * from courses where is_deleted = 0 and is_published = 1 
                                                and is_featured_course = 1'); 
    
            return response()->json([
                'message' => 'All featured courses',
                'featured_courses' => $featured_courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllCoursesByCourseCategoryId(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_category_id = check_if_null_or_empty($request->course_category_id);
            
            $courses_result = DB::select('select * from courses where is_deleted = 0  
                                    and is_published = 1 and course_category = :course_category', 
                                ['course_category' => $course_category_id]); 
    
            return response()->json([
                'message' => 'Course gotten',
                'courses' => $courses_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }



    //Curriculum
    public function createCurriculum(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            $topic = check_if_null_or_empty($request->topic);
            $description = check_if_null_or_empty($request->description);
            $week_number = check_if_null_or_empty($request->week_number);
            $created_by = check_if_null_or_empty($user_id);
            $is_deleted = Config::get('constants.false');
            $date_created = get_current_date_time();
    

            //check if week number exist

            $data = array(
                'course_id' => $course_id,
                'topic' => $topic,
                'description' => $description,
                'week_number' => $week_number,
                'created_by' => $created_by,
                'is_deleted' => $is_deleted,
                'created_at' => $date_created
            );
    
            $curriculum_id = DB::table('curriculum')->insertGetId($data);

            save_activity_trail($user_id, 'Curriculum created', 
                                'Curriculum with id ('.$curriculum_id.') created',
                                $date_created);
    
            return response()->json([
                'message' => 'Curriculum created',
                'curriculum_id' => $curriculum_id,
                'topic' => $topic,
                'description' => $description,
                'week_number' => $week_number,
                'course_id' => $course_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateCurriculum(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
            $topic = check_if_null_or_empty($request->topic);
            $description = check_if_null_or_empty($request->description);
            $week_number = check_if_null_or_empty($request->week_number);
            $date_updated = get_current_date_time();
    
            $data = array(
                'topic' => $topic,
                'description' => $description,
                'week_number' => $week_number,
                'updated_at' => $date_updated
            );
    
            DB::table('curriculum')->where('curriculum_id', $curriculum_id)->update($data);   

            save_activity_trail($user_id, 'Curriculum updated', 
                                'Curriculum with id ('.$curriculum_id.') updated',
                                $date_updated);
    
            return response()->json([
                'message' => 'Curriculum updated',
                'curriculum_id' => $curriculum_id,
                'topic' => $topic,
                'description' => $description,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function deleteCurriculum(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
            $is_deleted = Config::get('constants.true');
            $date_updated = get_current_date_time();

            $data = array(
                'is_deleted' => $is_deleted,
                'updated_at' => $date_updated
            );
    
            DB::table('curriculum')->where('curriculum_id', $curriculum_id)->update($data); 
            
            save_activity_trail($user_id, 'Curriculum deleted', 
                                'Curriculum with id ('.$curriculum_id.') deleted',
                                $date_updated);
    
            return response()->json([
                'message' => 'Curriculum deleted',
                'curriculum_id' => $curriculum_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllCurriculumForCourse(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $curriculum_result = DB::select('select * from curriculum where is_deleted = 0 
                                        and course_id = :course_id', ['course_id' => (int)$course_id]); 
    
            return response()->json([
                'message' => 'All course curriculum',
                'all_course_curriculum' => $curriculum_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getCurriculumByCurriculumId(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'];
            }
                  
            $user_id = $user->id;

            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
            
            $curriculum_result = DB::select('select * from curriculum where is_deleted = 0 
                                        and curriculum_id = :curriculum_id', ['curriculum_id' => (int)$curriculum_id]); 
    
            return response()->json([
                'message' => 'Curriculum',
                'curriculum' => $curriculum_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getAllCourseContentsForCurriculum(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $curriculum_id = check_if_null_or_empty($request->curriculum_id);
            
            $course_contents_result = DB::select('select * from course_content where course_id = 
                                            (select course_id from curriculum where curriculum_id = :curriculum_id)', 
                                            ['curriculum_id' => (int)$curriculum_id]); 
    
            return response()->json([
                'message' => 'Course contents for curriculum',
                'course_contents' => $course_contents_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }


    public function changePublishStatus(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            $is_published = check_if_null_or_empty($request->is_published);
            $date_updated = get_current_date_time();

            if($is_published != 0 && $is_published != 1){
                return response()->json([
                    'message' => 'is_published parameter must either be 0 for false or 1 for true',
                ], 200);
            } else{
                $data = array(
                    'is_published' => $is_published,
                    'updated_at' => $date_updated
                );
        
                DB::table('courses')->where('id', $course_id)->update($data);   

                save_activity_trail($user_id, 'Course published status changed', 
                                    'Course with id ('.$course_id.') published status changed to '.$is_published,
                                    $date_updated);
        
                return response()->json([
                    'message' => 'Course publish status changed',
                    'course_id' => $course_id
                ], 200);
            }
           
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function searchForCourse(Request $request){
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

            // $course_search_result = DB::table("courses")
            //                             ->join("course_category", function($join){
            //                                 $join->on("courses.id", "=", "course_category.id");
            //                             })
            //                             ->join("users", function($join){
            //                                 $join->on("courses.created_by", "=", "users.id");
            //                             })
            //                             ->join("instructors", function($join){
            //                                 $join->on("courses.created_by", "=", "instructors.user_id");
            //                             })
            //                             ->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", 
            //                                     "course_category.name", "users.name", "instructors.professional_title", 
            //                                     "instructors.profile_picture_url")
            //                             ->where("courses.course_name", "like", "$search_keyword")
            //                             ->where("courses.is_deleted", "=", Config::get('constants.false'))
            //                             ->where("courses.is_published", "=", Config::get('constants.true'))
            //                             ->paginate(Config::get('constants.no_of_items_for_pagination'));
            


            $course_search_result = DB::table("courses")
                                        ->join("course_category", function($join){
                                            $join->on("courses.course_category", "=", "course_category.id");
                                        })
                                        ->join("users", function($join){
                                            $join->on("courses.created_by", "=", "users.id");
                                        })
                                        ->join("instructors", function($join){
                                            $join->on("courses.created_by", "=", "instructors.user_id");
                                        })
                                        ->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", "courses.price", 
                                                "course_category.name as course_category_name", "users.name as instructor_name", 
                                                "instructors.professional_title", "instructors.profile_picture_url")
                                        ->where("courses.course_name", "like", "$search_keyword")
                                        ->where("courses.is_deleted", "=", Config::get('constants.false'))
                                        ->where("courses.is_published", "=", Config::get('constants.true'))
                                        ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);
            /*
            $course_search_result = DB::select('SELECT c.id, c.course_name, c.thumbnail_file_url, cc.name, u.name, 
                                                i.professional_title, i.profile_picture_url 
                                                FROM courses c 
                                                INNER JOIN course_category cc ON c.course_category = cc.id 
                                                INNER JOIN users u ON c.created_by = u.id 
                                                INNER JOIN instructors i ON c.created_by = i.user_id 
                                                WHERE c.course_name LIKE "%' . $search_keyword . '%" 
                                                AND c.is_deleted = 0 AND c.is_published = 1')->paginate(1);
            */

            if(count($course_search_result) > 0){
                return response()->json([
                    'message' => 'Courses found',
                    'courses' => $course_search_result
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function filterCourse(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $course_category = check_if_null_or_empty($request->course_category);
            $level_of_competence = check_if_null_or_empty($request->level_of_competence);
            $price = check_if_null_or_empty($request->price);
            $duration = check_if_null_or_empty($request->duration);
            $page_number = check_if_null_or_empty($request->page_number);

            $search_keyword = check_if_null_or_empty($request->search_keyword);
            $search_keyword = '%' . $search_keyword . '%';

            $duration_start_value = 0;
            $duration_end_value = 99999999;

            //get duration values by id from db
            $duration_result = DB::select('select * from duration where duration_id = :duration_id', 
                                    ['duration_id' => (int)$duration]);

            if(count($duration_result) > 0){
                $duration_start_value = $duration_result[0]->start_value;
                $duration_end_value = $duration_result[0]->end_value;
            }

            $course_search_result = DB::table("courses")
                                        ->join("course_category", function($join){
                                            $join->on("courses.course_category", "=", "course_category.id");
                                        })
                                        ->join("users", function($join){
                                            $join->on("courses.created_by", "=", "users.id");
                                        })
                                        ->join("instructors", function($join){
                                            $join->on("courses.created_by", "=", "instructors.user_id");
                                        })
                                        ->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", 
                                                "courses.price", "course_category.name as course_category_name", 
                                                "users.name as instructor_name", "instructors.professional_title", 
                                                "instructors.profile_picture_url")
                                        ->where("courses.course_name", "like", "$search_keyword")
                                        ->where("courses.course_category", "=", $course_category)
                                        ->where("courses.level_of_competence", "=", $level_of_competence)
                                        ->whereRaw("DATEDIFF(courses.end_date, courses.start_date) BETWEEN $duration_start_value AND $duration_end_value")
                                        ->where("courses.is_deleted", "=", Config::get('constants.false'))
                                        ->where("courses.is_published", "=", Config::get('constants.true'))
                                        ->orderBy("courses.price", $price)
                                        ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);



            /*
            $course_search_result = DB::select('SELECT c.id, c.course_name, c.thumbnail_file_url, c.price, cc.name as course_category_name, u.name as instructor_name, 
                                                i.professional_title, i.profile_picture_url
                                                FROM courses c 
                                                INNER JOIN course_category cc ON c.course_category = cc.id 
                                                INNER JOIN users u ON c.created_by = u.id 
                                                INNER JOIN instructors i ON c.created_by = i.user_id 
                                                WHERE c.course_category = 1 AND c.level_of_competence = 1
                                                AND DATEDIFF (c.end_date, c.start_date) BETWEEN 1 AND 100
                                                AND c.is_deleted = 0 AND c.is_published = 1
                                                ORDER BY c.price DESC')->paginate(1); */
            
            if(count($course_search_result) > 0){
                return response()->json([
                    'message' => 'Courses found',
                    'courses' => $course_search_result
                ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function courseSearchOld(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $course_category = check_if_null_or_empty($request->course_category);
            $level_of_competence = check_if_null_or_empty($request->level_of_competence);
            $price = check_if_null_or_empty($request->price);
            $duration = check_if_null_or_empty($request->duration);
            $page_number = check_if_null_or_empty($request->page_number);

            $search_keyword = check_if_null_or_empty($request->search_keyword);

            if(is_null($search_keyword) && is_null($course_category) && is_null($level_of_competence) && is_null($price) && is_null($duration)){
                //No filters passed. Return all courses
                $course_search_result = DB::table("courses")
                                ->join("course_category", function($join){
                                    $join->on("courses.course_category", "=", "course_category.id");
                                })
                                ->join("users", function($join){
                                    $join->on("courses.created_by", "=", "users.id");
                                })
                                ->join("instructors", function($join){
                                    $join->on("courses.created_by", "=", "instructors.user_id");
                                })
                                ->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", 
                                        "courses.price", "course_category.name as course_category_name", 
                                        "users.name as instructor_name", "instructors.professional_title", 
                                        "instructors.profile_picture_url")
                                ->where("courses.is_deleted", "=", Config::get('constants.false'))
                                ->where("courses.is_published", "=", Config::get('constants.true'))
                                ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);

            } else{
                $search_keyword = '%' . $search_keyword . '%';

                $duration_start_value = 0;
                $duration_end_value = 99999999;

                //get duration values by id from db
                $duration_result = DB::select('select * from duration where duration_id = :duration_id', 
                                    ['duration_id' => (int)$duration]);

                if(count($duration_result) > 0){
                    $duration_start_value = $duration_result[0]->start_value;
                    $duration_end_value = $duration_result[0]->end_value;
                }

                echo 'price' . $price;

                if(strtoupper($price) !== 'ASC' && strtoupper($price !== 'DESC')){
                    $price = 'ASC';
                }


                echo 'price' . $price;

                $course_search_result = DB::table("courses")
                                ->join("course_category", function($join){
                                    $join->on("courses.course_category", "=", "course_category.id");
                                })
                                ->join("users", function($join){
                                    $join->on("courses.created_by", "=", "users.id");
                                })
                                ->join("instructors", function($join){
                                    $join->on("courses.created_by", "=", "instructors.user_id");
                                })
                                ->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", 
                                        "courses.price", "course_category.name as course_category_name", 
                                        "users.name as instructor_name", "instructors.professional_title", 
                                        "instructors.profile_picture_url")
                                ->where("courses.course_name", "like", "$search_keyword")
                                ->where("courses.course_category", "=", $course_category)
                                ->where("courses.level_of_competence", "=", $level_of_competence)
                                ->whereRaw("DATEDIFF(courses.end_date, courses.start_date) BETWEEN $duration_start_value AND $duration_end_value")
                                ->where("courses.is_deleted", "=", Config::get('constants.false'))
                                ->where("courses.is_published", "=", Config::get('constants.true'))
                                ->orderBy("courses.price", $price)
                                ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);
            }


            if(count($course_search_result) > 0){
                return response()->json([
                    'message' => 'Courses found',
                    'courses' => $course_search_result
                    ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function courseSearchNew(Request $request){
        try{
            $validator = Validator::make($request->all(),[ 
                'page_number' => 'required|numeric'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }  

            $course_category = check_if_null_or_empty($request->course_category);
            $level_of_competence = check_if_null_or_empty($request->level_of_competence);
            $price = check_if_null_or_empty($request->price);
            $duration = check_if_null_or_empty($request->duration);
            $page_number = check_if_null_or_empty($request->page_number);

            $search_keyword = check_if_null_or_empty($request->search_keyword);

            if(is_null($search_keyword) && is_null($course_category) && is_null($level_of_competence) && is_null($price) && is_null($duration)){
                //No filters passed. Return all courses
                $course_search_result = DB::table("courses")
                                ->join("course_category", function($join){
                                    $join->on("courses.course_category", "=", "course_category.id");
                                })
                                ->join("users", function($join){
                                    $join->on("courses.created_by", "=", "users.id");
                                })
                                ->join("instructors", function($join){
                                    $join->on("courses.created_by", "=", "instructors.user_id");
                                })
                                ->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", 
                                        "courses.price", "course_category.name as course_category_name", 
                                        "users.name as instructor_name", "instructors.professional_title", 
                                        "instructors.profile_picture_url")
                                ->where("courses.is_deleted", "=", Config::get('constants.false'))
                                ->where("courses.is_published", "=", Config::get('constants.true'))
                                ->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);

            } else{
               
                $course_search = DB::table("courses")
                                    ->join("course_category", function($join){
                                        $join->on("courses.course_category", "=", "course_category.id");
                                    })
                                    ->join("users", function($join){
                                        $join->on("courses.created_by", "=", "users.id");
                                    })
                                    ->join("instructors", function($join){
                                        $join->on("courses.created_by", "=", "instructors.user_id");
                                    });


                $course_search->select("courses.id", "courses.course_name", "courses.thumbnail_file_url", 
                        "courses.price", "course_category.name as course_category_name", 
                        "users.name as instructor_name", "instructors.professional_title", 
                        "instructors.profile_picture_url");

                
                if(!is_null($search_keyword) ){
                    //echo 'in search_keyword - ' . $search_keyword;
                    $search_keyword = '%' . $search_keyword . '%';

                    $course_search->where("courses.course_name", "like", "$search_keyword");
                }

                if(!is_null($course_category) ){
                    //echo 'in course_category - ' . $course_category;
                    $course_search->where("courses.course_category", "=", $course_category);
                }

                if(!is_null($level_of_competence) ){
                    //echo 'in level_of_competence - ' . $level_of_competence;
                    $course_search->where("courses.level_of_competence", "=", $level_of_competence);
                }

                if(!is_null($duration) ){
                    $duration_start_value = 0;
                    $duration_end_value = 99999999;
    
                    //get duration values by id from db
                    $duration_result = DB::select('select * from duration where duration_id = :duration_id', 
                                        ['duration_id' => (int)$duration]);
    
                    if(count($duration_result) > 0){
                        $duration_start_value = $duration_result[0]->start_value;
                        $duration_end_value = $duration_result[0]->end_value;
                    }

                    //echo 'in duration - ' . $duration_start_value . ' ---- ' . $duration_end_value;

                    $course_search->whereRaw("DATEDIFF(courses.end_date, courses.start_date) BETWEEN $duration_start_value AND $duration_end_value");
                }
                
                $course_search->where("courses.is_deleted", "=", Config::get('constants.false'));
                $course_search->where("courses.is_published", "=", Config::get('constants.true'));

                if(!is_null($price)){
                    //echo 'in price - ' . $price;

                    if(strtoupper($price) !== 'ASC' && strtoupper($price) !== 'DESC'){
                        $price = 'ASC';
                    }

                    //echo 'in price - ' . $price;

                    $course_search->orderBy("courses.price", $price);
                }
                
                $course_search_result = $course_search->paginate(Config::get('constants.no_of_items_for_pagination') * $page_number);
            
            }

            if(count($course_search_result) > 0){
                return response()->json([
                    'message' => 'Courses found',
                    'courses' => $course_search_result
                    ], 200);
            } else{
                return response()->json([
                    'message' => 'No course found',
                ], 200);
            }

        } catch(Exception $e){
            return $e->getMessage();
        }
    }


    //who is this course for
    public function createWhoIsThisCourseFor(Request $request){
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
            $course_id = check_if_null_or_empty($request->course_id);
            $category = check_if_null_or_empty($request->category);
            $description = check_if_null_or_empty($request->description);
            $is_deleted = Config::get('constants.false');
            $date_created = get_current_date_time();
    
            $data = array(
                'course_id' => $course_id,
                'category' => $category,
                'description' => $description,
                'is_deleted' => $is_deleted
            );
    
            $who_is_this_course_for_id = DB::table('who_is_this_course_for')->insertGetId($data);

            $who_is_this_course_for_category_result = DB::select('select name from who_is_this_course_for_category where 
                                                category_id = :category_id', ['category_id' => (int)$category]); 
    
            save_activity_trail($user_id, 'Who is this course for created', 'Who is this course for with id ('.$who_is_this_course_for_id.') created',
                                                $date_created);

            return response()->json([
                'message' => 'Who is this course for created',
                'who_is_this_course_for_id' => $who_is_this_course_for_id,
                'category_id' => $category,
                'category_name' => $who_is_this_course_for_category_result[0]->name,
                'description' => $description,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function updateWhoIsThisCourseFor(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $who_is_this_course_for_id = check_if_null_or_empty($request->who_is_this_course_for_id);
            $course_id = check_if_null_or_empty($request->course_id);
            $category = check_if_null_or_empty($request->category);
            $description = check_if_null_or_empty($request->description);
            $date_updated = get_current_date_time();

            $data = array(
                'course_id' => $course_id,
                'category' => $category,
                'description' => $description
            );
    
            DB::table('who_is_this_course_for')->where('id', $who_is_this_course_for_id)->update($data);

            $who_is_this_course_for_category_result = DB::select('select name from who_is_this_course_for_category where 
                                                category_id = :category_id', ['category_id' => (int)$category]);
    
            save_activity_trail($user_id, 'Who is this course for updated', 'Who is this course for with id ('.$who_is_this_course_for_id.') updated',
                                                $date_updated);

            return response()->json([
                'message' => 'Who is this course for updated',
                'category_id' => $category,
                'category_name' => $who_is_this_course_for_category_result[0]->name,
                'description' => $description,
                'who_is_this_course_for_id' => $who_is_this_course_for_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function deleteWhoIsThisCourseFor(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $who_is_this_course_for_id = check_if_null_or_empty($request->who_is_this_course_for_id);
            $is_deleted = Config::get('constants.true');
            $date_updated = get_current_date_time();

            $data = array(
                'is_deleted' => $is_deleted,
            );
    
            DB::table('who_is_this_course_for')->where('id', $who_is_this_course_for_id)->update($data);   
    
            save_activity_trail($user_id, 'Who is this course for deleted', 'Who is this course for with id ('.$who_is_this_course_for_id.') deleted',
                                                $date_updated);

            return response()->json([
                'message' => 'Who is this course for deleted',
                'who_is_this_course_for_id' => $who_is_this_course_for_id,
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllWhoIsThisCourseForDataForCourse(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            
            $who_is_this_course_for_result = DB::select('select * from who_is_this_course_for where is_deleted = 0 
                                        and course_id = :course_id', ['course_id' => (int)$course_id]); 
    
            return response()->json([
                'message' => 'All who is this course for',
                'all_who_is_this_course_for' => $who_is_this_course_for_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getWhoIsThisCourseForById(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'];
            }
                  
            $user_id = $user->id;

            $who_is_this_course_for_id = check_if_null_or_empty($request->who_is_this_course_for_id);
            
            $who_is_this_course_for_result = DB::select('select * from who_is_this_course_for where is_deleted = 0 
                                        and id = :id', ['id' => (int)$who_is_this_course_for_id]); 
    
            return response()->json([
                'message' => 'Record gotten',
                'who_is_this_course_for_result' => $who_is_this_course_for_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function getAllCurriculumsAndCourseContentsForCourse(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);

            $all_curriculums_with_course_content = array();
            
            $curriculum_result = DB::select('select * from curriculum where is_deleted = 0 
                                        and course_id = :course_id order by created_at asc', 
                                        ['course_id' => (int)$course_id]);

            if(count($curriculum_result) > 0){
                $instructor_details = DB::select('select about_you from instructors 
                                            where user_id = (select created_by from courses where is_deleted = 0 and id = :id)', 
                                            ['id' => (int)$course_id]); 

                foreach ($curriculum_result as $curriculum) {
                    $course_contents_result = DB::select('select c.*, r.name as resource_type_name, 
                                    (select is_completed from course_content_tracker ct 
                                    where ct.course_content_id = c.id and ct.user_id = :user_id) as is_completed 
                                    from course_content c 
                                    inner join resource_type r on r.resource_type_id = c.resource_type 
                                    where c.is_deleted = 0 and c.curriculum_id = :curriculum_id', 
                                        ['curriculum_id' => (int)$curriculum->curriculum_id, 
                                        'user_id' => (int)$user_id]);


                    $total_estimated_time = 0;
                    
                    foreach ($course_contents_result as $course_content) {
                        $total_estimated_time += (int) $course_content->estimated_time;
                    }

                    $curriculum->course_contents = $course_contents_result;
                    $curriculum->total_estimated_time = (int) $total_estimated_time;

                    array_push($all_curriculums_with_course_content, $curriculum);
                }

                return response()->json([
                    'message' => 'All course curriculum and course contents',
                    'all_course_curriculums_and_course_contents' => $all_curriculums_with_course_content,
                    'about_instructor' => $instructor_details[0]->about_you
                ], 200);

            } else{
                return response()->json([
                    'message' => 'All course curriculum and course contents',
                    'all_course_curriculums_and_course_contents' => $curriculum_result
                ], 200);
            }
        } catch(Exception $e){
            return $e->getMessage();
        }
        
    }

    public function markCourseAsComplete(Request $request){
        try{
            $header_auth_token = $request->header('AuthToken');

            $auth_check_result = check_authentication($header_auth_token);
            
            if($auth_check_result['status'] == false){
                return $auth_check_result;
            } else{
                $user = $auth_check_result['data'] ;
            }
                  
            $user_id = $user->id;

            $course_id = check_if_null_or_empty($request->course_id);
            $date_completed = get_current_date_time();
            $is_completed = Config::get('constants.true');

            $data = array(
                'is_completed' => $is_completed,
                'date_completed' => $date_completed
            );
    
            DB::table('enrolment_history')
                ->where('course_id', $course_id)
                ->where('user_id', $user_id)
                ->update($data);

            $course_name_result = DB::select('select course_name from courses where is_deleted = 0 
                and id = :id', ['id' => (int)$course_id]); 

            $student_name = $user->name;
            $student_email = $user->email;

            $course_name = $course_name_result[0]->course_name;

            MailController::send_course_completion_mail($student_name, $student_email, $course_id, $course_name);

            save_activity_trail($user_id, 'Course Completed', 'Course with id ('.$course_id.') completed',
                                $date_completed);
    
            return response()->json([
                'message' => 'Course marked as completed',
                'course_id' => $course_id
            ], 200);
           
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function markCourseContentAsComplete(Request $request){
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
                'course_content_id' => 'required'
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }

            $course_content_id = check_if_null_or_empty($request->course_content_id);
            $date_completed = get_current_date_time();
            $is_completed = Config::get('constants.true');

            $data = array(
                'is_completed' => $is_completed,
                'date_completed' => $date_completed
            );
    
            DB::table('course_content_tracker')
                ->where('course_content_id', $course_content_id)
                ->where('user_id', $user_id)
                ->update($data);


            save_activity_trail($user_id, 'Course content completed', 'User ('.$user_id.') completed the course content with id ('.$course_content_id.')',
                                $date_completed);

            $course_content_result = DB::select('select * from course_content where is_deleted = 0 
                                and id = :id', ['id' => (int)$course_content_id]); 
    
            return response()->json([
                'message' => 'Course content marked as completed',
                'course_content_id' => $course_content_id,
                'resource_name' => $course_content_result[0]->resource_name,
                'estimated_time' => $course_content_result[0]->estimated_time,        
                'attachment_link' => $course_content_result[0]->attachment_link,
                'resource_type' => $course_content_result[0]->resource_type,
                'description' => $course_content_result[0]->description
            ], 200);
           
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getCourseContentTrackingInfo(Request $request){
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
                'course_content_id' => 'required',
                'course_id' => 'required',
            ]);

            if($validator->fails()) {          
                return response()->json(['error' => $validator->errors()], 401);                        
            }

            $course_content_id = check_if_null_or_empty($request->course_content_id);
            $course_id = check_if_null_or_empty($request->course_id);

            $tracker_result = DB::select('select * from course_content_tracker where course_content_id = :course_content_id and user_id = :user_id', 
                                        ['course_content_id' => $course_content_id, 'user_id' => $user_id]);

            if(!empty($tracker_result)){
                return response()->json([
                    'message' => 'Course content tracking data gotten',
                    'course_content_id' => $course_content_id,
                    'tracking_data' => $tracker_result
                ], 200);
            } else {

                $data = array(
                    'course_id' => $course_id,
                    'user_id' => $user_id,
                    'course_content_id' => $course_content_id,
                    'is_completed' => Config::get('constants.false'),
                    'created_at' => get_current_date_time()
                );
        
                $tracking_id = DB::table('course_content_tracker')->insertGetId($data);

                $tracker_result = DB::select('select * from course_content_tracker where id = :id', ['id' => $tracking_id]);

                return response()->json([
                    'message' => 'Course content tracking initialized',
                    'course_content_id' => $course_content_id,
                    'tracking_data' => $tracker_result
                ], 200);
            }

            $course_content_id = check_if_null_or_empty($request->course_content_id);
            $date_completed = get_current_date_time();
            $is_completed = Config::get('constants.true');

            $data = array(
                'is_completed' => $is_completed,
                'date_completed' => $date_completed
            );
    
            DB::table('course_content_tracker')
                ->where('course_content_id', $course_content_id)
                ->where('user_id', $user_id)
                ->update($data);


            save_activity_trail($user_id, 'Course content completed', 'Course content with id ('.$course_content_id.') completed',
                                $date_completed);

            $course_content_result = DB::select('select * from course_content where is_deleted = 0 
                                and id = :id', ['id' => (int)$course_content_id]); 
    
            return response()->json([
                'message' => 'Course content marked as completed',
                'course_content_id' => $course_content_id,
                'resource_name' => $course_content_result[0]->resource_name,
                'estimated_time' => $course_content_result[0]->estimated_time,        
                'attachment_link' => $course_content_result[0]->attachment_link,
                'resource_type' => $course_content_result[0]->resource_type,
                'description' => $course_content_result[0]->description
            ], 200);
           
        } catch(Exception $e){
            return $e->getMessage();
        }
    }
}
