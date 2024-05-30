<?php

namespace App\Http\Controllers\API;

use App\Helpers\UserLocationHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Clinic;
use App\Models\Docter;
use App\Models\DocterAvailability;
use App\Models\DoctorClinicRelation;
use App\Models\DoctorClinicSpecialization;
use App\Models\Hospital;
use App\Models\Medicalshop;
use App\Models\NewTokens;
use App\Models\Specialize;
use App\Models\User;
use App\Services\DistanceMatrixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class HospitalController extends BaseController
{

    public function HospitalRegister(Request $request)
    {
        try {
            DB::beginTransaction();

            $input = $request->all();

            $validator = Validator::make($input, [
                'firstname' => 'required',
                'email' => 'required',
                'password' => 'required',
                'mobileNo' => 'required',
                'address' => 'required',
                'Type' => 'sometimes|in:1,2' //1 for hospital 2 for clinic

            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }


            $emailExists = Hospital::where('email', $input['email'])->count();
            $emailExistsinUser = User::where('email', $input['email'])->count();

            if ($emailExists && $emailExistsinUser) {
                return $this->sendResponse("Hospital", null, '3', 'Email already exists.');
            }

            $input['password'] = Hash::make($input['password']);

            $userId = DB::table('users')->insertGetId([
                'firstname' => $input['firstname'],
                'secondname' => 'Hospital',
                'email' => $input['email'],
                'password' => $input['password'],
                'user_role' => 6, //6 for hospital
            ]);

            $HospitalData = [

                'firstname' => $input['firstname'],
                'mobileNo' => $input['mobileNo'],
                'email' => $input['email'],
                'location' => $input['location'],
                'address' => $input['address'],
                'Type' => $input['Type'],
                'UserId' => $userId,
            ];

            if ($request->hasFile('hospital_image')) {
                $imageFile = $request->file('hospital_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('HospitalImages/images'), $imageName);

                    $DocterData['hospital_image'] = $imageName;
                }
            }

            $Hospital = new Hospital($HospitalData);
            $Hospital->save();
            DB::commit();

            return $this->sendResponse("Hospital", $Hospital, '1', 'Hospital created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }


    public function AddMedicalshop(Request $request)
    {
        try {
            DB::beginTransaction();

            $input = $request->all();
            $validator = Validator::make($input, [
                'HospitalId' => 'required',
                'firstname' => 'required',
                'email' => 'required',
                'password' => 'required',
                'mobileNo' => 'required',
                'address' => 'required',

            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }

            $emailExists = Medicalshop::where('email', $input['email'])->count();
            $emailExistsinUser = User::where('email', $input['email'])->count();

            if ($emailExists && $emailExistsinUser) {
                return $this->sendResponse("Laboratory", null, '3', 'Email already exists.');
            }

            $input['password'] = Hash::make($input['password']);

            $userId = DB::table('users')->insertGetId([
                'firstname' => $input['firstname'],
                'secondname' => 'Medicalshop',
                'email' => $input['email'],
                'password' => $input['password'],
                'mobileNo' => $input['mobileNo'],
                'user_role' => 5,
            ]);

            $DocterData = [
                'HospitalId' => $input['HospitalId'],
                'firstname' => $input['firstname'],
                'mobileNo' => $input['mobileNo'],
                'email' => $input['email'],
                'location' => $input['location'],
                'address' => $input['address'],
                'UserId' => $userId,
            ];

            if ($request->hasFile('shop_image')) {
                $imageFile = $request->file('shop_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('shopImages/images'), $imageName);

                    $DocterData['shop_image'] = $imageName;
                }
            }

            $Medicalshop = new Medicalshop($DocterData);
            $Medicalshop->save();
            DB::commit();



            return $this->sendResponse("Medicalshop", $Medicalshop, '1', 'Medicalshop created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }






    public function AddDocter(Request $request)
    {

        try {
            DB::beginTransaction();

            $input = $request->all();

            $validator = Validator::make($input, [
                'HospitalId' => 'required',
                'firstname' => 'required',
                'secondname' => 'required',
                'email' => 'required',
                'password' => 'required',
                'mobileNo' => 'required',


            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors());
            }
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

            $DocterData = [
                'HospitalId' => $input['HospitalId'],
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
            DB::commit();

            return $this->sendResponse("Docters", $Docter, '1', 'Docter created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }





    public function GetAllDoctorsbyHospitalId($hospitalId)
    {
        $doctors = DB::table('docter')
            ->join('Hosptal', 'docter.HospitalId', '=', 'Hosptal.UserId')
            ->where('Hosptal.UserId', $hospitalId)
            ->select('docter.*')
            ->get();

        return response()->json(['status' => true, 'doctors' => $doctors]);
    }





    public function GetCountOfDocter($hospitalId)
    {
        $doctorCount = DB::table('docter')
            ->join('Hosptal', 'docter.HospitalId', '=', 'Hosptal.UserId')
            ->where('Hosptal.UserId', $hospitalId)
            ->count();

        return response()->json(['status' => true, 'doctorCount' => $doctorCount]);
    }

    private function calculateDoctorCount($clinic_id, $specialization_id)
    {
        $doctors = Docter::select(
            'docter.id'
        )
            ->join('doctor_clinic_specialization', 'docter.id', '=', 'doctor_clinic_specialization.doctor_id')
            ->join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
            ->where('doctor_clinic_specialization.specialization_id', $specialization_id)
            ->where('doctor_clinic_relations.clinic_id', $clinic_id)
            ->distinct('docter.id')
            ->distinct('doctor_clinic_specialization.specialization_id')
            ->get();

        return $doctors->count();
    }


    public function getAppointmentCountByHospitalId($hospitalId)
    {
        $appointmentCount = DB::table('token_booking')
            ->join('Hosptal', 'token_booking.clinic_id', '=', 'Hosptal.id')
            ->where('token_booking.clinic_id', $hospitalId)
            ->count();

        return response()->json(['status' => true, 'appointmentCount' => $appointmentCount]);
    }


    /// ashwin
    public function getAllClinics()
    {


        try{
        $user_id = Auth::user()->id;

        $all_clinics = Clinic::leftJoin('doctor_clinic_relations', 'clinics.clinic_id', '=', 'doctor_clinic_relations.clinic_id')
            ->select(
                'clinics.clinic_id',
                'clinic_name',
                'address',
                'location',
                'clinics.latitude',
                'clinics.longitude',
                'clinic_main_image',
                
            )
            ->distinct()
            ->groupBy('clinics.clinic_id','clinics.latitude','clinics.longitude', 'clinic_name', 'address', 'location', 'clinic_main_image', 'doctor_clinic_relations.doctor_id')
            ->get();

        $clinics_array = $all_clinics->toArray();

        if (empty($clinics_array)) {
            return response()->json(['status' => false, 'message' => 'No clinics found', 'clinics' => $clinics_array]);
        }

         //user location
         $current_location_data = UserLocationHelper::getUserCurrentLocation($user_id);
         $user_latitude = $current_location_data ? $current_location_data->latitude : null;
         $user_longitude = $current_location_data ? $current_location_data->longitude : null;


        foreach ($clinics_array as &$clinic) {
            $clinic['clinic_main_image'] = asset($clinic['clinic_main_image']);
            $specializations = $this->getSpecializationsForClinic($clinic['clinic_id']);
            $clinic['specializations'] = implode(',', $specializations);

            $doctorCount = DoctorClinicRelation::where('clinic_id', $clinic['clinic_id'])->distinct()->count('doctor_id');
            $clinic['doctor_count'] = $doctorCount;
             //distance
             $doctor_latitude = $clinic['latitude'];
             $doctor_longitude = $clinic['longitude'];

            //  $apiKey = 'AIzaSyCA2yqbro5BkjC8xEaAAeWiNWcpaAqmfMo';
            $apiKey = config('services.google.api_key');


             $service = new DistanceMatrixService($apiKey);
             $distance = $service->getDistance($user_latitude, $user_longitude, $doctor_latitude, $doctor_longitude);
             $clinic['distance_from_user'] = $distance;
        }

        return response()->json(['status' => true, 'message' => 'success', 'clinics' => $clinics_array]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }



    private function getSpecializationsForClinic($clinicId)
    {
        $specializations = DB::table('doctor_clinic_specialization')
            ->where('clinic_id', $clinicId)
            ->pluck('specialization_id')
            ->toArray();

        if (empty($specializations)) {
            return [];
        }

        $specializationNames = Specialize::whereIn('id', $specializations)
            ->pluck('specialization')
            ->toArray();

        return $specializationNames;
    }
    public function getAllClinicDetails($clinic_id)
    {

        try{
        $clinic_details = Clinic::select(
            'clinic_id',
            'clinic_name',
            'clinic_description',
            'address',
            'location',
            'first_banner',
            'second_banner',
            'third_banner'
        )->where('clinic_id', $clinic_id)->get()->toArray();
        $specializations = DB::table('doctor_clinic_specialization')
            ->where('clinic_id', $clinic_id)
            ->pluck('specialization_id')
            ->unique()
            ->toArray();
        $specializationDetails = Specialize::whereIn('id', $specializations)
            ->pluck('specialization', 'id', 'specialization_icon')
            ->toArray();
        $specializationIcons = Specialize::whereIn('id', $specializations)
            ->pluck('specialize_Icon', 'id')
            ->toArray();
        foreach ($clinic_details as &$clinic) {
            $clinic['images'] = [
                asset($clinic['first_banner']),
                asset($clinic['second_banner']),
                asset($clinic['third_banner']),
            ];
            $clinic['specializations'] = [];
            foreach ($specializations as $specialization_id) {
                $spec_count = $this->calculateDoctorCount($clinic_id, $specialization_id);
                $clinic['specializations'][] = [
                    'specialization_id' => $specialization_id,
                    'specialization_name' => $specializationDetails[$specialization_id],
                    'specialization_icon' => asset('specializeIcon/' . $specializationIcons[$specialization_id]),
                    'available_doctor_count' => $spec_count,
                ];
            }
            $doctorCount = 0;
            foreach ($specializations as $specialization_id) {
                $doctorCount += $this->calculateDoctorCount($clinic_id, $specialization_id);
            }
            //   $clinic['doctor_count'] = $doctorCount;
            unset($clinic['clinic_main_image'], $clinic['first_banner'], $clinic['second_banner'], $clinic['third_banner']);
        }
        if (empty($specializations)) {
            return response()->json(['clinic_details' => $clinic_details, 'specializations' => []]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Successfully retrieved clinic details.',
            'clinic_details' => $clinic_details
        ]);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }


    public function getSpecializationDoctors($specialization_id, $clinic_id)
    {

        try{
        if (isset($specialization_id) && isset($clinic_id)) {


            $doctors = Docter::select(
                'docter.id',
                'docter.UserId',
                'docter.firstname',
                'docter.lastname',
                'docter.location',
                'docter.Services_at',
                'docter.docter_image',
                'docter.specialization_id',
            )
                ->join('doctor_clinic_specialization', 'docter.id', '=', 'doctor_clinic_specialization.doctor_id')
                ->join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                ->where('doctor_clinic_specialization.specialization_id', $specialization_id)
                ->where('doctor_clinic_relations.clinic_id', $clinic_id)
                ->orderBy('doctor_clinic_relations.clinic_id')
                ->distinct('docter.id')
                ->get();

            $specializations = DB::table('doctor_clinic_specialization')
                ->where('clinic_id', $clinic_id)
                ->pluck('specialization_id')
                ->toArray();


            $specializationDetails = Specialize::whereIn('id', $specializations)
                ->pluck('specialization')
                ->toArray();

            foreach ($doctors as &$doctor) {
                $doctor['docter_image'] = asset("DocterImages/images/{$doctor['docter_image']}");
                $doctor['Specialization'] = implode(', ', $specializationDetails);
                unset($doctor['specialization_id']);

                $all_doctor_clinics = DoctorClinicRelation::select('clinic_id')
                    ->where('doctor_id', $doctor->id)->get();

                $clinicsData = [];

                foreach ($all_doctor_clinics as $clinics) {
                    $clinic_data = Clinic::select(
                        'clinic_id',
                        'clinic_name',
                        'clinic_description',
                        'address',
                        'location',
                        'clinic_start_time',
                        'clinic_end_time',
                        'clinic_main_image',
                    )
                        ->where('clinic_id', $clinics->clinic_id)
                        ->get()->toArray();

                    //schedule availabilty and token counts

                    $current_date = Carbon::now()->toDateString();
                    $current_time = Carbon::now()->toDateTimeString();


                    $total_token_count = NewTokens::where('clinic_id', $clinic_data[0]['clinic_id'])
                        ->where('token_scheduled_date', $current_date)
                        ->where('doctor_id', $doctor->id)
                        ->count();

                    $available_token_count = NewTokens::where('clinic_id', $clinic_data[0]['clinic_id'])
                        ->where('token_scheduled_date', $current_date)
                        ->where('token_booking_status', NULL)
                        ->where('token_start_time', '>', $current_time)
                        ->where('doctor_id', $doctor->id)
                        ->count();

                    //schedule details

                    // $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

                    $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                        ->where('doctor_id', $doctor->id)
                        ->where('clinic_id', $clinic_data[0]['clinic_id'])
                        ->where('token_scheduled_date', $current_date)
                        ->orderBy('token_start_time', 'ASC')
                        ->first();


                    $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                        ->where('doctor_id', $doctor->id)
                        ->where('clinic_id', $clinic_data[0]['clinic_id'])
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

                    $clinicsData[] = [
                        'clinic_id' => $clinic_data[0]['clinic_id'] ?? null,
                        'clinic_name' => $clinic_data[0]['clinic_name'] ?? null,
                        'clinic_start_time' => $start_time ?? null,
                        'clinic_end_time' => $end_time ?? null,
                        'clinic_address' => $clinic_data[0]['address'] ?? null,
                        'clinic_location' => $clinic_data[0]['location'] ?? null,
                        'clinic_main_image' => asset($clinic_data[0]['clinic_main_image']) ?? null,
                        'clinic_description' => $clinic_data[0]['clinic_description'] ?? null,
                        'total_token_Count' => $total_token_count,
                        'available_token_count' => $available_token_count,
                    ];
                }

                $doctor['clinics'] = $clinicsData;
            }



            return response()->json([
                'status' => true,
                'message' => 'Successfully retrieved doctor details.',
                'doctor_details' => $doctors
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid specialization_id or clinic_id provided.',
                'doctor_details' => []
            ]);
        }
    } catch (\Exception $e) {
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }
}
