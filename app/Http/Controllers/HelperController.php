<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HelperController extends Controller
{
    public function getAllCountries(Request $request){
        try{           
            $countries_result = DB::select('select * from country order by country_name'); 
    
            return response()->json([
                'message' => 'All countries',
                'countries' => $countries_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStates(Request $request){
        try{           
            $states_result = DB::select('select * from state order by state_name'); 
    
            return response()->json([
                'message' => 'All states',
                'states' => $states_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllStatesByCountry(Request $request, $country_id) {
        try{                       
            $states_result = DB::select('select * from state where country_id = :country_id order by state_name', ['country_id' => $country_id]);
    
            return response()->json([
                'message' => 'All states by country',
                'states' => $states_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllCourseCategories(Request $request){
        try{           
            $course_categories_result = DB::select('select * from course_category order by name'); 
    
            return response()->json([
                'message' => 'All course categories',
                'course_categories' => $course_categories_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllLiveClassPlatforms(Request $request){
        try{           
            $live_class_platforms_result = DB::select('select * from live_class_platform order by name'); 
    
            return response()->json([
                'message' => 'All live class platforms',
                'live_class_platforms' => $live_class_platforms_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllResourceTypes(Request $request){
        try{           
            $resource_types_result = DB::select('select * from resource_type order by name'); 
    
            return response()->json([
                'message' => 'All resource types',
                'resource_types' => $resource_types_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllLevelOfCompetences(Request $request){
        try{           
            $level_of_competences_result = DB::select('select level_of_competence_id, name, description from level_of_competence order by level_of_competence_id'); 
    
            return response()->json([
                'message' => 'All level of competences',
                'level_of_competences' => $level_of_competences_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllWhoIsThisCourseForCategories(Request $request){
        try{           
            $who_is_this_course_for_categories_result = DB::select('select category_id, name, description from 
                                                    who_is_this_course_for_category order by name'); 
    
            return response()->json([
                'message' => 'All who is this course for categories',
                'who_is_this_course_for_categories_result' => $who_is_this_course_for_categories_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }
    
    public function getAllTimezones(Request $request){
        try{           
            $timezones_result = DB::select('select id, value, abbr, offset, text from 
                                                    timezones order by id'); 
    
            return response()->json([
                'message' => 'All Timezones',
                'timezones_result' => $timezones_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllLiveClassTypes(Request $request){
        try{           
            $live_class_types_result = DB::select('select live_class_type_id, name from 
                                    live_class_type order by name'); 
    
            return response()->json([
                'message' => 'All live class types',
                'live_class_types_result' => $live_class_types_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function getAllDuration(Request $request){
        try{           
            $duration_result = DB::select('select * from duration order by duration_id'); 
    
            return response()->json([
                'message' => 'All durations',
                'duration_result' => $duration_result
            ], 200);
        } catch(Exception $e){
            return $e->getMessage();
        }
    }
}
