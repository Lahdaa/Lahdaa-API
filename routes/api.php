<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PassportAuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Student Routes
Route::get('student/get-student-dashboard-stats', [StudentController::class, 'dashboardStats']);
Route::post('student/get-student-upcoming-classes', [StudentController::class, 'getAllStudentUpcomingClasses']);

Route::post('student/get-student-enrolled-courses', [StudentController::class, 'getAllStudentEnrolledCourses']);
Route::post('student/get-student-ongoing-courses', [StudentController::class, 'getAllStudentOngoingCourses']);
Route::post('student/get-student-completed-courses', [StudentController::class, 'getAllStudentCompletedCourses']);
Route::post('student/get-student-course-wishlist', [StudentController::class, 'getAllStudentCourseWishlist']);

Route::post('student/update-student-personal-profile', [StudentController::class, 'updateStudentPersonalProfile']);
Route::post('student/update-student-password', [StudentController::class, 'updateStudentPassword']);
Route::post('student/update-student-notification-setting', [StudentController::class, 'updateStudentNotificationSetting']);
Route::post('student/update-student-billing-info', [StudentController::class, 'updateStudentBillingAddress']);

Route::post('student/get-student-personal-profile', [StudentController::class, 'getStudentPersonalProfile']);
Route::post('student/get-student-notification-setting', [StudentController::class, 'getStudentNotificationSetting']);
Route::post('student/get-student-billing-info', [StudentController::class, 'getStudentBillingAddress']);
Route::post('student/save-course-to-wishlist', [StudentController::class, 'saveCourseToWishlist']);
Route::post('student/upload-profile-picture', [StudentController::class, 'uploadProfilePicture']);
Route::post('student/save-course-review', [StudentController::class, 'saveCourseReview']);

//Course Routes
Route::post('create-course', [CourseController::class, 'createCourse']);
Route::post('update-course', [CourseController::class, 'updateCourse']);
Route::post('delete-course', [CourseController::class, 'deleteCourse']);
Route::post('get-course-by-id', [CourseController::class, 'getCourseById']);
Route::post('get-course-by-id-for-edit', [CourseController::class, 'getCourseByIdForEdit']);
Route::get('get-instructor-courses', [CourseController::class, 'getInstructorCourses']);
Route::post('get-course-profile', [CourseController::class, 'getCourseProfile']);
Route::post('update-course-duration', [CourseController::class, 'updateCourseDuration']);
Route::post('upload-promo-video', [CourseController::class, 'uploadPromoVideo']);


Route::post('create-live-class', [CourseController::class, 'createLiveClass']);
Route::post('update-live-class', [CourseController::class, 'updateLiveClass']);
Route::post('delete-live-class', [CourseController::class, 'deleteLiveClass']);
Route::get('get-next-live-class', [CourseController::class, 'getNextLiveClass']);
Route::post('get-live-class-by-id', [CourseController::class, 'getLiveClassByLiveClassId']);
Route::post('get-live-classes-by-course-id', [CourseController::class, 'getLiveClassesByCourseId']);
Route::get('get-instructor-past-live-classes', [CourseController::class, 'getInstructorPastLiveClasses']);
Route::get('get-instructor-future-live-classes', [CourseController::class, 'getInstructorFutureLiveClasses']);
Route::post('update-course-price', [CourseController::class, 'updatePriceForCourse']);
Route::post('change-publish-status', [CourseController::class, 'changePublishStatus']);

Route::get('get-course-by-id/{course_id}', [CourseController::class, 'getCourseByIdFromURL']);

Route::post('create-course-content', [CourseController::class, 'createCourseContent']);
Route::post('update-course-content', [CourseController::class, 'updateCourseContent']);
Route::post('delete-course-content', [CourseController::class, 'deleteCourseContent']);
Route::post('get-all-course-contents-for-course', [CourseController::class, 'getAllCourseContentsForCourse']);
Route::post('get-all-course-contents-for-curriculum', [CourseController::class, 'getAllCourseContentsForCurriculum']);
Route::post('upload-file-attachment', [CourseController::class, 'uploadCourseContentAttachment']);
Route::post('get-course-content-by-course-content-id', [CourseController::class, 'getCourseContentByCourseContentId']);

Route::post('create-curriculum', [CourseController::class, 'createCurriculum']);
Route::post('update-curriculum', [CourseController::class, 'updateCurriculum']);
Route::post('delete-curriculum', [CourseController::class, 'deleteCurriculum']);
Route::post('get-all-curriculum-for-course', [CourseController::class, 'getAllCurriculumForCourse']);
Route::post('get-curriculum-by-curriculum-id', [CourseController::class, 'getCurriculumByCurriculumId']);

Route::post('create-who-is-this-course-for', [CourseController::class, 'createWhoIsThisCourseFor']);
Route::post('update-who-is-this-course-for', [CourseController::class, 'updateWhoIsThisCourseFor']);
Route::post('delete-who-is-this-course-for', [CourseController::class, 'deleteWhoIsThisCourseFor']);
Route::post('get-all-who-is-this-course-for-data-for-course', [CourseController::class, 'getAllWhoIsThisCourseForDataForCourse']);
Route::post('get-who-is-this-course-for-by-id', [CourseController::class, 'getWhoIsThisCourseForById']);

Route::post('get-all-courses', [CourseController::class, 'getAllCourses']);
Route::get('get-all-courses-paginated', [CourseController::class, 'getAllCoursesPaginated']);
Route::post('get-all-featured-courses', [CourseController::class, 'getAllFeaturedCourses']);
Route::post('get-all-courses-by-course-category', [CourseController::class, 'getAllCoursesByCourseCategoryId']);
Route::post('get-all-course-curriculums-and-course-contents', [CourseController::class, 'getAllCurriculumsAndCourseContentsForCourse']);

Route::post('/course/course-search', [CourseController::class, 'searchForCourse']);
Route::post('/course/filter-course-search', [CourseController::class, 'filterCourse']);
Route::get('/course/course-search-new', [CourseController::class, 'courseSearchNew']);

Route::post('mark-course-as-complete', [CourseController::class, 'markCourseAsComplete']);
Route::post('mark-course-content-as-complete', [CourseController::class, 'markCourseContentAsComplete']);

//Instructor Routes
Route::get('instructor/get-instructor-dashboard-stats', [InstructorController::class, 'getInstructorDashboardStats']);
Route::post('instructor/update-instructor-personal-profile', [InstructorController::class, 'updateInstructorPersonalProfile']);
Route::post('instructor/update-instructor-bank-information', [InstructorController::class, 'updateInstructorBankInformation']);
Route::post('instructor/update-instructor-password', [InstructorController::class, 'updateInstructorPassword']);
Route::post('instructor/update-instructor-notification-setting', [InstructorController::class, 'updateInstructorNotificationSetting']);

Route::post('instructor/get-instructor-personal-profile', [InstructorController::class, 'getInstructorPersonalProfile']);
Route::post('instructor/get-instructor-bank-information', [InstructorController::class, 'getInstructorBankInformation']);
Route::post('instructor/get-instructor-notification-setting', [InstructorController::class, 'getInstructorNotificationSetting']);
Route::post('instructor/upload-profile-picture', [InstructorController::class, 'uploadProfilePicture']);
Route::post('instructor/get-all-enrolled-students', [InstructorController::class, 'getAllEnrolledStudentsForCourse']);
Route::post('instructor/check-instructor-bank-information', [InstructorController::class, 'checkIfInstructorBankInformationIsFilled']);
Route::post('instructor/get-all-instructor-upcoming-classes', [InstructorController::class, 'getAllInstructorUpcomingClasses']);
Route::post('instructor/get-all-published-classes', [InstructorController::class, 'getAllInstructorPublishedCourses']);
Route::post('instructor/process-instructor-application', [InstructorController::class, 'processInstructorApplication']);
Route::get('instructor/get-all-instructors', [InstructorController::class, 'getAllInstructors']);
Route::get('instructor/get-instructor-info/{user_id}', [InstructorController::class, 'getInstructorInfo']);

Route::post('/instructor/instructor-search', [InstructorController::class, 'searchForInstructor']);
Route::post('/instructor/filter-instructor-search', [InstructorController::class, 'filterInstructor']);
Route::get('/instructor/instructor-search-new', [InstructorController::class, 'searchForInstructorNew']);



//Helper Routes
Route::get('helper/get-all-countries', [HelperController::class, 'getAllCountries']);
Route::get('helper/get-all-states', [HelperController::class, 'getAllStates']);
Route::get('helper/get-all-states-by-country/{country_id}', [HelperController::class, 'getAllStatesByCountry']);
Route::get('helper/get-all-course-categories', [HelperController::class, 'getAllCourseCategories']);
Route::get('helper/get-all-live-class-platforms', [HelperController::class, 'getAllLiveClassPlatforms']);
Route::get('helper/get-all-resource-types', [HelperController::class, 'getAllResourceTypes']);
Route::get('helper/get-all-level-of-competences', [HelperController::class, 'getAllLevelOfCompetences']);
Route::get('helper/get-all-who_is_this_course_for_categories', [HelperController::class, 'getAllWhoIsThisCourseForCategories']);
Route::get('helper/get-all-timezones', [HelperController::class, 'getAllTimezones']);
Route::get('helper/get-all-live-class-types', [HelperController::class, 'getAllLiveClassTypes']);
Route::get('helper/get-all-durations', [HelperController::class, 'getAllDuration']);


//Payout Routes
Route::post('payout/create-payout-request', [PaymentController::class, 'createPayoutRequest']);
Route::get('payout/get-settlement-dashboard-stats', [PaymentController::class, 'getSettlementDashboardStats']);
Route::get('payout/get-all-settlement-transactions', [PaymentController::class, 'getAllSettlementTransactions']);
Route::post('payout/get-settlement-request-by-id', [PaymentController::class, 'getSettlementRequestById']);

//Payment Routes
Route::post('pay-and-enroll-for-course', [PaymentController::class, 'payAndEnrollForCourse']);

//Auth Routes
Route::post('student-register', [PassportAuthController::class, 'registerStudent']);
Route::post('instructor-register', [PassportAuthController::class, 'registerInstructor']);
Route::post('login', [PassportAuthController::class, 'login']);
Route::post('forgot-password', [PassportAuthController::class, 'forgotPassword']);
Route::get('verify/{user_id}/{token}', [PassportAuthController::class, 'verifyActivationLink']);


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:api')->get('/student', function (Request $request) {
    return $request->user();
});
