<?php

use App\Http\Controllers\API\AppoinmentsController;
use App\Http\Controllers\API\Appointment\AppointmentBookingController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BannerController;
use App\Http\Controllers\API\BookingEstimationController;
use App\Http\Controllers\API\CategoriesController;
use App\Http\Controllers\API\ChatBot\ChatBotController;
use App\Http\Controllers\API\CheckInCheckOutController;
use App\Http\Controllers\API\CompletedAppointmentsController;
use App\Http\Controllers\API\ContactusController;
use App\Http\Controllers\API\DocterController;
use App\Http\Controllers\API\GetTokenController;
use App\Http\Controllers\API\HospitalController;
use App\Http\Controllers\API\LabController;
use App\Http\Controllers\API\LoginController;
use App\Http\Controllers\API\MedicalshopController;
use App\Http\Controllers\API\MedicineController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\specializeController;
use App\Http\Controllers\API\SpecificationController;
use App\Http\Controllers\API\SubspecificationController;
use App\Http\Controllers\API\TokenBookingController;
use App\Http\Controllers\API\TokenGenerationController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\DoctorDashboardController;
use App\Http\Controllers\API\DoctorRegistration\DoctorRegisterAttemptController;
use App\Http\Controllers\API\FamilyMemberController;
use App\Http\Controllers\API\MedicineBase\MedicineBaseController;
use App\Http\Controllers\API\MedicineBase\UserMedicineController;
use App\Http\Controllers\API\Payments\PaymentController;
use App\Http\Controllers\API\SuggestionController;
use App\Http\Controllers\API\Symptoms\SymptomsFAQController;
use App\Http\Controllers\API\UserLocation\UserLocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserRatingController;
use App\Http\Controllers\API\UserTokensETA\UserTokensETAController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//auth
Route::post('auth/social-accounts/login', [AuthController::class, 'socialAccountsAuth']);

Route::post('send/notification', [ArticleController::class, 'sendNotification']);

Route::post('/reciveFCMToken', [AuthController::class, 'recieveFCMToken']);
//

Route::post('send/mail', [ArticleController::class, 'sendMail']);

Route::post('user/chat', [ChatBotController::class, 'saveUserChat']);
//Specialization
//doc temp register
Route::post('/doctor/doctor_register_attempt', [DoctorRegisterAttemptController::class, 'doctorRegistration']);


Route::get('/specialization', [SpecificationController::class, 'index']);
Route::get('/specialization/{id}', [SpecificationController::class, 'show']);

Route::put('patient/updateMedicines', [MedicineController::class, 'updateMedicine']);
Route::post('/Specialization', [specializeController::class, 'Specialize']);


Route::post('/patient_history', [UserController::class, 'PatientHistory']);

Route::post('/specialization', [SpecificationController::class, 'store']);
Route::put('/specialization/{id}', [SpecificationController::class, 'update']);
Route::delete('/specialization/{id}', [SpecificationController::class, 'destroy']);

//Subpecialization
Route::get('/subspecialization', [SubspecificationController::class, 'index']);
Route::get('/subspecialization/{id}', [SubspecificationController::class, 'show']);

Route::post('/subspecialization', [SubspecificationController::class, 'store']);
Route::put('/subspecialization/{id}', [SubspecificationController::class, 'update']);
Route::delete('/subspecialization/{id}', [SubspecificationController::class, 'destroy']);

///sreelakshmi
Route::get('/userbanner', [BannerController::class, 'getallUserbanner'])->name('  userbanner.view');

Route::get('/userbanner_view', [BannerController::class, 'getUserBannerByType'])->name('userbanner.listview');
Route::get('/userbanner/{banner_type}', [BannerController::class, 'getUserBannerByType']);
Route::Delete('/userbannerDelete/{banner_type}', [BannerController::class, 'deleteBannersByType']);
Route::put('/userbannerUpdate/{banner_type}', [BannerController::class, 'updateBannerByType']);


////////////lab
Route::group(['prefix' => 'lab'], function () {

    Route::get('/getLabdetail', [LabController::class, 'getLabdetails']);
    Route::post('/LabRegister', [LabController::class, 'LabRegister']);
    Route::post('/LabDocumentUpload', [LabController::class, 'LabDocumentUpload']);
    Route::get('/getUpComingLabdetails/{lab_id}', [LabController::class, 'getUpComingLabdetails']);
    Route::get('/getCompletedLabdetails/{lab_id}', [LabController::class, 'getCompletedLabdetails']);
    Route::post('/updateLabUserDetails/{lab_id}', [LabController::class, 'updateLabUserDetails']);
    Route::get('/getLabUserdetails/{lab_id}', [LabController::class, 'getLabUserdetails']);
    Route::get('/getLabAndScandetails/{lab_id}', [LabController::class, 'getLabAndScandetails']);
    Route::get('/getUpcomingDetailsCount/{lab_id}', [LabController::class, 'getUpcomingDetailsCount']);
    Route::get('/getCompletedLabDetailsCount/{lab_id}', [LabController::class, 'getCompletedLabDetailsCount']);
});

///end
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/docter', [DocterController::class, 'index']);
});
//Doctor
Route::get('/getalldocters', [DocterController::class, 'getallDocters']);
Route::get('/getDoctorProfileDetails/{userId}', [DocterController::class, 'getDoctorProfileDetails']);
Route::post('/docter', [DocterController::class, 'store']);
Route::put('/docter/{userId}', [DocterController::class, 'update']);
Route::delete('/docter/{id}', [DocterController::class, 'destroy']);
Route::get('/symptoms/{specializationId}', [DocterController::class, 'getSymptomsBySpecialization']);
Route::get('/docter/docterByspecialization/{id}', [DocterController::class, 'getDoctorsBySpecialization']);

//specialize
Route::get('/specialize', [specializeController::class, 'index']);
Route::get('/specialize/{id}', [specializeController::class, 'show']);

Route::post('/specialize', [specializeController::class, 'store']);
Route::put('/specialize/{id}', [specializeController::class, 'update']);
Route::delete('/specialize/{id}', [specializeController::class, 'destroy']);

//Route::get('/schedule', [ScheduleController::class, 'index']);
Route::get('/schedule/{date}/{clinicId}', [ScheduleController::class, 'show']);

Route::post('/schedule', [ScheduleController::class, 'store']);
Route::put('/schedule/{id}', [ScheduleController::class, 'update']);
Route::delete('/schedule/{id}', [ScheduleController::class, 'destroy']);
Route::post('/getTokenCount', [ScheduleController::class, 'calculateMaxTokens']);

Route::post('/banner', [BannerController::class, 'store']);
Route::post('/set-first-image/{id}', [BannerController::class, 'setFirstImage']);
Route::put('/update-footer/{id}', [BannerController::class, 'updateFooterImages']);
//Medicine
Route::get('/Medicine', [MedicineController::class, 'index']);
Route::get('/Medicine/{id}', [MedicineController::class, 'show']);
Route::post('/Medicine', [MedicineController::class, 'store']);
Route::put('/Medicine/{id}', [MedicineController::class, 'update']);
Route::delete('/Medicine/{id}', [MedicineController::class, 'destroy']);
// Docter Registration
Route::post('/register', [RegisterController::class, 'register']);
// User Registration
Route::post('/Userregister', [UserController::class, 'UserRegister']);
Route::get('/Useredit/{userId}', [UserController::class, 'UserEdit']);
Route::put('/Userupdate/{userId}', [UserController::class, 'updateUserDetails']);
Route::post('/UserDP/{userId}', [UserController::class, 'userimage']);

Route::get('/UserDP/{userId}', [UserController::class, 'getUserImage']);
//  Login
Route::post('/login', [LoginController::class, 'login']);
Route::post('/generate-cards', [TokenGenerationController::class, 'generateTokenCards']);
Route::get('/generate-cards', [TokenGenerationController::class, 'generateTokenCards']);
Route::middleware('auth:api')->get('/today-schedule', [TokenGenerationController::class, 'getTodayTokens']);
Route::get('/get-hospital-name/{doctor_id}', [DocterController::class, 'getHospitalName']);
Route::get('/gethospitalbyId/{id}', [DocterController::class, 'getHospitalDetailsById']);
Route::post('/approveorreject', [DocterController::class, 'ApproveOrReject']);
//login with token
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/TokenBooking', [TokenBookingController::class, 'bookToken']);
// Route::get('/getallappointments/{userId}/{date}/{clinicid}', [TokenBookingController::class, 'GetallAppointmentOfDocter']);
Route::get('/getallcompletedappointments/{userId}/{date}/{clinicid}', [TokenBookingController::class, 'GetallAppointmentOfDocterCompleted']);

Route::group(['prefix' => 'user'], function () {
    Route::any('/get_docter_tokens', [DocterController::class, 'getTokens']);
    Route::get('/userCompletedAppoinments/{userId}', [UserController::class, 'GetUserCompletedAppoinments']);
    Route::post('/addtofavourites', [UserController::class, 'favouritestatus']);
    Route::get('/getallfavourites/{id}', [UserController::class, 'getallfavourites']);
    Route::post('/upload_document', [UserController::class, 'uploadDocument']);
    Route::post('/update_document', [UserController::class, 'updateDocument']);
    Route::post('/get_uploaded_documents', [UserController::class, 'getUploadedDocuments']);
    Route::post('/reports_time_line', [UserController::class, 'ReportsTimeLine']);
    Route::post('/get_prescriptions', [UserController::class, 'getPrescriptions']);
    Route::post('/manage_member', [UserController::class, 'manageMembers']);
    Route::post('/manage_address', [UserController::class, 'manageAddress']);
    Route::post('/get_address', [UserController::class, 'getUserAddresses']);
    Route::post('/get_patients', [UserController::class, 'getPatients']);
    Route::get('/recentlyBookedDoctor', [UserController::class, 'recentlyBookedDoctor']);
    Route::delete('/delete-document/{user_id}/{document_id}/{type}', [UserController::class, 'deleteDocument']);
    Route::post('/get-health-record', [DoctorDashboardController::class, 'getHealthRecord']);
    Route::get('/get_Allergy', [UserController::class, 'GetAllergy']);

    Route::post('/update-document1', [DoctorDashboardController::class, 'updateDocument1']);
    Route::get('/doctorbyclinics/{userId}', [DocterController::class, 'GetDoctorByClinic']);
    Route::get('/CompletedAppointmentsDetails/{booked_user_id}/{date}', [UserController::class, 'CompletedAppointmentsDetails']);
});

//code for add_prescription
Route::group(['prefix' => 'docter'], function () {
    Route::post('/get_appointment_details', [TokenBookingController::class, 'appointmentDetails']);
    Route::post('/today_token_schedule', [TokenGenerationController::class, 'todayTokenSchedule']);
    Route::post('/add_prescription', [TokenBookingController::class, 'addPrescription']);
    Route::get('/get_medicine/{medicineid}', [TokenBookingController::class, 'getMedicineById']);
    Route::delete('/medicine/{id}', [TokenBookingController::class, 'deleteMedicine']);
    Route::post('/delete_tokens', [TokenGenerationController::class, 'deleteToken']);
    Route::post('/leave_update', [DocterController::class, 'leaveUpdate']);
    Route::post('/leaves', [DocterController::class, 'getDoctorLeaveList']);
    Route::post('/check_pincode_available', [DocterController::class, 'checkPincodeAvailable']);
    Route::post('/get_booked_patients', [DocterController::class, 'getBookedPatients']);
    Route::post('/searchPatients', [DocterController::class, 'searchPatients']);
    Route::post('/SortPatients', [DocterController::class, 'sortPatient']);
    Route::post('/AddTestDetails', [DocterController::class, 'Addtestdetails']);
    //Route::post('/doctorBreakRequest',[TokenGenerationController::class,'doctorBreakRequest']);
    // Route::post('/docteredit/{userId}', [DocterController::class, 'doctorUpdate']);
    Route::POST('/doctor_update/{userId}', [DocterController::class, 'doctorUpdate']);
    Route::post('/doctor_register', [DocterController::class, 'registerDoctor']);
});

Route::group(['prefix' => 'Tokens'], function () {
    Route::post('/getTokendetails', [GetTokenController::class, 'getTokensForCheckInAndComplete']);
    Route::post('/getcurrentTokens', [GetTokenController::class, 'getCurrentDateTokens']);
});
Route::get('/user/userAppoinments/{userId}', [AppoinmentsController::class, 'GetUserAppointments']);

//Workfrom athira
Route::get('/Showcategories', [CategoriesController::class, 'index']);
Route::get('/ShowCategoriesdocter/{id}', [CategoriesController::class, 'show']);
Route::post('/Categories', [CategoriesController::class, 'store']);
Route::get('/searchdoctor', [DocterController::class, 'searchDoctor']);
//categories Symptoms
Route::get('/Showsymptoms', [CategoriesController::class, 'indexs']);
Route::get('/ShowCategoriessymptoms/{id}', [CategoriesController::class, 'shows']);

//medicalshop
Route::group(['prefix' => 'medicalshop'], function () {
    Route::post('/Register', [MedicalshopController::class, 'MedicalshopRegister']);
    Route::post('/medicine', [MedicalshopController::class, 'MedicineProduct']);
    Route::get('/getallmedicalshop', [MedicalshopController::class, 'GetMedicalShopForDoctors']);
    Route::get('/getmedicalshops', [MedicalshopController::class, 'GetAllMedicalShops']);
    Route::post('/addfavmedicalshop', [MedicalshopController::class, 'addFavouirtesshop']);
    Route::post('/Removefavmedicalshop', [MedicalshopController::class, 'removeFavouirtesshop']);
    Route::get('/getfavmedicalshop', [MedicalshopController::class, 'getFavMedicalshop']);
    Route::post('/searchmedicalshop', [MedicalshopController::class, 'searchmedicalshop']);
    Route::get('/getNewOrders', [MedicalshopController::class, 'getMedicineNewOrder']);
    Route::get('/getfavlab', [LabController::class, 'getFavlab']);
    Route::post('/getUpcomingOrder', [MedicalshopController::class, 'getUpcomingOrder']);
    Route::post('/getUpcomingOrderdetails', [MedicalshopController::class, 'getUpcomingOrderDetails']);
    Route::POST('/MedicineOrderSubmit', [MedicalshopController::class, 'MedicineOrderSubmit']);
    Route::POST('/getMedicineCompleteOrder', [MedicalshopController::class, 'getMedicineCompleteOrder']);
    Route::GET('/medicalshopEdit/{UserId}', [MedicalshopController::class, 'medicalshopEdit']);
    Route::PUT('/medicalshopUpdate/{UserId}', [MedicalshopController::class, 'medicalshopUpdate']);



    //Laboratory
    Route::group(['prefix' => 'Lab'], function () {
        Route::get('/getLabdetail', [LabController::class, 'getLabdetails']);
        Route::post('/LabRegister', [LabController::class, 'LabRegister']);
        Route::post('/LabDocumentUpload', [LabController::class, 'LabDocumentUpload']);
        Route::get('/getCompletedLabdetails/{lab_id}', [LabController::class, 'getCompletedLabdetails']);
        Route::post('/getLabdetail', [LabController::class, 'getLabdetails']);
        Route::post('/Test', [LabController::class, 'LabTest']);
        Route::get('/getallLabandScanningCenter', [LabController::class, 'GetLabandScanForDoctors']);
        Route::get('/getalllab', [LabController::class, 'GetLabForDoctors']);
        Route::get('/getallScanningCenter', [LabController::class, 'GetScanningForDoctors']);
        Route::post('/addfavLab', [LabController::class, 'addFavouirtesLab']);
        Route::post('/RemovefavLab', [LabController::class, 'RemoveFavouirtesLab']);
        Route::get('/getLabs', [LabController::class, 'GetAllLabs']);
        Route::post('/searchLab', [LabController::class, 'searchLabByName']);
        Route::post('/searchScanningCenter', [LabController::class, 'searchLabByNameScanningCenter']);
        Route::post('/LabRegister', [LabController::class, 'Labregister']);
        Route::get('/getLabdetail', [LabController::class, 'getLabdetails']);
        Route::post('/searchLabandScan', [LabController::class, 'searchLabandScan']);
        Route::get('/getUpComingLabdetails/{lab_id}', [LabController::class, 'getUpComingLabdetails']);
    });
});

Route::post('/payment-image', [UserController::class, 'store']);
Route::group(['prefix' => 'Hospital'], function () {
    Route::post('/Register', [HospitalController::class, 'HospitalRegister']);
});
Route::get('/patientsedit/{patientId}', [UserController::class, 'editPatient']);
Route::post('/patientupdate/{patientId}', [UserController::class, 'updatePatient']);
Route::delete('/DeleteMemeber/{patientId}', [UserController::class, 'DeleteMemeber']);
/////
Route::post('/previous-appointments', [DoctorDashboardController::class, 'getPreviousAppointments']);
Route::post('/PreviousPatient-AppoitmentsDetails', [DoctorDashboardController::class, 'PreviousPatientAppoitmentsDetails']);
Route::post('/getCompletedAppointments', [DoctorDashboardController::class, 'getCompletedAppointments']);
Route::post('/Autofetch', [usercontroller::class, 'Autofetch']);
Route::post('/GetFamily', [usercontroller::class, 'GetFamily']);
Route::post('/addsuggestions', [SuggestionController::class, 'addSuggestion']);

Route::group(['middleware' => 'auth:api'], function () {

    Route::group(['prefix' => 'doctor'], function () {

        Route::post('/update-user-eta/checkin', [UserTokensETAController::class, 'updateEstimateTimeIfCheckin']);

        Route::get('/getAllUserAppointments/{userId}/{date}/{clinicid}/{schedule_type}', [TokenBookingController::class, 'getAllUserAppointments']);

        //generate tokens and schedule
        Route::post('/generateTokenSchedule', [ScheduleController::class, 'generateTokenSchedule']);
        //doctor reschedules
        Route::post('/doctorRequestForlate', [ScheduleController::class, 'doctorRequestForlate']);
        Route::post('/doctorRequestForEarly', [ScheduleController::class, 'doctorRequestForEarly']);
        Route::put('/doctorRequestForBreak', [ScheduleController::class, 'doctorRequestForBreak']);

        // delete doctor reschedules
        Route::get('/getAllDoctorReschedules/{doctor_user_id}/{clinic_id}/{reschedule_type}', [ScheduleController::class, 'getAllDoctorReschedules']);
        Route::post('/getAllDoctorBreakRequests', [ScheduleController::class, 'getAllDoctorBreakRequests']);
        Route::post('/deleteDoctorBreakRequests', [ScheduleController::class, 'deleteDoctorBreakRequests']);
        Route::post('/deleteDoctorLateReschedules', [ScheduleController::class, 'deleteDoctorLateReschedules']);
        Route::post('/deleteDoctorEarlyReschedules', [ScheduleController::class, 'deleteDoctorEarlyReschedules']);

        Route::get('/getDoctorTokenDetails/{date}/{clinic_id}/{user_id}', [ScheduleController::class, 'getDoctorTokenDetails']);
        //checkin check out
        Route::post('/getTokensCheckInCheckOut', [CheckInCheckOutController::class, 'getTokensCheckInCheckOut']);
        // Route::post('/getTokensCheckInCheckOut', [GetTokenController::class, 'getTokensCheckInCheckOut']);

        ///booked and completed tokens
        Route::get('/getallappointments/{userId}/{date}/{clinicid}/{schedule_type}', [TokenBookingController::class, 'GetallAppointmentOfDocter']);
        Route::get('/getallcompletedappointments/{userId}/{date}/{clinicid}/{schedule_type}', [TokenBookingController::class, 'GetallAppointmentOfDocterCompleted']);
        Route::get('/getAllAppointmentDetails/{token_id}', [TokenBookingController::class, 'getAllAppointmentDetails']);

        Route::get('/getCompletedAppointmentDetails/{id}', [TokenBookingController::class, 'getCompletedAppointmentDetails']);

        //// doctor leaves
        Route::put('/doctorLeaveUpdate', [DocterController::class, 'doctorLeaveUpdate']);
        Route::delete('/doctorleaveDelete', [DocterController::class, 'doctorLeaveDelete']);

        // search and sort patients
        Route::post('/getAllSortedPatients', [DocterController::class, 'getAllSortedPatients']);

        //reserve tokens
        Route::put('/doctorReserveTokens', [ScheduleController::class, 'doctorReserveTokens']);
        Route::post('/getReservedToKenDetails', [ScheduleController::class, 'GetReservedTokensDetails']);
        Route::put('/getUnReservedToKenDetails', [ScheduleController::class, 'UnreserveToken']);

        //completed appoitments
        Route::post('/get_completed_appoitment_details', [CompletedAppointmentsController::class, 'listCompletedAppoitmentsDetails']);

        // check for existing tokens brfore leave
        Route::post('/checkForExistingTokens', [DocterController::class, 'checkForExistingTokens']);

        // get appointment details
        Route::post('/getAppointmentDetails', [DocterController::class, 'getAppointmentDetails']);

        //med base
        Route::post('/getMedicineBySearch', [MedicineBaseController::class, 'getMedicineBySearch']);
        //fav med
        Route::post('/updateFavoriteMedicines', [UserMedicineController::class, 'updateFavoriteMedicines']);

        ////delete medicine
        Route::delete('/deleteMedicineHistory', [MedicineBaseController::class, 'deleteMedicineHistory']);

        Route::get('/getSortedDoctorPatientAppointments/{patient_id}/{doctor_user_id}', [CompletedAppointmentsController::class, 'getSortedDoctorPatientAppointments']);
    });

    Route::delete('/deleteAllAppointments', [ScheduleController::class, 'deleteAllAppointments']);
    //////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////

    Route::group(['prefix' => 'patient'], function () {



        //capture payment
        Route::post('/capturePayment', [PaymentController::class, 'capturePayment']);


        Route::post('/get_Vitals', [UserController::class, 'getVitals']);
        Route::get('/patientLiveTokenEstimate/{patient_user_id}', [GetTokenController::class, 'patientLiveTokenEstimate']); //old
        //live token // old one dont use
        Route::get('/patientLiveTokenEstimatenew/{patient_user_id}', [AppoinmentsController::class, 'patientLiveTokenEstimate2']);

        // estimate time changes
        Route::get('/upcomingEstimateCalculation/{patient_user_id}', [BookingEstimationController::class, 'upcomingEstimateCalculation']);

        Route::any('/getPatientTokenDetails/{date}/{clinic_id}/{user_id}', [ScheduleController::class, 'getPatientTokenDetails']);
        //token booking
        Route::post('/patientBookGeneratedTokens', [TokenBookingController::class, 'patientBookGeneratedTokens']);
        Route::post('/initiatePaymentsforTokenBooking', [AppointmentBookingController::class, 'validateTokenBooking']);


        //
        Route::get('/getPatientCompletedAppointments/{userId}', [UserController::class, 'getPatientCompletedAppointments']);
        // Route::get('/getSortedPatientAppointments/{patient_id}/{userId}', [AppoinmentsController::class, 'getSortedPatientAppointments']);
        Route::get('/getSortedPatientAppointments/{patient_id}/{userId}', [CompletedAppointmentsController::class, 'getSortedPatientAppointments']);

        //edit patient details
        Route::get('/getEditPatientDetails/{patient_id}', [SpecificationController::class, 'getEditPatientDetails']);
        Route::post('/editPatientDetails', [SpecificationController::class, 'editPatientDetails']);

        // clinic list
        Route::get('/getAllClinics', [HospitalController::class, 'getAllClinics']);
        Route::get('/getAllClinicDetails/{clinic_id}', [HospitalController::class, 'getAllClinicDetails']);
        // doctor-clinic-specilzn
        Route::get('/getSpecializationDoctors/{specialization_id}/{clinic_id}', [HospitalController::class, 'getSpecializationDoctors']);

        //articles
        Route::get('/getAllArticles', [ArticleController::class, 'getAllArticles']);

        //patient medicine details
        Route::post('/addPatientMedicine', [MedicineController::class, 'addPatientMedicine']);
        Route::post('/listPatientMedicines', [MedicineController::class, 'listPatientMedicines']);
        // Route::put('/updatetMedicines', [MedicineController::class, 'updateMedicine']);
        Route::DELETE('/deleteMedicines/{id}/{patientId}', [MedicineController::class, 'deleteMedicine']);
        Route::GET('/getMedicine/{patient_id}', [MedicineController::class, 'getMedicine']);
        Route::put('/update_medicine', [TokenBookingController::class, 'UpdatemedicineById']);

        //suggestDoctor
        Route::post('/suggestDoctor', [SuggestionController::class, 'suggestDoctor']);

        //add family member
        Route::post('/addFamilyMember', [FamilyMemberController::class, 'createFamilyMember']);
        Route::post('/addFamilyMember/savePatientImage', [FamilyMemberController::class, 'savePatientImage']);
        Route::post('/editFamilyMembers', [FamilyMemberController::class, 'editFamilyMember']);

        //qr code check
        Route::post('/checkPatientReach/qr', [UserController::class, 'checkPatientReach']);

        //completed appoitmnets
        Route::post('/appointment/getPatientCompletedAppointments', [CompletedAppointmentsController::class, 'getPatientCompletedAppointments']);
        //user
        Route::group(['prefix' => 'user_locations'], function () {
            Route::post('/addUserLocations', [UserLocationController::class, 'addUserLocations']);
            Route::post('/list_nearby_doctors', [UserLocationController::class, 'patientListNearbyDoctors']);
        });

        Route::get('/listSymptoms', [SymptomsFAQController::class, 'listSymptoms']);
        Route::post('/addSymptomsQuestions', [SymptomsFAQController::class, 'addSymptomsQuestions']);
        Route::post('/getSymptomsQuestions', [SymptomsFAQController::class, 'getSymptomsQuestions']);

        //get available doctor locations
        Route::get('/getDoctorLocations', [DocterController::class, 'getDoctorLocations']);

        //other user booking
        Route::post('/otherUserTokenBooking', [TokenBookingController::class, 'otherUserTokenBooking']);
    });


    //midhun
    Route::post('/addVitals', [AppoinmentsController::class, 'addVitals']);
    Route::put('/editVitals', [AppoinmentsController::class, 'editVitals']);
    Route::delete('/deleteVitals', [AppoinmentsController::class, 'deleteVitals']);

    Route::post('/getAllDatesOfDeletedTokens', [ScheduleController::class, 'getAllDatesOfDeletedTokens']);
    Route::post('/getDeletedTokens', [ScheduleController::class, 'getDeletedTokens']);
    Route::post('/restoreTokens', [ScheduleController::class, 'restoreTokens']);

    // ContactusController
    Route::post('/getContactUsDetails', [ContactusController::class, 'ContactUs']);

    Route::post('/updateSymptominage', [SymptomsFAQController::class, 'updateSymptominage']);
    Route::post('/addUserRating', [UserRatingController::class, 'addUserRating']);
    Route::post('/addReview', [UserRatingController::class, 'addUserReview']);
    Route::get('/getUserRating/{user_rating}', [UserRatingController::class, 'getUserRating']);
    Route::post('/addDoctorReview', [UserRatingController::class, 'addUserDoctorRating']);
    Route::post('/userCompletedFeedback', [UserRatingController::class, 'userCompletedFeedback']);
    ////////////////////////////////////////////////////////
});
