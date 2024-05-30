<?php

namespace App\Http\Controllers\API;

use App\Helpers\UserLocationHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Docter;
use App\Models\Allergy;
use App\Models\Clinic;
use App\Models\ClinicConsultation;
use App\Models\CompletedAppointments;
use App\Models\DischargeSummary;
use App\Models\TokenBooking;
use App\Models\DocterAvailability;
use App\Models\Favouritestatus;
use App\Models\Laboratory;
use App\Models\LabReport;
use App\Models\MainSymptom;
use App\Models\Medicalshop;
use App\Models\Medicine;
use App\Models\NewTokens;
use App\Models\Patient;
use App\Models\PatientAllergies;
use App\Models\PatientDocument;
use App\Models\PatientPrescriptions;
use App\Models\Payment_image;
use App\Models\ScanReport;
use App\Models\Symtoms;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Specialize;
use App\Models\Specification;
use App\Models\Subspecification;
use App\Services\DistanceMatrixService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use League\CommonMark\Node\Block\Document;

class UserController extends BaseController
{

    // public function UserRegister(Request $request)
    // {
    //     $rules = [
    //         'firstname'  => 'required',
    //         'mobileNo'      => 'required',
    //         'gender'    => 'required',
    //         'age' => 'required',
    //         'email' => 'required|email',
    //         'password' => 'required'
    //     ];
    //     $messages = [
    //         'email.required' => 'Email is required',
    //     ];
    //     $validation = Validator::make($request->all(), $rules, $messages);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }

    //     $check_email_exits = Patient::where('email', $request->email)->exists();
    //     if ($check_email_exits) {
    //         return $this->sendResponse("Doctors", null, '3', 'Email already exists.');
    //     }

    //     $password = Hash::make($request->password);
    //     $save_user = new User();
    //     $save_user->firstname =  $request->firstname;
    //     $save_user->email =  $request->email;
    //     $save_user->mobileNo =  $request->mobileNo;
    //     $save_user->password =  $password;
    //     $save_user->user_role =  "3";
    //     $save_user->save();
    //     $savedUserId = $save_user->id;


    //     $mediezy_patient_id = $this->generatePatientUniqueId();




    //     $patient_data = new Patient();
    //     $patient_data->firstname = $request->firstname;
    //     $patient_data->mobileNo = $request->mobileNo;
    //     $patient_data->email = $request->email;
    //     $patient_data->age = $request->age;
    //     $patient_data->location = $request->location;
    //     $patient_data->gender = $request->gender;
    //     $patient_data->UserId = $savedUserId;
    //     $patient_data->mediezy_patient_id = $mediezy_patient_id;

    //     if ($request->hasFile('user_image')) {
    //         $imageFile = $request->file('user_image');

    //         if ($imageFile->isValid()) {
    //             $imageName = $imageFile->getClientOriginalName();
    //             $imageFile->move(public_path('UserImages'), $imageName);

    //             $patient_data->user_image = $imageName;
    //         }
    //     }
    //     $patient_data->save();
    //     Log::info("mediezy_patient_id $patient_data");


    //     return $this->sendResponse("users", $patient_data, '1', 'User created successfully.');
    // }
    public function UserRegister(Request $request)
    {
        $rules = [
            'firstname'  => 'required',
            'mobileNo'   => 'required',
            'gender'     => 'required',
            'dateofbirth' => 'required',
            // 'age'        => 'sometimes',
            'email'      => 'required|email',
            'password'   => 'required'
        ];
        $messages = [
            'email.required' => 'Email is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => $validation->errors()->first()]);
        }

        $check_email_exits = Patient::where('email', $request->email)->exists();
        if ($check_email_exits) {
            return response()->json(['status' => false, 'message' => 'Email already exits'], 400);
        }



        $password = Hash::make($request->password);
        $save_user = new User();
        $save_user->firstname =  $request->firstname;
        $save_user->email =  $request->email;
        $save_user->mobileNo =  $request->mobileNo;
        $save_user->age = $request->age;
        // $save_user->dob =  $request->dateofbirth;
        $save_user->dateofbirth =  $request->dateofbirth;
        $save_user->password =  $password;
        $save_user->user_role =  "3";
        $save_user->save();
        $savedUserId = $save_user->id;
        $mediezy_patient_id = $this->generatePatientUniqueId();
        $patient_data = new Patient();
        $patient_data->firstname = $request->firstname;
        $patient_data->mobileNo = $request->mobileNo;
        $patient_data->email = $request->email;
        $patient_data->age = $request->age;
        $patient_data->dateofbirth  = $request->dateofbirth;
        $patient_data->location = $request->location;
        $patient_data->gender = $request->gender;
        $patient_data->UserId = $savedUserId;
        $patient_data->mediezy_patient_id = $mediezy_patient_id;

        // $dob = Carbon::createFromFormat('Y/m/d', $request->dob);
        // $age = $dob->diffInYears(Carbon::now());
        $age = carbon::parse($request->dateofbirth)->age;
        $patient_data->age = $age;

        // $dob = Carbon::createFromFormat('Y/m/d', $request->dob);
        // $age = $dob->age;
        // $patient_data->age = $age;

        if ($request->hasFile('user_image')) {
            $imageFile = $request->file('user_image');

            if ($imageFile->isValid()) {
                $imageName = $imageFile->getClientOriginalName();
                $imageFile->move(public_path('UserImages'), $imageName);

                $patient_data->user_image = $imageName;
            }
        }
        $patient_data->save();
        $response = $patient_data->toArray();
        $response['dob'] = $response['dateofbirth'];
        unset($response['dateofbirth']);
        Log::info("mediezy_patient_id $patient_data");
        return $this->sendResponse("users", $response, '1', 'User created successfully.');

        //  return $this->sendResponse("users", $patient_data, '1', 'User created successfully.');
    }

    function generatePatientUniqueId()
    {
        $uniquePatientDetails = Patient::select('mediezy_patient_id')
            ->whereNotNull('mediezy_patient_id')
            ->orderBy('created_at', 'desc')
            ->first();
        Log::info("uniquePatientDetails = " . json_encode($uniquePatientDetails));
        if ($uniquePatientDetails === null) {
            $uniquePatientDetails = [
                'mediezy_patient_id' => 'MAA00000',
            ];
        }
        $lastUniqueId = $uniquePatientDetails->mediezy_patient_id;
        $numericPart = (int) substr($lastUniqueId, 3) + 1;
        $newNumericPart = str_pad($numericPart, 5, '0', STR_PAD_LEFT);
        $newUniqueId = 'MAA' . $newNumericPart;
        return $newUniqueId;
    }
    //endashwin



    // public function updateUserDetails(Request $request, $userId)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Check if the user exists
    //         $user = User::find($userId);

    //         if (!$user) {
    //             return $this->sendResponse(null, null, '2', 'User not found.');
    //         }

    //         // Update user details
    //         $user->firstname = $request->input('firstname') ?? $user->firstname;
    //         $user->secondname = $request->input('secondname') ?? $user->secondname;
    //         $user->email = $request->input('email') ?? $user->email;
    //         $user->mobileNo = $request->input('mobileNo') ?? $user->mobileNo;
    //         $user->save();

    //         // Update patient details
    //         $patient = Patient::where('UserId', $userId)->where('user_type', 1)->first();

    //         if (!$patient) {
    //             return $this->sendResponse(null, null, '3', 'Patient not found.');
    //         }
    //         $firstname = $request->input('firstname') ?? $patient->firstname;
    //         $Secondname = $request->input('secondname') ?? $patient->secondname;
    //         $Mobileno = $request->input('mobileNo') ?? $patient->mobileNo;
    //         $age = $request->input('age') ?? $patient->age;
    //         $gender = $request->input('gender') ?? $patient->gender;
    //         $location = $request->input('location') ?? $patient->location;

    //         $patientData = [
    //             'firstname' => $firstname,
    //             'secondname' => $Secondname,
    //             'age' => $age,
    //             'mobileNo' => $Mobileno,
    //             'location' => $location,
    //             'gender' => $gender,
    //         ];


    //         $patient->update($patientData);

    //         DB::commit();

    //         return $this->sendResponse("users", $user, '1', 'User details updated successfully.');
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
    //     }
    // }
    public function updateUserDetails(Request $request, $userId)
    {
        try {
            DB::beginTransaction();
            $user = User::find($userId);

            if (!$user) {
                return $this->sendResponse(null, null, '2', 'User not found.');
            }
            $user->firstname = $request->input('firstname') ?? $user->firstname;
            $user->secondname = $request->input('secondname') ?? $user->secondname;
            $user->email = $request->input('email') ?? $user->email;
            $user->mobileNo = $request->input('mobileNo') ?? $user->mobileNo;
            $user->age = $request->input('age') ?? $user->age;
            $user->dateofbirth = $request->input('dateofbirth') ?? $user->dateofbirth;
            $user->save();
            $patient = Patient::where('UserId', $userId)->where('user_type', 1)->first();

            if (!$patient) {
                return $this->sendResponse(null, null, '3', 'Patient not found.');
            }
            $patient->firstname = $request->input('firstname') ?? $patient->firstname;
            $patient->lastname = $request->input('lastname') ?? $patient->lastname;
            $patient->mobileNo = $request->input('mobileNo') ?? $patient->mobileNo;
            // $patient->age = $request->input('age') ?? $patient->age;
            $patient->dateofbirth = $request->input('dateofbirth') ?? $patient->dateofbirth;
            $patient->gender = $request->input('gender') ?? $patient->gender;
            $patient->location = $request->input('location') ?? $patient->location;
            if ($request->has('dateofbirth')) {
                $age = Carbon::parse($request->input('dateofbirth'))->age;
                $patient->age = $age;
            }
            $patient->save();
            DB::commit();
            return $this->sendResponse("users", $patient, '1', 'User details updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }
    public function userimage(Request $request, $userId)
    {
        try {
            $patient = Patient::where('UserId', $userId)->where('user_type', 1)->first();
            if (!$patient) {
                return $this->sendResponse(null, null, '3', 'Patient not found.');
            }
            if ($request->hasFile('user_image')) {
                $destination = public_path('UserImages/') . $patient->user_image;
                // Delete existing image if it exists
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                $file = $request->file('user_image');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                // Move the uploaded file to the specified directory
                $file->move(public_path('UserImages'), $filename);

                // Save the filename to the patient's 'user_image' field in the database
                $patient->user_image = $filename;
                $patient->save();

                // Send a success response
                return response()->json(['status' => true, 'response' => 'Image upload successfully']);
            } else {
                return $this->sendResponse(null, null, '4', 'No image uploaded.');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }


    public function getUserImage($userId)
    {
        try {
            $patient = Patient::where('UserId', $userId)->where('user_type', 1)->first();

            if (!$patient) {
                return $this->sendResponse(null, null, '3', 'Patient not found.');
            }


            $assetUrl = asset("UserImages/{$patient->user_image}");

            return $this->sendResponse("UserImage", $assetUrl, '1', 'Image retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }




    public function UserEdit($userId)
    {
        $userDetails = Patient::where('UserId', $userId)->where('user_type', 1)->first();
        if (!$userDetails) {
            $response = ['message' => 'User not found with the given UserId'];
            return response()->json($response, 404);
        }
        if ($userDetails->user_image === null) {
            $userDetails->UserProfile = null;
        } else {

            $userDetails->UserProfile = asset("UserImages/{$userDetails->user_image}");
        }

        return $this->sendResponse('Userdetails', $userDetails, '1', 'User retrieved successfully.');
    }

    // public function getallfavourites($id)
    // {
    //     try {
    //           $authenticatedUserId = auth()->user()->id;
    //         if (!auth()->check()) {
    //             return response()->json(['status' => false, 'response' => "Unauthorized"]);
    //         }
    //         $authenticatedUserId = auth()->user()->id;

    //         $specializeArray['specialize'] = Specialize::all();
    //         $specificationArray['specification'] = Specification::all();
    //         $subspecificationArray['subspecification'] = Subspecification::all();
    //         $favoriteDoctors = Favouritestatus::where('UserId', $id)->get();
    //         if ($favoriteDoctors->isEmpty()) {
    //             return response()->json(['status' => true, 'Favorite Doctors' => [], 'message' => 'Success']);
    //         }
    //         $doctorsWithSpecifications = [];
    //         foreach ($favoriteDoctors as $favoriteDoctor) {
    //             $doctor = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
    //                 ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
    //                 ->select(
    //                     'docter.UserId',
    //                     'docter.id',
    //                     'docter.docter_image',
    //                     'docter.firstname',
    //                     'docter.lastname',
    //                     'docter.specialization_id',
    //                     'docter.subspecification_id',
    //                     'docter.specification_id',
    //                     'docter.about',
    //                     'docter.location',
    //                     'docter.latitude',
    //                     'docter.longitude',
    //                     'clinics.clinic_id as avaliblityId',
    //                     'docter.gender',
    //                     'docter.email',
    //                     'docter.mobileNo',
    //                     'docter.Services_at',
    //                     'clinics.clinic_name',
    //                     'clinics.clinic_start_time',
    //                     'clinics.clinic_end_time',
    //                     'clinics.address',
    //                     'clinics.location',
    //                     'clinics.clinic_description',
    //                     'clinics.clinic_main_image',
    //                     'clinics.latitude as clinic_latitude',
    //                     'clinics.longitude as clinic_longitude',
    //                 )
    //                 ->where('docter.UserId', $favoriteDoctor->doctor_id)
    //                 ->get();


    //             //user location
    //             $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //             $user_latitude = $current_location_data ? $current_location_data->latitude : null;
    //             $user_longitude = $current_location_data ? $current_location_data->longitude : null;

    //             foreach ($doctor as $doc) {
    //                 $id = $doc['id'];
    //                 $favoriteStatus = DB::table('addfavourite')
    //                     ->where('UserId', $authenticatedUserId)
    //                     ->where('doctor_id', $doc['UserId'])
    //                     ->exists();
    //                 if (!isset($doctorsWithSpecifications[$id])) {
    //                     $specialize = $specializeArray['specialize']->firstWhere('id', $doc['specialization_id']);
    //                     //distance
    //                     $doctorsWithSpecifications[$id] = [
    //                         'id' => $id,
    //                         'UserId' => $doc['UserId'],
    //                         'firstname' => $doc['firstname'],
    //                         'secondname' => $doc['lastname'],
    //                         // 'distance_from_user' => $distance,
    //                         'Specialization' => $specialize ? $specialize['specialization'] : null,
    //                         'DocterImage' => asset("DocterImages/images/{$doc['docter_image']}"),
    //                         'Location' => $doc['location'],
    //                         'MainHospital' => $doc['Services_at'],
    //                         'clinics' => [],
    //                         'favoriteStatus' => $favoriteStatus ? 1 : 0,
    //                     ];
    //                 }
    //                 ///
    //                 $current_date = Carbon::now()->toDateString();
    //                 $current_time = Carbon::now()->toDateTimeString();

    //                 //                     //clinic wise location
    //                 $userLocation = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //                 if ($userLocation && $doc['clinic_latitude'] && $doc['clinic_longitude']) {
    //                     $distanceService = new DistanceMatrixService(config('services.google.api_key'));
    //                     $clinicDetails['distance'] = $distanceService->getDistance($userLocation->latitude, $userLocation->longitude, $doc['clinic_latitude'], $doc['clinic_longitude']);
    //                 } else {
    //                     $clinicDetails['distance'] = null;
    //                 }

    //                 //                     /////////////////////
    //                 $total_token_count = NewTokens::where('clinic_id', $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->where('doctor_id', $id)
    //                     ->count();

    //                 $available_token_count = NewTokens::where('clinic_id', $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->where('token_booking_status', NULL)
    //                     ->where('token_start_time', '>', $current_time)
    //                     ->where('doctor_id', $id)
    //                     ->count();
    //                 //
    //                 // token counts
    //                 $current_date = Carbon::now()->toDateString();
    //                 $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
    //                     ->where('doctor_id', $id)
    //                     ->where('clinic_id', $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->orderBy('token_start_time', 'ASC')
    //                     ->first();

    //                     $consultation_fee = ClinicConsultation::where('doctor_id', $id)
    //                     ->where('clinic_id', $doc['avaliblityId'])
    //                     ->value('consultation_fee');

    //                 $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
    //                     ->where('doctor_id', $id)
    //                     ->where('clinic_id',  $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->orderBy('token_start_time', 'DESC')
    //                     ->first();

    //                 if ($schedule_start_data && $schedule_end_data) {
    //                     $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
    //                     $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
    //                 } else {

    //                     $start_time = null;
    //                     $end_time = null;
    //                 }
    //                 //

    //                 $next_available_token_time = NewTokens::where('clinic_id', $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->where('token_booking_status', null)
    //                     ->where('token_start_time', '>', $current_time)
    //                     ->where('doctor_id', $doc['id'])
    //                     ->orderBy('token_start_time', 'ASC')
    //                     ->value('token_start_time');
    //                 $next_date_available_token_time = NewTokens::where('clinic_id', $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', '>', $current_date)
    //                     ->where('token_booking_status', NULL)
    //                     ->orderBy('token_scheduled_date', 'ASC')
    //                     ->where('token_start_time', '>', $current_time)
    //                     ->where('doctor_id', $doc['id'])
    //                     ->orderBy('token_start_time', 'ASC')
    //                     ->value('token_start_time');
    //                 Log::info('Token Time: ' . $next_date_available_token_time);

    //                 if ($next_date_available_token_time) {
    //                     $next_date_token_carbon = Carbon::createFromFormat('Y-m-d H:i:s', $next_date_available_token_time);

    //                     if ($next_date_token_carbon->isTomorrow()) {
    //                         $formatted_next_date_available_token_time =   $next_date_token_carbon->format('h:i A') . " Tomorrow ";
    //                     } else {
    //                         $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A d M');
    //                     }
    //                 } else {
    //                     $formatted_next_date_available_token_time = null;
    //                 }

    //                 $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;


    //                 $clinicDetails = [
    //                     'clinic_id' => $doc['avaliblityId'],
    //                     'clinic_name' => $doc['clinic_name'],
    //                     'clinic_start_time' => $start_time,
    //                     'clinic_end_time' => $end_time,
    //                     'clinic_address' => $doc['address'],
    //                     'clinic_location' => $doc['location'],
    //                     'clinic_main_image' => isset($doc['clinic_main_image']) ? asset("clinic_images/{$doc['clinic_main_image']}") : null,
    //                     'clinic_description' => $doc['clinic_description'],
    //                     'total_token_Count' => $total_token_count,
    //                     'available_token_count' => $available_token_count,
    //                     'next_available_token_time' => $next_available_token_time,
    //                     'next_date_available_token_time' => $formatted_next_date_available_token_time,
    //                     'distance_from_clinic' => $clinicDetails['distance'],
    //                     'consultation_fee' => $consultation_fee,
    //                 ];

    //                 $doctorsWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //             }
    //         }
    //         ////first token generated clinic show first
    //         if (!empty($doctorsWithSpecifications[$id]['clinics'])) {
    //             usort($doctorsWithSpecifications[$id]['clinics'], function ($clinicA, $clinicB) {
    //                 return $clinicB['available_token_count'] <=> $clinicA['available_token_count'];
    //             });
    //         }

    //         // if (!empty($doctorsWithSpecifications)) {
    //         //     usort($doctorsWithSpecifications, function ($a, $b) {
    //         //         return $a['distance_from_user'] <=> $b['distance_from_user'];
    //         //     });
    //         // }

    //         $formattedOutput = array_values($doctorsWithSpecifications);
    //         return response()->json(['status' => true, 'Favorite Doctors' => $formattedOutput, 'message' => 'Success']);
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         return response()->json(['status' => false, 'response' => "Internal Server Error"]);
    //     }
    // }

    public function getallfavourites($id)
    {
        try {
            // $authenticatedUserId = auth()->user()->id;
            if (!auth()->check()) {
                return response()->json(['status' => false, 'response' => "Unauthorized"]);
            }
            $authenticatedUserId = auth()->user()->id;

            $specializeArray['specialize'] = Specialize::all();
            $specificationArray['specification'] = Specification::all();
            $subspecificationArray['subspecification'] = Subspecification::all();
            $favoriteDoctors = Favouritestatus::where('UserId', $id)->get();
            if ($favoriteDoctors->isEmpty()) {
                return response()->json(['status' => true, 'Favorite Doctors' => [], 'message' => 'Success']);
            }

            $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
            $user_latitude = $current_location_data ? $current_location_data->latitude : null;
            $user_longitude = $current_location_data ? $current_location_data->longitude : null;

            $distanceHelper = new \App\Helpers\UserDistanceHelper();


            $doctorsWithSpecifications = [];
            foreach ($favoriteDoctors as $favoriteDoctor) {
                $doctor = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                    ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
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
                        'clinics.clinic_main_image',
                        'clinics.latitude as clinic_latitude',
                        'clinics.longitude as clinic_longitude',
                    )
                    ->where('docter.UserId', $favoriteDoctor->doctor_id)
                    ->get();

                foreach ($doctor as $doc) {
                    $id = $doc['id'];
                    $favoriteStatus = DB::table('addfavourite')
                        ->where('UserId', $authenticatedUserId)
                        ->where('doctor_id', $doc['UserId'])
                        ->exists();
                    if (!isset($doctorsWithSpecifications[$id])) {
                        $specialize = $specializeArray['specialize']->firstWhere('id', $doc['specialization_id']);
                        //distance
                        $doctorsWithSpecifications[$id] = [
                            'id' => $id,
                            'UserId' => $doc['UserId'],
                            'firstname' => $doc['firstname'],
                            'secondname' => $doc['lastname'],
                            // 'distance_from_user' => $distance,
                            'Specialization' => $specialize ? $specialize['specialization'] : null,
                            'DocterImage' => asset("DocterImages/images/{$doc['docter_image']}"),
                            'Location' => $doc['location'],
                            'MainHospital' => $doc['Services_at'],
                            'clinics' => [],
                            'favoriteStatus' => $favoriteStatus ? 1 : 0,
                        ];
                    }
                    ///////clinics lattitude and longitude
                    $clinicDetails = [];
                    $clinicDetails['distance'] = $distanceHelper->calculateHaversineDistance(
                        $user_latitude,
                        $user_longitude,
                        $doc->clinic_latitude,
                        $doc->clinic_longitude
                    );
                    ///////////
                    $current_date = Carbon::now()->toDateString();
                    $current_time = Carbon::now()->toDateTimeString();

                    //                     //clinic wise location
                    // $userLocation = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
                    // if ($userLocation && $doc['clinic_latitude'] && $doc['clinic_longitude']) {
                    //     $distanceService = new DistanceMatrixService(config('services.google.api_key'));
                    //     $clinicDetails['distance'] = $distanceService->getDistance($userLocation->latitude, $userLocation->longitude, $doc['clinic_latitude'], $doc['clinic_longitude']);
                    // } else {
                    //     $clinicDetails['distance'] = null;
                    // }

                    //                     /////////////////////
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
                    //
                    // token counts
                    $current_date = Carbon::now()->toDateString();
                    $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                        ->where('doctor_id', $id)
                        ->where('clinic_id', $doc['avaliblityId'])
                        ->where('token_scheduled_date', $current_date)
                        ->orderBy('token_start_time', 'ASC')
                        ->first();

                    $consultation_fee = ClinicConsultation::where('doctor_id', $id)
                        ->where('clinic_id', $doc['avaliblityId'])
                        ->value('consultation_fee');

                    $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                        ->where('doctor_id', $id)
                        ->where('clinic_id',  $doc['avaliblityId'])
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
                    //

                    $next_available_token_time = NewTokens::where('clinic_id', $doc['avaliblityId'])
                        ->where('token_scheduled_date', $current_date)
                        ->where('token_booking_status', null)
                        ->where('token_start_time', '>', $current_time)
                        ->where('doctor_id', $doc['id'])
                        ->orderBy('token_start_time', 'ASC')
                        ->value('token_start_time');
                    $next_date_available_token_time = NewTokens::where('clinic_id', $doc['avaliblityId'])
                        ->where('token_scheduled_date', '>', $current_date)
                        ->where('token_booking_status', NULL)
                        ->orderBy('token_scheduled_date', 'ASC')
                        ->where('token_start_time', '>', $current_time)
                        ->where('doctor_id', $doc['id'])
                        ->orderBy('token_start_time', 'ASC')
                        ->value('token_start_time');
                    Log::info('Token Time: ' . $next_date_available_token_time);

                    if ($next_date_available_token_time) {
                        $next_date_token_carbon = Carbon::createFromFormat('Y-m-d H:i:s', $next_date_available_token_time);

                        if ($next_date_token_carbon->isTomorrow()) {
                            $formatted_next_date_available_token_time =   $next_date_token_carbon->format('h:i A') . " Tomorrow ";
                        } else {
                            $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A d M');
                        }
                    } else {
                        $formatted_next_date_available_token_time = null;
                    }

                    $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;


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
                        'next_date_available_token_time' => $formatted_next_date_available_token_time,
                        'distance_from_clinic' => $clinicDetails['distance'],
                        'consultation_fee' => $consultation_fee,
                    ];

                    $doctorsWithSpecifications[$id]['clinics'][] = $clinicDetails;
                }
            }
            ////first token generated clinic show first
            if (!empty($doctorsWithSpecifications[$id]['clinics'])) {
                usort($doctorsWithSpecifications[$id]['clinics'], function ($clinicA, $clinicB) {
                    return $clinicB['available_token_count'] <=> $clinicA['available_token_count'];
                });
            }

            // if (!empty($doctorsWithSpecifications)) {
            //     usort($doctorsWithSpecifications, function ($a, $b) {
            //         return $a['distance_from_user'] <=> $b['distance_from_user'];
            //     });
            // }

            $formattedOutput = array_values($doctorsWithSpecifications);
            return response()->json(['status' => true, 'Favorite Doctors' => $formattedOutput, 'message' => 'Success']);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function UserLogin(Request $req)
    {
        // validate inputs
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string'
        ];
        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            $response = ['message' => 'Validation failed', 'errors' => $validator->errors()];
            Log::channel('doctor_schedules')->info("$response");

            return response()->json($response, 422);
        }

        // find user email in users table
        $user = User::where('email', $req->email)->first();

        if (!$user) {
            $response = ['message' => 'User Not found'];
            Log::channel('doctor_schedules')->info("$response");

            return response()->json($response, 400);
        }

        // if user email found and password is correct
        if ($user && Hash::check($req->password, $user->password)) {

            $token = $user->createToken('Personal Access Token')->plainTextToken;

            $response = ['user' => $user, 'token' => $token];
            return response()->json($response, 200);
        }

        $response = ['message' => 'Incorrect email or password'];
        return response()->json($response, 400);
    }



    private function getClinics($doctorUserId)
    {
        // Assuming 'UserId' is the column name in the 'Docter' model
        $docter = Docter::select('id')->where('UserId', $doctorUserId)->first();

        if (!$docter) {
            // Handle the case where the doctor with the given user ID is not found
            return []; // or throw an exception, log an error, etc.
        }

        $clinics = DocterAvailability::where('docter_id', $docter->id)
            ->get(['id', 'hospital_Name', 'startingTime', 'endingTime', 'address', 'location']);

        return $clinics;
    }




    public function GetUserCompletedAppoinments(Request $request, $userId)
    {
        try {
            // Get the currently authenticated doctor
            $doctor = Patient::where('UserId', $userId)->first();


            if (!$doctor) {
                return response()->json(['message' => 'Patient not found.'], 404);
            }

            // Validate the date format (if needed)

            // Get all appointments for the doctor on the selected date
            $appointments = Patient::join('token_booking', 'token_booking.BookedPerson_id', '=', 'patient.UserId')
                ->join('docter', 'docter.UserId', '=', 'token_booking.doctor_id') // Join the doctor table
                ->where('patient.UserId', $doctor->UserId)
                ->orderByRaw('CAST(token_booking.TokenNumber AS SIGNED) ASC')
                ->where('Is_completed', 1)
                ->distinct()
                ->get(['token_booking.*', 'docter.*']);

            // Initialize an array to store appointments along with doctor details
            $appointmentsWithDetails = [];

            // Iterate through each appointment and add symptoms information
            foreach ($appointments as $appointment) {
                $symptoms = json_decode($appointment->Appoinmentfor_id, true);

                // Extract appointment details
                $appointmentDetails = [
                    'TokenNumber' => $appointment->TokenNumber,
                    'Date' => $appointment->date,
                    'Startingtime' => $appointment->TokenTime,
                    'PatientName' => $appointment->PatientName,
                    'main_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor1'])->get()->toArray(),
                    'other_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray(),
                ];

                $clinicName = DocterAvailability::where('id', $appointment->clinic_id)->value('hospital_Name');
                $doctorDetails = [
                    'firstname' => $appointment->firstname,
                    'secondname' => $appointment->lastname,
                    'Specialization' => $appointment->specialization,
                    'DocterImage' => asset("DocterImages/images/{$appointment->docter_image}"),
                    'Mobile Number' => $appointment->mobileNo,
                    'MainHospital' => $clinicName,
                    'subspecification_id' => $appointment->subspecification_id,
                    'specification_id' => $appointment->specification_id,
                    'specifications' => explode(',', $appointment->specifications),
                    'subspecifications' => explode(',', $appointment->subspecifications),
                    'clincs' => $this->getClinics($appointment->doctor_id),
                ];




                // Combine appointment and doctor details
                $combinedDetails = array_merge($appointmentDetails, $doctorDetails);

                // Add to the array
                $appointmentsWithDetails[] = $combinedDetails;
            }

            // Return a success response with the appointments and doctor details
            return $this->sendResponse('Appointments', $appointmentsWithDetails, '1', 'Appointments retrieved successfully.');
        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }



    public function getPatientCompletedAppointments($userId)
    {
        $patient_data = Patient::where('UserId', $userId)->first();

        if (!$patient_data) {
            return response()->json([
                'status' => true,
                'completed_appointments' => NULL,
                'message' => 'Patient not found'
            ]);
        }

        try {

            // $completed_token_details = NewTokens::select('token_id', 'doctor_id', 'clinic_id', 'token_number', 'token_start_time')
            //     ->where('booked_user_id', $userId)
            //     ->where('is_checkedout', 1)
            //     ->orderBy('updated_at', 'DESC')
            //     ->get();

            $completed_token_details = CompletedAppointments::select('doctor_id', 'clinic_id', 'token_number', 'token_start_time')
                ->where('booked_user_id', $userId)
                //->where('is_checkedout', 1)
                ->orderBy('updated_at', 'DESC')
                ->get();

            if (!$completed_token_details) {
                return response()->json([
                    'status' => true,
                    'completed_appointments' => NULL,
                    'message' => 'No completed tokens found'
                ]);
            }

            $completed_appointments = [];
            foreach ($completed_token_details as $completed_tokens) {
                $appointment = [

                    'doctor_id' => $completed_tokens->doctor_id,
                    'clinic_id' => $completed_tokens->clinic_id,
                    'token_number' => $completed_tokens->token_number,
                    'token_start_time' => $completed_tokens->token_start_time,
                ];
                //doctor details
                $doctor_id = $completed_tokens->doctor_id;
                $doctor_details = Docter::select('firstname', 'lastname', 'docter_image')
                    ->where('id', $doctor_id)
                    ->first();
                if (!$doctor_details) {
                    return response()->json(['message' => 'Doctor not found.'], 404);
                }

                $appointment['doctor_name'] = $doctor_details->firstname . ' ' . $doctor_details->lastname;

                //$appointment['doctor_image'] = $doctor_details->docter_image;
                $doctor_image = $doctor_details->docter_image;
                $userImage = $doctor_image ? asset("DocterImages/images/{$doctor_image}") : null;
                $appointment['doctor_image'] = $userImage;
                //clinic details

                $clinic_id = $completed_tokens->clinic_id;
                $clinic_details = Clinic::where('clinic_id', $clinic_id)->first();
                $appointment['clinic_name'] = $clinic_details ? $clinic_details->clinic_name : null;

                //symptoms
                $token_number_loop  = $completed_tokens->token_number;
                $ClinicId = $completed_tokens->clinic_id;

                $main_symptoms = MainSymptom::select('Mainsymptoms')
                    ->where('user_id', $userId)
                    ->where('TokenNumber', $token_number_loop)
                    ->where('clinic_id', $ClinicId)
                    ->first();
                $appointment['main_symptoms'] = $main_symptoms;
                $other_symptom_id = CompletedAppointments::select('appointment_for')
                    ->where('booked_user_id', $userId)
                    ->where('token_number', $token_number_loop)
                    ->where('clinic_id', $ClinicId)
                    ->orderBy('created_at', 'DESC')->first();

                $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);

                $other_symptom_array_value = [];

                if (isset($other_symptom_json['Appoinmentfor2'])) {
                    $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
                }
                $other_symptom = Symtoms::select('symtoms')
                    ->where('id', $other_symptom_array_value)
                    ->first();
                $appointment['other_symptom'] = $other_symptom;

                /////patient data
                // $patient_details_data = TokenBooking::select(
                //     'PatientName',
                //     'notes',
                //     'ReviewAfter',
                //     'labtest',
                //     'Tokentime',
                //     'date',
                //     'prescription_image',
                //     'lab_id',
                //     'id',
                //     'medicalshop_id'
                // )
                //     ->where('BookedPerson_id', $userId)
                //     ->where('TokenNumber', $token_number_loop)
                //     ->where('clinic_id', $ClinicId)
                //     ->orderBy('created_at', 'DESC')->first();


                $patient_details_data = CompletedAppointments::select(
                    'completed_appointments.patient_id',
                    'completed_appointments.notes',
                    'completed_appointments.review_after',
                    'completed_appointments.labtest',
                    'completed_appointments.token_start_time',
                    'completed_appointments.date',
                    'completed_appointments.prescription_image',
                    'completed_appointments.lab_id',
                    //  'completed_appointments.id',
                    'completed_appointments.medical_shop_id',
                    'patient.firstname'
                )
                    ->leftJoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
                    ->where('booked_user_id', $userId)
                    ->where('token_number', $token_number_loop)
                    ->where('clinic_id', $ClinicId)
                    //->orderBy('created_at', 'DESC')
                    ->first();

                $appointment['patient_name'] = $patient_details_data->firstname;
                $appointment['notes'] = $patient_details_data->notes;
                $appointment['review_after'] = $patient_details_data->ReviewAfter;
                $appointment['lab_test'] = $patient_details_data->labtest;
                $appointment['token_time'] = $patient_details_data->token_start_time;
                $appointment['token_date'] = $patient_details_data->date;
                $appointment['prescription_image'] = asset("LabImages/prescription/{$patient_details_data->prescription_image}") ?? null;

                if ($patient_details_data->prescription_image == null) {

                    $appointment['prescription_image'] = null;
                }


                //lab details
                $lab_data = Laboratory::select('firstname')->where('id', $patient_details_data->lab_id)->first();
                $appointment['lab_name'] = isset($lab_data->firstname) ? $lab_data->firstname : null;
                // Medical store
                $medical_store_data = Medicalshop::select('firstname')
                    ->where('id', $patient_details_data->medicalshop_id)
                    ->first();
                $appointment['medical_store_name'] = isset($medical_store_data->firstname) ? $medical_store_data->firstname : null;

                // Medical prescriptions
                $medical_prescriptions = Medicine::select('medicineName', 'Dosage', 'NoofDays', 'Noon', 'night', 'morning', 'type')
                    ->where('user_id', $completed_tokens->patient_id)
                    ->where('token_id', $completed_tokens->token_number)
                    ->where('medicine_type', 2)
                    ->get();

                $prescriptions_data = [];

                foreach ($medical_prescriptions as $medical_prescription) {
                    $prescription_data = [
                        'medicine_name' => $medical_prescription->medicineName,
                        'medicine_dosage' => $medical_prescription->Dosage,
                        'medicine_number_ofdays' => $medical_prescription->NoofDays,
                        'Noon' => $medical_prescription->Noon,
                        'night' => $medical_prescription->night,
                        'morning' => $medical_prescription->morning,
                        'medicine_type' => $medical_prescription->type,
                    ];


                    $prescriptions_data[] = $prescription_data;
                }


                $appointment['medical_prescriptions'] = $prescriptions_data;
                $appointment_token_time = $appointment['token_time'];
                $appointment_token_time = Carbon::parse($appointment_token_time)->format('h:i:A');
                $appointment['token_time'] = $appointment_token_time;

                $completed_appointments[] = $appointment;
            }

            return response()->json(['status' => true, 'completed_appointments' => $completed_appointments]);
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }


    public function favouritestatus(Request $request)
    {
        $userId = $request->user_id;
        $docterId = $request->docter_id;
        $existingFavourite = Favouritestatus::where('UserId', $userId)
            ->where('doctor_id', $docterId)
            ->first();

        if ($existingFavourite) {
            Favouritestatus::where('doctor_id', $docterId)->where('UserId', $userId)->delete();
            return response()->json(['status' => true, 'message' => 'Favorite removed successfully.']);
        } else {
            $addfav = new Favouritestatus();
            $addfav->UserId = $userId;
            $addfav->doctor_id = $docterId;
            $addfav->save();
        }

        return response()->json(['status' => true, 'message' => 'Favorite added successfully.']);
    }



    public function uploadDocument(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'document'    => 'required|mimes:doc,docx,pdf,jpeg,png,jpg',

        ];
        $messages = [
            'document.required' => 'Document is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $patient_doc = new PatientDocument();
            $patient_doc->user_id = $request->user_id;
            if ($request->hasFile('document')) {
                $imageFile = $request->file('document');
                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('user/documents'), $imageName);
                    $patient_doc->document = $imageName;
                }
            }
            $patient_doc->patient_id = $request->patient_id;
            $patient_doc->save();
            $patient_doc->document = asset('user/documents') . '/' . $patient_doc->document;
            return response()->json(['status' => true, 'response' => "Uploading Success", 'document' => $patient_doc]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function updateDocument(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
            'document_id'    => 'required',
            'patient_id'     => 'required',
            'type'           => 'required',
            'test_name'      => 'sometimes',
            'lab_name'       => 'sometimes',
            'doctor_name'    => 'sometimes',
            'date'           => 'sometimes',
            'admitted_for'   => 'sometimes',
            'file_name'   => 'sometimes',
        ];
        $messages = [
            'document_id.required' => 'DocumentId is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        // try {
        DB::beginTransaction();
        $user = User::where('id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['status' => false, 'response' => "User not found"]);
        }

        $document = PatientDocument::where('id', $request->document_id)->first();
        if (!$document) {
            return response()->json(['status' => false, 'response' => 'Document not found']);
        }
        $this->updateDocumentType($request, $document);
        DB::commit();
        return response()->json(['status' => true, 'response' => "File Uploaded successfully"]);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        // }
    }


    private function updateDocumentType(Request $request, PatientDocument $document)
    {
        $type = $request->type;

        if ($type == '1' || $type == '2' || $type == '3' || $type == '4') {
            $model = ($type == '1') ? LabReport::class : (($type == '2') ? PatientPrescriptions::class : (($type == '3') ? DischargeSummary::class : ScanReport::class));
            $record = $model::where('user_id', $request->user_id)->where('document_id', $request->document_id)->first();

            if (!$record) {
                $record = new $model();
            }

            $record->patient_id = $request->patient_id;
            $record->user_id = $request->user_id;
            $record->document_id = $request->document_id;
            $record->date = $request->date;
            $record->doctor_name = $request->doctor_name;

            $record->test_name = $request->test_name ?? null;
            $record->lab_name  = $request->lab_name ?? null;
            $record->hospital_name = $request->hospital_name ?? null;
            $record->admitted_for = $request->admitted_for ?? null;


            if ($request->notes) {
                $record->notes = $request->notes;
            }

            if ($request->file_name) {
                $this->updateDocumentFile($request->file_name, $document, $record);
            }

            $record->save();
            $document->patient_id = $request->patient_id;
            $document->status = 1;
            $document->type = $type;
            $document->save();
        }
    }


    private function updateDocumentFile($fileName, PatientDocument $document, $record)
    {
        log::info('Start document save');
        $oldFilePath = public_path('user/documents/' . $document->document);
        log::info('Old file path: ' . $oldFilePath);

        if (!File::exists($oldFilePath)) {
            log::info('File not found');
            return response()->json(['status' => false, 'response' => 'File not found']);
        }

        $newFileName = $fileName;
        log::info('New file name: ' . $newFileName);

        $newFileNameWithExtension = $newFileName . '.' . pathinfo($oldFilePath, PATHINFO_EXTENSION);
        log::info('New file name with extension: ' . $newFileNameWithExtension);

        $newFilePath = public_path('user/documents/' . $newFileNameWithExtension);
        log::info('New file path: ' . $newFilePath);

        // Move the file to the new name
        File::move($oldFilePath, $newFilePath);

        // Update the file name in the database
        $record->file_name = $newFileName;
        $document->document = $newFileNameWithExtension;
        $document->save();

        log::info('Document file updated successfully');
    }




    public function deleteDocument($user_id, $document_id, $type)
    {


        try {
            DB::beginTransaction();

            $user = User::find($user_id);

            if (!$user) {
                return response()->json(['status' => false, 'response' => 'User not found']);
            }

            $document = PatientDocument::where('id', $document_id)
                ->where('user_id', $user_id)
                ->where('type', $type)
                ->first();

            if (!$document) {
                return response()->json(['status' => false, 'response' => 'Document not found']);
            }

            $this->deleteDocumentFile($document);

            if ($type == '1') {
                LabReport::where('user_id', $user_id)
                    ->where('document_id', $document_id)
                    ->delete();
            } elseif ($type == '2') {
                PatientPrescriptions::where('user_id', $user_id)
                    ->where('document_id', $document_id)
                    ->delete();
            } elseif ($type == '3') {
                DischargeSummary::where('user_id', $user_id)
                    ->where('document_id', $document_id)
                    ->delete();
            } elseif ($type == '4') {
                ScanReport::where('user_id', $user_id)
                    ->where('document_id', $document_id)
                    ->delete();
            }

            $document->delete();

            DB::commit();

            return response()->json(['status' => true, 'response' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'response' => 'Internal Server Error']);
        }
    }
    private function deleteDocumentFile(PatientDocument $document)
    {
        $filePath = public_path('user/documents/' . $document->document);

        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }


    // public function getUploadedDocuments(Request $request)
    // {
    //     $rules = [
    //         'user_id'     => 'required',
    //         'patient_id'  => 'required'
    //     ];
    //     $messages = [
    //         'user_id.required' => 'UserId is required',
    //     ];
    //     $validation = Validator::make($request->all(), $rules, $messages);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }

    //     try {
    //         $patient_doc = PatientDocument::select(
    //             'id',
    //             'user_id',
    //             'status',
    //             'patient_id',
    //             'type',
    //             'created_at',
    //             'updated_at',
    //             DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path")
    //         )
    //             ->where('user_id', $request->user_id)
    //             ->when($request->has('type'), function ($query) use ($request) {
    //                 return $query->where('type', $request->type);
    //             })
    //             ->distinct()
    //             ->where('patient_id', $request->patient_id);


    //         switch ($request->type) {
    //             case 1:
    //                 $patient_doc->with(['LabReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,test_name,file_name AS name']);
    //                 break;
    //             case 2:
    //                 $patient_doc->with(['PatientPrescription:id,document_id,date,doctor_name,notes,admitted_for,hospital_name,file_name as name']);
    //                 break;
    //             case 3:
    //                 $patient_doc->with(['DischargeSummary:id,document_id,date,doctor_name,doctor_name,notes,admitted_for,hospital_name,file_name as name']);
    //                 break;
    //             case 4:
    //                 $patient_doc->with(['ScanReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,file_name as name']);
    //                 break;
    //             default:
    //                 $patient_doc->with(['PatientPrescription:id,document_id,date,doctor_name,notes,admitted_for,hospital_name,file_name as name'])
    //                     ->with(['LabReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,test_name,file_name AS name'])
    //                     ->with(['DischargeSummary:id,document_id,date,doctor_name,doctor_name,notes,admitted_for,hospital_name,file_name as name'])
    //                     ->with(['ScanReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,file_name as name']);
    //         }

    //         $patient_doc = $patient_doc->orderBy('created_at', 'desc')->get();

    //         foreach ($patient_doc as $patient_document) {
    //             $patient_detail = Patient::where('id', $request->patient_id)->first();
    //             $formattedDate = Carbon::parse($patient_document->created_at)->format('Y/m/d');
    //             $patient_document->date = $formattedDate;
    //             $patient_document->patient = $patient_detail->firstname;

    //             $updatedTimestamp = Carbon::parse($patient_document->updated_at);
    //             $currentTimestamp = Carbon::now();
    //             $timeDifference = $currentTimestamp->diff($updatedTimestamp);
    //             $hours = $timeDifference->h + $timeDifference->days * 24;
    //             $minutes = $timeDifference->i;
    //             $patient_document->hours_ago = ($hours == 0) ? "{$minutes} mins ago" : "{$hours} hr & {$minutes} mins ago";
    //         }

    //         if ($patient_doc->isEmpty()) {
    //             $patient_doc = null;
    //         }

    //         return response()->json(['status' => true, 'document_data' => $patient_doc]);
    //     } catch (\Exception $e) {
    //         dd($e->getMessage());
    //         return response()->json(['status' => false, 'response' => "Internal Server Error"]);
    //     }
    // }

    public function getUploadedDocuments(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'patient_id'  => 'required'
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $patient_doc = PatientDocument::select(
                'id',
                'user_id',
                'status',
                'patient_id',
                'type',
                'created_at',
                'updated_at',
                DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path")
            )
                ->where('user_id', $request->user_id)
                ->when($request->has('type'), function ($query) use ($request) {
                    return $query->where('type', $request->type);
                })
                ->distinct()
                ->where('patient_id', $request->patient_id);


            switch ($request->type) {
                case 1:
                    $patient_doc->with(['LabReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,test_name,file_name AS name']);
                    break;
                case 2:
                    $patient_doc->with(['PatientPrescription:id,document_id,date,doctor_name,notes,admitted_for,hospital_name,file_name as name']);
                    break;
                case 3:
                    $patient_doc->with(['DischargeSummary:id,document_id,date,doctor_name,doctor_name,notes,admitted_for,hospital_name,file_name as name']);
                    break;
                case 4:
                    $patient_doc->with(['ScanReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,file_name as name']);
                    break;
                default:
                    $patient_doc->with(['PatientPrescription:id,document_id,date,doctor_name,notes,admitted_for,hospital_name,file_name as name'])
                        ->with(['LabReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,test_name,file_name AS name'])
                        ->with(['DischargeSummary:id,document_id,date,doctor_name,doctor_name,notes,admitted_for,hospital_name,file_name as name'])
                        ->with(['ScanReport:id,document_id,date,test_name,doctor_name,lab_name,notes,admitted_for,hospital_name,file_name as name']);
            }

            $patient_doc = $patient_doc->orderBy('created_at', 'desc')->get();

            // foreach ($patient_doc as $patient_document) {
            //     $patient_detail = Patient::where('id', $request->patient_id)->first();
            //     $formattedDate = Carbon::parse($patient_document->created_at)->format('Y/m/d');
            //     $patient_document->date = $formattedDate;
            //     $patient_document->patient = $patient_detail->firstname;

            //     $updatedTimestamp = Carbon::parse($patient_document->updated_at);
            //     $currentTimestamp = Carbon::now();
            //     $timeDifference = $currentTimestamp->diff($updatedTimestamp);
            //     $hours = $timeDifference->h + $timeDifference->days * 24;
            //     $minutes = $timeDifference->i;
            //     $patient_document->hours_ago = ($hours == 0) ? "{$minutes} mins ago" : "{$hours} hr & {$minutes} mins ago";
            // }

            foreach ($patient_doc as $patient_document) {
                $patient_detail = Patient::where('id', $request->patient_id)->first();
                $formattedDate = Carbon::parse($patient_document->created_at)->format('Y/m/d');
                $patient_document->date = $formattedDate;
                $patient_document->patient = $patient_detail->firstname;

                $updatedTimestamp = Carbon::parse($patient_document->updated_at);
                $currentTimestamp = Carbon::now();
                $timeDifference = $currentTimestamp->diff($updatedTimestamp);
                $days = $timeDifference->days;
                $hours = $timeDifference->h;
                if ($days > 0) {
                    $patient_document->hours_ago = "$days day" . ($days > 1 ? 's' : '') . " ago";
                } else {
                    $patient_document->hours_ago = "$hours hr" . ($hours > 1 ? 's' : '') . " ago";
                }
            }


            if ($patient_doc->isEmpty()) {
                $patient_doc = null;
            }

            return response()->json(['status' => true, 'document_data' => $patient_doc]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    // public function getUploadedDocuments(Request $request)
    // {
    //     $rules = [
    //         'user_id'     => 'required',
    //         'patient_id'  => 'required'
    //     ];
    //     $messages = [
    //         'user_id.required' => 'UserId is required',
    //     ];
    //     $validation = Validator::make($request->all(), $rules, $messages);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }


    //     try {
    //         $patient_doc = PatientDocument::select(
    //             'id',
    //             'user_id',
    //             'status',
    //             'patient_id',
    //             'type',
    //             'created_at',
    //             'updated_at',
    //             DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path")
    //         )
    //             ->where('user_id', $request->user_id)
    //             ->where('patient_id', $request->patient_id)
    //             ->with(['PatientPrescription:id,document_id,date,doctor_name,file_name as name'])
    //             ->with(['LabReport:id,document_id,date,test_name,lab_name,test_name AS name']);


    //         if ($request->type) {
    //             $patient_doc = $patient_doc->where('type', $request->type);
    //             if ($request->type == 2) {
    //                 $patient_doc = $patient_doc->with(['PatientPrescription:id,document_id,date,doctor_name,file_name as name']);
    //             }
    //             if ($request->type == 1) {
    //                 $patient_doc = $patient_doc->with(['LabReport:id,document_id,date,test_name,lab_name,test_name AS name']);
    //             }
    //         }
    //         // $patient_doc = $patient_doc->get();

    //         $patient_doc = $patient_doc->orderBy('created_at', 'desc')->get();

    //         foreach ($patient_doc as $patient_document) {

    //             $patient_detail = Patient::where('id', $request->patient_id)->first();
    //             $formattedDate = Carbon::parse($patient_document->created_at)->format('Y/m/d');
    //             $patient_document->date = $formattedDate;
    //             $patient_document->patient = $patient_detail->firstname;

    //             ///ashwin
    //             $updatedTimestamp = Carbon::parse($patient_document->updated_at);
    //             $currentTimestamp = Carbon::now();
    //             $timeDifference = $currentTimestamp->diff($updatedTimestamp);
    //             $hours = $timeDifference->h + $timeDifference->days * 24;
    //             $minutes = $timeDifference->i;
    //             $patient_document->hours_ago = "{$hours} hrs & {$minutes} mins ago";
    //             ///end ashwin
    //         }

    //         if ($patient_doc->isEmpty()) {
    //             $patient_doc = null;
    //         }

    //         return response()->json(['status' => true, 'document_data' => $patient_doc]);
    //     } catch (\Exception $e) {
    //         dd($e->getMessage());
    //         return response()->json(['status' => false, 'response' => "Internal Server Error"]);
    //     }
    // }
    public function ReportsTimeLine(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'patient_id'  => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        $user = User::where('id', $request->user_id)->first();

        try {
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $time_line = PatientDocument::select('id', 'user_id', 'status', 'created_at', DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path"))
                ->where('user_id', $request->user_id)
                ->where('type', 1)
                ->whereHas('LabReport', function ($query) use ($request) {
                    $query->where('patient_id', $request->patient_id);
                })
                ->with('LabReport')
                ->get();
            if (!$time_line) {
                return response()->json(['status' => true, 'time_line' => null]);
            }
            return response()->json(['status' => true, 'time_line' => $time_line]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function getPrescriptions(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $prescriptions = PatientDocument::select('id', 'user_id', 'status', 'created_at', DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path"))
                ->where('user_id', $request->user_id)->where('type', 2)
                ->whereHas('PatientPrescription', function ($query) use ($request) {
                    $query->where('patient_id', $request->patient_id);
                })->with('PatientPrescription')->get();

            if (!$prescriptions) {
                return response()->json(['status' => true, 'prescriptions' => null]);
            }
            return response()->json(['status' => true, 'prescriptions' => $prescriptions]);
        } catch (\Exception $e) {

            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function manageMembers(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'first_name'  => 'required',
            'mobileNumber'  => 'required',
            'gender'      => 'required|in:1,2,3',
            'relation'    => 'required|in:1,2,3',
            'dateofbirth' => 'required|date_format:Y-m-d',
            'patient_image' => 'sometimes'
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
            'dateofbirth.date_format' => 'Date of birth should be in Y-m-d format',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {

            if ($request->relation == '1') {
                $patient_detail = Patient::where('user_type', 1)->first();
                if ($patient_detail) {
                    return response()->json(['status' => false, 'response' => "A profile is already in self"]);
                }
            }
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }

            if ($request->patient_id) {
                $patient = Patient::find($request->patient_id);
                $msg = "Member update successfully";
            } else {
                $patient = new Patient();
                $msg = "Member added successfully";
            }
            $patient->dateofbirth = $request->dateofbirth;
            $age = \Carbon\Carbon::parse($request->dateofbirth)->age;
            $patient->age = $age;
            //  dd($request->surgery_name);
            $surgeryDetails = null;
            // if 'surgery_name' is 'other' and 'surgery_details' is provided
            if ($request->surgery_name === '[Other]' && $request->has('surgery_details')) {
                $patient->surgery_name = $request->surgery_name;
                $surgeryDetails = $request->surgery_details;
            } else {
                // if there is no surgery details
                $patient->surgery_name = $request->surgery_name;
                $surgeryDetails = null;
            }
            $patient->surgery_details = $surgeryDetails;
            $treatmenttakenDetails = null;
            // if 'treatment_taken' is 'other' and 'treatment_taken_details' is provided
            if ($request->treatment_taken === '[Other]' && $request->has('treatment_taken_details')) {
                $patient->treatment_taken = $request->treatment_taken;
                $treatmenttakenDetails = $request->treatment_taken_details;
            } else {

                $patient->treatment_taken = $request->treatment_taken;
                $treatmenttakenDetails = null;
            }
            $patient->treatment_taken_details = $treatmenttakenDetails;
            //ashwin
            //check existing patients
            $existing_patients = Patient::where('age', $request->age)
                ->where('firstname', $request->first_name)
                ->where('UserId', $request->user_id)
                ->first();

            if ($existing_patients) {
                return response()->json(['status' => false, 'response' => "Family member already exists."], 400);
            }

            $newUniqueId = $this->generatePatientUniqueId();
            $mediezy_patient_id = $newUniqueId;
            //end ashwin

            $patient->firstname = $request->first_name;
            $patient->lastname = $request->last_name;
            $patient->mobileNo = $request->mobileNumber;
            $patient->gender    = $request->gender;
            $patient->age = $age;

            $patient->dateofbirth    = $request->dateofbirth;
            $patient->user_type = $request->relation;
            $patient->email     = $request->email;
            $patient->UserId    = $request->user_id;
            $patient->regularMedicine = $request->regularMedicine;

            if ($request->regularMedicine === "Yes") {
                $patient->illness = $request->illness;
                $patient->Medicine_Taken = $request->Medicine_Taken;
            }

            $surgeryString = is_array($request->surgery_name) ? implode(', ', $request->surgery_name) : $request->surgery_name;
            $TreatmentString = is_array($request->treatment_taken) ? implode(', ', $request->treatment_taken) : $request->treatment_taken;
            $patient->allergy_id = $request->allergy_id;
            $patient->allergy_name = $request->allergy_name;
            $patient->treatment_taken = $TreatmentString;
            $patient->mediezy_patient_id = $mediezy_patient_id;

            if ($request->hasFile('patient_image')) {
                $imageFile = $request->file('patient_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('UserImages'), $imageName);
                    $patient->user_image = $imageName;
                }
            }

            $patient->save();
            //   $medicines = json_decode($request->medicines, true);
            $medicines = $request->medicines;
            if ($medicines) {
                foreach ($medicines as $medicine) {
                    $newMedicine = new Medicine();
                    $newMedicine->user_id = $request->user_id;
                    $newMedicine->patient_id = $patient->id;
                    $newMedicine->medicineName = $medicine['medicineName'];
                    $newMedicine->illness = $medicine['illness'];
                    $newMedicine->save();
                }
            }

            return response()->json(['status' => true, 'response' => $msg]);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }


    public function manageAddress(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
            'building_name'  => 'required',
            'area'           => 'required',
            'Landmark'       => 'required',
            'pincode'        => 'required',
            'city'           => 'required',
            'state'          => 'required'
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $address = new UserAddress();
            if ($request->id) {
                $address = UserAddress::find($request->id);
                $msg = "address update successfully";
            } else {
                $msg = "address added successfully";
            }
            $address->user_id       = $request->user_id;
            $address->building_name = $request->building_name;
            $address->area          = $request->area;
            $address->Landmark      = $request->Landmark;
            $address->pincode       = $request->pincode;
            $address->city          = $request->city;
            $address->state         = $request->state;
            $address->save();
            return response()->json(['status' => true, 'response' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function getUserAddresses(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $address = UserAddress::where('user_id', $request->user_id)->get();
            return response()->json(['status' => true, 'address_data' => $address]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function getPatients(Request $request)
    {
        $rules = [
            'user_id' => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $patients = Patient::select(
                'patient.id',
                'patient.firstname',
                'patient.mobileNo',
                'patient.gender',
                'patient.email',
                'patient.age',
                'patient.dateofbirth',
                'patient.regularMedicine',
                // 'patient.illness',
                // 'patient.Medicine_Taken',
                'patient.allergy_name',
                'patient.surgery_name',
                'patient.surgery_details',
                'patient.treatment_taken',
                'patient.treatment_taken_details',
                'patient.allergy_id',
                'patient.mediezy_patient_id',
                'allergies.allergy AS allergy_detail',
                'patient.user_image',
            )
                ->leftJoin('allergies', 'patient.allergy_id', '=', 'allergies.id')
                ->where('UserId', $request->user_id)
                ->get();
            $patient_data_array = [];
            foreach ($patients as $patient) {
                $surgery_name = $patient->surgery_name ? array_map('trim', explode(',', trim($patient->surgery_name, '[]'))) : [];
                $treatment_taken = $patient->treatment_taken ? array_map('trim', explode(',', trim($patient->treatment_taken, '[]'))) : [];
                // $surgery_details = $patient->surgery_details ? array_map('trim', explode(',', trim($patient->surgery_details, '[]'))) : [];
                // $treatment_taken_details = $patient->treatment_taken_details ? array_map('trim', explode(',', trim($patient->treatment_taken_details, '[]'))) : [];
                $medicines = Medicine::where('patient_id', $patient->id)->where('docter_id',0)->get();
                $medicine_details = $medicines->map(function ($medicine) {
                    return [
                        'medicine_id' => $medicine->id,
                        'medicineName' => $medicine->medicineName,
                        'illness' => $medicine->illness,

                    ];
                });
                $patient_allergies = PatientAllergies::where('patient_id', $patient->id)->get();
                $allergies_details = $patient_allergies->map(function ($allergies) {
                    return [
                        'allergy_id' => $allergies->allergy_id,
                        'allergy_details' => $allergies->allergy_details,
                    ];
                });
                $dob = new DateTime($patient->dateofbirth);
                $now = new DateTime();
                $diff = $now->diff($dob);
                $ageInMonths = $diff->y * 12 + $diff->m;
                if ($ageInMonths < 12) {
                    $displayAge = $ageInMonths . ' months';
                } else {
                    $displayAge = $diff->y . ' years';
                }
                $patient_data_array[] = [
                    'id' => $patient->id ?? null,
                    'patient_name' => $patient->firstname ?? null,
                    'mediezy_patient_id' => $patient->mediezy_patient_id ?? null,
                    'patient_age' => $patient->age ?? null,
                    // 'dob' => $patient->dateofbirth ?? null,
                    'dob' => Carbon::parse($patient->dateofbirth)->format('Y-m-d'),
                    'dobshow' => Carbon::parse($patient->dateofbirth)->format('d-m-Y'),
                    'display_age' => $displayAge,
                    'patient_gender' => $patient->gender ?? null,
                    'patient_mobile_number' => $patient->mobileNo ?? null,
                    // 'allergy_id' => $patient->allergy_id ?? null,
                    // 'allergy_name' => $patient->allergy_detail ?? null,
                    // 'allergy_detail' => $patient->allergy_name ?? null,
                    'regular_medicine' => ucfirst(strtolower($patient->regularMedicine ?? null)),
                    // 'illness' => $patient->illness ?? null,
                    // 'medicine_taken' => $patient->Medicine_Taken ?? null,
                    'surgery_name' => $surgery_name ?? null,
                    'treatment_taken' => $treatment_taken ?? null,
                    'surgery_details' =>  $patient->surgery_details ?? null,
                    'treatment_taken_details' =>  $patient->treatment_taken_details ?? null,
                    'patient_image' => $patient->user_image ? asset("UserImages/{$patient->user_image}") : null,
                    'medicine_details' => $medicine_details,
                    'allergies_details' => $allergies_details,
                ];
            }
            return response()->json(['status' => true, 'patient_data' => $patient_data_array]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function PatientHistory(Request $request)
    {
        $rules = [
            'patient_id'        => 'required',
        ];
        $messages = [
            'patient_id.required' => 'PatientId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $patientId = $request->patient_id;
            $history = PatientDocument::where('patient_id', $patientId)->with('LabReports', 'PatientPrescriptions')->first();
            if (!$history) {
                $history = null;
            }
            return response()->json(['status' => true, 'history_data' => $history]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    // Priya
    public function editPatient(Request $request, $patientId)
    {
        try {
            $patient = Patient::find($patientId);

            if (!$patient) {
                return response()->json(['status' => false, 'response' => 'Patient not found']);
            }

            // You can customize the fields you want to include in the response
            $patientData = [
                'id'        => $patient->id,
                'firstname' => $patient->firstname,
                'gender'    => $patient->gender,
                'age'       => $patient->age,
            ];

            return response()->json(['status' => true, 'patient_data' => $patientData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => 'Internal Server Error']);
        }
    }
    public function updatePatient(Request $request, $patientId)
    {
        try {
            $patient = Patient::find($patientId);

            if (!$patient) {
                return response()->json(['status' => false, 'response' => 'Patient not found']);
            }

            $patient->fill([
                'firstname' => $request->input('firstname', $patient->firstname),
                'gender'    => $request->input('gender', $patient->gender),
                'dateofbirth' => $request->input('dateofbirth', $patient->dateofbirth),
                'mobilenumber' => $request->input('mobilenumber', $patient->mobilenumber),
            ]);
            if ($request->has('dateofbirth')) {
                $age = Carbon::parse($request->input('dateofbirth'))->age;
                $patient->age = $age;
            }
            $patient->regularMedicine = $request->input('regularMedicine');
            if ($request->input('regularMedicine') === "Yes") {
                $patient->illness = $request->input('illness');
                $patient->Medicine_Taken = $request->input('Medicine_Taken');
            }
            $patient->allergy_id = $request->input('allergy_id');
            $patient->allergy_name = $request->input('allergy_name');
            //updating surgery_name and surgery_details
            if ($request->has('surgery_name')) {
                if ($request->input('surgery_name') === '[Other]' && $request->has('surgery_details')) {
                    $patient->surgery_name = $request->input('surgery_name');
                    $patient->surgery_details = $request->input('surgery_details');
                } else {
                    $patient->surgery_name = $request->input('surgery_name');
                    $patient->surgery_details = null;
                }
            }
            //updating treatment_taken and treatment_taken_details
            if ($request->has('treatment_taken')) {
                if ($request->input('treatment_taken') === '[Other]' && $request->has('treatment_taken_details')) {
                    $patient->treatment_taken = $request->input('treatment_taken');
                    $patient->treatment_taken_details = $request->input('treatment_taken_details');
                } else {
                    $patient->treatment_taken = $request->input('treatment_taken');
                    $patient->treatment_taken_details = null;
                }
            }

            $patient->save();


            // $medicines = $request->input('medicines', []);
            // foreach ($medicines as $medicine) {
            //     if (isset($medicine['medicine_id'])) {
            //         $existingMedicine = Medicine::find($medicine['medicine_id']);
            //         if ($existingMedicine) {
            //             $existingMedicine->update([
            //                 'medicineName' => $medicine['medicineName'] ?? null,
            //                 'illness' => $medicine['illness'] ?? null,
            //             ]);
            //         }
            //     }
            // }

            return response()->json(['status' => true, 'response' => 'Patient updated successfully']);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function DeleteMemeber($patientId)
    {
        $Patient = Patient::find($patientId);

        if (is_null($Patient)) {
            return $this->sendError('Patient not found.');
        }

        $Patient->delete();
        return $this->sendResponse("Patient", $Patient, '1', 'Member Deleted successfully');
    }



    // public function recentlyBookedDoctor(Request $request)
    // {
    //     try {
    //         $authenticatedUserId = auth()->user()->id;
    //         $specializeArray['specialize'] = Specialize::all();
    //         $specificationArray['specification'] = Specification::all();
    //         $subspecificationArray['subspecification'] = Subspecification::all();


    //         $recentBooking = TokenBooking::select('id', 'doctor_id', 'date', 'TokenTime')
    //             ->where('BookedPerson_id', $authenticatedUserId)
    //             ->latest('date')
    //             ->get();

    //         if ($recentBooking->isEmpty()) {
    //             return response()->json(['status' => true, 'recently_booked_doctor' => [], 'message' => 'No recent bookings found']);
    //         }
    //         /////user current location access
    //         $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //         $user_latitude = $current_location_data ? $current_location_data->latitude : null;
    //         $user_longitude = $current_location_data ? $current_location_data->longitude : null;

    //         $distanceHelper = new \App\Helpers\UserDistanceHelper();
    //         /////
    //         $doctorsWithSpecifications = [];

    //         foreach ($recentBooking as $booking) {
    //             $doctorId = $booking->doctor_id;

    //             $doctor =  Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
    //                 ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
    //                 ->select(
    //                     'docter.UserId',
    //                     'docter.id',
    //                     'docter.docter_image',
    //                     'docter.firstname',
    //                     'docter.lastname',
    //                     'docter.specialization_id',
    //                     'docter.subspecification_id',
    //                     'docter.specification_id',
    //                     'docter.latitude',
    //                     'docter.longitude',
    //                     'docter.about',
    //                     'docter.Services_at',
    //                     'docter.location',
    //                     'clinics.clinic_id as avaliblityId',
    //                     'clinics.clinic_name',
    //                     'clinics.clinic_start_time',
    //                     'clinics.clinic_end_time',
    //                     'clinics.address',
    //                     'clinics.location',
    //                     'clinics.clinic_description',
    //                     'clinics.clinic_main_image',
    //                     'clinics.latitude as clinic_latitude',
    //                     'clinics.longitude as clinic_longitude',

    //                 )
    //                 ->where('UserId', $doctorId)
    //                 ->with('tokencount')
    //                 ->get();

    //             //user location
    //             // $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //             // $user_latitude = $current_location_data ? $current_location_data->latitude : null;
    //             // $user_longitude = $current_location_data ? $current_location_data->longitude : null;

    //             foreach ($doctor as $doc) {
    //                 $id = $doc->id;
    //                 $favoriteStatus = DB::table('addfavourite')
    //                     ->where('UserId', $authenticatedUserId)
    //                     ->where('doctor_id', $doc['UserId'])
    //                     ->exists();
    //                 if (!isset($doctorsWithSpecifications[$id])) {
    //                     $specialize = $specializeArray['specialize']->firstWhere('id', $doc->specialization_id);
    //                     // $doctor_latitude = $doc['latitude'];
    //                     // $doctor_longitude = $doc['longitude'];

    //                     // $apiKey = config('services.google.api_key');

    //                     // $service = new DistanceMatrixService($apiKey);
    //                     // $distance = $service->getDistance($user_latitude, $user_longitude, $doctor_latitude, $doctor_longitude);

    //                     $doctorsWithSpecifications[$id] = [
    //                         'id' => $id,
    //                         'UserId' => $doc->UserId,
    //                         'firstname' => $doc->firstname,
    //                         'secondname' => $doc->lastname,
    //                         // 'distance_from_user' => $distance,
    //                         'Specialization' => $specialize ? $specialize->specialization : null,
    //                         'DocterImage' => asset("DocterImages/images/{$doc->docter_image}"),
    //                         'Location' => $doc->location,
    //                         'MainHospital' => $doc->Services_at,
    //                         'clinics' => [],
    //                         'favoriteStatus' => $favoriteStatus ? 1 : 0,
    //                     ];
    //                 }
    //                  ///////clinics lattitude and longitude
    //                  $clinicDetails = [];
    //                 $clinicDetails['distance'] = $distanceHelper->calculateHaversineDistance(
    //                     $user_latitude,
    //                     $user_longitude,
    //                     $doc->clinic_latitude,
    //                     $doc->clinic_longitude
    //                 );
    //                  ///////////
    //                 /// token cound and avalilability data
    //                 $current_date = Carbon::now()->toDateString();
    //                 $current_time = Carbon::now()->toDateTimeString();
    //                 // $userLocation = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
    //                 // if ($userLocation && $doc['clinic_latitude'] && $doc['clinic_longitude']) {
    //                 //     $distanceService = new DistanceMatrixService(config('services.google.api_key'));
    //                 //     $clinicDetails['distance'] = $distanceService->getDistance($userLocation->latitude, $userLocation->longitude, $doc['clinic_latitude'], $doc['clinic_longitude']);
    //                 // } else {
    //                 //     $clinicDetails['distance'] = null;
    //                 // }
    //                 $clinicDetails['distance'] = null;
    //                 $total_token_count = NewTokens::where('clinic_id', $doc->avaliblityId)
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->where('doctor_id', $id)
    //                     ->count();

    //                 $available_token_count = NewTokens::where('clinic_id', $doc->avaliblityId)
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->where('token_booking_status', NULL)
    //                     ->where('token_start_time', '>', $current_time)
    //                     ->where('doctor_id', $id)
    //                     ->count();

    //                 //schedule details

    //                 // $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

    //                 $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
    //                     ->where('doctor_id', $id)
    //                     ->where('clinic_id', $doc->avaliblityId)
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->orderBy('token_start_time', 'ASC')
    //                     ->first();


    //                 $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
    //                     ->where('doctor_id', $id)
    //                     ->where('clinic_id', $doc->avaliblityId)
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->orderBy('token_start_time', 'DESC')
    //                     ->first();

    //                 if ($schedule_start_data && $schedule_end_data) {
    //                     $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
    //                     $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
    //                 } else {

    //                     $start_time = null;
    //                     $end_time = null;
    //                 }
    //                 $consultation_fee = ClinicConsultation::where('doctor_id', $id)
    //                     ->where('clinic_id', $doc->avaliblityId)
    //                     ->value('consultation_fee');

    //                 $next_available_token_time = NewTokens::where('clinic_id', $doc->avaliblityId)
    //                     ->where('token_scheduled_date', $current_date)
    //                     ->where('token_booking_status', Null)
    //                     // ->where('is_checkedin', 0)
    //                     ->where('token_start_time', '>', $current_time)
    //                     ->where('doctor_id', $doc->id)
    //                     ->orderBy('token_start_time', 'ASC')
    //                     ->value('token_start_time');
    //                 $next_date_available_token_time = NewTokens::where('clinic_id', $doc['avaliblityId'])
    //                     ->where('token_scheduled_date', '>', $current_date)
    //                     ->where('token_booking_status', NULL)
    //                     ->orderBy('token_scheduled_date', 'ASC')
    //                     ->where('token_start_time', '>', $current_time)
    //                     ->where('doctor_id', $doc->id)
    //                     ->orderBy('token_start_time', 'ASC')
    //                     ->value('token_start_time');
    //                 Log::info('Token Time: ' . $next_date_available_token_time);

    //                 if ($next_date_available_token_time) {
    //                     $next_date_token_carbon = Carbon::createFromFormat('Y-m-d H:i:s', $next_date_available_token_time);

    //                     if ($next_date_token_carbon->isTomorrow()) {
    //                         $formatted_next_date_available_token_time =   $next_date_token_carbon->format('h:i A') . " Tomorrow ";
    //                     } else {
    //                         $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A d M');
    //                     }
    //                 } else {
    //                     $formatted_next_date_available_token_time = null;
    //                 }

    //                 $next_date_available_token_time = $next_date_available_token_time ? Carbon::parse($next_date_available_token_time)->format('h:i A d M ') : null;

    //                 $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

    //                 ///
    //                 //if clinic already exist
    //                 $existingClinic = collect($doctorsWithSpecifications[$id]['clinics'])->firstWhere('clinic_id', $doc->avaliblityId);

    //                 if (!$existingClinic) {
    //                     $clinicDetails = [
    //                         'clinic_id' => $doc->avaliblityId,
    //                         'clinic_name' => $doc->clinic_name,
    //                         'clinic_start_time' => $start_time,
    //                         'clinic_end_time' => $end_time,
    //                         'clinic_address' => $doc->address,
    //                         'clinic_location' => $doc->location,
    //                         'clinic_main_image' => isset($doc->clinic_main_image) ? asset("clinic_images/{$doc->clinic_main_image}") : null,
    //                         'clinic_description' => $doc->clinic_description,
    //                         'total_token_Count' => $total_token_count,
    //                         'available_token_count' => $available_token_count,
    //                         'next_available_token_time' => $next_available_token_time,
    //                         'next_date_available_token_time' => $formatted_next_date_available_token_time,
    //                      ////   'distance_from_clinic' => $clinicDetails['distance'],
    //                      'distance_from_clinic' => $clinicDetails['distance'],
    //                         'consultation_fee' => $consultation_fee,
    //                     ];
    //                     $doctorsWithSpecifications[$id]['clinics'][] = $clinicDetails;
    //                 }
    //             }
    //         }
    //         // usort($doctorsWithSpecifications, function ($a, $b) {
    //         //     return $a['distance_from_user'] <=> $b['distance_from_user'];
    //         // });

    //         $formattedOutput = array_values($doctorsWithSpecifications);
    //         return response()->json(['status' => true, 'recently_booked_doctor' => $formattedOutput, 'message' => 'Success']);
    //     } catch (\Exception $e) {
    //         dd($e->getMessage());
    //         return response()->json(['status' => false, 'response' => "Internal Server Error"]);
    //     }
    // }

    public function recentlyBookedDoctor(Request $request)
    {
        try {
            $authenticatedUserId = auth()->user()->id;
            $specializeArray['specialize'] = Specialize::all();
            $specificationArray['specification'] = Specification::all();
            $subspecificationArray['subspecification'] = Subspecification::all();

            $recentBooking = TokenBooking::select('id', 'doctor_id', 'date', 'TokenTime')
                ->where('BookedPerson_id', $authenticatedUserId)
                ->latest('date')
                ->get();

            if ($recentBooking->isEmpty()) {
                return response()->json(['status' => true, 'recently_booked_doctor' => [], 'message' => 'No recent bookings found']);
            }

            $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
            $user_latitude = $current_location_data ? $current_location_data->latitude : null;
            $user_longitude = $current_location_data ? $current_location_data->longitude : null;

            $distanceHelper = new \App\Helpers\UserDistanceHelper();
            $doctorsWithSpecifications = [];

            foreach ($recentBooking as $booking) {
                $doctorId = $booking->doctor_id;

                $doctor = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                    ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
                    ->select(
                        'docter.UserId',
                        'docter.id',
                        'docter.docter_image',
                        'docter.firstname',
                        'docter.lastname',
                        'docter.specialization_id',
                        'docter.subspecification_id',
                        'docter.specification_id',
                        'docter.latitude',
                        'docter.longitude',
                        'docter.about',
                        'docter.Services_at',
                        'docter.location',
                        'clinics.clinic_id as avaliblityId',
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
                    ->where('UserId', $doctorId)
                    ->with('tokencount')
                    ->get();

                foreach ($doctor as $doc) {
                    $id = $doc->id;
                    $favoriteStatus = DB::table('addfavourite')
                        ->where('UserId', $authenticatedUserId)
                        ->where('doctor_id', $doc->UserId)
                        ->exists();

                    if (!isset($doctorsWithSpecifications[$id])) {
                        $specialize = $specializeArray['specialize']->firstWhere('id', $doc->specialization_id);

                        $doctorsWithSpecifications[$id] = [
                            'id' => $id,
                            'UserId' => $doc->UserId,
                            'firstname' => $doc->firstname,
                            'secondname' => $doc->lastname,
                            'Specialization' => $specialize ? $specialize->specialization : null,
                            'DocterImage' => asset("DocterImages/images/{$doc->docter_image}"),
                            'Location' => $doc->location,
                            'MainHospital' => $doc->Services_at,
                            'clinics' => [],
                            'favoriteStatus' => $favoriteStatus ? 1 : 0,
                        ];
                    }

                    $clinicDetails = [];
                    $clinicDetails['distance'] = $distanceHelper->calculateHaversineDistance(
                        $user_latitude,
                        $user_longitude,
                        $doc->clinic_latitude,
                        $doc->clinic_longitude
                    );

                    $current_date = Carbon::now()->toDateString();
                    $current_time = Carbon::now()->toDateTimeString();

                    $total_token_count = NewTokens::where('clinic_id', $doc->avaliblityId)
                        ->where('token_scheduled_date', $current_date)
                        ->where('doctor_id', $id)
                        ->count();

                    $available_token_count = NewTokens::where('clinic_id', $doc->avaliblityId)
                        ->where('token_scheduled_date', $current_date)
                        ->where('token_booking_status', NULL)
                        ->where('token_start_time', '>', $current_time)
                        ->where('doctor_id', $id)
                        ->count();

                    $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                        ->where('doctor_id', $id)
                        ->where('clinic_id', $doc->avaliblityId)
                        ->where('token_scheduled_date', $current_date)
                        ->orderBy('token_start_time', 'ASC')
                        ->first();

                    $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                        ->where('doctor_id', $id)
                        ->where('clinic_id', $doc->avaliblityId)
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

                    $consultation_fee = ClinicConsultation::where('doctor_id', $id)
                        ->where('clinic_id', $doc->avaliblityId)
                        ->value('consultation_fee');

                    $next_available_token_time = NewTokens::where('clinic_id', $doc->avaliblityId)
                        ->where('token_scheduled_date', $current_date)
                        ->where('token_booking_status', Null)
                        ->where('token_start_time', '>', $current_time)
                        ->where('doctor_id', $doc->id)
                        ->orderBy('token_start_time', 'ASC')
                        ->value('token_start_time');

                    $next_date_available_token_time = NewTokens::where('clinic_id', $doc->avaliblityId)
                        ->where('token_scheduled_date', '>', $current_date)
                        ->where('token_booking_status', NULL)
                        ->orderBy('token_scheduled_date', 'ASC')
                        ->where('token_start_time', '>', $current_time)
                        ->where('doctor_id', $doc->id)
                        ->orderBy('token_start_time', 'ASC')
                        ->value('token_start_time');

                    if ($next_date_available_token_time) {
                        $next_date_token_carbon = Carbon::createFromFormat('Y-m-d H:i:s', $next_date_available_token_time);
                        if ($next_date_token_carbon->isTomorrow()) {
                            $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A') . " Tomorrow";
                        } else {
                            $formatted_next_date_available_token_time = $next_date_token_carbon->format('h:i A d M');
                        }
                    } else {
                        $formatted_next_date_available_token_time = null;
                    }

                    $next_available_token_time = $next_available_token_time ? Carbon::parse($next_available_token_time)->format('h:i A') : null;

                    $existingClinic = collect($doctorsWithSpecifications[$id]['clinics'])->firstWhere('clinic_id', $doc->avaliblityId);

                    if (!$existingClinic) {
                        $clinicDetails = [
                            'clinic_id' => $doc->avaliblityId,
                            'clinic_name' => $doc->clinic_name,
                            'clinic_start_time' => $start_time,
                            'clinic_end_time' => $end_time,
                            'clinic_address' => $doc->address,
                            'clinic_location' => $doc->location,
                            'clinic_main_image' => isset($doc->clinic_main_image) ? asset("clinic_images/{$doc->clinic_main_image}") : null,
                            'clinic_description' => $doc->clinic_description,
                            'total_token_Count' => $total_token_count,
                            'available_token_count' => $available_token_count,
                            'next_available_token_time' => $next_available_token_time,
                            'next_date_available_token_time' => $formatted_next_date_available_token_time,
                            'distance_from_clinic' => $clinicDetails['distance'],
                            'consultation_fee' => $consultation_fee,
                        ];
                        $doctorsWithSpecifications[$id]['clinics'][] = $clinicDetails;
                    }
                }
            }


            $formattedOutput = array_values($doctorsWithSpecifications);
            return response()->json(['status' => true, 'recently_booked_doctor' => $formattedOutput, 'message' => 'Success']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function GetAllergy()
    {
        $alleries = Allergy::all();
        return response()->json(['status' => true, 'Allergies' => $alleries, 'message' => 'Success']);
    }


    // public function Autofetch(Request $request)
    // {

    //     $rules = [
    //         'section'  => 'required',
    //         'user_id'        => 'required',

    //     ];
    //     $messages = [
    //         'user_id.required' => 'User Id is required',
    //     ];
    //     $validation = Validator::make($request->all(), $rules, $messages);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }

    //     try {

    //         $userId = $request->input('user_id');



    //         if ($request->section === 'Self') {
    //             $details = Patient::select('id AS patientId', 'firstname', 'age', 'mobileNo', 'gender')->where('UserId', $userId)->where('user_type', 1)->get();
    //         } elseif ($request->section == 'Family Member') {
    //             $validator = Validator::make($request->all(), [
    //                 'patient_id' => 'required',
    //             ]);

    //             if ($validator->fails()) {
    //                 return response()->json(['success' => false, 'message' => $validator->errors()]);
    //             }
    //             $selectedId = $request->input('patient_id');
    //             $details = Patient::select('id AS patientId', 'firstname', 'age', 'mobileNo', 'gender')->where('id', $selectedId)->where('user_type', 2)->get();
    //         }
    //         return response()->json(['status' => true, 'details' => $details, 'message' => 'Family Member Retrive Successfully'], 200);
    //     } catch (\Exception $e) {
    //         // Handle exceptions as needed
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }

    public function Autofetch(Request $request)
    {
        $rules = [
            'section'  => 'required',
            'user_id'  => 'required',
        ];

        $messages = [
            'user_id.required' => 'User Id is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $userId = $request->input('user_id');

            if ($request->section === 'Self') {
                $details = Patient::select('id AS patientId', 'firstname', 'dateofbirth', 'mobileNo', 'gender', 'age')
                    ->where('UserId', $userId)
                    ->where('user_type', 1)
                    ->get();

                // Calculate age in months
                $details->map(function ($item) {
                    $dob = new DateTime($item->dateofbirth);
                    $now = new DateTime();
                    $diff = $now->diff($dob);

                    // Calculate age in months
                    $ageInMonths = $diff->y * 12 + $diff->m;

                    // If the age is less than a year, display age in months
                    if ($ageInMonths < 12) {
                        $item->displayAge = $ageInMonths . ' months';
                    } else {
                        $item->displayAge = $diff->y . ' years';
                    }

                    return $item;
                });
            } elseif ($request->section == 'Family Member') {
                $validator = Validator::make($request->all(), [
                    'patient_id' => 'required',
                ]);

                if ($validator->fails()) {
                    return response()->json(['success' => false, 'message' => $validator->errors()]);
                }

                $selectedId = $request->input('patient_id');
                $details = Patient::select('id AS patientId', 'firstname', 'dateofbirth', 'mobileNo', 'gender', 'age')
                    ->where('id', $selectedId)
                    // ->where('user_type', 2)
                    ->get();

                // Calculate age in months
                $details->map(function ($item) {
                    $dob = new DateTime($item->dateofbirth);
                    $now = new DateTime();
                    $diff = $now->diff($dob);
                    $ageInMonths = $diff->y * 12 + $diff->m;
                    if ($ageInMonths < 12) {
                        $item->displayAge = $ageInMonths . ' months';
                    } else {
                        $item->displayAge = $diff->y . ' years';
                    }

                    return $item;
                });
            }

            return response()->json(['status' => true, 'details' => $details, 'message' => 'Family Member Retrieve Successfully'], 200);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function GetFamily(Request $request)
    {

        $rules = [

            'user_id'        => 'required',

        ];
        $messages = [
            'user_id.required' => 'User Id is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {

            $userId = $request->input('user_id');


            $details = Patient::select('id', 'firstname')->where('UserId', $userId)->where('user_type', 2)->get();
            // if ($details->isEmpty()) {
            //     return response()->json(['status' => true, 'FamilyMember' => [], 'message' => 'Family Member is null'], 200);
            // }
            return response()->json(['status' => true, 'FamilyMember' => $details, 'message' => 'Family Member Retrive Successfully'], 200);
        } catch (\Exception $e) {
            // Handle exceptions as needed
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function checkPatientReach(Request $request)
    {

        $rules = [
            'patient_id' => 'required',
            'new_token_id' => 'required',
            'reached_status' => 'required',
        ];

        $messages = [
            'patient_id.required' => 'Patient Id is required',
        ];


        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $patient_details = Patient::where('id', $request->patient_id)->first();

        if (!$patient_details) {

            return response()->json(['status' => false, 'response' => 'Patient not found'], 200);
        }

        try {
            if ($request->reached_status == 1) {
                $token_data = NewTokens::where('token_id', $request->new_token_id)
                    ->first();
                if ($token_data) {
                    $token_data->is_reached = 1;
                    $token_data->save();
                    $booking_data = TokenBooking::where('new_token_id', $request->new_token_id)->first();
                    $booking_data->is_reached = 1;
                    $booking_data->save();

                    return response()->json(['status' => true, 'response' => 'Status updated'], 200);
                } else {
                    return response()->json(['status' => false, 'response' => 'Token not found'], 200);
                }
            }
            return response()->json(['status' => false, 'response' => 'Status not updated'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal server error'], 500);
        }
    }
    // public function getVitals(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'patient_id' => 'sometimes|required',
    //         'user_id' => 'sometimes|required',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(['status' => true, 'message' => $validator->errors()], 400);
    //     }
    //     $patientId = $request->input('patient_id');
    //     $bookedPersonId = $request->input('user_id');
    //     if ($patientId || $bookedPersonId) {
    //         $query = TokenBooking::query();
    //         if ($patientId) {
    //             $query->where('patient_id', $patientId);
    //         }
    //         if ($bookedPersonId) {
    //             $query->where('BookedPerson_id', $bookedPersonId);
    //         }
    //         $query->whereNotNull('height')
    //             ->whereNotNull('weight')
    //             ->whereNotNull('temperature')
    //             ->whereNotNull('spo2')
    //             ->whereNotNull('sys')
    //             ->whereNotNull('dia')
    //             ->whereNotNull('heart_rate')
    //             ->whereNotNull('temperature_type');
    //         $Vitals = $query->select(
    //             'token_booking.date',
    //             'patient.firstname as patient_name',
    //             DB::raw("CONCAT(docter.firstname, ' ', docter.lastname) AS doctor_firstname"),
    //             'token_booking.height',
    //             'token_booking.weight',
    //             'token_booking.temperature',
    //             'token_booking.spo2',
    //             'token_booking.sys',
    //             'token_booking.dia',
    //             'token_booking.heart_rate',
    //             'token_booking.temperature_type'
    //         )
    //             ->join('patient', 'patient.id', '=', 'token_booking.patient_id')
    //             ->join('docter', 'docter.UserId', '=', 'token_booking.doctor_id')
    //             ->get();
    //         if ($Vitals->isEmpty()) {
    //             return response()->json(['status' => true, 'vitals' => null]);
    //         }
    //         return response()->json(['status' => true, 'vitals' => $Vitals]);
    //     }
    // }

    public function getVitals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|required',
            'user_id' => 'sometimes|required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => true, 'message' => $validator->errors()], 400);
        }
        $patientId = $request->input('patient_id');
        $bookedPersonId = $request->input('user_id');
        if ($patientId || $bookedPersonId) {
            $query = CompletedAppointments::query();
            if ($patientId) {
                $query->where('patient_id', $patientId);
            }
            if ($bookedPersonId) {
                $query->where('booked_user_id', $bookedPersonId);
            }
            $query->whereNotNull('height')
                //     ->whereNotNull('weight')
                //     ->whereNotNull('temperature')
                //     ->whereNotNull('spo2')
                //     ->whereNotNull('sys')
                //     ->whereNotNull('dia')
                //     ->whereNotNull('heart_rate')
                //     ->whereNotNull('temperature_type')
            ;
            $Vitals = $query->select(
                'completed_appointments.date',
                'patient.firstname as patient_name',
                DB::raw("CONCAT(docter.firstname, ' ', docter.lastname) AS doctor_firstname"),
                'completed_appointments.height',
                'completed_appointments.weight',
                'completed_appointments.temperature',
                'completed_appointments.spo2',
                'completed_appointments.sys',
                'completed_appointments.dia',
                'completed_appointments.heart_rate',
                'completed_appointments.temperature_type'
            )
                ->join('patient', 'patient.id', '=', 'completed_appointments.patient_id')
                ->join('docter', 'docter.id', '=', 'completed_appointments.doctor_id')
                ->orderBy('date', 'DESC')
                ->orderBy('token_start_time', 'DESC')
                ->get();
            if ($Vitals->isEmpty()) {
                return response()->json(['status' => true, 'vitals' => null]);
            }
            return response()->json(['status' => true, 'vitals' => $Vitals]);
        }
    }

    // public function CompletedAppointmentsDetails($booked_user_id, $date)
    // {


    //     $doctorDetails = CompletedAppointments::where('booked_user_id', $booked_user_id)->first();

    //         if(!$doctorDetails){
    //             return response()->json(['status'=>false , 'message'=>'booked_user_id not found'],400);
    //         }

    //         $validator = Validator::make(['date' => $date], [
    //             'date' => 'required|date_format:Y-m-d',
    //         ]);

    //         // Check if validation fails
    //         if ($validator->fails()) {
    //             return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
    //         }


    //     if (now()->hour < 12) {
    //         return response()->json(['status' => false, 'message' => 'Details are only available until 12 am']);
    //     }

    //     if ($date != now()->toDateString()) {
    //         return response()->json(['status' => false, 'message' => 'Details are only available for today'],400);
    //     }

    //     $baseImageUrl = 'http://your-base-url.com/images/';

    //     $doctorDetails = CompletedAppointments::where('completed_appointments.booked_user_id', $booked_user_id)
    //         ->where('completed_appointments.date', $date)
    //         ->where('token_booking.is_completed', 1)
    //         ->leftJoin('token_booking', 'completed_appointments.booked_user_id', '=', 'token_booking.BookedPerson_id')
    //         ->leftjoin('docter','docter.id','=','completed_appointments.doctor_id')
    //         ->leftJoin('laboratory', 'completed_appointments.lab_id', '=', 'laboratory.id')
    //         ->leftJoin('clinics', 'completed_appointments.clinic_id', '=', 'clinics.clinic_id')
    //         ->leftJoin('mainsymptoms', 'completed_appointments.booked_user_id', '=', 'mainsymptoms.user_id')
    //                     ->select(
    //             'completed_appointments.lab_id',
    //             'completed_appointments.labtest',
    //             'completed_appointments.scan_id',
    //             'completed_appointments.scan_test',
    //             'completed_appointments.clinic_id',
    //             'clinics.clinic_name',
    //             'completed_appointments.doctor_id',
    //             DB::raw("CONCAT(docter.firstname, ' ', docter.lastname) AS doctor_name"),
    //             DB::raw("CONCAT('$baseImageUrl', docter.docter_image) AS docter_image_url"),
    //             'completed_appointments.patient_id',
    //             'token_booking.PatientName',
    //             'token_booking.whenitstart',
    //             'token_booking.whenitcomes',
    //             'completed_appointments.date',
    //             'completed_appointments.token_start_time',
    //             'completed_appointments.token_number',
    //             'mainsymptoms.Mainsymptoms'
    //         )

    //         ->get();

    //     if ($doctorDetails->isEmpty()) {
    //         return response()->json(['status' => true, 'message' => 'Corresponding id details are not available.', 'CompletedDetails' => $doctorDetails]);
    //     }

    //     return response()->json(['status' => true, 'message' => 'Completed appointments', 'CompletedDetails' => $doctorDetails]);
    // }
    public function CompletedAppointmentsDetails($booked_user_id, $date)
    {
        $doctorDetails = CompletedAppointments::where('booked_user_id', $booked_user_id)->first();

        if (!$doctorDetails) {
            return response()->json(['status' => false, 'message' => 'booked_user_id not found'], 400);
        }

        $validator = Validator::make(['date' => $date], [
            'date' => 'required|date_format:Y-m-d',
        ]);


        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        if (now()->hour < 12) {
            return response()->json(['status' => false, 'message' => 'Details are only available until 12 am']);
        }

        if ($date != now()->toDateString()) {
            return response()->json(['status' => false, 'message' => 'Details are only available for today'], 400);
        }

        $baseImageUrl = 'http://your-base-url.com/images/';

        $doctorDetails = CompletedAppointments::where('completed_appointments.booked_user_id', $booked_user_id)
            ->where('completed_appointments.date', $date)
            ->where('token_booking.is_completed', 1)
            ->leftJoin('token_booking', 'completed_appointments.booked_user_id', '=', 'token_booking.BookedPerson_id')
            ->leftjoin('docter', 'docter.id', '=', 'completed_appointments.doctor_id')
            ->leftJoin('laboratory', 'completed_appointments.lab_id', '=', 'laboratory.id')
            ->leftJoin('clinics', 'completed_appointments.clinic_id', '=', 'clinics.clinic_id')
            ->leftJoin('mainsymptoms', 'completed_appointments.booked_user_id', '=', 'mainsymptoms.user_id')
            ->select(
                'completed_appointments.clinic_id',
                'clinics.clinic_name',
                'completed_appointments.doctor_id',
                DB::raw("CONCAT(docter.firstname, ' ', docter.lastname) AS doctor_name"),
                DB::raw("CONCAT('$baseImageUrl', docter.docter_image) AS docter_image_url"),
                'completed_appointments.patient_id',
                'token_booking.PatientName',
                'token_booking.whenitstart',
                'token_booking.whenitcomes',
                'completed_appointments.date',
                'completed_appointments.token_start_time',
                'completed_appointments.token_number',
                'mainsymptoms.Mainsymptoms'
            )
            ->get();

        if ($doctorDetails->isEmpty()) {
            return response()->json(['status' => true, 'message' => 'Corresponding id details are not available.', 'CompletedDetails' => $doctorDetails]);
        }


        foreach ($doctorDetails as &$appointment) {
            $main_symptoms = MainSymptom::select('Mainsymptoms')
                ->where('user_id', $appointment->patient_id)
                ->where('TokenNumber', $appointment->token_number)
                ->where('clinic_id', $appointment->clinic_id)
                ->first();

            $other_symptom_id = CompletedAppointments::select('appointment_for')
                ->where('booked_user_id', $appointment->patient_id)
                ->where('token_number', $appointment->token_number)
                ->where('clinic_id', $appointment->clinic_id)
                ->orderBy('created_at', 'DESC')->first();

            if ($other_symptom_id) {
                $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);

                $other_symptom_array_value = [];

                if (isset($other_symptom_json['Appoinmentfor2'])) {
                    $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
                }

                $other_symptom = Symtoms::select('symtoms')
                    ->whereIn('id', $other_symptom_array_value)
                    ->get()->toArray();

                $appointment->main_symptoms = $main_symptoms->Mainsymptoms ?? null;
                $appointment->other_symptoms = $other_symptom;
            }
        }

        return response()->json(['status' => true, 'message' => 'Completed appointments', 'CompletedDetails' => $doctorDetails]);
    }
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'razorpay_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
    //     ]);

    //     if ($request->hasFile('razorpay_image')) {
    //         $image = $request->file('razorpay_image');
    //         $imageName = $image->getClientOriginalName();
    //         $image->move(public_path('UserImages'), $imageName);
    //     } else {
    //         return response()->json(['error' => 'Image not found in request'], 400);
    //     }

    //     $paymentImage = Payment_image::create([
    //         'razorpay_image' => $imageName,
    //       ]);

    //     return response()->json([
    //         'message' => 'Image uploaded successfully',
    //         'data' => $paymentImage,
    //     ], 200);
    // }
    public function store(Request $request)
    {
        $request->validate([
            'razorpay_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        if ($request->hasFile('razorpay_image')) {
            $image = $request->file('razorpay_image');
            $imageName = $image->getClientOriginalName();
            $image->move(public_path('UserImages'), $imageName);
            $imageUrl = asset('UserImages/' . $imageName);
        } else {
            return response()->json(['error' => 'Image not found in request'], 400);
        }
        $paymentImage = Payment_image::create([
            'razorpay_image' => $imageName,
        ]);
        return response()->json([
            'message' => 'Image uploaded successfully',
            'image_url' => $imageUrl,
            'data' => $paymentImage,
        ], 200);
    }


}
