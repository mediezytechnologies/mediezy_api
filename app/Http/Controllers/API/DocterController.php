<?php

namespace App\Http\Controllers\API;

use App\Helpers\UserLocationHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Clinic;
use App\Models\ClinicConsultation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Docter;
use App\Models\DocterAvailability;
use App\Models\DocterLeave;
use App\Models\DoctorClinicRelation;
use App\Models\DoctorRegister;
use App\Models\NewDoctorSchedule;
use App\Models\NewTokens;
use App\Models\schedule;
use App\Models\Patient;
use App\Models\Specialize;
use App\Models\Specification;
use App\Models\Subspecification;
use App\Models\Symtoms;
use App\Models\TodaySchedule;
use App\Models\TokenBooking;
use App\Models\User;
use App\Services\DistanceMatrixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DocterController extends BaseController
{



    public function getallDocters()
    {
        $specializeArray['specialize'] = Specialize::all();
        $specificationArray['specification'] = Specification::all();
        $subspecificationArray['subspecification'] = Subspecification::all();

        $docters = Docter::join('docteravaliblity', 'docter.id', '=', 'docteravaliblity.docter_id')
            ->select('docter.UserId', 'docter.id', 'docter.docter_image', 'docter.firstname', 'docter.lastname', 'docter.specialization_id', 'docter.subspecification_id', 'docter.specification_id', 'docter.about', 'docter.location', 'docteravaliblity.id as avaliblityId', 'docter.gender', 'docter.email', 'docter.mobileNo', 'docter.Services_at', 'docteravaliblity.hospital_Name', 'docteravaliblity.startingTime', 'docteravaliblity.endingTime')
            ->get();

        $doctersWithSpecifications = [];

        foreach ($docters as $doctor) {
            $id = $doctor['id'];

            if (!isset($doctersWithSpecifications[$id])) {

                $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);

                $doctersWithSpecifications[$id] = [
                    'id' => $id,
                    'UserId' => $doctor['UserId'],
                    'firstname' => $doctor['firstname'],
                    'secondname' => $doctor['lastname'],
                    'Specialization' => $specialize ? $specialize['specialization'] : null,
                    'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
                    'About' => $doctor['about'],
                    'Location' => $doctor['location'],
                    'Gender' => $doctor['gender'],
                    'emailID' => $doctor['email'],
                    'Mobile Number' => $doctor['mobileNo'],
                    'MainHospital' => $doctor['Services_at'],
                    'subspecification_id' => $doctor['subspecification_id'],
                    'specification_id' => $doctor['specification_id'],
                    'specifications' => [],
                    'subspecifications' => [],
                    'clincs' => [],
                ];
            }

            $specificationIds = explode(',', $doctor['specification_id']);
            $subspecificationIds = explode(',', $doctor['subspecification_id']);

            $doctersWithSpecifications[$id]['specifications'] = array_merge(
                $doctersWithSpecifications[$id]['specifications'],
                array_map(function ($id) use ($specificationArray) {
                    return $specificationArray['specification']->firstWhere('id', $id)['specification'];
                }, $specificationIds)
            );

            $doctersWithSpecifications[$id]['subspecifications'] = array_merge(
                $doctersWithSpecifications[$id]['subspecifications'],
                array_map(function ($id) use ($subspecificationArray) {
                    return $subspecificationArray['subspecification']->firstWhere('id', $id)['subspecification'];
                }, $subspecificationIds)
            );

            $doctersWithSpecifications[$id]['clincs'][] = [
                'id'  => $doctor['avaliblityId'],
                'name' => $doctor['hospital_Name'],
                'StartingTime' => $doctor['startingTime'],
                'EndingTime' => $doctor['endingTime'],
                'Address' => $doctor['address'],
                'Location' => $doctor['location'],
            ];
        }


        // Format the output to match the expected structure
        $formattedOutput = array_values($doctersWithSpecifications);

        return $this->sendResponse("Docters", $formattedOutput, '1', 'Docters retrieved successfully.');
    }


    // public function index()
    // {
    //     $authenticatedUserId = auth()->user()->id;
    //     $user_id = $authenticatedUserId;
    //     $specializeArray['specialize'] = Specialize::all();
    //     $specificationArray['specification'] = Specification::all();
    //     $subspecificationArray['subspecification'] = Subspecification::all();
    //     $docters = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
    //         ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
    //         ->select(
    //             'docter.UserId',
    //             'docter.id',
    //             'docter.docter_image',
    //             'docter.firstname',
    //             'clinics.clinic_main_image',
    //             'docter.lastname',
    //             'docter.latitude',
    //             'docter.longitude',
    //             'docter.specialization_id',
    //             'docter.subspecification_id',
    //             'docter.specification_id',
    //             'docter.about',
    //             'docter.location',
    //             'clinics.clinic_id as avaliblityId',
    //             'docter.gender',
    //             'docter.email',
    //             'docter.mobileNo',
    //             'docter.Services_at',
    //             'clinics.clinic_name',
    //             'clinics.clinic_start_time',
    //             'clinics.clinic_end_time',
    //             'clinics.address',
    //             'clinics.location',
    //             'clinics.clinic_description',
    //             'clinics.clinic_main_image',
    //             'clinics.latitude as clinic_latitude',
    //             'clinics.longitude as clinic_longitude',
    //         )
    //         ->get();

    //     $doctersWithSpecifications = [];


    //     //user location
    //     $current_location_data = UserLocationHelper::getUserCurrentLocation($user_id);
    //     $user_latitude = $current_location_data ? $current_location_data->latitude : null;
    //     $user_longitude = $current_location_data ? $current_location_data->longitude : null;

    //     $distanceHelper = new \App\Helpers\UserDistanceHelper();

    //     foreach ($docters as $doctor) {
    //         $id = $doctor['id'];
    //         $favoriteStatus = DB::table('addfavourite')
    //             ->where('UserId', $authenticatedUserId)
    //             ->where('doctor_id', $doctor['UserId'])
    //             ->exists();
    //         if (!isset($doctersWithSpecifications[$id])) {
    //             $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);
    //             //distance
    //             // $doctor_latitude = $doctor['latitude'];
    //             // $doctor_longitude = $doctor['longitude'];

    //             // $apiKey = config('services.google.api_key');
    //             // $service = new DistanceMatrixService($apiKey);
    //             // $distance = $service->getDistance($user_latitude, $user_longitude, $doctor_latitude, $doctor_longitude);
    //             $doctersWithSpecifications[$id] = [
    //                 'id' => $id,
    //                 'UserId' => $doctor['UserId'],
    //                 'firstname' => $doctor['firstname'],
    //                 'secondname' => $doctor['lastname'],
    //                 // 'distance_from_user' => $distance,
    //                 'Specialization' => $specialize ? $specialize['specialization'] : null,
    //                 'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
    //                 'Location' => $doctor['location'],
    //                 'MainHospital' => $doctor['Services_at'],
    //                 'clinics' => [],
    //                 'favoriteStatus' => $favoriteStatus ? 1 : 0,

    //             ];
    //         }
    //         $clinicDetails = [];
    //         $clinicDetails['distance'] = $distanceHelper->calculateHaversineDistance(
    //             $user_latitude,
    //             $user_longitude,
    //             $doctor->clinic_latitude,
    //             $doctor->clinic_longitude
    //         );

    //             $current_date = Carbon::now()->toDateString();
    //             $current_time = Carbon::now()->toDateTimeString();
    //             ///clinic wise location/////
    //             // $userLocation = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //             // if ($userLocation && $doctor->clinic_latitude && $doctor->clinic_longitude) {
    //             //     $distanceService = new DistanceMatrixService(config('services.google.api_key'));
    //             //     $clinicDetails['distance'] = $distanceService->getDistance($userLocation->latitude, $userLocation->longitude, $doctor->clinic_latitude, $doctor->clinic_longitude);
    //             // } else {
    //             //     $clinicDetails['distance'] = null;
    //             // }

    //             // $doctorsWithDetails[] = [

    //             //     'clinics' => $clinicDetails
    //             // ];
    //             $clinicDetails['distance'] = null;
    //             /////////////////////
    //             $total_token_count = NewTokens::where('clinic_id', $doctor->avaliblityId)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('doctor_id', $id)
    //                 ->count();

    //             $available_token_count = NewTokens::where('clinic_id', $doctor->avaliblityId)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('token_booking_status', NULL)
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $id)
    //                 ->count();



    //             $next_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('token_booking_status', null)
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->value('token_start_time');
    //             $consultation_fee = ClinicConsultation::where('doctor_id', $id)
    //                 ->where('clinic_id', $doctor->avaliblityId)
    //                 ->value('consultation_fee');
    //             $next_date_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
    //                 ->where('token_scheduled_date', '>', $current_date)
    //                 ->where('token_booking_status', NULL)
    //                 ->orderBy('token_scheduled_date', 'ASC')
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->value('token_start_time');
    //             // $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('y-m-d h:i A') : null;
    //             $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('d M h:i A') : null;

    //             $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;


    //             $clinicDetails = [
    //                 'clinic_id' => $doctor['avaliblityId'],
    //                 'clinic_name' => $doctor['clinic_name'],
    //                 'clinic_start_time' => $doctor['clinic_start_time'],
    //                 'clinic_end_time' => $doctor['clinic_end_time'],
    //                 'clinic_address' => $doctor['address'],
    //                 'clinic_location' => $doctor['location'],
    //                 'clinic_main_image' => isset($doctor['clinic_main_image']) ? asset("{$doctor['clinic_main_image']}") : null,
    //                 'clinic_location' => $doctor['location'],
    //                 'clinic_description' => $doctor['clinic_description'],
    //                 'total_token_Count' => $total_token_count,
    //                 'available_token_count' => $available_token_count,
    //                 'next_available_token_time' => $next_available_token_time,
    //                 'next_date_available_token_time' => $next_date_available_token_time,
    //                 'distance_from_clinic' => $clinicDetails['distance'],
    //                 'consultation_fee' => $consultation_fee,
    //             ];
    //             $clinicExists = false;
    //             foreach ($doctersWithSpecifications[$id]['clinics'] as $clinic) {
    //                 if ($clinic['clinic_id'] == $clinicDetails['clinic_id']) {
    //                     $clinicExists = true;
    //                     break;
    //                 }
    //             }
    //             if (!$clinicExists) {
    //                 $doctersWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //             }
    //         }

    //     //   $doctersWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //     // usort($doctersWithSpecifications, function ($a, $b) {
    //     //     return $a['distance_from_user'] <=> $b['distance_from_user'];
    //     // });
    //     $formattedOutput = array_values($doctersWithSpecifications);
    //     return $this->sendResponse("All Doctors", $formattedOutput, '1', 'Doctors retrieved successfully.');
    // }

    public function index()
    {
        $authenticatedUserId = auth()->user()->id;
        $user_id = $authenticatedUserId;
        $specializeArray['specialize'] = Specialize::all();
        $specificationArray['specification'] = Specification::all();
        $subspecificationArray['subspecification'] = Subspecification::all();
        $docters = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
            ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
            ->select(
                'docter.UserId',
                'docter.id',
                'docter.docter_image',
                'docter.firstname',
                'docter.lastname',
                'docter.latitude',
                'docter.longitude',
                'docter.specialization_id',
                'docter.subspecification_id',
                'docter.specification_id',
                'docter.about',
                'docter.location',
                'clinics.clinic_id as avaliblityId',
                'docter.gender',
                'docter.email',
                'docter.mobileNo',
                'docter.Services_at',
                'clinics.clinic_name',
                'clinics.clinic_start_time',
                'clinics.clinic_end_time',
                'clinics.address',
                'clinics.location',
                'clinics.clinic_description',
                'clinics.clinic_main_image',
                'clinics.latitude as clinic_latitude',
                'clinics.longitude as clinic_longitude'
            )
            ->get();

        $doctersWithSpecifications = [];

        // User location
        $current_location_data = UserLocationHelper::getUserCurrentLocation($user_id);
        $user_latitude = $current_location_data ? $current_location_data->latitude : null;
        $user_longitude = $current_location_data ? $current_location_data->longitude : null;

        $distanceHelper = new \App\Helpers\UserDistanceHelper();

        foreach ($docters as $doctor) {
            $id = $doctor['id'];
            $favoriteStatus = DB::table('addfavourite')
                ->where('UserId', $authenticatedUserId)
                ->where('doctor_id', $doctor['UserId'])
                ->exists();

            if (!isset($doctersWithSpecifications[$id])) {
                $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);
                $doctersWithSpecifications[$id] = [
                    'id' => $id,
                    'UserId' => $doctor['UserId'],
                    'firstname' => $doctor['firstname'],
                    'secondname' => $doctor['lastname'],
                    'Specialization' => $specialize ? $specialize['specialization'] : null,
                    'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
                    'Location' => $doctor['location'],
                    'MainHospital' => $doctor['Services_at'],
                    'nearest_doctor_clinic' => null,
                    'clinics' => [],
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            $clinicDetails = [];
            $clinicDetails['distance'] = $distanceHelper->calculateHaversineDistance(
                $user_latitude,
                $user_longitude,
                $doctor->clinic_latitude,
                $doctor->clinic_longitude
            );

            $current_date = Carbon::now()->toDateString();
            $current_time = Carbon::now()->toDateTimeString();

            $total_token_count = NewTokens::where('clinic_id', $doctor->avaliblityId)
                ->where('token_scheduled_date', $current_date)
                ->where('doctor_id', $id)
                ->count();

            $available_token_count = NewTokens::where('clinic_id', $doctor->avaliblityId)
                ->where('token_scheduled_date', $current_date)
                ->where('token_booking_status', NULL)
                ->where('token_start_time', '>', $current_time)
                ->where('doctor_id', $id)
                ->count();

            $next_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
                ->where('token_scheduled_date', $current_date)
                ->where('token_booking_status', null)
                ->where('token_start_time', '>', $current_time)
                ->where('doctor_id', $doctor->id)
                ->orderBy('token_start_time', 'ASC')
                ->value('token_start_time');

            $consultation_fee = ClinicConsultation::where('doctor_id', $id)
                ->where('clinic_id', $doctor->avaliblityId)
                ->value('consultation_fee');
            $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id', $doctor['avaliblityId'])
                ->where('token_scheduled_date', $current_date)
                ->orderBy('token_start_time', 'ASC')
                ->first();
            $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id',  $doctor['avaliblityId'])
                ->where('token_scheduled_date', $current_date)
                ->orderBy('token_start_time', 'DESC')
                ->first();
            if ($schedule_start_data && $schedule_end_data) {
                $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
            } else {
                $start_time = null;
                $end_time = null;
            }
            $next_date_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
                ->where('token_scheduled_date', '>', $current_date)
                ->where('token_booking_status', NULL)
                ->orderBy('token_scheduled_date', 'ASC')
                ->where('token_start_time', '>', $current_time)
                ->where('doctor_id', $doctor->id)
                ->orderBy('token_start_time', 'ASC')
                ->value('token_start_time');

            $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('d M h:i A') : null;
            $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

            $clinicDetails = [
                'clinic_id' => $doctor['avaliblityId'],
                'clinic_name' => $doctor['clinic_name'],
                'clinic_start_time' => $start_time,
                'clinic_end_time' =>  $end_time,
                'clinic_address' => $doctor['address'],
                'clinic_location' => $doctor['location'],
                'clinic_main_image' => isset($doctor['clinic_main_image']) ? asset("{$doctor['clinic_main_image']}") : null,
                'clinic_description' => $doctor['clinic_description'],
                'total_token_Count' => $total_token_count,
                'available_token_count' => $available_token_count,
                'next_available_token_time' => $next_available_token_time,
                'next_date_available_token_time' => $next_date_available_token_time,
                'distance_from_clinic' => $clinicDetails['distance'],
                'consultation_fee' => $consultation_fee,
            ];

            $clinicExists = false;
            foreach ($doctersWithSpecifications[$id]['clinics'] as $clinic) {
                if ($clinic['clinic_id'] == $clinicDetails['clinic_id']) {
                    $clinicExists = true;
                    break;
                }
            }

            if (!$clinicExists) {
                $doctersWithSpecifications[$id]['clinics'][] = $clinicDetails;
            }
            // find and set the distance to the nearest clinic
            $nearestClinic = collect($doctersWithSpecifications[$id]['clinics'])->sortBy('distance_from_clinic')->first();
            $doctersWithSpecifications[$id]['nearest_doctor_clinic'] = $nearestClinic['distance_from_clinic'];
        }
        usort($doctersWithSpecifications, function ($a, $b) {
            return $a['nearest_doctor_clinic'] <=> $b['nearest_doctor_clinic'];
        });


        $formattedOutput = array_values($doctersWithSpecifications);
        return $this->sendResponse("All Doctors", $formattedOutput, '1', 'Doctors retrieved successfully.');
    }



    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $input = $request->all();

            // print_r($input);
            // exit;

            $emailExists = Docter::where('email', $input['email'])->count();
            $emailExistsinUser = User::where('email', $input['email'])->count();

            if ($emailExists && $emailExistsinUser) {
                return $this->sendResponse("Docters", null, '3', 'Email already exists.');
            }

            $input['password'] = Hash::make($input['password']);

            $userId = DB::table('users')->insertGetId([
                'firstname' => $input['firstname'],
                'secondname' => $input['secondname'],
                'email' => $input['email'],
                'password' => $input['password'],
                'user_role' => 2,
            ]);

            // $doctor_id =

            $DocterData = [

                'firstname' => $input['firstname'],
                'lastname' => $input['secondname'],
                'mobileNo' => $input['mobileNo'],
                'email' => $input['email'],
                'location' => $input['location'],
                'specification_id' => $input['specification_id'],
                'subspecification_id' => $input['subspecification_id'],
                'specialization_id' => $input['specialization_id'],
                'about' => $input['about'],
                'Services_at' => $input['service_at'],
                'gender' => $input['gender'],
                'UserId' => $userId,
                'mediezy_doctor_id' => $this->generateDoctorUniqueId(),
            ];

            if ($request->hasFile('docter_image')) {
                $imageFile = $request->file('docter_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('DocterImages/images'), $imageName);

                    $DocterData['docter_image'] = $imageName;
                }
            }

            $Docter = new Docter($DocterData);
            $Docter->save();
            $hospitalData = json_decode($input['hospitals'], true); // Decode the JSON string

            // Create DocterAvailability records
            if (is_array($hospitalData)) {
                foreach ($hospitalData as $hospital) {
                    $availabilityData = [
                        'docter_id' => $Docter->id,
                        'hospital_Name' => $hospital['hospitalName'],
                        'startingTime' => $hospital['startingTime'],
                        'endingTime' => $hospital['endingTime'],
                        'address' => $hospital['hospitalAddress'],
                        'location' => $hospital['hospitalLocation'],
                    ];

                    // Create and save DocterAvailability records
                    $docterAvailability = new DocterAvailability($availabilityData);


                    $docterAvailability->save();
                }
            }

            DB::commit();

            return $this->sendResponse("Docters", $Docter, '1', 'Docter created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }
    function generateDoctorUniqueId()
    {
        $uniquePatientDetails = Docter::select('mediezy_doctor_id')
            ->whereNotNull('mediezy_doctor_id')
            ->orderBy('created_at', 'desc')
            ->first();
        if ($uniquePatientDetails === null) {
            $uniquePatientDetails = [
                'mediezy_doctor_id' => 'DMA00000',
            ];
        }
        $lastUniqueId = $uniquePatientDetails->mediezy_doctor_id;
        $numericPart = (int) substr($lastUniqueId, 3) + 1;
        $newNumericPart = str_pad($numericPart, 5, '0', STR_PAD_LEFT);
        $newUniqueId = 'DMA' . $newNumericPart;
        return $newUniqueId;
    }




    // public function getDoctorProfileDetails($userId)
    // {
    //     $authenticatedUserId = auth()->user()->id;

    //     $specializeArray['specialize'] = Specialize::all();
    //     $specificationArray['specification'] = Specification::all();
    //     $subspecificationArray['subspecification'] = Subspecification::all();
    //     $doctors = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
    //         ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
    //         ->join('users', 'docter.UserId', '=', 'users.id')
    //         ->select(
    //             'docter.UserId',
    //             'docter.id',
    //             'docter.mediezy_doctor_id',
    //             'docter.docter_image',
    //             'docter.firstname',
    //             'docter.lastname',
    //             'docter.specialization_id',
    //             'docter.subspecification_id',
    //             'docter.specification_id',
    //             'docter.consultation_fee',
    //             'docter.latitude',
    //             'docter.longitude',
    //             'docter.about',
    //             'docter.location',
    //             'clinics.clinic_id as avaliblityId',
    //             'docter.gender',
    //             'docter.email',
    //             'docter.mobileNo',
    //             'docter.Services_at',
    //             'clinics.clinic_name',
    //             'clinics.clinic_start_time',
    //             'clinics.clinic_end_time',
    //             'clinics.address',
    //             'clinics.location',
    //             'clinics.clinic_description',
    //             'clinics.clinic_main_image'
    //         )
    //         ->where('users.id', $userId)
    //         ->get();

    //     $doctersWithSpecifications = [];

    //     //user location
    //     $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //     $user_latitude = $current_location_data ? $current_location_data->latitude : null;
    //     $user_longitude = $current_location_data ? $current_location_data->longitude : null;

    //     // var_dump($user_latitude);
    //     // var_dump($user_longitude);

    //     foreach ($doctors as $doctor) {
    //         $id = $doctor['id'];
    //         $favoriteStatus = DB::table('addfavourite')
    //             ->where('UserId', $authenticatedUserId)
    //             ->where('doctor_id', $doctor['UserId'])
    //             ->exists();
    //         if (!isset($doctersWithSpecifications[$id])) {
    //             $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);
    //             //distance
    //             $doctor_latitude = $doctor['latitude'];
    //             $doctor_longitude = $doctor['longitude'];

    //             $apiKey = config('services.google.api_key');

    //             $service = new DistanceMatrixService($apiKey);
    //             $distance = $service->getDistance($user_latitude, $user_longitude, $doctor_latitude, $doctor_longitude);
    //             // var_dump($doctor['latitude']);
    //             // var_dump($doctor['longitude']);
    //             $doctersWithSpecifications[$id] = [
    //                 'id' => $id,
    //                 'mediezy_doctor_id' => $doctor['mediezy_doctor_id'],
    //                 'distance_from_user' => $distance,
    //                 'UserId' => $doctor['UserId'],
    //                 'firstname' => $doctor['firstname'],
    //                 'secondname' => $doctor['lastname'],
    //                 'Specialization' => $specialize ? $specialize['specialization'] : null,
    //                 'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
    //                 'About' => $doctor['about'],
    //                 'Location' => $doctor['location'],
    //                 'Gender' => $doctor['gender'],
    //                 'emailID' => $doctor['email'],
    //                 'Mobile Number' => $doctor['mobileNo'],
    //                 'MainHospital' => $doctor['Services_at'],
    //                 'consulation_fees' => $doctor['consultation_fee'],
    //                 'specifications' => [],
    //                 'subspecifications' => [],
    //                 'clinics' => [],
    //                 'favoriteStatus' => $favoriteStatus ? 1 : 0,


    //             ];
    //         }
    //         $specificationIds = explode(',', $doctor['specification_id']);
    //         $subspecificationIds = explode(',', $doctor['subspecification_id']);
    //         $specifications = array_map(function ($id) use ($specificationArray) {
    //             return $specificationArray['specification']->firstWhere('id', $id)['specification'];
    //         }, $specificationIds);
    //         $subspecifications = array_map(function ($id) use ($subspecificationArray) {
    //             return $subspecificationArray['subspecification']->firstWhere('id', $id)['subspecification'];
    //         }, $subspecificationIds);
    //         $doctersWithSpecifications[$id]['specifications'] = array_values(array_unique(array_merge(
    //             $doctersWithSpecifications[$id]['specifications'],
    //             $specifications
    //         )));
    //         $doctersWithSpecifications[$id]['subspecifications'] = array_values(array_unique(array_merge(
    //             $doctersWithSpecifications[$id]['subspecifications'],
    //             $subspecifications
    //         )));

    //         //// clinic doctor relations for token count
    //         $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();
    //         $clinic_ids = DoctorClinicRelation::where('doctor_id', $doctor_id)
    //             ->distinct()
    //             ->pluck('clinic_id');

    //         ///// end
    //         foreach ($clinic_ids as $clinic_id) {
    //             $current_date = Carbon::now()->toDateString();
    //             $current_time = Carbon::now()->toDateTimeString();


    //             $total_token_count = NewTokens::where('clinic_id', $doctor['avaliblityId'])
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('doctor_id', $doctor_id)
    //                 ->count();

    //             $available_token_count = NewTokens::where('clinic_id', $doctor['avaliblityId'])
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('token_booking_status', NULL)
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor_id)
    //                 ->count();

    //             //schedule details

    //             $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

    //             $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
    //                 ->where('doctor_id', $doctor_id)
    //                 ->where('clinic_id', $doctor['avaliblityId'])
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->first();


    //             $current_date = Carbon::now()->toDateString();
    //             $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
    //                 ->where('doctor_id', $id)
    //                 ->where('clinic_id', $doctor['avaliblityId'])
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->first();


    //             $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
    //                 ->where('doctor_id', $id)
    //                 ->where('clinic_id',  $doctor['avaliblityId'])
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->orderBy('token_start_time', 'DESC')
    //                 ->first();

    //             if ($schedule_start_data && $schedule_end_data) {
    //                 $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
    //                 $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
    //             } else {

    //                 $start_time = null;
    //                 $end_time = null;
    //             }
    //             $next_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('token_booking_status', null)
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->value('token_start_time');

    //             $next_date_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
    //                 ->where('token_scheduled_date', '>', $current_date)
    //                 ->where('token_booking_status', NULL)
    //                 ->orderBy('token_scheduled_date', 'ASC')
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->value('token_start_time');
    //            // $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('y-m-d h:i A') : null;
    //            $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('d M h:i A') : null;
    //            $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;
    //             $clinicDetails = [
    //                 'clinic_id' => $doctor['avaliblityId'],
    //                 'clinic_name' => $doctor['clinic_name'],
    //                 'clinic_start_time' => $start_time,
    //                 'clinic_end_time' => $end_time,
    //                 'clinic_address' => $doctor['address'],
    //                 'clinic_location' => $doctor['location'],
    //                 'clinic_main_image' => isset($doctor['clinic_main_image']) ? asset("{$doctor['clinic_main_image']}") : null,
    //                 'clinic_location' => $doctor['location'],
    //                 'clinic_description' => $doctor['clinic_description'],
    //                 'total_token_Count' => $total_token_count,
    //                 'available_token_count' => $available_token_count,
    //                 'next_available_token_time' => $next_available_token_time,
    //                 'next_date_available_token_time' => $next_date_available_token_time,
    //             ];
    //             $clinicExists = false;
    //             foreach ($doctersWithSpecifications[$id]['clinics'] as $clinic) {
    //                 if ($clinic['clinic_id'] == $clinicDetails['clinic_id']) {
    //                     $clinicExists = true;
    //                     break;
    //                 }
    //             }
    //             if (!$clinicExists) {
    //                 $doctersWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //             }
    //         }
    //     }
    //     ////first token generated clinic show first
    //     usort($doctersWithSpecifications[$id]['clinics'], function ($clinicA, $clinicB) {
    //         return $clinicB['available_token_count'] <=> $clinicA['available_token_count'];
    //     });
    //     //   $doctersWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //     usort($doctersWithSpecifications, function ($a, $b) {
    //         return $a['distance_from_user'] <=> $b['distance_from_user'];
    //     });
    //     $formattedOutput = array_values($doctersWithSpecifications);
    //     return $this->sendResponse("Doctor Details", $formattedOutput, '1', 'Doctors retrieved successfully.');
    // }

    public function getDoctorProfileDetails($userId)
    {
        $authenticatedUserId = auth()->user()->id;
        $specializeArray['specialize'] = Specialize::all();
        $specificationArray['specification'] = Specification::all();
        $subspecificationArray['subspecification'] = Subspecification::all();
        $doctors = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
            ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
            ->join('users', 'docter.UserId', '=', 'users.id')
            ->select(
                'docter.UserId',
                'docter.id',
                'docter.mediezy_doctor_id',
                'docter.docter_image',
                'docter.firstname',
                'docter.lastname',
                'docter.specialization_id',
                'docter.subspecification_id',
                'docter.specification_id',
                'docter.consultation_fee',
                 DB::raw("CONCAT(docter.main_qualification, ',', docter.other_qualification) AS qualification"),
                'docter.latitude',
                'docter.longitude',
                'docter.about',
                'docter.location',
                'clinics.clinic_id as avaliblityId',
                'docter.gender',
                'docter.email',
                'docter.mobileNo',
                'docter.Services_at',
                'clinics.clinic_name',
                'clinics.clinic_start_time',
                'clinics.clinic_end_time',
                'clinics.address',
                'clinics.location',
                'clinics.clinic_description',
                'clinics.clinic_main_image',
                'clinics.latitude as clinic_latitude',
                'clinics.longitude as clinic_longitude'
            )
            ->where('users.id', $userId)
            ->get();

        $doctersWithSpecifications = [];

        /// user location
        $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
        $user_latitude = $current_location_data ? $current_location_data->latitude : null;
        $user_longitude = $current_location_data ? $current_location_data->longitude : null;

        $distanceHelper = new \App\Helpers\UserDistanceHelper();
        ///
        foreach ($doctors as $doctor) {
            $id = $doctor['id'];
            $favoriteStatus = DB::table('addfavourite')
                ->where('UserId', $authenticatedUserId)
                ->where('doctor_id', $doctor['UserId'])
                ->exists();
            if (!isset($doctersWithSpecifications[$id])) {
                $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);
                $doctersWithSpecifications[$id] = [
                    'id' => $id,
                    'mediezy_doctor_id' => $doctor['mediezy_doctor_id'],
                    'UserId' => $doctor['UserId'],
                    'firstname' => $doctor['firstname'],
                    'secondname' => $doctor['lastname'],
                    'Specialization' => $specialize ? $specialize['specialization'] : null,
                    'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
                    'qualification' => $doctor['qualification'],
                    'About' => $doctor['about'],
                    'Location' => $doctor['location'],
                    'Gender' => $doctor['gender'],
                    'emailID' => $doctor['email'],
                    'Mobile Number' => $doctor['mobileNo'],
                    'MainHospital' => $doctor['Services_at'],
                    'consulation_fees' => $doctor['consultation_fee'],
                    'specifications' => [],
                    'subspecifications' => [],
                    'clinics' => [],
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            $specificationIds = explode(',', $doctor['specification_id']);
            $subspecificationIds = explode(',', $doctor['subspecification_id']);
            $specifications = array_map(function ($id) use ($specificationArray) {
                return $specificationArray['specification']->firstWhere('id', $id)['specification'];
            }, $specificationIds);
            $subspecifications = array_map(function ($id) use ($subspecificationArray) {
                return $subspecificationArray['subspecification']->firstWhere('id', $id)['subspecification'];
            }, $subspecificationIds);
            $doctersWithSpecifications[$id]['specifications'] = array_values(array_unique(array_merge(
                $doctersWithSpecifications[$id]['specifications'],
                $specifications
            )));
            $doctersWithSpecifications[$id]['subspecifications'] = array_values(array_unique(array_merge(
                $doctersWithSpecifications[$id]['subspecifications'],
                $subspecifications
            )));

            $clinic_ids = DoctorClinicRelation::where('doctor_id', $doctor->id)
                ->distinct()
                ->pluck('clinic_id');

            foreach ($clinic_ids as $clinic_id) {
                /////clinic and user lattitude and langitude
                $clinicDetails = [];
                $clinicDetails['distance'] = $distanceHelper->calculateHaversineDistance(
                    $user_latitude,
                    $user_longitude,
                    $doctor->clinic_latitude,
                    $doctor->clinic_longitude
                );

                $current_date = Carbon::now()->toDateString();
                $current_time = Carbon::now()->toDateTimeString();

                $total_token_count = NewTokens::where('clinic_id', $doctor['avaliblityId'])
                    ->where('token_scheduled_date', $current_date)
                    ->where('doctor_id', $doctor->id)
                    ->count();

                $available_token_count = NewTokens::where('clinic_id', $doctor['avaliblityId'])
                    ->where('token_scheduled_date', $current_date)
                    ->where('token_booking_status', NULL)
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $doctor->id)
                    ->count();

                $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $doctor->id)
                    ->where('clinic_id', $doctor['avaliblityId'])
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'ASC')
                    ->first();
                $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $doctor->id)
                    ->where('clinic_id',  $doctor['avaliblityId'])
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'DESC')
                    ->first();

                if ($schedule_start_data && $schedule_end_data) {
                    $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                    $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
                } else {
                    $start_time = null;
                    $end_time = null;
                }

                $consultation_fee = ClinicConsultation::where('doctor_id', $doctor->id)
                    ->where('clinic_id', $doctor->avaliblityId)
                    ->value('consultation_fee');

                $next_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
                    ->where('token_scheduled_date', $current_date)
                    ->where('token_booking_status', null)
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $doctor->id)
                    ->orderBy('token_start_time', 'ASC')
                    ->value('token_start_time');

                $next_date_available_token_time = NewTokens::where('clinic_id', $doctor->avaliblityId)
                    ->where('token_scheduled_date', '>', $current_date)
                    ->where('token_booking_status', NULL)
                    ->orderBy('token_scheduled_date', 'ASC')
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $doctor->id)
                    ->orderBy('token_start_time', 'ASC')
                    ->value('token_start_time');

                if ($next_date_available_token_time) {
                    $next_date_token_carbon = Carbon::createFromFormat('Y-m-d H:i:s', $next_date_available_token_time);
                    if ($next_date_token_carbon->isTomorrow()) {
                        $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A') . " Tomorrow ";
                    } else {
                        $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A d M');
                    }
                } else {
                    $formatted_next_date_available_token_time = null;
                }

                $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

                $clinicDetails = [
                    'clinic_id' => $doctor['avaliblityId'],
                    'clinic_name' => $doctor['clinic_name'],
                    'clinic_start_time' => $start_time,
                    'clinic_end_time' => $end_time,
                    'clinic_address' => $doctor['address'],
                    'clinic_location' => $doctor['location'],
                    'clinic_main_image' => isset($doctor['clinic_main_image']) ? asset("{$doctor['clinic_main_image']}") : null,
                    'clinic_description' => $doctor['clinic_description'],
                    'total_token_Count' => $total_token_count,
                    'available_token_count' => $available_token_count,
                    'next_available_token_time' => $next_available_token_time,
                    'next_date_available_token_time' => $formatted_next_date_available_token_time,
                    'distance_from_clinic' => $clinicDetails['distance'],
                    'consultation_fee' => $consultation_fee,
                ];

                $clinicExists = false;
                foreach ($doctersWithSpecifications[$id]['clinics'] as $clinic) {
                    if ($clinic['clinic_id'] == $clinicDetails['clinic_id']) {
                        $clinicExists = true;
                        break;
                    }
                }
                if (!$clinicExists) {
                    $doctersWithSpecifications[$id]['clinics'][] = $clinicDetails;
                }
            }
        }

        // Sort clinics within each doctor based on token count and distance
        foreach ($doctersWithSpecifications as &$doctorDetails) {
            usort($doctorDetails['clinics'], function ($clinicA, $clinicB) {
                return $clinicB['available_token_count'] <=> $clinicA['available_token_count'];
            });
        }
        /////sort clinics based on distance
        // foreach ($doctersWithSpecifications as &$doctorDetails) {
        //     usort($doctorDetails['clinics'], function ($a, $b) {
        //         return $a['distance_from_clinic'] <=> $b['distance_from_clinic'];
        //     });
        // }

        $formattedOutput = array_values($doctersWithSpecifications);
        return $this->sendResponse("Doctor Details", $formattedOutput, '1', 'Doctors retrieved successfully.');
    }

    public function getDoctorsBySpecialization($specializationId)
    {
        $authenticatedUserId = auth()->user()->id;
        $specializeArray['specialize'] = Specialize::all();
        $specificationArray['specification'] = Specification::all();
        $subspecificationArray['subspecification'] = Subspecification::all();

        $specialization = Specialize::findOrFail($specializationId);
        $doctors = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
            ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
            ->where('docter.specialization_id', $specializationId)
            ->select(
                'docter.UserId',
                'docter.id',
                'docter.docter_image',
                'docter.firstname',
                'docter.lastname',
                'docter.specialization_id',
                'docter.subspecification_id',
                'docter.specification_id',
                'docter.about',
                'docter.location',
                'docter.latitude',
                'docter.longitude',
                'clinics.clinic_id as avaliblityId',
                'docter.gender',
                'docter.email',
                'docter.mobileNo',
                'docter.Services_at',
                'clinics.clinic_name',
                'clinics.clinic_start_time',
                'clinics.clinic_end_time',
                'clinics.address',
                'clinics.location',
                'clinics.clinic_description',
                'clinics.clinic_main_image'
            )
            ->get();
        //user location
        $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
        $user_latitude = $current_location_data ? $current_location_data->latitude : null;
        $user_longitude = $current_location_data ? $current_location_data->longitude : null;
        $doctorsWithSpecifications = [];
        foreach ($doctors as $doc) {
            $id = $doc['id'];
            //distance
            $doctor_latitude = $doc['latitude'];
            $doctor_longitude = $doc['longitude'];

            $apiKey = config('services.google.api_key');
            $service = new DistanceMatrixService($apiKey);
            $distance = $service->getDistance($user_latitude, $user_longitude, $doctor_latitude, $doctor_longitude);
            //     var_dump($doc['latitude']);
            //   var_dump($doc['longitude']);
            if (!isset($doctorsWithSpecifications[$id])) {
                $specialize = $specializeArray['specialize']->firstWhere('id', $doc['specialization_id']);
                $doctorsWithSpecifications[$id] = [
                    'id' => $id,
                    'UserId' => $doc['UserId'],
                    'firstname' => $doc['firstname'],
                    'secondname' => $doc['lastname'],
                    'distance_from_user' => $distance,
                    'Specialization' => $specialize ? $specialize['specialization'] : null,
                    'DocterImage' => asset("DocterImages/images/{$doc['docter_image']}"),
                    'Location' => $doc['location'],
                    'MainHospital' => $doc['Services_at'],
                    'clinics' => [],
                ];
            }

            //schedule availabilty and token counts

            $current_date = Carbon::now()->toDateString();
            $current_time = Carbon::now()->toDateTimeString();


            $total_token_count = NewTokens::where('clinic_id', $doc['avaliblityId'])
                ->where('token_scheduled_date', $current_date)
                ->where('doctor_id', $id)
                ->count();

            $available_token_count = NewTokens::where('clinic_id', $doc['avaliblityId'])
                ->where('token_scheduled_date', $current_date)
                ->where('token_booking_status', NULL)
                ->where('token_start_time', '>', $current_time)
                ->where('doctor_id', $id)
                ->count();

            //schedule details

            // $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

            $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                ->where('doctor_id', $id)
                ->where('clinic_id', $doc['avaliblityId'])
                ->where('token_scheduled_date', $current_date)
                ->orderBy('token_start_time', 'ASC')
                ->first();


            $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                ->where('doctor_id', $id)
                ->where('clinic_id', $doc['avaliblityId'])
                ->where('token_scheduled_date', $current_date)
                ->orderBy('token_start_time', 'DESC')
                ->first();

            if ($schedule_start_data && $schedule_end_data) {
                $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
            } else {

                $start_time = null;
                $end_time = null;
            }
            $next_available_token_time = NewTokens::where('clinic_id', $doc->avaliblityId)
                ->where('token_scheduled_date', $current_date)
                ->where('token_booking_status', null)
                ->where('token_start_time', '>', $current_time)
                ->where('doctor_id', $doc->id)
                ->orderBy('token_start_time', 'ASC')
                ->value('token_start_time');

            $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

            //

            $clinicDetails = [
                'clinic_id' => $doc['avaliblityId'],
                'clinic_name' => $doc['clinic_name'],
                'clinic_start_time' => $start_time,
                'clinic_end_time' => $end_time,
                'clinic_address' => $doc['address'],
                'clinic_location' => $doc['location'],
                'clinic_main_image' => isset($doc['clinic_main_image']) ? asset("clinic_images/{$doc['clinic_main_image']}") : null,
                'clinic_description' => $doc['clinic_description'],
                'total_token_Count' => $total_token_count,
                'available_token_count' => $available_token_count,
                'next_available_token_time' => $next_available_token_time,
            ];

            $doctorsWithSpecifications[$id]['clinics'][] = $clinicDetails;
        }
        usort($doctorsWithSpecifications, function ($a, $b) {
            return $a['distance_from_user'] <=> $b['distance_from_user'];
        });
        $formattedOutput = array_values($doctorsWithSpecifications);
        return $this->sendResponse("Doctor_By_Specialization", $formattedOutput, '1', 'Doctor By Specialization retrieved successfully.');
    }

    // public function getDoctorsBySpecialization($specializationId)
    // {
    //     $specializeArray['specialize'] = Specialize::all();
    //     $specificationArray['specification'] = Specification::all();
    //     $subspecificationArray['subspecification'] = Subspecification::all();

    //     $specialization = Specialize::findOrFail($specializationId);
    //     $doctors = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
    //         ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
    //         ->where('docter.specialization_id', $specializationId)
    //         ->select(
    //             'docter.UserId',
    //             'docter.id',
    //             'docter.docter_image',
    //             'docter.firstname',
    //             'docter.lastname',
    //             'docter.specialization_id',
    //             'docter.subspecification_id',
    //             'docter.specification_id',
    //             'docter.about',
    //             'docter.location',
    //             'clinics.clinic_id as avaliblityId',
    //             'docter.gender',
    //             'docter.email',
    //             'docter.mobileNo',
    //             'docter.Services_at',
    //             'clinics.clinic_name',
    //             'clinics.clinic_start_time',
    //             'clinics.clinic_end_time',
    //             'clinics.address',
    //             'clinics.location',
    //             'clinics.clinic_description',
    //             'clinics.clinic_main_image'
    //         )
    //         ->get();

    //     $doctorsWithSpecifications = [];
    //     $current_date = now()->toDateString(); // Define $current_date here
    //     $current_time = now()->toDateTimeString(); // Define $current_time here

    //     foreach ($doctors as $doc) {
    //         $id = $doc['id'];

    //         // Initialize $doctorsWithSpecifications[$id] if not already initialized
    //         if (!isset($doctorsWithSpecifications[$id])) {
    //             $doctorsWithSpecifications[$id] = [
    //                 'clinics' => [],
    //             ];
    //         }

    //         // Inside the loop, use $current_date for token counts and schedule details
    //         $total_token_count = NewTokens::where('clinic_id', $doc->avaliblityId)
    //             ->where('token_scheduled_date', $current_date)
    //             ->where('doctor_id', $id)
    //             ->count();

    //         $available_token_count = NewTokens::where('clinic_id', $doc->avaliblityId)
    //             ->where('token_scheduled_date', $current_date)
    //             ->where('token_booking_status', null)
    //             ->where('token_start_time', '>', $current_time)
    //             ->where('doctor_id', $id)
    //             ->count();

    //         // Schedule details
    //         $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
    //             ->where('doctor_id', $id)
    //             ->where('clinic_id', $doc->avaliblityId)
    //             ->where('token_scheduled_date', $current_date)
    //             ->orderBy('token_start_time', 'ASC')
    //             ->first();

    //         $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
    //             ->where('doctor_id', $id)
    //             ->where('clinic_id', $doc->avaliblityId)
    //             ->where('token_scheduled_date', $current_date)
    //             ->orderBy('token_start_time', 'DESC')
    //             ->first();

    //         $start_time = null;
    //         $end_time = null;

    //         if ($schedule_start_data && $schedule_end_data) {
    //             $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
    //             $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
    //         }

    //         $next_available_token_time = NewTokens::where('clinic_id', $doc->avaliblityId)
    //             ->where('token_scheduled_date', $current_date)
    //             ->where('token_booking_status', null)
    //             ->where('token_start_time', '>', $current_time)
    //             ->where('doctor_id', $doc->id)
    //             ->orderBy('token_start_time', 'ASC')
    //             ->value('token_start_time');

    //         $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

    //         // Clinic details
    //         $clinicDetails = [
    //             'clinic_id' => $doc['avaliblityId'],
    //             'clinic_name' => $doc['clinic_name'],
    //             'clinic_start_time' => $start_time,
    //             'clinic_end_time' => $end_time,
    //             'clinic_address' => $doc['address'],
    //             'clinic_location' => $doc['location'],
    //             'clinic_main_image' => isset($doc['clinic_main_image']) ? asset("clinic_images/{$doc['clinic_main_image']}") : null,
    //             'clinic_description' => $doc['clinic_description'],
    //             'total_token_Count' => $total_token_count,
    //             'available_token_count' => $available_token_count,
    //             'next_available_token_time' => $next_available_token_time,
    //         ];

    //         // Check if the clinic already exists in the list
    //         $clinicExists = false;
    //         foreach ($doctorsWithSpecifications[$id]['clinics'] as $clinic) {
    //             if ($clinic['clinic_id'] == $clinicDetails['clinic_id']) {
    //                 $clinicExists = true;
    //                 break;
    //             }
    //         }

    //         // If clinic does not exist, add it to the list
    //         if (!$clinicExists) {
    //             $doctorsWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //         }
    //     }

    //     $formattedOutput = array_values($doctorsWithSpecifications);

    //     return $this->sendResponse("Doctor Details", $formattedOutput, '1', 'Docters retrieved successfully.');
    // }


    public function update(Request $request, $userId)
    {
        try {
            DB::beginTransaction();


            $docter = Docter::where('UserId', $userId)->first();

            if (is_null($docter)) {
                return $this->sendError('Docter not found.');
            }

            $input = $request->all();

            // Update fields as needed
            $docter->firstname = $input['firstname'] ?? $docter->firstname;
            $docter->lastname = $input['lastname'] ?? $docter->lastname;
            $docter->mobileNo = $input['mobileNo'] ?? $docter->mobileNo;
            $docter->email = $input['email'] ?? $docter->email;
            $docter->location = $input['location'] ?? $docter->location;

            // Handle image upload if a new image is provided
            if ($request->hasFile('docter_image')) {
                $imageFile = $request->file('docter_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('DocterImages/images'), $imageName);

                    $docter->docter_image = $imageName;
                }
            }
            $docter->save();
            $user = User::find($docter->UserId);
            if (!is_null($user)) {
                $user->firstname = $input['firstname'] ?? $user->firstname;
                $user->secondname = $input['lastname'] ?? $user->secondname;
                $user->mobileNo = $input['mobileNo'] ?? $user->mobileNo;
                $user->email = $input['email'] ?? $user->email;
                $user->save();
            }
            DB::commit();
            // Include UserId in the response
            $response = [
                'success' => true,
                'UserId' => $user->id,
                'Docter' => $docter,
                'code' => '1',
                'message' => 'Docter updated successfully.'
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }


    // public function doctorUpdate(Request $request, $userId)
    // {
    //     try {

    //         $docter = Docter::where('UserId', $userId)->first();

    //         if (is_null($docter)) {
    //             return $this->sendError('Docter not found.');
    //         }

    //         $input = $request->all();
    //         $docter->firstname = $input['firstname'] ?? $docter->firstname;
    //         $docter->lastname = $input['lastname'] ?? $docter->lastname;
    //         $docter->mobileNo = $input['mobileNo'] ?? $docter->mobileNo;
    //         $docter->email = $input['email'] ?? $docter->email;
    //         $docter->location = $input['location'] ?? $docter->location;
    //         if ($request->hasFile('docter_image')) {
    //             $imageFile = $request->file('docter_image');

    //             if ($imageFile->isValid()) {
    //                 $imageName = $imageFile->getClientOriginalName();
    //                 $imageFile->move(public_path('DocterImages/images'), $imageName);

    //                 $docter->docter_image = $imageName;
    //             }
    //         }
    //         $docter->save();
    //         $user = User::find($docter->UserId);
    //         if (!is_null($user)) {
    //             $user->firstname = $input['firstname'] ?? $user->firstname;
    //             $user->secondname = $input['lastname'] ?? $user->secondname;
    //             $user->mobileNo = $input['mobileNo'] ?? $user->mobileNo;
    //             $user->email = $input['email'] ?? $user->email;
    //             $user->save();
    //         }
    //         $response = [
    //             'success' => true,
    //             'UserId' => $user->id,
    //             'Docter' => $docter,
    //             'code' => '1',
    //             'message' => 'Docter updated successfully.'
    //         ];

    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
    //     }
    // }

    public function doctorUpdate(Request $request, $userId)
    {
        try {
            $docter = Docter::where('UserId', $userId)->first();
            if (is_null($docter)) {
                return $this->sendError('Docter not found.');
            }
            // dd($request->all());
            $input = $request->all();
            $docter->firstname = $input['firstname'] ?? $docter->firstname;
            $docter->lastname = $input['lastname'] ?? $docter->lastname;
            $docter->mobileNo = $input['mobileNo'] ?? $docter->mobileNo;
            $docter->email = $input['email'] ?? $docter->email;
            $docter->location = $input['location'] ?? $docter->location;
            if ($request->hasFile('docter_image')) {
                $imageFile = $request->file('docter_image');
                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('DocterImages/images'), $imageName);
                    $docter->docter_image = $imageName;
                }
            }
            $docter->save();
            $user = User::find($docter->UserId);
            if (!is_null($user)) {
                $user->firstname = $input['firstname'] ?? $user->firstname;
                $user->secondname = $input['lastname'] ?? $user->secondname;
                $user->mobileNo = $input['mobileNo'] ?? $user->mobileNo;
                $user->email = $input['email'] ?? $user->email;
                $user->save();
            }
            Log::info($request->all());
            $response = [
                'success' => true,
                'user_id' => $user->id,
                'doctor_name' => $user->firstname . ' ' . $user->secondname,
                'email' => $user->email,
                'phone_number' => $user->mobileNo,
                'doctor_image' => $docter->docter_image ? asset("DocterImages/images/{$docter->docter_image}") : null,
                'message' => 'Doctor details updated succesfully'
            ];
            return response()->json($response);
        } catch (\Exception $e) {
            // return $this->sendError($e->getMessage(), $code = 404);
            return $this->sendError('Update failed: ' . $e->getMessage());
        }
    }

    //     public function doctorUpdate(Request $request, $userId)
    // {
    //     try {
    //         $doctor = Docter::where('UserId', $userId)->first();
    //         if (is_null($doctor)) {
    //             return $this->sendError('Doctor not found.');
    //         }

    //         $input = $request->all();
    //         $doctor->firstname = $input['firstname'] ?? $doctor->firstname;
    //         $doctor->lastname = $input['lastname'] ?? $doctor->lastname;
    //         $doctor->mobileNo = $input['mobileNo'] ?? $doctor->mobileNo;
    //         $doctor->email = $input['email'] ?? $doctor->email;
    //         $doctor->location = $input['location'] ?? $doctor->location;

    //         // Check if a new image is uploaded
    //         if ($request->hasFile('docter_image')) {
    //             $file = $request->file('docter_image');
    //             $filename = time() . '.' . $file->getClientOriginalExtension();
    //             $file->move(public_path('DoctorImages/images'), $filename);
    //             $doctor->docter_image = $filename; // assuming 'docter_image' is the column name in your database
    //         }

    //         $doctor->save();

    //         // Load user model
    //         $user = User::find($doctor->UserId);
    //         if (!is_null($user)) {
    //             $user->firstname = $input['firstname'] ?? $user->firstname;
    //             $user->secondname = $input['lastname'] ?? $user->secondname;
    //             $user->mobileNo = $input['mobileNo'] ?? $user->mobileNo;
    //             $user->email = $input['email'] ?? $user->email;
    //             $user->save();
    //         }

    //         $response = [
    //             'success' => true,
    //             'user_id' => $user->id,
    //             'doctor_name' => $user->firstname . ' ' . $user->secondname,
    //             'email' => $user->email,
    //             'phone_number' => $user->mobileNo,
    //             'docter_image' => asset("DocterImages/images/{$doctor->docter_image}"), // Updated image path
    //             'message' => 'Doctor details updated successfully'
    //         ];

    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         return $this->sendError($e->getMessage(), 404);
    //     }
    // }

    // public function doctorUpdate(Request $request, $userId)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $doctor = Docter::where('UserId', $userId)->first();
    //         if (is_null($doctor)) {
    //             return $this->sendError('Doctor not found.');
    //         }

    //         $input = $request->all();
    //         $doctor->fill([
    //             'firstname' => $input['firstname'] ?? $doctor->firstname,
    //             'lastname' => $input['lastname'] ?? $doctor->lastname,
    //             'mobileNo' => $input['mobileNo'] ?? $doctor->mobileNo,
    //             'email' => $input['email'] ?? $doctor->email,
    //             'location' => $input['location'] ?? $doctor->location
    //         ]);



    //         $doctor->save();

    //         $user = User::find($doctor->UserId);
    //         if (!is_null($user)) {
    //             $user->update([
    //                 'firstname' => $input['firstname'] ?? $user->firstname,
    //                 'secondname' => $input['lastname'] ?? $user->secondname,
    //                 'mobileNo' => $input['mobileNo'] ?? $user->mobileNo,
    //                 'email' => $input['email'] ?? $user->email,
    //             ]);
    //         }

    //         DB::commit();  // Commit transaction

    //         $response = [
    //             'success' => true,
    //             'user_id' => $user->id,
    //             'doctor_name' => $user->firstname . ' ' . $user->secondname,
    //             'email' => $user->email,
    //             'phone_number' => $user->mobileNo,

    //             'message' => 'Doctor details updated successfully'
    //         ];
    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         DB::rollback();  // Roll back on any error
    //         return $this->sendError('Failed to update doctor.', $e->getMessage());
    //     }
    // }


    public function getHospitalName($userId)
    {
        $doctor = Docter::where('UserId', $userId)->first();

        if (is_null($doctor)) {
            return response()->json(['status' => 'error', 'message' => 'Doctor not found for the given UserId'], 404);
        }
        $doctorId = $doctor->id;

        $clinicDetails = Clinic::select('clinics.clinic_id', 'clinic_name')
            ->join('doctor_clinic_relations', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
            ->where('doctor_clinic_relations.doctor_id', $doctorId)
            ->get();

        if ($clinicDetails->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Hospital details not found for the selected doctor'], 404);
        }

        $result = [
            'status' => true,
            'message' => 'Hospital details found successfully',
            'hospital_details' => $clinicDetails,
        ];

        return response()->json($result);
    }

    public function getHospitalDetailsById($hospitalId)
    {
        // Query the DocterAvailability table to get hospital details based on the provided hospitalId
        $hospitalDetails = DocterAvailability::find($hospitalId);

        if (is_null($hospitalDetails)) {
            return response()->json(['error' => 'Hospital not found for the given Hospital ID'], 404);
        }


        // Combine hospital details with doctor details
        $result = [
            'clinic_details' => $hospitalDetails,

        ];

        return response()->json($result);
    }


    public function ApproveOrReject(Request $request)
    {
        $doctorId = $request->input('doctor_id');
        $action = $request->input('action'); // 'approve' or 'reject'

        $doctor = Docter::find($doctorId);

        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        // Update the is_approve column based on the action
        if ($action == 'approve') {
            $doctor->is_approve = 1;
        } elseif ($action == 'reject') {
            $doctor->is_approve = 2;
        }
        $doctor->save();

        return response()->json(['message' => 'Doctor ' . ucfirst($action) . 'd successfully']);
    }

    public function getSymptomsBySpecialization($userId)
    {
        // Find the doctor by user ID
        $doctor = Docter::where('UserId', $userId)->first();

        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found.'], 404);
        }

        // Use a join to fetch symptoms based on the doctor's specialization and specialization_id in symtoms table
        $symptoms = Symtoms::join('docter', 'symtoms.specialization_id', '=', 'docter.specialization_id')
            ->where('docter.UserId', $doctor->UserId) // Assuming 'UserId' is the correct column name in 'docter' table
            ->get(['symtoms.*']); // Select only the columns from the 'symtoms' table

        return response()->json(['symptoms' => $symptoms], 200);
    }


    // public function getTokens(Request $request)
    // {
    //     $rules = [
    //         'doctor_id'     => 'required',
    //         'hospital_id'   => 'required',
    //         'date'          => 'required',
    //     ];
    //     $messages = [
    //         'date.required' => 'Date is required',
    //     ];
    //     $validation = Validator::make($request->all(), $rules, $messages);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }
    //     try {
    //         $docter = Docter::where('id', $request->doctor_id)->first();
    //         if (!$docter) {
    //             return response()->json(['status' => false, 'message' => 'Doctor not found']);
    //         }
    //         $shedulded_tokens =  schedule::where('docter_id', $request->doctor_id)->where('hospital_Id', $request->hospital_id)->first();
    //         if (!$shedulded_tokens) {
    //             return response()->json(['status' => false, 'message' => 'Data not found']);
    //         }
    //         $requestDate = Carbon::parse($request->date);
    //         $startDate = Carbon::parse($shedulded_tokens->date);
    //         $scheduledUptoDate = Carbon::parse($shedulded_tokens->scheduleupto);
    //         // Get the day of the week
    //         $dayOfWeek = $requestDate->format('l'); // 'l' format gives the full name of the day
    //         $allowedDaysArray = json_decode($shedulded_tokens->selecteddays);
    //         $token_booking = TokenBooking::where('date', $request->date)->where('doctor_id', $request->doctor_id)->where('clinic_id', $request->hospital_id)->get();

    //         if (!$requestDate->between($startDate, $scheduledUptoDate)) {
    //             return response()->json(['status' => true, 'token_data' => null, 'message' => 'Token not found on this date']);
    //         }

    //         if (!in_array($dayOfWeek, $allowedDaysArray)) {
    //             return response()->json(['status' => true, 'token_data' => null, 'message' => 'Token not found on this day']);
    //         }

    //         $shedulded_tokens =  schedule::select('id', 'tokens', 'date', 'hospital_Id', 'startingTime', 'endingTime')->where('docter_id', $request->doctor_id)->where('hospital_Id', $request->hospital_id)->first();
    //         $shedulded_tokens['tokens'] = json_decode($shedulded_tokens->tokens);


    //         $today_schedule = TodaySchedule::select('id', 'tokens', 'date', 'hospital_Id')->where('docter_id', $request->doctor_id)->where('hospital_Id', $request->hospital_id)->where('date', $request->date)->first();

    //         if ($today_schedule) {
    //             $today_schedule['startingTime'] = $shedulded_tokens->startingTime;
    //             $today_schedule['endingTime']   = $shedulded_tokens->endingTime;
    //             $shedulded_tokens = $today_schedule;
    //             $shedulded_tokens['tokens'] = json_decode($today_schedule->tokens);
    //         }

    //         foreach ($shedulded_tokens['tokens'] as $token) {
    //             // Set is_booked to 1 (or any other value you want)
    //             $token_booking = TokenBooking::where('date', $request->date)->where('doctor_id', $request->doctor_id)->where('clinic_id', $request->hospital_id)->where('TokenTime', $token->Time)->where('TokenNumber', $token->Number)->first();
    //             if ($token_booking) {
    //                 $token->is_booked = 1;
    //             }
    //         }
    //         return response()->json(['status' => true, 'token_data' => $shedulded_tokens]);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => "Internal Server Error"]);
    //     }
    // }


    public function getTokens(Request $request)
    {
        $rules = [
            'doctor_id'     => 'required',
            'hospital_id'   => 'required',
            'date'          => 'required',
        ];
        $messages = [
            'date.required' => 'Date is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $doctor = Docter::where('id', $request->doctor_id)->first();
            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }



            // $scheduledTokens = schedule::where('docter_id', $request->doctor_id)
            //     ->where('hospital_Id', $request->hospital_id)
            //     ->first();

            $date = $request->date;
            $scheduledTokens = Schedule::where('docter_id', $request->doctor_id)
                ->where('hospital_Id', $request->hospital_id)
                ->where(function ($query) use ($date) {
                    $query->where('date', '<=', $date)
                        ->where('scheduleupto', '>=', $date);
                })
                ->orderBy('date', 'desc')
                ->first();

            if (!$scheduledTokens) {
                return response()->json(['status' => true, 'token_data' => null, 'message' => 'Token not found on this day']);
            }
            $leaveCheck = DocterLeave::where('docter_id', $request->doctor_id)
                ->where('hospital_Id', $request->hospital_id)
                ->where('date', $request->date)
                ->exists();


            if ($leaveCheck) {
                // The doctor has a leave on the selected date
                return response()->json(['status' => true, 'token_data' => null, 'message' => 'Doctor On Emergency Leave']);
            }
            $requestDate = Carbon::parse($request->date);
            $startDate = Carbon::parse($scheduledTokens->date);
            $scheduledUptoDate = Carbon::parse($scheduledTokens->scheduleupto);

            // Get the day of the week
            $dayOfWeek = $requestDate->format('l'); // 'l' format gives the full name of the day
            $allowedDaysArray = json_decode($scheduledTokens->selecteddays);
            $tokenBooking = TokenBooking::where('date', $request->date)
                ->where('doctor_id', $request->doctor_id)
                ->where('clinic_id', $request->hospital_id)
                ->get();

            if (!$requestDate->between($startDate, $scheduledUptoDate)) {
                return response()->json(['status' => true, 'token_data' => null, 'message' => 'Token not found on this date']);
            }

            if (!in_array($dayOfWeek, $allowedDaysArray)) {
                return response()->json(['status' => true, 'token_data' => null, 'message' => 'Token not found on this day']);
            }

            $scheduledTokens = schedule::select('id', 'tokens', 'date', 'hospital_Id', 'startingTime', 'endingTime')
                ->where('docter_id', $request->doctor_id)
                ->where('hospital_Id', $request->hospital_id)
                ->where(function ($query) use ($date) {
                    $query->where('date', '<=', $date)
                        ->where('scheduleupto', '>=', $date);
                })
                ->orderBy('date', 'desc')
                ->first();

            $scheduledTokens['tokens'] = json_decode($scheduledTokens->tokens);

            $todaySchedule = TodaySchedule::select('id', 'tokens', 'date', 'hospital_Id')
                ->where('docter_id', $request->doctor_id)
                ->where('hospital_Id', $request->hospital_id)
                ->where('date', $request->date)
                ->first();

            if ($todaySchedule) {
                $todaySchedule['startingTime'] = $scheduledTokens->startingTime;
                $todaySchedule['endingTime']   = $scheduledTokens->endingTime;
                $scheduledTokens = $todaySchedule;
                $scheduledTokens['tokens'] = json_decode($todaySchedule->tokens);
            }




            $morningTokens = [];
            $eveningTokens = [];
            $currentDateTime = now();
            foreach ($scheduledTokens['tokens'] as $token) {
                // Set is_booked to 1 (or any other value you want)
                $tokenBooking = TokenBooking::where('date', $request->date)
                    ->where('doctor_id', $request->doctor_id)
                    ->where('clinic_id', $request->hospital_id)
                    ->where('TokenNumber', $token->Number)
                    ->first();

                $token->is_booked = $tokenBooking ? 1 : 0;
                $token->is_timeout  = Carbon::parse($request->date . ' ' . $token->Time) < $currentDateTime ? 1 : 0;

                // Categorize tokens into morning and evening
                if (Carbon::parse($token->Time) < Carbon::parse('13:00:00')) {
                    $morningTokens[] = $token;
                } else {
                    $eveningTokens[] = $token;
                }

                // Format the time in 12-hour format
                $token->FormatedTime = Carbon::parse($token->Time)->format('h:i A');
            }

            // Now $morningTokens and $eveningTokens will contain tokens with formatted time

            $token_Data = new \stdClass(); // Create a new object to store token data
            $token_Data->morning_tokens = $morningTokens;
            $token_Data->evening_tokens = $eveningTokens;

            return response()->json([
                'status' => true,
                'token_data' => $token_Data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "Internal Server Error"]);
        }
    }



    // public function getDoctorLeaveList(Request $request)
    // {
    //     $rules = [
    //         'doctor_id'     => 'required',
    //         'hospital_id'   => 'required',
    //     ];
    //     $messages = [
    //         'doctor_id.required' => 'Docter is required',
    //     ];
    //     $validation = Validator::make($request->all(), $rules, $messages);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }
    //     try {
    //         $leaves = DocterLeave::select('id', 'docter_id', 'hospital_id', 'date')->where('docter_id', $request->doctor_id)->where('hospital_id', $request->hospital_id)->get();
    //         if (!$leaves) {
    //             return response()->json(['status' => true, 'leaves_data' => null, 'message' => 'No leaves.']);
    //         }
    //         return response()->json(['status' => true, 'leaves_data' => $leaves, 'message' => 'success']);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => "Internal Server Error"]);
    //     }
    // }
    ///code by priya change of nandhakumar
    public function getDoctorLeaveList(Request $request)
    {
        $rules = [
            'doctor_id'   => 'required',
            'hospital_id' => 'required',
        ];
        $messages = [
            'doctor_id.required' => 'Doctor is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $doctorId = $request->doctor_id;
            $hospitalId = $request->hospital_id;


            $upcomingLeaves = DocterLeave::select('id', 'docter_id', 'hospital_id', 'date')
                ->where('docter_id', $doctorId)
                ->where('hospital_id', $hospitalId)
                ->whereDate('date', '>=', now()->toDateString())
                ->orderBy('date', 'asc')
                ->get();

            if ($upcomingLeaves->isEmpty()) {
                return response()->json(['status' => true, 'leaves_data' => null, 'message' => 'No upcoming leaves.']);
            }

            return response()->json(['status' => true, 'leaves_data' => $upcomingLeaves, 'message' => 'Success']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "Internal Server Error"]);
        }
    }


    public function doctorLeaveUpdate(Request $request)
    {
        $rules = [
            'doctor_id'     => 'required',
            'clinic_id'   => 'required',
            'from_date'     => 'required',
            'to_date'       => 'required',
        ];
        $messages = [
            'from_date.required' => 'Date is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            // $doctor = Docter::find($request->doctor_id);
            $doctor = Docter::where('UserId', $request->doctor_id)->pluck('id')->first();
            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }

            $fromDate = Carbon::parse($request->from_date);
            $toDate = Carbon::parse($request->to_date);

            for ($date = $fromDate; $date->lte($toDate); $date->addDay()) {
                $leave = DocterLeave::where('docter_id', $request->doctor_id)
                    ->where('hospital_id', $request->clinic_id)
                    ->where('date', $date->toDateString())
                    ->first();

                if (!$leave) {
                    $newLeave = new DocterLeave();
                    $newLeave->date = $date->toDateString();
                    $newLeave->hospital_id = $request->clinic_id;
                    $newLeave->docter_id   = $request->doctor_id;
                    $newLeave->save();
                }
            }

            return response()->json(['status' => true, 'message' => 'Leaves updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal Server Error'], 500);
        }
    }



    public function doctorLeaveDelete(Request $request)
    {
        // in this case the doctor_id is the doctor user id
        $rules = [
            'doctor_id'     => 'required',
            'clinic_id'   => 'required',
            'date'     => 'required',

        ];
        $messages = [
            'date.required' => 'Date is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $doctor = Docter::where('UserId', $request->doctor_id)->pluck('id')->first();
            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }

            $date = Carbon::parse($request->date);

            $leave = DocterLeave::where('docter_id', $request->doctor_id)
                ->where('hospital_id', $request->clinic_id)
                ->where('date', $date->toDateString())
                ->first();


            if ($leave) {
                DocterLeave::where('docter_id', $request->doctor_id)
                    ->where('hospital_id', $request->clinic_id)
                    ->where('date', $date->toDateString())
                    ->delete();
            } else {
                return response()->json(['status' => false, 'message' => "No leaves request found."]);
            }

            return response()->json(['status' => true, 'message' => 'Leaves updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "Internal Server Error"], 500);
        }
    }

    // public function searchDoctor(Request $request)
    // {
    //     $authenticatedUserId = auth()->user()->id;
    //     $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //     $userLatitude = $current_location_data ? $current_location_data->latitude : null;
    //     $userLongitude = $current_location_data ? $current_location_data->longitude : null;

    //    // $apiKey = config('services.google.api_key');

    //     $doctors = Docter::with(['clinics', 'specializations'])
    //         ->where(function ($query) use ($request) {
    //             $query->where('firstname', 'LIKE', '%' . $request->name . '%')
    //                 ->orWhere('lastname', 'LIKE', '%' . $request->name . '%')
    //                 ->orWhere('Services_at', 'LIKE', '%' . $request->name . '%')
    //                 ->orWhere('location', 'LIKE', '%' . $request->name . '%');
    //         })
    //         ->orWhereHas('specializations', function ($query) use ($request) {
    //             $query->where('specialization', 'LIKE', '%' . $request->name . '%');
    //         })
    //         ->orWhereHas('clinics', function ($query) use ($request) {
    //             $query->where('clinic_name', 'LIKE', '%' . $request->name . '%');
    //         })
    //         ->get();

    //     $doctorsWithSpecifications = $doctors->map(function ($doctor) use ($userLatitude, $userLongitude) {
    //         // Calculate distance to doctor
    //         $authenticatedUserId = auth()->user()->id;
    //         // $service = new DistanceMatrixService($apiKey);
    //         // $distance = $service->getDistance($userLatitude, $userLongitude, $doctor->latitude, $doctor->longitude);
    //         $specializeArray['specialize'] = Specialize::all();
    //         $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);
    //         $clinicData = [];
    //         $favoriteStatus = DB::table('addfavourite')
    //             ->where('UserId', $authenticatedUserId)
    //             ->where('doctor_id', $doctor['UserId'])
    //             ->exists();

    //         foreach ($doctor->clinics as $clinic) {
    //             $clinicLatitude = $clinic->latitude;
    //             $clinicLongitude = $clinic->longitude;
    //             //    Log::info("Clinic Latitude: {$clinicLatitude}, Clinic Longitude: {$clinicLongitude}");

    //             $current_date = Carbon::now()->toDateString();
    //             $current_time = Carbon::now()->toDateTimeString();
    //             ///clinic wise location
    //             //  $userLocation = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //             /////
    //             // if ($userLocation && $clinic['clinic_latitude'] && $clinic['clinic_longitude']) {
    //             //     $distanceService = new DistanceMatrixService(config('services.google.api_key'));
    //             //     $clinicDetails['distance'] = $distanceService->getDistance($userLocation->latitude, $userLocation->longitude, $clinic['clinic_latitude'], $clinic['clinic_longitude']);
    //             // } else {
    //             //     $clinicDetails['distance'] = null;
    //             // }
    //             //////
    //             // if ($userLatitude && $userLongitude && $clinicLatitude && $clinicLongitude) {
    //             //     $clinicDistance = $service->getDistance($userLatitude, $userLongitude, $clinicLatitude, $clinicLongitude);
    //             // } else {
    //             //     Log::warning("Invalid latitude or longitude for Clinic ID: {$clinic->id}");
    //             // }
    //             $clinicDistance = null;
    //             /////////////////////

    //             $total_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->count();

    //             $available_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('token_booking_status', null)
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->count();
    //             // Schedule details
    //             $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
    //                 ->where('doctor_id', $doctor->id)
    //                 ->where('clinic_id', $clinic->clinic_id)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->first();
    //             $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
    //                 ->where('doctor_id', $doctor->id)
    //                 ->where('clinic_id', $clinic->clinic_id)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->orderBy('token_start_time', 'DESC')
    //                 ->first();

    //             $start_time = $schedule_start_data ? Carbon::parse($schedule_start_data->token_start_time)->format('h:i A') : null;
    //             $end_time = $schedule_end_data ? Carbon::parse($schedule_end_data->token_end_time)->format('h:i A') : null;
    //             $next_available_token_time = NewTokens::where('clinic_id', $clinic->clinic_id)
    //                 ->where('token_scheduled_date', $current_date)
    //                 ->where('token_booking_status', null)
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->value('token_start_time');
    //             $consultation_fee = ClinicConsultation::where('doctor_id', $doctor->id)
    //                 ->where('clinic_id', $clinic->clinic_id)
    //                 ->value('consultation_fee');

    //             $next_date_available_token_time = NewTokens::where('clinic_id', $clinic->clinic_id)
    //                 ->where('token_scheduled_date', '>', $current_date)
    //                 ->where('token_booking_status', NULL)
    //                 ->orderBy('token_scheduled_date', 'ASC')
    //                 ->where('token_start_time', '>', $current_time)
    //                 ->where('doctor_id', $doctor->id)
    //                 ->orderBy('token_start_time', 'ASC')
    //                 ->value('token_start_time');
    //             // $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('y-m-d h:i A') : null;
    //             $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('d M h:i A') : null;

    //             $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

    //             $clinicData[] = [
    //                 'clinic_id' => $clinic->clinic_id,
    //                 'clinic_name' => $clinic->clinic_name,
    //                 'clinic_start_time' => $start_time,
    //                 'clinic_end_time' => $end_time,
    //                 'clinic_address' => $clinic->address,
    //                 'clinic_location' => $clinic->location,
    //                 'clinic_main_image' => isset($clinic->clinic_main_image) ? asset("clinic_images/{$clinic->clinic_main_image}") : null,
    //                 'clinic_description' => $clinic->clinic_description,
    //                 'total_token_Count' => $total_token_count,
    //                 'available_token_count' => $available_token_count,
    //                 'next_available_token_time' => $next_available_token_time,
    //                 'next_date_available_token_time' => $next_date_available_token_time,
    //                 //'distance_from_clinic' => $clinicDetails['distance'],
    //                 'distance_from_clinic' => $clinicDistance,
    //                 'consultation_fee' => $consultation_fee,
    //             ];
    //         }

    //         return [
    //             'id' => $doctor->id,
    //             'UserId' => $doctor->UserId,
    //             'firstname' => $doctor->firstname,
    //             'lastname' => $doctor->lastname,
    //             'Specialization' => $specialize ? $specialize['specialization'] : null,
    //             'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
    //             // 'DocterImage' => asset("DocterImages/images/{$doctor->docter_image}"),
    //             'Location' => $doctor->location,
    //             'MainHospital' => $doctor->Services_at,
    //             // 'distance_from_user' => $distance,
    //             'clinics' => $clinicData,
    //             'favoriteStatus' => $favoriteStatus ? 1 : 0,
    //         ];
    //     });

    //     return response()->json(['status' => 'success', 'Search Doctors' => $doctorsWithSpecifications, 'code' => '1', 'message' => 'Search Doctors retrieved successfully.']);
    // }

    public function searchDoctor(Request $request)
    {
        $authenticatedUserId = auth()->user()->id;

        // get current user location
        ///
        $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
        $user_latitude = $current_location_data ? $current_location_data->latitude : null;
        $user_longitude = $current_location_data ? $current_location_data->longitude : null;

        $distanceHelper = new \App\Helpers\UserDistanceHelper();
        ///
        $doctors = Docter::with(['clinics', 'specializations'])
            ->where(function ($query) use ($request) {
                $query->where('firstname', 'LIKE', '%' . $request->name . '%')
                    ->orWhere('lastname', 'LIKE', '%' . $request->name . '%')
                    ->orWhere('Services_at', 'LIKE', '%' . $request->name . '%')
                    ->orWhere('location', 'LIKE', '%' . $request->name . '%');
            })
            ->orWhereHas('specializations', function ($query) use ($request) {
                $query->where('specialization', 'LIKE', '%' . $request->name . '%');
            })
            ->orWhereHas('clinics', function ($query) use ($request) {
                $query->where('clinic_name', 'LIKE', '%' . $request->name . '%');
            })
            ->get();

        // process each doctor
        $doctorsWithSpecifications = $doctors->map(function ($doctor) use ($user_latitude, $user_longitude, $distanceHelper) {

            $specializeArray['specialize'] = Specialize::all();
            $specialize = $specializeArray['specialize']->firstWhere('id', $doctor->specialization_id);

            $clinicData = [];
            $favoriteStatus = DB::table('addfavourite')
                ->where('UserId', auth()->user()->id)
                ->where('doctor_id', $doctor->UserId)
                ->exists();

            foreach ($doctor->clinics as $clinic) {
                // calculate distance from user to clinic
                $clinicDistance = $distanceHelper->calculateHaversineDistance(
                    $user_latitude,
                    $user_longitude,
                    $clinic->latitude,
                    $clinic->longitude
                );

                $current_date = Carbon::now()->toDateString();
                $current_time = Carbon::now()->toDateTimeString();

                $total_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('doctor_id', $doctor->id)
                    ->count();

                $available_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('token_booking_status', null)
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $doctor->id)
                    ->count();


                $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $doctor->id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'ASC')
                    ->first();
                $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $doctor->id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'DESC')
                    ->first();

                $start_time = $schedule_start_data ? Carbon::parse($schedule_start_data->token_start_time)->format('h:i A') : null;
                $end_time = $schedule_end_data ? Carbon::parse($schedule_end_data->token_end_time)->format('h:i A') : null;

                $next_available_token_time = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('token_booking_status', null)
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $doctor->id)
                    ->orderBy('token_start_time', 'ASC')
                    ->value('token_start_time');

                $consultation_fee = ClinicConsultation::where('doctor_id', $doctor->id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->value('consultation_fee');

                $next_date_available_token_time = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', '>', $current_date)
                    ->where('token_booking_status', NULL)
                    ->orderBy('token_scheduled_date', 'ASC')
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $doctor->id)
                    ->orderBy('token_start_time', 'ASC')
                    ->value('token_start_time');
                $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('d M h:i A') : null;

                $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

                $clinicData[] = [
                    'clinic_id' => $clinic->clinic_id,
                    'clinic_name' => $clinic->clinic_name,
                    'clinic_start_time' => $start_time,
                    'clinic_end_time' => $end_time,
                    'clinic_address' => $clinic->address,
                    'clinic_location' => $clinic->location,
                    'clinic_main_image' => isset($clinic->clinic_main_image) ? asset("clinic_images/{$clinic->clinic_main_image}") : null,
                    'clinic_description' => $clinic->clinic_description,
                    'total_token_Count' => $total_token_count,
                    'available_token_count' => $available_token_count,
                    'next_available_token_time' => $next_available_token_time,
                    'next_date_available_token_time' => $next_date_available_token_time,
                    //'distance_from_clinic' => $clinicDetails['distance'],
                    'distance_from_clinic' => $clinicDistance,
                    'consultation_fee' => $consultation_fee,
                ];
            }

            return [
                'id' => $doctor->id,
                'UserId' => $doctor->UserId,
                'firstname' => $doctor->firstname,
                'lastname' => $doctor->lastname,
                'Specialization' => $specialize ? $specialize['specialization'] : null,
                'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
                // 'DocterImage' => asset("DocterImages/images/{$doctor->docter_image}"),
                'Location' => $doctor->location,
                'MainHospital' => $doctor->Services_at,
                // 'distance_from_user' => $distance,
                'clinics' => $clinicData,
                'favoriteStatus' => $favoriteStatus ? 1 : 0,
            ];
        });

        return response()->json(['status' => 'success', 'Search Doctors' => $doctorsWithSpecifications, 'code' => '1', 'message' => 'Search Doctors retrieved successfully.']);
    }



    public function getBookedPatients(Request $request)
    {
        $rules = [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
        ];
        $messages = [
            'doctor_id.required' => 'Doctor Id is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $data =  Docter::select('UserId')->where('UserId', $request->doctor_id)->with('appointments:id,doctor_id,BookedPerson_id,clinic_id')->first();

            foreach ($data->appointments as $key => $appointment) {
                $user_id = $appointment->BookedPerson_id;
                $user = User::where('id', $user_id)->where('user_role', 3)->first();

                if ($appointment->clinic_id != $request->clinic_id) {
                    $data->appointments->forget($key);
                } elseif (empty($user)) {
                    $data->appointments->forget($key);
                }
            }
            // $patients = TokenBooking::select('token_booking.*', 'patient.*', 'users.*')
            $patients = TokenBooking::select(
                'patient.id',
                'patient.UserId',
                'patient.firstname',
                'patient.lastname',
                'patient.gender',
                'patient.age',
                'patient.mediezy_patient_id',
                'patient.user_image',
                'patient.dateofbirth'
            )
                ->leftjoin('patient', 'patient.id', '=', 'token_booking.patient_id')
                ->join('users', 'users.id', '=', 'token_booking.BookedPerson_id')
                //->where('token_booking.user_type', 1)
                ->where('users.user_role', 3)
                ->where('token_booking.clinic_id', $request->clinic_id)
                ->where('token_booking.doctor_id', $request->doctor_id)
                ->get();
            $patients = $patients->unique('id');

            $patients->transform(function ($patient) {
                $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
                if ($patient->age === null) {
                    $dob = $patient->dateofbirth ?? null;
                    $age = $dob ? Carbon::parse($dob)->age : null;
                    $patient->age = $age;
                }
                if (isset($patient->dateofbirth)) {
                    $dob = \Carbon\Carbon::parse($patient->dateofbirth);
                    $now = \Carbon\Carbon::now();
                    $diff = $now->diffInMonths($dob);

                    if ($diff < 12) {
                        $displayAge = $diff . ' months old';
                    } else {
                        $years = floor($diff / 12);
                        $months = $diff % 12;
                        $displayAge =  $years . ' years old';
                    }
                } else {
                    $displayAge = 'Unknown age';
                }

                $patient->displayAge = $displayAge;

                return $patient;
            });



            $patients = $patients->values()->all();
            // patients count

            $patient_count = count($patients);



            return response()->json(['status' => true, 'patient_data' => $patients, 'patient_count' => $patient_count]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'message' => "Internal Server Error"]);
        }
    }








    // public function searchPatients(Request $request)
    // {
    //     $rules = [
    //         'doctor_id' => 'required',
    //     ];

    //     $messages = [
    //         'doctor_id.required' => 'Doctor Id is required',
    //     ];

    //     $validation = Validator::make($request->all(), $rules, $messages);

    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }

    //     try {
    //         $doctor = Docter::select('UserId')->where('UserId', $request->doctor_id)->with('appointments:id,doctor_id,BookedPerson_id')->first();

    //         if (!$doctor) {
    //             return response()->json(['status' => false, 'message' => 'Doctor not found']);
    //         }

    //         $filteredAppointments = $doctor->appointments->filter(function ($appointment) use ($request) {
    //             $user = User::where('id', $appointment->BookedPerson_id)->where('user_role', 3)->first();
    //             return !empty($user);
    //         });

    //         $userIds = $filteredAppointments->pluck('BookedPerson_id')->unique()->values()->all();

    //         $patientsQuery = Patient::select('id', 'UserId', 'firstname', 'lastname', 'gender', 'age', 'mediezy_patient_id', 'user_image')
    //             ->whereIn('UserId', $userIds)
    //             ->where('user_type', 1);


    //         if (isset($request->search_name) && !empty($request->search_name)) {
    //             $patientsQuery->where(function ($query) use ($request) {
    //                 $query->where('firstname', 'like', '%' . $request->search_name . '%')
    //                     ->orWhere('mediezy_patient_id', 'like', '%' . $request->search_name . '%');
    //             });
    //         }

    //         $patients = $patientsQuery->get();

    //         $patients->transform(function ($patient) {
    //             $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
    //             return $patient;
    //         });

    //         return response()->json(['status' => true, 'patient_data' => $patients]);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => "Internal Server Error"]);
    //     }
    // }

    // public function searchPatients(Request $request)
    // {
    //     $rules = [
    //         'doctor_id' => 'required',
    //     ];

    //     $messages = [
    //         'doctor_id.required' => 'Doctor Id is required',
    //     ];

    //     $validation = Validator::make($request->all(), $rules, $messages);

    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }

    //     try {
    //         $doctor = Docter::select('UserId')->where('UserId', $request->doctor_id)->with('appointments:id,doctor_id,BookedPerson_id')->first();

    //         if (!$doctor) {
    //             return response()->json(['status' => false, 'message' => 'Doctor not found']);
    //         }

    //         $filteredAppointments = $doctor->appointments->filter(function ($appointment) use ($request) {
    //             $user = User::where('id', $appointment->BookedPerson_id)->where('user_role', 3)->first();
    //             return !empty($user);
    //         });

    //         $userIds = $filteredAppointments->pluck('BookedPerson_id')->unique()->values()->all();

    //         // $patientsQuery = Patient::select('id', 'UserId', 'firstname', 'lastname', 'gender', 'age', 'mediezy_patient_id', 'user_image')
    //         //     ->whereIn('UserId', $userIds)
    //         //     ->where('user_type', 1)
    //         //   ;

    //         $patientsQuery = TokenBooking::leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
    //         ->select('patient.id', 'patient.UserId', 'patient.firstname', 'patient.lastname', 'token_booking.gender', 'patient.age', 'patient.mediezy_patient_id', 'patient.user_image')
    //         ->whereIn('patient.UserId', $userIds)
    //        // ->where('patient.user_type', 1)
    //        ;
    //         if (isset($request->search_name) && !empty($request->search_name)) {
    //             $patientsQuery->where(function ($query) use ($request) {
    //                 $query->where('firstname', 'like', '%' . $request->search_name . '%')
    //                     ->orWhere('mediezy_patient_id', 'like', '%' . $request->search_name . '%');
    //             });
    //         }

    //         $patients = $patientsQuery->get();

    //         $patients->transform(function ($patient) {
    //             $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
    //             return $patient;
    //         });

    //         return response()->json(['status' => true, 'patient_data' => $patients]);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => "Internal Server Error"]);
    //     }
    // }

    // public function searchPatients(Request $request)
    // {
    //     $rules = [
    //         'doctor_id' => 'required',
    //     ];

    //     $messages = [
    //         'doctor_id.required' => 'Doctor Id is required',
    //     ];

    //     $validation = Validator::make($request->all(), $rules, $messages);

    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }

    //     try {
    //         $doctor = Docter::select('UserId')->where('UserId', $request->doctor_id)->with('appointments:id,doctor_id,BookedPerson_id')->first();

    //         if (!$doctor) {
    //             return response()->json(['status' => false, 'message' => 'Doctor not found']);
    //         }

    //         $filteredAppointments = $doctor->appointments->filter(function ($appointment) use ($request) {
    //             $user = User::where('id', $appointment->BookedPerson_id)->where('user_role', 3)->first();
    //             return !empty($user);
    //         });

    //         $userIds = $filteredAppointments->pluck('BookedPerson_id')->unique()->values()->all();

    //         $patientsQuery = TokenBooking::leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
    //             ->select('patient.id', 'patient.UserId', 'patient.firstname', 'patient.lastname', 'token_booking.gender', 'patient.age', 'patient.mediezy_patient_id', 'patient.user_image')
    //             ->whereIn('patient.UserId', $userIds);

    //         if (isset($request->search_name) && !empty($request->search_name)) {
    //             $patientsQuery->where(function ($query) use ($request) {
    //                 $query->where('firstname', 'like', '%' . $request->search_name . '%')
    //                     ->orWhere('mediezy_patient_id', 'like', '%' . $request->search_name . '%');
    //             });
    //         }

    //         // $patients = $patientsQuery->groupBy('patient.id')->get();
    //         $patients = $patientsQuery->groupBy('patient.id', 'patient.UserId', 'patient.firstname', 'patient.lastname', 'token_booking.gender', 'patient.age', 'patient.mediezy_patient_id', 'patient.user_image')->get();
    //         $patients->transform(function ($patient) {
    //             $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
    //             return $patient;
    //         });

    //         return response()->json(['status' => true, 'patient_data' => $patients]);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'message' => $e->getMessage()]);
    //     }
    // }

    public function searchPatients(Request $request)
    {
        $rules = [
            'doctor_id' => 'required',
        ];

        $messages = [
            'doctor_id.required' => 'Doctor Id is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $doctor = Docter::select('UserId')->where('UserId', $request->doctor_id)->with('appointments:id,doctor_id,BookedPerson_id')->first();

            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }

            $filteredAppointments = $doctor->appointments->filter(function ($appointment) use ($request) {
                $user = User::where('id', $appointment->BookedPerson_id)->where('user_role', 3)->first();
                return !empty($user);
            });

            $userIds = $filteredAppointments->pluck('BookedPerson_id')->unique()->values()->all();

            $patientsQuery = TokenBooking::leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
                ->select('patient.id', 'patient.UserId', 'patient.firstname', 'patient.lastname', 'token_booking.gender', 'patient.dateofbirth', 'patient.age', 'patient.mediezy_patient_id', 'patient.user_image')
                ->whereIn('patient.UserId', $userIds);

            if (isset($request->search_name) && !empty($request->search_name)) {
                $patientsQuery->where(function ($query) use ($request) {
                    $query->where('firstname', 'like', '%' . $request->search_name . '%')
                        ->orWhere('mediezy_patient_id', 'like', '%' . $request->search_name . '%');
                });
            }

            // $patients = $patientsQuery->groupBy('patient.id')->get();
            $patients = $patientsQuery->groupBy('patient.id', 'patient.UserId', 'patient.firstname', 'patient.lastname', 'token_booking.gender', 'patient.dateofbirth', 'patient.age', 'patient.mediezy_patient_id', 'patient.user_image')->get();
            $patients->transform(function ($patient) {
                $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;

                if (isset($patient->dateofbirth)) {
                    $dob = \Carbon\Carbon::parse($patient->dateofbirth);
                    $now = \Carbon\Carbon::now();
                    $diff = $now->diffInMonths($dob);

                    if ($diff < 12) {
                        $displayAge = $diff . ' months old';
                    } else {
                        $years = floor($diff / 12);
                        $months = $diff % 12;
                        $displayAge =  $years . ' years old';
                    }
                } else {
                    $displayAge = 'Unknown age';
                }

                $patient->displayAge = $displayAge;
                return $patient;
            });

            return response()->json(['status' => true, 'patient_data' => $patients]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function sortPatient(Request $request)
    {
        $rules = [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            'interval'  => 'required|in:Today,Week,Month,Year,All',
        ];

        $messages = [
            'doctor_id.required' => 'Doctor Id is required',
            'interval.required'  => 'Sorting interval is required',
            'interval.in'        => 'Invalid sorting interval. Allowed values: Today, Week, Month, Year, All',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            ///$doctor = Docter::where('UserId', $request->doctor_id)->with('appointments:id,doctor_id,BookedPerson_id,created_at,clinic_id')->first();
            $doctor = Docter::where('UserId', $request->doctor_id)
                ->with(['appointments' => function ($query) use ($request) {
                    $query->where('clinic_id', $request->clinic_id);
                }])
                ->whereHas('appointments', function ($query) use ($request) {
                    $query->where('clinic_id', $request->clinic_id);
                })
                ->firstOrFail();

            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }
            $filteredAppointments = $this->filterAppointmentsByInterval($doctor->appointments, $request->interval);

            // Get unique BookedPerson_ids from the filtered appointments
            $userIds = $filteredAppointments->pluck('BookedPerson_id')->unique()->values()->all();

            // Get patient data based on user_ids
            $patients = Patient::select('id', 'UserId', 'firstname', 'lastname', 'gender', 'age', 'dateofbirth', 'mediezy_patient_id', 'user_image')
                ->whereIn('UserId', $userIds)
                ->where('user_type', 1)
                ->get();
            $patients->transform(function ($patient) {
                $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;

                if (isset($patient->dateofbirth)) {
                    $dob = \Carbon\Carbon::parse($patient->dateofbirth);
                    $now = \Carbon\Carbon::now();
                    $diff = $now->diffInMonths($dob);

                    if ($diff < 12) {
                        $displayAge = $diff . ' months old';
                    } else {
                        $years = floor($diff / 12);
                        $months = $diff % 12;
                        $displayAge = $months > 0 ? $years . ' years ' . $months . ' months old' : $years . ' years old';
                    }
                } else {
                    $displayAge = 'Unknown age';
                }

                $patient->displayAge = $displayAge;


                return $patient;
            });
            // patients count
            $patient_count = count($patients);

            return response()->json(['status' => true, 'patient_data' => $patients, 'patient_count' => $patient_count]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => true, 'patient_data' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal Server Error']);
        }
    }






    private function filterAppointmentsByInterval($appointments, $interval)
    {
        $now = now();

        return $appointments->filter(function ($appointment) use ($now, $interval) {

            if ($appointment->created_at) {

                switch ($interval) {
                    case 'Today':
                        return $appointment->created_at->isToday();
                    case 'Week':
                        return $appointment->created_at->isCurrentWeek();
                    case 'Month':
                        return $appointment->created_at->isCurrentMonth();
                    case 'Year':
                        return $appointment->created_at->isCurrentYear();
                    default:
                        return true;
                }
            }

            return false;
        });
    }

    public function Addtestdetails(Request $request)
    {
        $current_time = Carbon::now()->format('h:i:s A');
        Log::info('ADD test details api called on ' . $current_time);

        $rules = [
            'token_id' => 'required',
        ];

        $messages = [
            'token_id.required' => 'Token Id is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $token_id = $request->token_id;
            $imageName = null;

            $tokenBooking = DB::table('token_booking')->where('new_token_id', $token_id)->first();

            if (!$tokenBooking) {
                return response()->json(['status' => false, 'message' => 'Token not found']);
            }
            $Bookingdate = Carbon::parse($tokenBooking->date);
            $ReviewDate = $Bookingdate->addDays($request->ReviewAfter);
            if ($request->hasFile('prescription_image')) {
                $imageFile = $request->file('prescription_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('LabImages/prescription'), $imageName);
                }
            }

            /////medical shop id
            $medical_shop_id = null;
            if ($request->medical_shop_id !== "null") {
                $medical_shop_id = intval($request->medical_shop_id);
            }
            DB::table('token_booking')
                ->where('new_token_id', $token_id)
                ->update([
                    'lab_id'           => $request->lab_id,
                    'labtest'          => $request->labtest,
                    //$labtestString,
                    'scan_id'           => $request->scan_id,
                    'scan_test'          => $request->scan_test,
                    //$scantestString,
                    'medicalshop_id'   => $medical_shop_id,
                    'prescription_image' => $imageName,
                    'ReviewAfter'      => $request->ReviewAfter,
                    'Reviewdate'       => $ReviewDate,
                    'notes'            => $request->notes,
                ]);

            return response()->json(['status' => true, 'message' => 'Test details added successfully']);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'message' => 'Internal Server Error']);
        }
    }



    public function getAllSortedPatients(Request $request)
    {
        $rules = [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            'interval'  => 'required|in:Today,Week,Month,Year,All,Custom',
            'from_date' => 'sometimes',
            'to_date'   => 'sometimes',
        ];

        $messages = [
            'doctor_id.required' => 'Doctor Id is required',
            'interval.required'  => 'Sorting interval is required',
            'interval.in'        => 'Invalid sorting interval. Allowed values: Today, Week, Month, Year, All, Custom',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        if ($request->interval === 'Custom' && (!$request->has('from_date') || !$request->has('to_date'))) {
            return response()->json(['status' => false, 'response' => 'From date and to date are required for custom interval.']);
        }
        try {
            $doctor = Docter::where('UserId', $request->doctor_id)
                ->with(['appointments' => function ($query) use ($request) {
                    $query->where('clinic_id', $request->clinic_id);
                }])
                ->whereHas('appointments', function ($query) use ($request) {
                    $query->where('clinic_id', $request->clinic_id);
                })
                ->first();
            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }
            $filteredAppointments = $this->sortAppointmentsByInterval($doctor->appointments, $request->interval, $request->from_date, $request->to_date);
            $userIds = $filteredAppointments->pluck('BookedPerson_id')->unique()->values()->all();

            $patients = TokenBooking::leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
                ->select('token_booking.doctor_id', 'token_booking.BookedPerson_id', 'patient.id', 'patient.UserId', 'patient.firstname', 'patient.lastname', 'token_booking.gender', 'patient.dateofbirth', 'patient.age', 'patient.mediezy_patient_id', 'patient.user_image')
                ->whereIn('patient.UserId', $userIds)
                ->distinct()
                ->get();

            $patients = $patients->filter(function ($patient) {
                return $patient->doctor_id !== $patient->BookedPerson_id;
            });

            $patients->transform(function ($patient) {
                unset($patient->doctor_id);
                unset($patient->BookedPerson_id);
                $patient->user_image = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
                if ($patient->age === null) {
                    $dob = $patient->dateofbirth ?? null;
                    $age = $dob ? Carbon::parse($dob)->age : null;
                    $patient->age = $age;
                }
                if (isset($patient->dateofbirth)) {
                    $dob = \Carbon\Carbon::parse($patient->dateofbirth);
                    $now = \Carbon\Carbon::now();
                    $diff = $now->diffInMonths($dob);

                    if ($diff < 12) {
                        $displayAge = $diff . ' months old';
                    } else {
                        $years = floor($diff / 12);
                        $months = $diff % 12;
                        $displayAge = $years . ' years old';
                    }
                } else {
                    $displayAge = 'Unknown age';
                }

                $patient->displayAge = $displayAge;
                return $patient;
            });

            return response()->json(['status' => true, 'patient_data' => $patients->unique('id')->values()]);
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }
    private function sortAppointmentsByInterval($appointments, $interval, $fromDate = null, $toDate = null)
    {
        $now = now();

        return $appointments->filter(function ($appointment) use ($now, $interval, $fromDate, $toDate) {

            if ($appointment->created_at) {
                switch ($interval) {
                    case 'Today':
                        return $appointment->created_at->isToday();
                    case 'Week':
                        return $appointment->created_at->isCurrentWeek();
                    case 'Month':
                        return $appointment->created_at->isCurrentMonth();
                    case 'Year':
                        return $appointment->created_at->isCurrentYear();
                    case 'Custom':
                        if ($fromDate && $toDate) {
                            return $appointment->created_at->between($fromDate, $toDate);
                        }
                        break;
                    default:
                        return true;
                }
            }

            return false;
        });
    }

    public function GetDoctorByClinic($userId)
    {
        $authenticatedUserId = auth()->user()->id;
        $clinics = Clinic::join('doctor_clinic_relations', 'clinics.clinic_id', '=', 'doctor_clinic_relations.clinic_id')
            ->join('docter', 'doctor_clinic_relations.doctor_id', '=', 'docter.id')
            ->join('users', 'docter.UserId', '=', 'users.id')
            ->select(
                'clinics.clinic_id as clinic_id',
                'clinics.clinic_name',
                'clinics.clinic_start_time',
                'clinics.clinic_end_time',
                'clinics.address',
                'clinics.location',
                'clinics.clinic_description',
                'clinics.clinic_main_image'
            )
            ->where('users.id', $userId)
            ->distinct()
            ->get();

        $clinicsWithSpecifications = [];

        foreach ($clinics as $clinic) {
            $current_date = Carbon::now()->toDateString();
            $current_time = Carbon::now()->toDateTimeString();

            $total_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                ->where('token_scheduled_date', $current_date)
                ->count();

            $available_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                ->where('token_scheduled_date', $current_date)
                ->where('token_booking_status', NULL)
                ->where('token_start_time', '>', $current_time)
                ->count();
            $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();
            $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                ->where('doctor_id', $doctor_id)
                ->where('clinic_id', $clinic->clinic_id)
                ->where('token_scheduled_date', $current_date)
                ->orderBy('token_start_time', 'ASC')
                ->first();
            $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                ->where('doctor_id', $doctor_id)
                ->where('clinic_id', $clinic->clinic_id)
                ->where('token_scheduled_date', $current_date)
                ->orderBy('token_start_time', 'DESC')
                ->first();
            if ($schedule_start_data && $schedule_end_data) {
                $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
            } else {
                $start_time = null;
                $end_time = null;
            }
            $clinicDetails = [
                'clinic_id' => $clinic->clinic_id,
                'clinic_name' => $clinic->clinic_name,
                'clinic_start_time' => $start_time,
                'clinic_end_time' => $end_time,
                'clinic_address' => $clinic->address,
                'clinic_location' => $clinic->location,
                'clinic_main_image' => isset($clinic->clinic_main_image) ? asset($clinic->clinic_main_image) : null,
                'clinic_description' => $clinic->clinic_description,
                'total_token_count' => $total_token_count,
                'available_token_count' => $available_token_count,
            ];

            $clinicsWithSpecifications[] = $clinicDetails;
        }

        return $this->sendResponse("Clinic Details", $clinicsWithSpecifications, '1', 'Clinics retrieved successfully.');
    }


    public function registerDoctor(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                // 'last_name' => 'required|string|max:255',
                'mobile_number' => 'required|numeric',
                'location' => 'required|string|max:255',
                'email' => 'required|email|unique:doctor_register,email',
                'hospital_name' => 'required|string|max:255',
                'specialization' => 'required|string|max:255',
                'dob' => 'required|date_format:Y-m-d',
                'age' => 'sometimes',
                'doctor_image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            $doctor = new DoctorRegister();
            $doctor->first_name = $request->first_name;
            $doctor->last_name = $request->last_name;
            $doctor->mobile_number = $request->mobile_number;
            $doctor->location = $request->location;
            $doctor->email = $request->email;
            $doctor->hospital_name = $request->hospital_name;
            $doctor->hospital_name = $request->hospital_name;
            $doctor->dob = $request->dob;
            $doctor->age = $request->age;
            $doctor->dob = $request->dob;
            $age = \Carbon\Carbon::parse($request->dob)->age;
            $doctor->age = $age;
            if ($request->hasFile('doctor_image')) {
                $imageFile = $request->file('doctor_image');
                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('DocterImages/images'), $imageName);
                    $doctor->doctor_image = $imageName;
                }
            }
            if ($doctor->save()) {
                $imageUrl = $doctor->doctor_image ? asset('DocterImages/images/' . $doctor->doctor_image) : null;


                $doctorArray = $doctor->toArray();
                $doctorArray['doctor_image_url'] = $imageUrl;

                return response()->json([
                    'doctor' => $doctorArray, 'message' => 'Your profile is under verification. Our team will contact you shortly.'
                ], 200);
            } else {
                return response()->json(['message' => 'Failed to register doctor',], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error', 'exception' => $e->getMessage(),], 500);
        }
    }

    public function checkForExistingTokens(Request $request)
    {
        $request->validate([
            'doctor_user_id' => 'required|numeric',
            'clinic_id' => 'required|numeric',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        $doctor_id = $request->doctor_user_id;
        $clinic_id = $request->clinic_id;
        $from_date = Carbon::createFromFormat('Y-m-d', $request->from_date)->toDateString();
        $to_date = Carbon::createFromFormat('Y-m-d', $request->to_date)->toDateString();

        $existing_tokens_check = TokenBooking::where('doctor_id', $doctor_id)
            ->where('clinic_id', $clinic_id)
            ->whereDate('date', '>=', $from_date)
            ->whereDate('date', '<=', $to_date)
            ->count();

        if ($existing_tokens_check > 0) {
            return response()->json(['status' => true, 'message' => 'Booked tokens found', 'booked token count' => $existing_tokens_check], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'No booked tokens', 'booked token count' => 0], 200);
        }
    }

    //////////////////
    public function getDoctorLocations()
    {
        $doctor_locations =  Docter::select('location')->distinct()->get();
        if ($doctor_locations->count() > 0) {
            return response()->json(['status' => true, 'locations' => $doctor_locations], 200);
        } else {
            return response()->json(['status' => false, 'locations' => $doctor_locations], 200);
        }
    }
}
