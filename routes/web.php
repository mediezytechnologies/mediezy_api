<?php

use App\Http\Controllers\Admin\AdminMedicineController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\API\ClinicController;
use App\Http\Controllers\ArticleManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ClinicManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocterController;
use App\Http\Controllers\DoctorClinicRelationController;
use App\Http\Controllers\DoctorClinicSpecializationController;
use App\Http\Controllers\FullCalenderController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\specializeController;
use App\Http\Controllers\SpecificationController;
use App\Http\Controllers\SubspecificationController;
use App\Http\Controllers\BannersController;
use App\Http\Controllers\ClinicConsultationFeeController;
use App\Models\DoctorClinicSpecialization;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('/admin_login', [AuthController::class, 'showLoginForm']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Registration Routes
Route::get('/register', [AuthController::class, 'showRegistrationForm']);
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/admin', function () {
    return view('admin.Dashboard.index');
});



Route::get('/', function () {
    if (Auth::check()) {
        Auth::logout();
    }
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

//ashwin
Route::get('/articles_list', [ArticleManagementController::class, 'getAllArticles'])->name('articles.list');
// Route::post('/add-article', [ArticleManagementController::class, 'addArticle'])->name('store-article');
Route::get('/articles', [ArticleManagementController::class, 'getAllArticles'])->name('articles');
Route::get('/add-article-form', [ArticleManagementController::class, 'index'])->name('add-article-form');
Route::post('/add-article', [ArticleManagementController::class, 'addArticle']);



Route::get('/PrivacyPolicy', function () {
    return view('PrivacyPolicy');
});

Route::get('/TermsAndCondition', function () {
    return view('TermsandCondition');
});


Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/Specialization', [SpecificationController::class, 'index'])->name('Specialization.index');
Route::get('/Subspecialization', [SubspecificationController::class, 'index'])->name('Subspecialization.index');
Route::get('/Docter', [DocterController::class, 'index'])->name('Docter.index');
Route::get('/Docter/create', [DocterController::class, 'create'])->name('Docter.create');
Route::get('/specialize', [specializeController::class, 'index'])->name('specialize.index');
Route::get('/schedulemanager', [ScheduleController::class, 'index'])->name('schedulemanager.index');
Route::get('/Tokengeneration', [ScheduleController::class, 'create'])->name('Tokengeneration.create');
Route::get('/banner', [BannerController::class, 'index'])->name('bannerImage.index');
Route::get('/Docteredit/{userId}', [DocterController::class, 'edit'])->name('Docter.edit');
Route::controller(FullCalenderController::class)->group(function () {
    Route::get('fullcalender', 'index');
    Route::post('fullcalenderAjax', 'ajax');
});


Route::get('/categories', [CategoriesController::class, 'index'])->name('Categories.index');

Route::get('/showTokencategories', function () {
    return view('ShowTokens');
});


//ashwin
Route::get('/clinics_list', [ClinicManagementController::class, 'getAllClinics'])->name('clinics.list');
// Route::post('/add-article', [ArticleManagementController::class, 'addArticle'])->name('store-article');

Route::get('/clinics', [ClinicManagementController::class, 'getAllClinics'])->name('clinics');

Route::get('/add-clinic-form', [ClinicManagementController::class, 'index'])->name('add-clinic-form');

// Route::get('/add-article-form', function () {
//     return view('articles.add_article_form');
// });

Route::post('/add-clinic', [ClinicManagementController::class, 'addClinic']);


/// clinic - doctor relations
Route::get('/manage-relations', [DoctorClinicRelationController::class, 'manageRelationsView'])->name('manageRelationsView');
Route::post('/save-doctor-clinic-relations', [DoctorClinicRelationController::class, 'saveDoctorClinicRelations'])->name('saveDoctorClinicRelations');
Route::post('/save-clinic-doctor-relations', [DoctorClinicRelationController::class, 'saveClinicDoctorRelations'])->name('saveClinicDoctorRelations');

/// clinic - doctor specializations
Route::get('/manage-specializations', [DoctorClinicSpecializationController::class, 'manageSpecializationsView'])->name('manageSpecializationsView');
Route::post('/save-doctor-clinic-specializations', [DoctorClinicSpecializationController::class, 'saveDoctorClinicSpecializations'])->name('saveDoctorClinicSpecializations');
Route::post('/save-clinic-doctor-specializations', [DoctorClinicSpecializationController::class, 'saveClinicDoctorSpecializations'])->name('saveClinicDoctorSpecializations');


///// consultation fee
Route::get('/manage-clinicconsultation', [ClinicConsultationFeeController::class, 'manageClinicConsultationfee'])->name('manageClinicConsultationfee');
Route::post('/save-clinic-consultation-fee', [ClinicConsultationFeeController::class, 'saveClinicConsultationfee'])->name('saveClinicConsultationfee');
Route::get('/clinic-consultation', [ClinicConsultationFeeController::class, 'clinicwiseConsultation'])->name('clinic-consultation.clinicwiseConsultation');
Route::post('/clinic-consultations', [ClinicConsultationFeeController::class, 'clinicwiseConsultationFees'])->name('clinic-consultations.clinicwiseConsultationFees');


//Banner

Route::get('/UserBanner', [BannersController::class, 'getallBanner'])->name('Banner.view');
Route::match(['get', 'post'], '/UserBanner/add', [BannersController::class, 'addBanner'])->name('UserBanner.add');
Route::get('/UserBanner_view', [BannersController::class, 'bannerView'])->name('UserBanner.listview');
Route::get('/UserBanner_update', [BannersController::class, ' updateBanner'])->name('UserBanner.update');

////////////////////ADMIN  ROUTES  ////////////////////

Route::prefix('admin')->group(function () {
    // Route::get('/medicine', [AdminMedicineController::class, 'index'])->name('medicine.index');
    Route::get('/add_meddicine', [AdminMedicineController::class, 'medicineAdd'])->name('medicine_add');
    Route::post('/upload_medicine_data', [AdminMedicineController::class, 'uploadMedicineData'])->name('uploadMedicineData');
});

////////////////////////////////////////////////////////


require __DIR__ . '/auth.php';
