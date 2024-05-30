<?php

namespace App\Http\Controllers\API;

use App\Helpers\PushNotificationHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Allergy;
use App\Models\CompletedAppointments;
use App\Models\Docter;
use App\Models\MainSymptom;
use App\Models\DoctorClinicSpecialization;
use App\Models\Medicine;
use App\Models\MedicineHistory;
use App\Models\NewTokens;
use App\Models\Patient;
use App\Models\PatientAllergies;
use App\Models\PatientSymptoms;
use App\Models\RescheduleTokens;
use App\Models\Symtoms;
use App\Models\TokenBooking;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TokenBookingController extends BaseController
{
    public function bookToken(Request $request)
    {
        try {
            // Validate request data
            $this->validate($request, [
                'BookedPerson_id' => 'required',
                'PatientName' => 'required',
                'gender' => 'required',
                'age' => 'required',
                'MobileNo' => 'required',
                'date' => 'required|date_format:Y-m-d',
                'TokenNumber' => 'required',
                'TokenTime' => 'required',
                'whenitstart' => 'required',
                'whenitcomes' => 'required',
                'regularmedicine' => 'required',
                'doctor_id' => 'required',
                'Appoinmentfor1' => 'required|array',
                'Appoinmentfor2' => 'required|array',
                'clinic_id' => 'required',
                'Bookingtype' => 'sometimes|in:1,2,3' //1 for self,2 for familymember ,3 for others
            ]);

            $isDoctor = $request->has('doctor_id');
            $specializationId = null;

            if ($isDoctor) {
                $specializationId = Docter::where('id', $request->input('doctor_id'))->value('specialization_id');
            }

            $symptomIds1 = [];

            foreach ($request->input('Appoinmentfor1') as $symptomName) {
                $symptom = MainSymptom::create(['Mainsymptoms' => $symptomName]);

                $symptom->user_id = $request->input('BookedPerson_id');
                $symptom->doctor_id = $request->input('doctor_id');
                $symptom->clinic_id = $request->input('clinic_id');
                $symptom->date = $request->input('date');
                $symptom->TokenNumber = $request->input('TokenNumber');
                $symptom->save();


                $symptomIds1[] = $symptom->id;
            }

            $symptomIds2 = array_map('intval', $request->input('Appoinmentfor2'));

            foreach ($symptomIds2 as $symptomId) {
                $symptom = Symtoms::find($symptomId);
                if (!$symptom) {
                    // Display a message or take appropriate action
                    return $this->sendError('Invalid Appoinmentfor2 ID', 'The specified Appoinmentfor2 ID does not exist in the symptoms table.', 400);
                }
            }

            $existingSymptoms2 = Symtoms::whereIn('id', $symptomIds2)->get();

            // Check if the patient already exists
            $existingPatient = Patient::where('firstname', $request->input('PatientName'))
                ->where('mobileNo', $request->input('MobileNo'))
                ->first();

            if ($existingPatient) {
                // If patient exists, use existing patient ID
                $patientId = $existingPatient->id;
            } else {
                // If patient doesn't exist, create a new patient
                $userId = DB::table('users')->insertGetId([
                    'firstname' => $request->input('PatientName'),
                    'mobileNo' => $request->input('MobileNo'),
                    'user_role' => 3,
                ]);

                $patientId = DB::table('patient')->insertGetId([
                    'firstname' => $request->input('PatientName'),
                    'age' => $request->input('age'),
                    'mobileNo' => $request->input('MobileNo'),
                    'user_type' => $request->input('Bookingtype'),
                    'UserId' => $userId,
                ]);
            }


            $existingBooking = TokenBooking::where('doctor_id', $request->input('doctor_id'))
                ->where('clinic_id', $request->input('clinic_id'))
                ->where('date', $request->input('date'))
                ->where('TokenNumber', $request->input('TokenNumber'))
                ->exists();

            if ($existingBooking) {
                // Display a message or take appropriate action
                return $this->sendError('Selected Token Already Booked.Please try again', 200);
            }
            // Create a new token booking with the current time
            $tokenbooking = new TokenBooking();
            $tokenbooking->BookedPerson_id = $request->BookedPerson_id;
            $tokenbooking->PatientName = $request->PatientName;
            $tokenbooking->gender = $request->gender;
            $tokenbooking->age = $request->age;
            $tokenbooking->MobileNo = $request->MobileNo;
            $tokenbooking->Appoinmentfor_id = json_encode(['Appoinmentfor1' => $symptomIds1, 'Appoinmentfor2' => $symptomIds2]);
            $tokenbooking->gender = $request->gender;
            $tokenbooking->date = $request->date;
            $tokenbooking->TokenNumber = $request->TokenNumber;
            $tokenbooking->TokenTime = $request->TokenTime;
            $tokenbooking->doctor_id = $request->doctor_id;
            $tokenbooking->whenitstart = $request->whenitstart;
            $tokenbooking->whenitcomes = $request->whenitcomes;
            $tokenbooking->regularmedicine = $request->regularmedicine;
            $tokenbooking->Bookingtime = now();
            $tokenbooking->patient_id = $request->patient_id;
            $tokenbooking->clinic_id = $request->clinic_id;
            $tokenbooking->save();

            // Return a success response
            return $this->sendResponse("TokenBooking", $tokenbooking, '1', 'Token Booked successfully.');
        } catch (\Exception $e) {

            return $this->sendError('message', $e->getMessage(), 500);
        }
    }



    public function GetallAppointmentOfDocter($userId, $date, $clinicId, $schedule_type)
    {
        try {
            $doctor = Docter::where('UserId', $userId)->first();

            if (!$doctor) {
                return response()->json(['message' => 'Doctor not found.'], 404);
            }

            $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

            if ($schedule_type == 0) {



                $appointments = NewTokens::select(
                    'new_tokens.*',
                    'patient.mediezy_patient_id',
                    'patient.user_image',
                    'token_booking.PatientName',
                    'patient.age',
                    'token_booking.is_reached',
                    'token_booking.*',
                    'token_booking.schedule_type',
                    'token_booking.BookedPerson_id',
                    'token_booking.doctor_id'

                )
                    ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
                    ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
                    ->where('new_tokens.clinic_id', $clinicId)
                    ->where('new_tokens.doctor_id', $doctor_id)
                    ->whereDate('new_tokens.token_scheduled_date', $date)
                    //->where('token_booking.schedule_type', $schedule_type)
                    ->whereRaw('CAST(new_tokens.token_number AS SIGNED) > 0')
                    ->where('new_tokens.token_booking_status', 1)
                    ->where('new_tokens.is_checkedout', 0)
                    ->orderByRaw('CAST(new_tokens.token_number AS SIGNED) ASC')
                    ->get();
            } else {
                $appointments = NewTokens::select(
                    'new_tokens.*',
                    'patient.mediezy_patient_id',
                    'patient.user_image',
                    'token_booking.PatientName',
                    'patient.age',
                    'token_booking.is_reached',
                    'token_booking.*',
                    'token_booking.schedule_type',
                    'token_booking.BookedPerson_id',
                    'token_booking.doctor_id'

                )
                    ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
                    ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
                    ->where('new_tokens.clinic_id', $clinicId)
                    ->where('new_tokens.doctor_id', $doctor_id)
                    ->whereDate('new_tokens.token_scheduled_date', $date)
                    ->where('token_booking.schedule_type', $schedule_type)
                    ->whereRaw('CAST(new_tokens.token_number AS SIGNED) > 0')
                    ->where('new_tokens.token_booking_status', 1)
                    ->where('new_tokens.is_checkedout', 0)
                    ->orderByRaw('CAST(new_tokens.token_number AS SIGNED) ASC')
                    ->get();
            }
            $appointmentsWithDetails = [];
            $anyCheckedIn = false;

            foreach ($appointments as $appointment) {

                //   $isOnline = $appointment->BookedPerson_id == $appointment->doctor_id;
                $userImage = $appointment->user_image ? asset("UserImages/{$appointment->user_image}") : null;
                $symptoms = json_decode($appointment->Appoinmentfor_id, true);
                $mainSymptoms = MainSymptom::select('id', 'Mainsymptoms AS symtoms')
                    ->where('user_id', $appointment->BookedPerson_id ?? $appointment->UserId)
                    ->where('doctor_id', $appointment->doctor_id)
                    ->where('clinic_id', $appointment->clinic_id)
                    ->where('date', $appointment->date)
                    ->where('TokenNumber', $appointment->TokenNumber)
                    ->get()
                    ->toArray();

                $patientDetails = [
                    'id' => $appointment->token_id,
                    'mediezy_patient_id' => $appointment->mediezy_patient_id,
                    'PatientName' => $appointment->PatientName,
                    'TokenNumber' => $appointment->token_number,
                    'user_image' => $userImage,
                    'Age' => $appointment->age,
                    'is_reached' => $appointment->is_reached,
                    'schedule_type' => $appointment->schedule_type,
                    'Startingtime' => Carbon::parse($appointment->token_start_time)->format('h:i A'),
                    // 'Appoinmentfor_id' => $appointment->Appoinmentfor_id,
                    'main_symptoms' => $mainSymptoms,
                    'other_symptoms' => Symtoms::select('id', 'symtoms')
                        ->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray(),
                ];

                $symptoms = json_decode($appointment->Appoinmentfor_id, true);

                $appointmentDetails = [];
                if ($appointment->BookedPerson_id != $appointment->doctor_id) {
                    $additionalDetails = [
                        'online_status' => "online",
                    ];

                    $patientDetails = array_merge($patientDetails, $additionalDetails);
                }
                if ($appointment->BookedPerson_id == $appointment->doctor_id) {
                    $additionalDetails = [
                        'online_status' => "offline",
                    ];

                    $patientDetails = array_merge($patientDetails, $additionalDetails);
                }

                // if ($appointment->is_checkedin == 1) {
                //     $anyCheckedIn = true;
                // }
                ///
                $checkin_check = NewTokens::where('new_tokens.clinic_id', $clinicId)
                    ->where('new_tokens.doctor_id', $doctor_id)
                    ->whereDate('new_tokens.token_scheduled_date', $date)
                    ->get();

                foreach ($checkin_check as $checkin) {
                    if ($checkin->is_checkedin == 1) {
                        $anyCheckedIn = true;
                        break;
                    }
                }

                $appointmentsWithDetails[] = $patientDetails;
            }
            foreach ($appointmentsWithDetails as $index => &$details) {
                $details['first_index_status'] = ($index == 0 && !$anyCheckedIn) ? 1 : 0;
            }


            return $this->sendResponse('Appointments', $appointmentsWithDetails, '1', 'Appointments retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('message', $e->getMessage(), 500);
        }
    }


    // public function GetallAppointmentOfDocterCompleted($userId, $date, $clinicId, $schedule_type)
    // {
    //     try {
    //         $doctor = Docter::where('UserId', $userId)->first();

    //         if (!$doctor) {
    //             return response()->json(['message' => 'Doctor not found.'], 404);
    //         }


    //         $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

    //         if ($schedule_type == 0) {

    //             $appointments = CompletedAppointments::select(
    //                 'completed_appointments.patient_id',
    //                 'patient.mediezy_patient_id',
    //                 'patient.firstname as patient_name',
    //                 'completed_appointments.token_number',
    //                 'patient.user_image',
    //                 'patient.age',
    //                 'completed_appointments.schedule_type',
    //                 'completed_appointments.token_start_time',
    //                 'completed_appointments.booked_user_id',
    //                 'completed_appointments.appointment_for',
    //                 'completed_appointments.appointment_id',
    //                 'completed_appointments.clinic_id',
    //                 'completed_appointments.new_token_id',
    //                 'token_booking.BookedPerson_id',
    //                 'token_booking.doctor_id'
    //             )

    //                 ->leftJoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
    //                 ->leftJoin('token_booking', 'token_booking.patient_id', '=', 'patient.id')
    //                 ->where('completed_appointments.doctor_id', $doctor_id)
    //                 ->where('completed_appointments.clinic_id', $clinicId)
    //                 ->where('completed_appointments.clinic_id', $clinicId)
    //                 ->orderBy('completed_appointments.checkout_time', 'desc')
    //                 ->whereDate('completed_appointments.date', $date)
    //                 ->distinct('completed_appointments.new_token_id')
    //                 ->get();
    //         } else {

    //             $appointments = CompletedAppointments::select(
    //                 'completed_appointments.patient_id',
    //                 'patient.mediezy_patient_id',
    //                 'patient.firstname as patient_name',
    //                 'completed_appointments.token_number',
    //                 'patient.user_image',
    //                 'patient.age',
    //                 'completed_appointments.schedule_type',
    //                 'completed_appointments.token_start_time',
    //                 'completed_appointments.booked_user_id',
    //                 'completed_appointments.appointment_for',
    //                 'completed_appointments.appointment_id',
    //                 'completed_appointments.clinic_id',
    //                 'completed_appointments.new_token_id',
    //                 'token_booking.BookedPerson_id',
    //                 'token_booking.doctor_id'
    //             )

    //                 ->leftJoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
    //                 //->leftjoin('new_tokens','patient.id','=','new_tokens.patient_id')
    //                 ->leftJoin('token_booking', 'token_booking.patient_id', '=', 'patient.id')
    //                 ->where('completed_appointments.doctor_id', $doctor_id)
    //                 ->where('completed_appointments.clinic_id', $clinicId)
    //                 ->whereDate('completed_appointments.date', $date)
    //                 ->where('completed_appointments.schedule_type', $schedule_type)
    //                 ->distinct('completed_appointments.new_token_id')
    //                 ->get();
    //         }

    //         $appointmentsWithDetails = [];
    //         foreach ($appointments as $appointment) {
    //             $userImage = $appointment->user_image ? asset("UserImages/{$appointment->user_image}") : null;

    //             ////////////////////////////////////////////////////////////
    //             // $symptoms = json_decode($appointment->appointment_for, true);

    //             // $mainSymptoms = MainSymptom::select('id', 'Mainsymptoms AS symtoms')
    //             //     ->where('user_id', $appointment->BookedPerson_id ?? $appointment->UserId)
    //             //     ->where('doctor_id', $appointment->doctor_id)
    //             //     ->where('clinic_id', $appointment->clinic_id)
    //             //     ->where('date', $appointment->date)
    //             //     ->where('TokenNumber', $appointment->TokenNumber)
    //             //     ->get()
    //             //     ->toArray();
    //             $patient_user_id = Patient::where('id', $appointment->patient_id)->pluck('UserId')->first();

    //             // print_r($appointment->clinic_id);
    //             // exit;
    //             $main_symptoms = MainSymptom::select('Mainsymptoms')
    //                 ->where('user_id', $patient_user_id)
    //                 ->where('TokenNumber', $appointment->token_number)
    //                 ->where('clinic_id',  $appointment->clinic_id)
    //                 ->first();

    //             // $appointment['main_symptoms'] = $main_symptoms;
    //             $other_symptom_id = CompletedAppointments::select('appointment_for')
    //                 ->where('booked_user_id', $patient_user_id)
    //                 ->where('token_number',  $appointment->token_number)
    //                 ->where('clinic_id', $appointment->clinic_id)
    //                 ->orderBy('created_at', 'DESC')->first();

    //             if ((isset($other_symptom_id))) {
    //                 $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);
    //             }



    //             $other_symptom_array_value = [];

    //             if (isset($other_symptom_json['Appoinmentfor2'])) {
    //                 $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
    //             }
    //             $other_symptom = Symtoms::select('symtoms')
    //                 ->whereIn('id', $other_symptom_array_value)
    //                 ->get()->toArray();

    //             /////////////////////////////////////////////////////////////
    //             $patientDetails = [
    //                 'id' => $appointment->appointment_id,
    //                 'mediezy_patient_id' => $appointment->mediezy_patient_id,
    //                 'PatientName' => $appointment->patient_name,
    //                 'TokenNumber' => $appointment->token_number,
    //                 'user_image' => $userImage,
    //                 'Age' => $appointment->age,
    //                 // 'is_reached' => $appointment->is_reached,
    //                 'schedule_type' => $appointment->schedule_type,
    //                 'Startingtime' => Carbon::parse($appointment->token_start_time)->format('h:i A'),
    //                 'main_symptoms' => $main_symptoms,
    //                 'other_symptoms' => $other_symptom,
    //             ];

    //             if ($appointment->BookedPerson_id != $appointment->doctor_id) {
    //                 $additionalDetails = [
    //                     'online_status' => "online",
    //                 ];
    //             } else {
    //                 $additionalDetails = [
    //                     'online_status' => "offline",
    //                 ];
    //             }

    //             $patientDetails = array_merge($patientDetails, $additionalDetails);
    //             $appointmentsWithDetails[] = $patientDetails;
    //         }

    //         return $this->sendResponse('Appointments', $appointmentsWithDetails, '1', 'Appointments retrieved successfully.');
    //     } catch (\Exception $e) {
    //         return $this->sendError('Error', $e->getMessage(), 500);
    //     }
    // }
    public function GetallAppointmentOfDocterCompleted($userId, $date, $clinicId, $schedule_type)
    {
        try {
            $doctor = Docter::where('UserId', $userId)->first();

            if (!$doctor) {
                return response()->json(['message' => 'Doctor not found.'], 404);
            }


            $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

            if ($schedule_type == 0) {

                $appointments = CompletedAppointments::select(
                    'completed_appointments.patient_id',
                    'patient.mediezy_patient_id',
                    'patient.firstname as patient_name',
                    'completed_appointments.token_number',
                    'patient.user_image',
                    'patient.age',
                    'completed_appointments.schedule_type',
                    'completed_appointments.token_start_time',
                    'completed_appointments.booked_user_id',
                    'completed_appointments.appointment_for',
                    'completed_appointments.appointment_id',
                    'completed_appointments.clinic_id',
                    'completed_appointments.new_token_id',
                    'token_booking.BookedPerson_id',
                    'token_booking.doctor_id'
                )

                    ->leftJoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
                    ->leftJoin('token_booking', 'token_booking.patient_id', '=', 'patient.id')
                    ->where('completed_appointments.doctor_id', $doctor_id)
                    ->where('completed_appointments.clinic_id', $clinicId)
                    ->where('completed_appointments.clinic_id', $clinicId)
                    ->orderBy('completed_appointments.checkout_time', 'desc')
                    ->whereDate('completed_appointments.date', $date)
                    ->distinct('completed_appointments.new_token_id')
                    ->distinct(
                        'token_booking.BookedPerson_id',
                        'token_booking.doctor_id'
                    )
                    ->get();
            } else {

                $appointments = CompletedAppointments::select(
                    'completed_appointments.patient_id',
                    'patient.mediezy_patient_id',
                    'patient.firstname as patient_name',
                    'completed_appointments.token_number',
                    'patient.user_image',
                    'patient.age',
                    'completed_appointments.schedule_type',
                    'completed_appointments.token_start_time',
                    'completed_appointments.booked_user_id',
                    'completed_appointments.appointment_for',
                    'completed_appointments.appointment_id',
                    'completed_appointments.clinic_id',
                    'completed_appointments.new_token_id',
                    'token_booking.BookedPerson_id',
                    'token_booking.doctor_id'
                )

                    ->leftJoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
                    //->leftjoin('new_tokens','patient.id','=','new_tokens.patient_id')
                    ->leftJoin('token_booking', 'token_booking.patient_id', '=', 'patient.id')
                    ->where('completed_appointments.doctor_id', $doctor_id)
                    ->where('completed_appointments.clinic_id', $clinicId)
                    ->whereDate('completed_appointments.date', $date)
                    ->where('completed_appointments.schedule_type', $schedule_type)
                    ->distinct('completed_appointments.new_token_id')
                    ->get();
            }



            $appointmentsWithDetails = [];
            foreach ($appointments as $appointment) {
                $userImage = $appointment->user_image ? asset("UserImages/{$appointment->user_image}") : null;

                ////////////////////////////////////////////////////////////
                // $symptoms = json_decode($appointment->appointment_for, true);

                // $mainSymptoms = MainSymptom::select('id', 'Mainsymptoms AS symtoms')
                //     ->where('user_id', $appointment->BookedPerson_id ?? $appointment->UserId)
                //     ->where('doctor_id', $appointment->doctor_id)
                //     ->where('clinic_id', $appointment->clinic_id)
                //     ->where('date', $appointment->date)
                //     ->where('TokenNumber', $appointment->TokenNumber)
                //     ->get()
                //     ->toArray();
                $patient_user_id = Patient::where('id', $appointment->patient_id)->pluck('UserId')->first();

                // print_r($appointment->clinic_id);
                // exit;
                $main_symptoms = MainSymptom::select('Mainsymptoms')
                    ->where('user_id', $patient_user_id)
                    ->where('TokenNumber', $appointment->token_number)
                    ->where('clinic_id',  $appointment->clinic_id)
                    ->first();

                // $appointment['main_symptoms'] = $main_symptoms;
                $other_symptom_id = CompletedAppointments::select('appointment_for')
                    ->where('booked_user_id', $patient_user_id)
                    ->where('token_number',  $appointment->token_number)
                    ->where('clinic_id', $appointment->clinic_id)
                    ->orderBy('created_at', 'DESC')->first();

                if ((isset($other_symptom_id))) {
                    $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);
                }



                $other_symptom_array_value = [];

                if (isset($other_symptom_json['Appoinmentfor2'])) {
                    $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
                }
                $other_symptom = Symtoms::select('symtoms')
                    ->whereIn('id', $other_symptom_array_value)
                    ->get()->toArray();

                /////////////////////////////////////////////////////////////
                $patientDetails = [
                    'id' => $appointment->appointment_id,
                    'mediezy_patient_id' => $appointment->mediezy_patient_id,
                    'PatientName' => $appointment->patient_name,
                    'TokenNumber' => $appointment->token_number,
                    'user_image' => $userImage,
                    'Age' => $appointment->age,
                    // 'is_reached' => $appointment->is_reached,
                    'schedule_type' => $appointment->schedule_type,
                    'Startingtime' => Carbon::parse($appointment->token_start_time)->format('h:i A'),
                    'main_symptoms' => $main_symptoms,
                    'other_symptoms' => $other_symptom,
                ];

                if ($appointment->BookedPerson_id != $appointment->doctor_id) {
                    $additionalDetails = [
                        'online_status' => "online",
                    ];
                } else {
                    $additionalDetails = [
                        'online_status' => "offline",
                    ];
                }

                $patientDetails = array_merge($patientDetails, $additionalDetails);
                $appointmentsWithDetails[] = $patientDetails;
            }
            $uniqueAppointments = [];
            foreach ($appointmentsWithDetails as $appointment) {
                $uniqueAppointments[$appointment['id']] = $appointment;
            }

            $uniqueAppointments = array_values($uniqueAppointments);

            return $this->sendResponse('Appointments', $uniqueAppointments, '1', 'Appointments retrieved successfully.');

            //return $this->sendResponse('Appointments', $appointmentsWithDetails, '1', 'Appointments retrieved successfully.');
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal Server Error'], 500);
        }
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


    public function appointmentDetails(Request $request)
    {
        $rules = [
            'token_id' => 'required',
        ];

        $messages = [
            'token_id.required' => 'Token is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {

            $tokenId = $request->token_id;
            $booking = NewTokens::select(
                'new_tokens.is_checkedout',
                'new_tokens.is_checkedin',
                'new_tokens.token_id',
                'new_tokens.token_booking_id',
                'token_booking.TokenNumber',
                'token_booking.doctor_id',
                'token_booking.BookedPerson_id',
                'token_booking.PatientName',
                'token_booking.age',
                'token_booking.date',
                'token_booking.TokenTime',
                'token_booking.Appoinmentfor_id',
                'token_booking.whenitstart',
                'token_booking.whenitcomes',
                'token_booking.attachment',
                'token_booking.notes',
                'new_tokens.clinic_id',
                'token_booking.TokenNumber',
                'new_tokens.doctor_id as newDoctorId',
                'new_tokens.token_booking_id',
                'new_tokens.patient_id'
            )
                ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
                ->where('new_tokens.token_id', $tokenId)
                ->whereDate('token_booking.date', now()->toDateString())
                ->first();

            if (!$booking) {
                return response()->json(['status' => false, 'response' => "Booking not found"]);
            }
            $bookedPersonId = $booking->BookedPerson_id;
            $doctorId = $booking->doctor_id;
            $patient_id = $booking->patient_id;

            $patientUserId = Patient::where('id', $patient_id)->value('id');
            $mediezyPatientId = Patient::where('id', $booking->patient_id)->value('mediezy_patient_id');
            $booking['mediezy_patient_id'] = $mediezyPatientId;
            $UserImage = Patient::where('id', $booking->patient_id)->value('user_image');
            $userImage = $UserImage ? asset("UserImages/{$UserImage}") : null;
            $booking['user_image'] = $userImage;

            $symptoms = json_decode($booking->Appoinmentfor_id, true);
            $mainSymptoms = MainSymptom::select('id', 'Mainsymptoms AS symtoms')
                ->where('user_id', $booking->BookedPerson_id)
                ->where('doctor_id', $booking->doctor_id)
                ->where('clinic_id', $booking->clinic_id)
                ->where('clinic_id', $booking->date)
                ->where('clinic_id', $booking->TokenNumber)
                ->get()
                ->toArray();

            $otherSymptoms = Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray();
            $booking['main_symptoms'] = array_merge($mainSymptoms, $otherSymptoms);

            $booking['medicine'] = Medicine::where('token_id', $request->token_id)
                ->where('docter_id', $doctorId)
                ->where('user_id', $bookedPersonId)
                ->get();

            $booking['PatientId'] = $patientUserId;

            // Allergies
            $patient_allergies = PatientAllergies::where('patient_id', $booking->patient_id)->get();
            $allergies_details = $patient_allergies->map(function ($allergies) {
                $allergy = Allergy::find($allergies->allergy_id);

                if ($allergy) {
                    return [
                        'allergy_id' => $allergy->id,
                        'allergy_name' => $allergy->allergy,
                        'allergy_details' => $allergies->allergy_details,
                    ];
                } else {
                    return null;
                }
            })->filter();

            $patient_allergy_data = Patient::where('id', $patient_id)
                ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
                ->first();
            $patient_allergy_data = Patient::where('id', $patient_id)
                ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
                ->first();
            $patient_allergy_id = $patient_allergy_data ? $patient_allergy_data->allergy_id : null;
            $allergy_data = $patient_allergy_id ? Allergy::where('id', $patient_allergy_id)->first() : null;
            $booking['allergy'] = $allergy_data ? $allergy_data->allergy : null;
            $booking['surgery_name'] = $patient_allergy_data ?
                ($patient_allergy_data->surgery_name === 'Other' ? $patient_allergy_data->surgery_details : $patient_allergy_data->surgery_name) : null;
            if (!empty($booking['surgery_name'])) {
                $booking['surgery_name'] = explode(',', $booking['surgery_name']);
                $booking['surgery_name'] = array_map(function ($surgery) {
                    return trim($surgery, " \t\n\r\0\x0B[]");
                }, $booking['surgery_name']);
            }
            $booking['treatment_taken'] = $patient_allergy_data ?
                ($patient_allergy_data->treatment_taken === 'Other' ? $patient_allergy_data->treatment_details : $patient_allergy_data->treatment_taken) : null;
            if (!empty($booking['treatment_taken'])) {
                $booking['treatment_taken'] = explode(',', $booking['treatment_taken']);
                $booking['treatment_taken'] = array_map(function ($treatmenttaken) {
                    return trim($treatmenttaken, " \t\n\r\0\x0B[]");
                }, $booking['treatment_taken']);
            }

            $booking['surgery_details'] = $patient_allergy_data ? $patient_allergy_data->surgery_details : null;
            $booking['treatment_taken_details'] = $patient_allergy_data ? $patient_allergy_data->treatment_taken_details : null;
            $booking['Medicine_Taken'] = $patient_allergy_data ? $patient_allergy_data->Medicine_Taken : null;
            $booking['allergies_details'] = $allergies_details;

            return response()->json(['status' => true, 'booking_data' => $booking, 'message' => 'Success']);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    // public function addPrescription(Request $request)
    // {
    //     $rules = [
    //         'BookedPerson_id' => 'required',
    //         'doctor_id' => 'required',
    //         'token_id' => 'required',
    //         'medical_shop_id' => 'required|exists:medicalshop,id',
    //         'medicine_name' => 'sometimes',
    //         'dosage' => 'required_with:medicine_name',
    //         'no_of_days' => 'required_with:medicine_name',
    //         'type' => 'required_with:medicine_name|in:1,2',
    //         'night' => 'required_with:medicine_name|in:0,1',
    //         'morning' => 'required_with:medicine_name|in:0,1',
    //         'noon' => 'required_with:medicine_name|in:0,1',
    //         'evening' => 'sometimes',


    //     ];

    //     $messages = [
    //         'BookedPerson_id.required' => 'BookedPerson_id is required',
    //         'medical_shop_id.required' => 'Medical shop id is required',
    //     ];

    //     $validator = Validator::make($request->all(), $rules, $messages);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => false, 'response' => $validator->errors()->first()], 400);
    //     }


    //     try {
    //         $tokenPrescription  = TokenBooking::select('TokenNumber')->where('new_token_id', $request->token_id)->first();

    //         if (!$tokenPrescription) {
    //             return response()->json(['status' => false, 'response' => "No Appointments found for the token"]);
    //         }
    //         $patient_id = NewTokens::where('token_id', $request->token_id)->value('patient_id');
    //         $token_number = NewTokens::where('token_id', $request->token_id)->value('token_number');

    //         if ($request->medicine_name) {
    //             $medicine  = new Medicine();
    //             $medicine->token_id     = $request->token_id;
    //             $medicine->user_id      = $request->BookedPerson_id;
    //             $medicine->docter_id     = $request->doctor_id;
    //             $medicine->patient_id =  $patient_id;
    //             $medicine->medical_shop_id     = $request->medical_shop_id;
    //             $medicine->medicineName = $request->medicine_name;
    //             $medicine->Dosage       = $request->dosage;
    //             $medicine->NoOfDays     = $request->no_of_days;
    //             $medicine->Noon         = $request->noon;
    //             $medicine->morning      = $request->morning;
    //             $medicine->night        = $request->night;
    //             $medicine->type         = $request->type;
    //             $medicine->medicine_type = 2;
    //             $medicine->evening         = $request->evening;
    //             $medicine->token_number   = $token_number;
    //             $medicine->save();
    //         }
    //         if ($request->notes) {
    //             $medicine->notes         = $request->notes;
    //             $medicine->save();
    //         }

    //         return response()->json(['status' => true, 'message' => 'Medicine added.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'response' => "Internal Server Error"]);
    //     }
    // }

    // public function addPrescription(Request $request)
    // {
    //     $rules = [
    //         'BookedPerson_id' => 'required',
    //         'doctor_id' => 'required',
    //         'token_id' => 'required',
    //         'medical_shop_id' => 'sometimes',
    //         'medicine_name' => 'sometimes',
    //         'dosage' => 'required_with:medicine_name',
    //         'no_of_days' => 'required_with:medicine_name',
    //         'type' => 'required_with:medicine_name|in:1,2,3',
    //         'night' => 'required_with:medicine_name|in:0,1',
    //         'morning' => 'required_with:medicine_name|in:0,1',
    //         'noon' => 'required_with:medicine_name|in:0,1',
    //         'evening' => 'sometimes',
    //         'interval' => 'sometimes',
    //         'time_section' => 'sometimes',

    //     ];

    //     $messages = [
    //         'BookedPerson_id.required' => 'BookedPerson_id is required',

    //     ];

    //     $validator = Validator::make($request->all(), $rules, $messages);

    //     if ($validator->fails()) {
    //         return response()->json(['status' => false, 'response' => $validator->errors()->first()], 400);
    //     }


    //     try {
    //         $tokenPrescription  = TokenBooking::select('TokenNumber')->where('new_token_id', $request->token_id)->first();

    //         if (!$tokenPrescription) {
    //             return response()->json(['status' => false, 'response' => "No Appointments found for the token"]);
    //         }
    //         $patient_id = NewTokens::where('token_id', $request->token_id)->value('patient_id');
    //         $token_number = NewTokens::where('token_id', $request->token_id)->value('token_number');

    //         if ($request->medicine_name) {
    //             $medicine  = new Medicine();
    //             $medicine->token_id     = $request->token_id;
    //             $medicine->user_id      = $request->BookedPerson_id;
    //             $medicine->docter_id     = $request->doctor_id;
    //             $medicine->patient_id =  $patient_id;
    //             $medicine->medical_shop_id     = $request->medical_shop_id;
    //             $medicine->medicineName = $request->medicine_name;
    //             $medicine->Dosage       = $request->dosage;
    //             $medicine->NoOfDays     = $request->no_of_days;
    //             $medicine->Noon         = $request->noon;
    //             $medicine->morning      = $request->morning;
    //             $medicine->night        = $request->night;
    //             $medicine->type         = $request->type;
    //             $medicine->interval = $request->interval;
    //             $medicine->time_section = $request->time_section;
    //             $medicine->medicine_type = 2;
    //             $medicine->evening         = $request->evening;
    //             $medicine->token_number   = $token_number;
    //             $medicine->save();
    //         }
    //         if ($request->notes) {
    //             $medicine->notes         = $request->notes;
    //             $medicine->save();
    //         }



    //         return response()->json(['status' => true, 'message' => 'Medicine added.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['status' => false, 'response' => "Internal Server Error"]);
    //     }
    // }
    public function addPrescription(Request $request)
    {
        $rules = [
            'BookedPerson_id' => 'required',
            'doctor_id' => 'required',
            'token_id' => 'required',
            'medical_shop_id' => 'sometimes',
            'medicine_name' => 'sometimes',
            'dosage' => 'sometimes:medicine_name',
            'no_of_days' => 'required_with:medicine_name',
            'type' => 'required_with:medicine_name|in:1,2,3,4',
            'night' => 'required_with:medicine_name|in:0,1',
            'morning' => 'required_with:medicine_name|in:0,1',
            'noon' => 'required_with:medicine_name|in:0,1',
            'evening' => 'sometimes',
            'interval' => 'sometimes',
            'time_section' => 'sometimes',
            'medicine_id' => 'sometimes|exists:medicine_base,id'
        ];

        $messages = [
            'BookedPerson_id.required' => 'BookedPerson_id is required',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'response' => $validator->errors()->first()], 400);
        }

        try {
            $tokenPrescription = TokenBooking::select('TokenNumber')->where('new_token_id', $request->token_id)->first();

            if (!$tokenPrescription) {
                return response()->json(['status' => false, 'response' => "No Appointments found for the token"]);
            }

            $patient_id = NewTokens::where('token_id', $request->token_id)->value('patient_id');
            $token_number = NewTokens::where('token_id', $request->token_id)->value('token_number');

            if ($request->medicine_name) {
                $medicine = new Medicine();
                $medicine->token_id = $request->token_id;
                $medicine->user_id = $request->BookedPerson_id;
                $medicine->docter_id = $request->doctor_id;
                $medicine->medicine_id = $request->medicine_id;
                $medicine->patient_id = $patient_id;
                $medicine->medical_shop_id = $request->medical_shop_id;
                $medicine->medicineName = $request->medicine_name;
                $medicine->Dosage = $request->dosage;
                $medicine->NoOfDays = $request->no_of_days;
                $medicine->Noon = $request->noon;
                $medicine->morning = $request->morning;
                $medicine->night = $request->night;
                $medicine->type = $request->type;
                $medicine->interval = $request->interval;
                $medicine->time_section = $request->time_section;
                $medicine->medicine_type = 2;
                $medicine->evening = $request->evening;
                $medicine->token_number = $token_number;
                $medicine->save();
                $medicineHistory = new MedicineHistory();
                $medicineHistory->medicine_id = $request->medicine_id;
                $medicineHistory->doctor_id = $request->doctor_id;
                Log::info('Inserting into medicine_history', [
                    'medicine_id' => $medicineHistory->medicine_id,
                    'doctor_id' => $medicineHistory->doctor_id,
                ]);
                $medicineHistory->save();
            }
            if ($request->notes) {
                $medicine->notes = $request->notes;
                $medicine->save();
            }
            return response()->json(['status' => true, 'message' => 'Medicine added.']);
        } catch (\Exception $e) {
            Log::error('Error adding prescription', ['exception' => $e]);

            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function getMedicineById($medicineid)
    {
        $medicines = Medicine::where('id', $medicineid)
            ->get();

        return response()->json(['medicine' => $medicines]);
    }

    public function deleteMedicine($id)
    {
        $medicine = Medicine::find($id);

        if (!$medicine) {
            return response()->json(['message' => 'Medicine not found'], 404);
        }
        $medicine->delete();

        return response()->json(['message' => 'Medicine deleted successfully'], 200);
    }


    public function UpdatemedicineById(Request $request)
    {
        $rules = [
            'medicine_id' => 'required',
            'medicine_name' => 'sometimes',
            'dosage' => 'required_with:medicine_name',
            'no_of_days' => 'required_with:medicine_name',
            'type' => 'required_with:medicine_name|in:1,2,3,4',
            'night' => 'required_with:medicine_name|in:0,1',
            'morning' => 'required_with:medicine_name|in:0,1',
            'evening' => 'required_with:medicine_name|in:0,1',
            'noon' => 'required_with:medicine_name|in:0,1',
            'notes' => 'sometimes',
            'attachment' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'time_section' => 'sometimes',
            'interval' => 'sometimes'
        ];

        $messages = [
            'medicine_id.required' => 'Medicine ID is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }


        try {
            $medicine = Medicine::find($request->medicine_id);

            if (!$medicine) {
                return response()->json(['status' => false, 'response' => 'Medicine not found.']);
            }

            if ($request->medicine_name) {
                $medicine->medicineName = $request->medicine_name;
                $medicine->dosage = $request->dosage;
                $medicine->NoOfDays = $request->no_of_days;
                $medicine->noon = $request->noon;
                $medicine->morning = $request->morning;
                $medicine->evening = $request->evening;
                $medicine->night = $request->night;
                $medicine->type = $request->type;
                $medicine->time_section = $request->time_section;
                $medicine->interval = $request->interval;
            }

            if ($request->notes) {
                $medicine->notes = $request->notes;
            }
            $medicine->save();

            return response()->json(['status' => true, 'message' => 'Medicine updated.']);
        } catch (\Exception $e) {

            return response()->json(['status' => false, 'message' => 'Internal Server Error']);
        }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function patientBookGeneratedTokens(Request $request)
    {

        try {
            $this->validate($request, [
                'BookedPerson_id' => 'required',
                'PatientName' => 'required',
                'gender' => 'required',
                'age' => 'required',
                'MobileNo' => 'required',
                'date' => 'required|date_format:Y-m-d',
                'TokenNumber' => 'required',
                'TokenTime' => 'required',
                'whenitstart' => 'required',
                'whenitcomes' => 'required',
                'doctor_id' => 'required',
                'Appoinmentfor1' => 'sometimes|array',
                'Appoinmentfor2' => 'sometimes|array',
                'clinic_id' => 'required',
                'Bookingtype' => 'sometimes|in:1,2,3', //one self 2 fm 3 other
                'schedule_type' => 'sometimes',
                'regular_medicine' => 'sometimes',
                'illness' => 'sometimes',
                'medicine_name' => 'sometimes',
                'allergy' => 'sometimes',
                'allergy_details' => 'sometimes',
                'surgery_details' => 'sometimes',
                'treatment_taken' => 'sometimes',
                'token_id' => 'sometimes',
                'normal_reschedule_token_id' => 'sometimes',
                'reschedule_type' => 'required|in:0,1,2'   // 0 = normal booking , 1= doctor reschedule booking , 2 = patinet reschedule

            ]);


            //add a patient if the booking is from doctor app
            if (!isset($request->patient_id)) {
                $new_doctor_patient = new Patient();
                $new_doctor_patient->firstname = $request->PatientName;
                $new_doctor_patient->gender = $request->gender;
                $new_doctor_patient->age = $request->age;
                $new_doctor_patient->dateofbirth = Carbon::now()->subYears($request->age)->toDateString();
                $new_doctor_patient->mediezy_patient_id = $this->generatePatientUniqueId();
                $new_doctor_patient->mobileNo = $request->MobileNo;
                $new_doctor_patient->UserId =  $request->BookedPerson_id;
                $new_doctor_patient->regularMedicine = $request->regular_medicine;
                $new_doctor_patient->illness = $request->illness ?? NULL;
                $new_doctor_patient->Medicine_Taken = $request->medicine_name;
                $new_doctor_patient->allergy_id = $request->allergy_id;
                $new_doctor_patient->allergy_name     = $request->allergy_name;
                $new_doctor_patient->surgery_name     = $request->surgery_details;
                $new_doctor_patient->treatment_taken     = $request->treatment_taken;
                $new_doctor_patient->save();
                $new_doctor_patient_id =  $new_doctor_patient->id;
            }


            $symptomIds1 = [];
            foreach ($request->input('Appoinmentfor1') as $symptomName) {
                $symptom = MainSymptom::create(['Mainsymptoms' => $symptomName]);
                $symptom->user_id = $request->input('BookedPerson_id');
                $symptom->doctor_id = $request->input('doctor_id');
                $symptom->clinic_id = $request->input('clinic_id');
                $symptom->date = $request->input('date');
                $symptom->TokenNumber = $request->input('TokenNumber');
                $symptom->save();
                $symptomIds1[] = $symptom->id;
            }
            $symptomIds2 = array_map('intval', $request->input('Appoinmentfor2'));
            foreach ($symptomIds2 as $symptomId) {
                $symptom = Symtoms::find($symptomId);
                if (!$symptom) {
                    return $this->sendError('Invalid Appoinmentfor2 ID', 'The specified Appoinmentfor2 ID does not exist in the symptoms table.', 400);
                }
            }

            $doctor_user_id = $request->doctor_id;
            $schedule_type = $request->schedule_type;
            $doctor_id = Docter::where('UserId', $doctor_user_id)->pluck('id')->first();

            $existingBooking = NewTokens::where('doctor_id', $doctor_id)
                ->where('clinic_id', $request->input('clinic_id'))
                ->where('token_scheduled_date', $request->input('date'))
                ->where('token_number', $request->input('TokenNumber'))
                ->where('token_booking_status', 1)
                ->exists();


            if ($existingBooking) {
                return $this->sendError('This token has already been booked by someone. Please book another token.', 200);
            }

            if ($request->patient_id) {
                //previous bookings
                $previous_bookings = NewTokens::select('token_start_time')
                    ->where('patient_id', $request->patient_id)
                    ->orderBy('token_start_time', 'desc')
                    ->whereDate('token_scheduled_date', $request->date)
                    ->get();

                // if ($previous_bookings) {
                //     foreach ($previous_bookings as $previous_booking) {
                //         $previous_booked_token_start_time = Carbon::parse($previous_booking->token_start_time)->format('H:i:s');
                //         $requestTokenTime = Carbon::parse($request->TokenTime)->format('H:i:s');
                //         $previous_start_time = Carbon::createFromFormat('H:i:s', $previous_booked_token_start_time);
                //         $selected_start_time = Carbon::createFromFormat('H:i:s', $requestTokenTime);

                //         if ($previous_start_time > $selected_start_time) {
                //             $time_difference_minutes = $previous_start_time->diffInMinutes($selected_start_time);
                //             if ($time_difference_minutes <= 30) {
                //                 return $this->sendError('Nearby appointment already exists.', 200);
                //             }
                //         }
                //         if ($previous_start_time < $selected_start_time) {
                //             $time_difference_minutes = $selected_start_time->diffInMinutes($previous_start_time);
                //             if ($time_difference_minutes <= 60) {
                //                 return $this->sendError('Nearby appointment already exists.', 200);
                //             }
                //         }
                //     }
                // }
            }

            //// reschedule tokens

            if ($request->reschedule_type == 1) {   //doctor reschedules

                $reschedule_check = RescheduleTokens::where('patient_id', $request->patient_id)
                    // where('doctor_id', $doctor_id)
                    //     ->
                    ->delete();
            }

            if ($request->reschedule_type == 2) {

                $existing_token_for_reschedule = NewTokens::where('token_id', $request->normal_reschedule_token_id)->first();
                $normal_reschedule_token_id = $request->normal_reschedule_token_id;
                Log::info('info normal_reschedule_token_id: ' . $normal_reschedule_token_id);
                Log::info('info normal_reschedule_token_id: ' . $normal_reschedule_token_id);


                if ($existing_token_for_reschedule) {

                    $existing_token_for_reschedule->token_booking_id = null;
                    $existing_token_for_reschedule->token_booking_status = null;
                    $existing_token_for_reschedule->patient_id = null;
                    $existing_token_for_reschedule->booked_user_id = null;
                    $existing_token_for_reschedule->doctor_user_id = null;
                    $existing_token_for_reschedule->token_booked_time = null;
                    $existing_token_for_reschedule->estimate_checkin_time = null;
                    $existing_token_for_reschedule->save();

                    TokenBooking::where('new_token_id', $request->normal_reschedule_token_id)->delete();
                }
            }

            $tokenbooking = new TokenBooking();
            $tokenbooking->BookedPerson_id = $request->BookedPerson_id;
            $tokenbooking->PatientName = $request->PatientName;
            $tokenbooking->gender = $request->gender;
            $tokenbooking->age = $request->age;
            $tokenbooking->MobileNo = $request->MobileNo;
            $tokenbooking->Appoinmentfor_id = json_encode(['Appoinmentfor1' => $symptomIds1, 'Appoinmentfor2' => $symptomIds2]);
            $tokenbooking->gender = $request->gender;
            $tokenbooking->date = $request->date;
            $tokenbooking->TokenNumber = $request->TokenNumber;
            $tokenbooking->TokenTime = $request->TokenTime;
            $tokenbooking->doctor_id = $request->doctor_id;
            $tokenbooking->whenitstart = $request->whenitstart;
            $tokenbooking->whenitcomes = $request->whenitcomes;
            $tokenbooking->regularmedicine = $request->regularmedicine;
            $tokenbooking->Bookingtime = now();
            $tokenbooking->patient_id = $request->patient_id ?? $new_doctor_patient_id;
            $tokenbooking->clinic_id = $request->clinic_id;
            $tokenbooking->schedule_type = $schedule_type;
            $tokenbooking->save();
            $token_booking_id = $tokenbooking->id;
            $booked_user_id = $request->BookedPerson_id;
            Log::channel('doctor_schedules')->info("token_booking_id $token_booking_id");
            //update patient data

            if (

                $request->has('illness') ||
                $request->has('medicine_name') ||
                $request->has('allergy') ||
                $request->has('allergy_name	') ||
                $request->has('surgery_details') ||
                $request->has('treatment_taken') &&
                $request->has('patient_id')
            ) {

                $patient_data = Patient::where('id', $request->patient_id)->first();
                $patient_data->illness = $request->illness;
                $patient_data->Medicine_Taken = $request->medicine_name;
                $patient_data->allergy = $request->allergy_id;
                $patient_data->allergy_name     = $request->allergy_name;
                $patient_data->surgery_name     = $request->surgery_details;
                $patient_data->treatment_taken     = $request->treatment_taken;
                $patient_data->save();
            }

            if (isset($token_booking_id)) {

                $doctorID = Docter::where('UserId', $request->doctor_id)->pluck('id')->first();
                $update_new_tokens_table =  NewTokens::where('doctor_id', $doctorID)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_number', $request->TokenNumber)
                    ->where('token_scheduled_date', $request->date)
                    ->first();

                $estimate_check_in_time =  $update_new_tokens_table ? $update_new_tokens_table->token_start_time : null;
                $estimate_check_in_time = Carbon::parse($estimate_check_in_time)->subMinutes(20)->format('Y-m-d H:i:s');

                if ($update_new_tokens_table) {
                    $update_new_tokens_table->token_booking_id = $token_booking_id;
                    $update_new_tokens_table->token_booking_status = 1;
                    $update_new_tokens_table->patient_id = $request->patient_id ?? $new_doctor_patient_id;
                    $update_new_tokens_table->booked_user_id = $booked_user_id;
                    $update_new_tokens_table->doctor_user_id = $request->input('doctor_id');
                    $update_new_tokens_table->token_booked_time = Carbon::now()->format('Y-m-d H:i:s');
                    $update_new_tokens_table->estimate_checkin_time = $update_new_tokens_table ? $estimate_check_in_time : null;
                    $update_new_tokens_table->save();
                    $new_token_id = $update_new_tokens_table->token_id;
                    $tokenbooking->new_token_id = $new_token_id;
                    $tokenbooking->save();

                    $request_symptoms = implode(', ', $request->Appoinmentfor1);
                    if ($request_symptoms != '') {

                        $patientSymptom = new PatientSymptoms();
                        $patientSymptom->patient_id = $request->patient_id ?? null;
                        $patientSymptom->doctor_id = $doctorID ?? null;
                        $patientSymptom->symptoms = implode(', ', $request->Appoinmentfor1);
                        $patientSymptom->save();
                        //add to symptoms  table if count is > 5

                        $request_symptoms = implode(', ', $request->Appoinmentfor1); //currently there will be only one symptom.

                        $symptoms_count_check = PatientSymptoms::where('symptoms', $request_symptoms)
                            ->count();

                        if ($symptoms_count_check > 5) {
                            $doctor_specializations = DoctorClinicSpecialization::select('specialization_id')
                                ->where('doctor_id', $doctorID)
                                ->get();
                            foreach ($doctor_specializations as $doctor_specialization) {

                                $existingSymptoms = Symtoms::where('specialization_id', $doctor_specialization->specialization_id)
                                    ->whereIn('symtoms', $request->Appoinmentfor1)
                                    ->exists();

                                if (!$existingSymptoms) {
                                    $symptoms = new Symtoms();
                                    $symptoms->specialization_id = $doctor_specialization->specialization_id;
                                    $symptoms->symtoms = implode(', ', $request->Appoinmentfor1);
                                    $symptoms->save();
                                }
                            }
                        }
                    }
                    //send push notification
                    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //PUSH NOTIFICATION
                    $userId = Auth::user()->id;
                    $userIds = [$userId];
                    $title = "Appointment Booked";
                    $startTime = Carbon::parse($update_new_tokens_table->token_start_time)->subMinutes(20)->format('F j, g:i A');
                    $message = "Your appointment starts at $startTime";
                    $type = "booking-success";

                    $notificationHelper = new PushNotificationHelper();
                    $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




                    return $this->sendResponse("TokenBooking", $update_new_tokens_table, '1', 'Token Booked successfully.');
                } else {
                }
            } else {
                Log::channel('doctor_schedules')->info(" Token Details not found in new tokens table ");

                return $this->sendResponse("TokenBooking", $tokenbooking, '1', 'Token_booking_id not found');
            }
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }

    public function getAllAppointmentDetails($token_id)
    {
        try {
            $booking = NewTokens::select(
                'new_tokens.is_checkedout',
                'new_tokens.is_checkedin',
                'new_tokens.token_id',
                'new_tokens.token_booking_id',
                'token_booking.TokenNumber',
                'token_booking.doctor_id',
                'token_booking.BookedPerson_id',
                'token_booking.PatientName',
                'token_booking.age',
                'token_booking.date',
                'token_booking.TokenTime',
                'token_booking.Appoinmentfor_id',
                'token_booking.whenitstart',
                'token_booking.whenitcomes',
                'token_booking.attachment',
                'token_booking.notes',
                'new_tokens.clinic_id',
                'token_booking.TokenNumber',
                'new_tokens.doctor_id as newDoctorId',
                'new_tokens.token_booking_id',
                'new_tokens.patient_id',
                'token_booking.Appoinmentfor_id',
                'token_booking.height',
                'token_booking.weight',
                'token_booking.temperature',
                'token_booking.temperature_type',
                'token_booking.spo2',
                'token_booking.sys',
                'token_booking.dia',
                'token_booking.heart_rate',
                'token_booking.heart_rate',
            )
                ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
                ->where('new_tokens.token_id', $token_id)
                //    ->whereDate('token_booking.date', now()->toDateString())
                ->first();

            if (!$booking) {
                return response()->json(['status' => false, 'response' => "Booking not found"]);
            }

            $patient_id = $booking->patient_id;
            $patient = Patient::where('id', $booking->patient_id)->first();
            $booking['patient'] = $patient;
            if ($patient) {

                $dob = new DateTime($patient->dateofbirth);
                $now = new DateTime();
                $diff = $now->diff($dob);
                $ageInMonths = $diff->y * 12 + $diff->m;
                if ($ageInMonths < 12) {
                    $displayAge = $ageInMonths . ' months old';
                } else {
                    $displayAge = $diff->y . ' years old';
                }
                $booking['patient'] = [

                    'dateofbirth' => $patient->dateofbirth,
                    'age' => $displayAge,

                ];
            } else {

                $booking['patient'] = null;
            }
            $appointmentForIds = json_decode($booking->Appoinmentfor_id, true);
            $Main_Symptoms = [];
            $Other_Symptoms = [];

            if (isset($appointmentForIds['Appoinmentfor1'])) {
                $mainSymptoms = MainSymptom::whereIn('id', $appointmentForIds['Appoinmentfor1'])->get();
                foreach ($mainSymptoms as $mainSymptom) {
                    $Main_Symptoms[] = [
                        'id' => $mainSymptom->id,
                        'name' => $mainSymptom->Mainsymptoms,
                    ];
                }
            }

            if (isset($appointmentForIds['Appoinmentfor2'])) {
                $symptoms = Symtoms::whereIn('id', $appointmentForIds['Appoinmentfor2'])->get();
                foreach ($symptoms as $symptom) {
                    $Other_Symptoms[] = [
                        'id' => $symptom->id,
                        'name' => $symptom->symtoms,
                    ];
                }
            }
            $booking->Main_Symptoms = empty($Main_Symptoms) ? [['id' => null, 'name' => null]] : $Main_Symptoms;
            $booking->Other_Symptoms = empty($Other_Symptoms) ? [['id' => null, 'name' => null]] : $Other_Symptoms;

            $booking->Main_Symptoms = $Main_Symptoms;
            $booking->Other_Symptoms = $Other_Symptoms;

            // Fetch Medicine
            $medicine = Medicine::where('token_id', $token_id)
                ->where('docter_id', $booking->doctor_id)
                ->where('user_id', $booking->BookedPerson_id)
                ->get();
            $booking['medicine'] = $medicine;

            // Fetch Patient Allergy Data
            $patient_id = $booking->patient_id;
            $patient_allergy_data = Patient::where('id', $patient_id)->first();

            $vitals = [];


            $allNull = true;

            if (!empty($booking['height'])) {
                $vitals['height'] = $booking['height'];
                $allNull = false;
            } else {
                $vitals['height'] = null;
            }

            if (!empty($booking['weight'])) {
                $vitals['weight'] = $booking['weight'];
                $allNull = false;
            } else {
                $vitals['weight'] = null;
            }

            if (!empty($booking['temperature'])) {
                $vitals['temperature'] = $booking['temperature'];
                $allNull = false;
            } else {
                $vitals['temperature'] = null;
            }

            if (!empty($booking['temperature_type'])) {
                $vitals['temperature_type'] = $booking['temperature_type'];
                $allNull = false;
            } else {
                $vitals['temperature_type'] = null;
            }

            if (!empty($booking['spo2'])) {
                $vitals['spo2'] = $booking['spo2'];
                $allNull = false;
            } else {
                $vitals['spo2'] = null;
            }

            if (!empty($booking['sys'])) {
                $vitals['sys'] = $booking['sys'];
                $allNull = false;
            } else {
                $vitals['sys'] = null;
            }

            if (!empty($booking['dia'])) {
                $vitals['dia'] = $booking['dia'];
                $allNull = false;
            } else {
                $vitals['dia'] = null;
            }

            if (!empty($booking['heart_rate'])) {
                $vitals['heart_rate'] = $booking['heart_rate'];
                $allNull = false;
            } else {
                $vitals['heart_rate'] = null;
            }

            if ($allNull) {
                $vitals = null;
            }

            $booking->vitals = $vitals;


            if ($patient_allergy_data) {
                if ($patient_allergy_data->treatment_taken_details !== null) {
                    $booking['treatment_taken'] = $patient_allergy_data->treatment_taken_details;
                } else {

                    $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;

                    $booking['treatment_taken'] = $treatment_taken;
                }

                // Allergies
                $patient_allergies = PatientAllergies::where('patient_id', $booking->patient_id)->get();
                $allergies_details = $patient_allergies->map(function ($allergies) {
                    $allergy = Allergy::find($allergies->allergy_id);

                    if ($allergy) {
                        return [
                            'allergy_id' => $allergy->id,
                            'allergy_name' => $allergy->allergy,
                            'allergy_details' => $allergies->allergy_details,
                        ];
                    } else {
                        return null;
                    }
                })->filter();
                $patient_allergy_data = Patient::where('id', $patient_id)
                    ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
                    ->first();

                $booking['surgery_name'] = $patient_allergy_data ?
                    ($patient_allergy_data->surgery_name === 'Other' ? $patient_allergy_data->surgery_details : $patient_allergy_data->surgery_name) : null;
                if (!empty($booking['surgery_name'])) {
                    $booking['surgery_name'] = explode(',', $booking['surgery_name']);
                    $booking['surgery_name'] = array_map(function ($surgery) {
                        return trim($surgery, " \t\n\r\0\x0B[]");
                    }, $booking['surgery_name']);
                }

                $booking['treatment_taken'] = $patient_allergy_data ?
                    ($patient_allergy_data->treatment_taken === 'Other' ? $patient_allergy_data->treatment_details : $patient_allergy_data->treatment_taken) : null;
                if (!empty($booking['treatment_taken'])) {
                    $booking['treatment_taken'] = explode(',', $booking['treatment_taken']);
                    $booking['treatment_taken'] = array_map(function ($treatmenttaken) {
                        return trim($treatmenttaken, " \t\n\r\0\x0B[]");
                    }, $booking['treatment_taken']);
                }

                $booking['surgery_details'] = $patient_allergy_data ? $patient_allergy_data->surgery_details : null;
                $booking['treatment_taken_details'] = $patient_allergy_data ? $patient_allergy_data->treatment_taken_details : null;
                $booking['allergies_details'] = $allergies_details;
                $json_booking = json_encode($booking);
            } else {
                $booking['treatment_taken'] = null;
                $booking['surgery_name'] = null;
            }
            $mediezyPatientId = Patient::where('id', $patient_id)->value('mediezy_patient_id');
            $userImage = Patient::where('id', $patient_id)->value('user_image');
            $userImage = $userImage ? asset("UserImages/{$userImage}") : null;

            //medicine details
            $medicine_data = Medicine::where('patient_id', $patient_id)->get();

            $medicine_details = [];
            $patient_medicines = Medicine::select('medicineName', 'illness')
                ->where('docter_id', 0)
                ->where('patient_id', $patient_id)
                ->orderBy('created_at', 'DESC')
                ->get();

            $patient_medicines_array = [];
            foreach ($patient_medicines as $patient_medicine) {
                $medicine_details[] = [
                    'medicine_name' => $patient_medicine->medicineName,
                    'illness'   => $patient_medicine->illness
                ];
            }

            //////////////////////
            if ($booking->BookedPerson_id == $booking->doctor_id) {
                $booking['doctor_app_booking'] = true;
            } else {
                $booking['doctor_app_booking'] = false;
            }

            //////////////////////////

            $booking['medicine_details'] = $medicine_details;
            $booking['mediezy_patient_id'] = $mediezyPatientId;
            $booking['user_image'] = $userImage;

            unset($booking['height']);
            unset($booking['weight']);
            unset($booking['temperature']);
            unset($booking['temperature_type']);
            unset($booking['spo2']);
            unset($booking['sys']);
            unset($booking['dia']);
            unset($booking['heart_rate']);

            return response()->json(['status' => true, 'booking_data' => $booking, 'message' => 'Success']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function getCompletedAppointmentDetails($id)
    {

        try {
            if (isset($id)) {
                $booking = CompletedAppointments::select(
                    'completed_appointments.token_number',
                    'patient.firstname',
                    'patient.age',
                    'patient.gender',
                    'completed_appointments.token_start_time',
                    'completed_appointments.symptom_start_time',
                    'completed_appointments.symptom_frequency',
                    'completed_appointments.notes',
                    'completed_appointments.review_after',
                    'completed_appointments.labtest',
                    'laboratory.firstname AS lab_name',
                    'medicalshop.firstname AS medical_store_name',
                    'completed_appointments.prescription_image',
                    'patient.user_image',
                    'completed_appointments.patient_id',
                    'patient.mediezy_patient_id',
                    'completed_appointments.height',
                    'completed_appointments.weight',
                    'completed_appointments.temperature',
                    'completed_appointments.temperature_type',
                    'completed_appointments.spo2',
                    'completed_appointments.sys',
                    'completed_appointments.dia',
                    'completed_appointments.heart_rate'
                )
                    ->where('appointment_id', $id)
                    ->leftjoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
                    ->leftjoin('laboratory', 'laboratory.id', '=', 'completed_appointments.lab_id')
                    ->leftjoin('medicalshop', 'medicalshop.id', '=', 'completed_appointments.medical_shop_id')
                    ->first();


                if ($booking) {
                    $userImage = $booking->user_image ? asset("UserImages/{$booking->user_image}") : null;
                    $booking->user_image = $userImage;
                } else {
                    $userImage = null;
                }

                ////vitals
                $vitals = [];
                $allNull = true;

                if (!empty($booking['height'])) {
                    $vitals['height'] = $booking['height'];
                    $allNull = false;
                } else {
                    $vitals['height'] = null;
                }

                if (!empty($booking['weight'])) {
                    $vitals['weight'] = $booking['weight'];
                    $allNull = false;
                } else {
                    $vitals['weight'] = null;
                }

                if (!empty($booking['temperature'])) {
                    $vitals['temperature'] = $booking['temperature'];
                    $allNull = false;
                } else {
                    $vitals['temperature'] = null;
                }

                if (!empty($booking['temperature_type'])) {
                    $vitals['temperature_type'] = $booking['temperature_type'];
                    $allNull = false;
                } else {
                    $vitals['temperature_type'] = null;
                }

                if (!empty($booking['spo2'])) {
                    $vitals['spo2'] = $booking['spo2'];
                    $allNull = false;
                } else {
                    $vitals['spo2'] = null;
                }

                if (!empty($booking['sys'])) {
                    $vitals['sys'] = $booking['sys'];
                    $allNull = false;
                } else {
                    $vitals['sys'] = null;
                }

                if (!empty($booking['dia'])) {
                    $vitals['dia'] = $booking['dia'];
                    $allNull = false;
                } else {
                    $vitals['dia'] = null;
                }

                if (!empty($booking['heart_rate'])) {
                    $vitals['heart_rate'] = $booking['heart_rate'];
                    $allNull = false;
                } else {
                    $vitals['heart_rate'] = null;
                }

                if ($allNull) {
                    $vitals = null;
                }
                $booking['vitals'] = $vitals;

                //// patient data
                $patient_id = $booking->patient_id ?? null;
                $patient_allergy_data = Patient::where('id', $patient_id)->first();

                if ($patient_allergy_data) {
                    $treatment_taken = $patient_allergy_data->treatment_taken ?? null;
                    $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;
                    $booking['treatment_taken'] = $treatment_taken;
                    $booking['treatment_taken_details'] = $patient_allergy_data ? $patient_allergy_data->treatment_taken_details : null;

                    $surgery_name = $patient_allergy_data->surgery_name ?? null;
                    $surgery_name = isset($surgery_name) ? implode(', ', array_map('trim', explode(',', trim($surgery_name, '[]')))) : null;
                    $booking['surgery_name'] = $surgery_name;
                    $booking['surgery_details'] = $patient_allergy_data ? $patient_allergy_data->surgery_details : null;

                    $booking['allergy'] = $patient_allergy_data->allergy ?? null;
                    $booking['allergy_name'] = $patient_allergy_data->allergy_name ?? null;
                    $booking['illness'] = $patient_allergy_data->illness ?? null;
                } else {
                    $booking['treatment_taken'] = null;
                    $booking['surgery_name'] = null;
                    $booking['allergy'] = null;
                    $booking['allergy_name'] = null;
                    $booking['illness'] = null;
                }

                /////////////////////////////////////////////////////

                $medicine_data = Medicine::where('id', $patient_id)->distinct()->get();
                $medicine_details = [];

                foreach ($medicine_data as $medicine) {
                    $medicine_details[] = [
                        'regularMedicine' => $medicine->medicineName,
                        'illness' => $medicine->illness
                    ];
                }

                $booking['medicine_details'] = $medicine_details;
                return response()->json([
                    'status' => false, 'response' => "Data fetched successfully",
                    'data' => $booking
                ]);
            } else {
                return response()->json(['status' => false, 'response' => "token_id is required"]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    ///////////////////////////////////////////

    public function otherUserTokenBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mediezy_patient_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'response' => $validator->errors()->first()]);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////

        if (isset($request->mediezy_patient_id)) {
            $details = Patient::select('id AS patientId', 'firstname', 'dateofbirth', 'mobileNo', 'gender', 'age', 'mediezy_patient_id')
                ->where('mediezy_patient_id', $request->mediezy_patient_id)
                ->first();

            if ($details) {
                $dob = new DateTime($details->dateofbirth);
                $now = new DateTime();
                $diff = $now->diff($dob);
                $ageInMonths = $diff->y * 12 + $diff->m;
                if ($ageInMonths < 12) {
                    $details->displayAge = $ageInMonths . ' months';
                } else {
                    $details->displayAge = $diff->y . ' years';
                }

                if ($details->age == NULL) {
                    $dateOfBirth = Carbon::parse($details->dateofbirth);

                    if ($details->age === null) {
                        $age = $dateOfBirth->age;
                        $details->age = $age;
                    }
                }

                return response()->json(['status' => true, 'details' => $details, 'message' => 'Patient ID matched.Please enter mobile number to continue'], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'No patient details found.'], 404);
            }
        }
    }

    // public function getAllUserAppointments($userId, $date, $clinicId, $schedule_type)
    // {
    //     try {
    //         $doctor = Docter::where('UserId', $userId)->first();

    //         if (!$doctor) {
    //             return response()->json(['message' => 'Doctor not found.'], 404);
    //         }

    //         $doctor_id = $doctor->id;

    //         $query = NewTokens::select(
    //             'new_tokens.is_checkedout',
    //             'new_tokens.is_checkedin',
    //             'new_tokens.token_id',
    //             'new_tokens.token_booking_id',
    //             'token_booking.TokenNumber',
    //             'token_booking.doctor_id',
    //             'token_booking.BookedPerson_id',
    //             'token_booking.PatientName',
    //             'token_booking.age',
    //             'token_booking.date',
    //             'token_booking.TokenTime',
    //             'token_booking.Appoinmentfor_id',
    //             'token_booking.whenitstart',
    //             'token_booking.whenitcomes',
    //             'token_booking.attachment',
    //             'token_booking.notes',
    //             'new_tokens.clinic_id',
    //             'new_tokens.doctor_id as newDoctorId',
    //             'new_tokens.patient_id',
    //             'token_booking.height',
    //             'token_booking.weight',
    //             'token_booking.temperature',
    //             'token_booking.temperature_type',
    //             'token_booking.spo2',
    //             'token_booking.sys',
    //             'token_booking.dia',
    //             'token_booking.heart_rate',
    //             'patient.mediezy_patient_id',
    //             'patient.user_image',
    //             'token_booking.is_reached',
    //             'token_booking.schedule_type'
    //         )
    //             ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
    //             ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
    //             ->where('new_tokens.clinic_id', $clinicId)
    //             ->where('new_tokens.doctor_id', $doctor_id)
    //             ->whereDate('new_tokens.token_scheduled_date', $date)
    //             ->whereRaw('CAST(new_tokens.token_number AS SIGNED) > 0')
    //             ->where('new_tokens.token_booking_status', 1)
    //             ->where('new_tokens.is_checkedout', 0)
    //             ->orderByRaw('CAST(new_tokens.token_number AS SIGNED) ASC');

    //         if ($schedule_type != 0) {
    //             $query->where('token_booking.schedule_type', $schedule_type);
    //         }

    //         $appointments = $query->get();
    //         $appointmentsWithDetails = [];
    //         $anyCheckedIn = false;
    //         // $anyCheckedIn = $appointments->contains('is_checkedin', 1);

    //         foreach ($appointments as $appointment) {
    //             $patient = Patient::find($appointment->patient_id);



    //             $booking = [];
    //             if ($patient) {
    //                 $dob = new DateTime($patient->dateofbirth);
    //                 $now = new DateTime();
    //                 $diff = $now->diff($dob);
    //                 $ageInMonths = $diff->y * 12 + $diff->m;
    //                 $displayAge = ($ageInMonths < 12) ? "$ageInMonths months old" : "{$diff->y} years old";
    //                 $booking['patient'] = [
    //                     'dateofbirth' => $patient->dateofbirth,
    //                     'age' => $displayAge,
    //                 ];
    //             } else {
    //                 $booking['patient'] = null;
    //             }

    //             $appointmentForIds = json_decode($appointment->Appoinmentfor_id, true);
    //             $Main_Symptoms = $this->getMainSymptoms(MainSymptom::class, $appointmentForIds['Appoinmentfor1'] ?? []);
    //             $Other_Symptoms = $this->getSymptoms(Symtoms::class, $appointmentForIds['Appoinmentfor2'] ?? []);

    //             $appointment->Main_Symptoms = $Main_Symptoms ?: [['id' => null, 'Mainsymptoms' => null]];
    //             $appointment->Other_Symptoms = $Other_Symptoms ?: [['id' => null, 'name' => null]];

    //             // $medicine = Medicine::where('docter_id', $appointment->doctor_id)

    //             //     ->where('user_id', $appointment->BookedPerson_id)
    //             //     ->get();
    //             $medicine = Medicine::leftJoin('new_tokens', 'new_tokens.token_id', '=', 'medicalprescription.token_id')
    //             ->where('medicalprescription.docter_id', $appointment->doctor_id)
    //             ->where('medicalprescription.user_id', $appointment->BookedPerson_id)
    //             ->where('medicalprescription.token_id', $appointment->token_id)
    //              ->get();


    //             $vitals = $this->getVitals($appointment);

    //             $patient_allergy_data = Patient::find($appointment->patient_id);
    //             $appointment['treatment_taken'] = $patient_allergy_data->treatment_taken_details ?? null;
    //             $appointment['surgery_name'] = $this->parseArrayField($patient_allergy_data->surgery_name);
    //             $appointment['treatment_taken'] = $this->parseArrayField($patient_allergy_data->treatment_taken);
    //             $appointment['surgery_details'] = $patient_allergy_data->surgery_details ?? null;
    //             $appointment['treatment_taken_details'] = $patient_allergy_data->treatment_taken_details ?? null;
    //             $appointment['allergies_details'] = $this->getAllergiesDetails($appointment->patient_id);

    //             $userImage = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
    //             $medicine_details = $this->getMedicineDetails($appointment->patient_id);
    //             if ($appointment->BookedPerson_id != $appointment->doctor_id) {
    //                 $onlineStatus = "online";
    //             } else {
    //                 $onlineStatus = "offline";
    //             }
    //             $patientDetails = [

    //                 'is_checkedout' => $appointment->is_checkedout,
    //                 'is_checkedin' => $appointment->is_checkedin,
    //                 'token_id' => $appointment->token_id,
    //                 'token_booking_id' => $appointment->token_booking_id,
    //                 'TokenNumber' => $appointment->TokenNumber,
    //                 'doctor_id' => $appointment->doctor_id,
    //                 'BookedPerson_id' => $appointment->BookedPerson_id,
    //                 'PatientName' => $appointment->PatientName,
    //                 'age' => $appointment->age,
    //                 'date' => $appointment->date,
    //                 'TokenTime' => $appointment->TokenTime,
    //                 'Appoinmentfor_id' => $appointment->Appoinmentfor_id,
    //                 'whenitstart' => $appointment->whenitstart,
    //                 'whenitcomes' => $appointment->whenitcomes,
    //                 'attachment' => $appointment->attachment,
    //                 'notes' => $appointment->notes,
    //                 'clinic_id' => $appointment->clinic_id,
    //                 'newDoctorId' => $appointment->newDoctorId,
    //                 'patient_id' => $appointment->patient_id,
    //                 'patient' => [
    //                     'dateofbirth' => $patient->dateofbirth,
    //                     'age' => $displayAge,
    //                 ],
    //                 'Main_Symptoms' => $Main_Symptoms,
    //                 'Other_Symptoms' => $Other_Symptoms,
    //                 'medicine' => $medicine,
    //                 'vitals' => $vitals,
    //                 'online_status' => $onlineStatus,
    //                 'treatment_taken' => $appointment['treatment_taken'],
    //                 'surgery_name' => $appointment['surgery_name'],
    //                 'surgery_details' => $appointment['surgery_details'],
    //                 'treatment_taken_details' => $appointment['treatment_taken_details'],
    //                 'allergies_details' => $appointment['allergies_details'],
    //                 'doctor_app_booking' => ($appointment->BookedPerson_id == $appointment->doctor_id),
    //                 'medicine_details' => $medicine_details,
    //                 'mediezy_patient_id' => $patient->mediezy_patient_id,
    //                 'user_image' => $userImage
    //             ];

    //             //     $appointmentsWithDetails[] = $patientDetails;
    //             // }
    //             $checkin_check = NewTokens::where('new_tokens.clinic_id', $clinicId)
    //                 ->where('new_tokens.doctor_id', $doctor_id)
    //                 ->whereDate('new_tokens.token_scheduled_date', $date)
    //                 ->get();

    //             foreach ($checkin_check as $checkin) {
    //                 if ($checkin->is_checkedin == 1) {
    //                     $anyCheckedIn = true;
    //                     break;
    //                 }
    //             }

    //             $appointmentsWithDetails[] = $patientDetails;
    //         }
    //         foreach ($appointmentsWithDetails as $index => &$details) {
    //             $details['first_index_status'] = ($index == 0 && !$anyCheckedIn) ? 1 : 0;
    //         }
    //         // foreach ($appointmentsWithDetails as $index => &$details) {
    //         //     $details['first_index_status'] = ($index == 0 && !$anyCheckedIn) ? 1 : 0;
    //         // }

    //         return response()->json(['status' => true, 'booking_data' => $appointmentsWithDetails, 'message' => 'Success']);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }
    ///live code
    public function getAllUserAppointments($userId, $date, $clinicId, $schedule_type)
    {
        try {
            $doctor = Docter::where('UserId', $userId)->first();

            if (!$doctor) {
                return response()->json(['message' => 'Doctor not found.'], 404);
            }

            $doctor_id = $doctor->id;

            $query = NewTokens::select(
                'new_tokens.is_checkedout',
                'new_tokens.is_checkedin',
                'new_tokens.token_id',
                'new_tokens.token_booking_id',
                'token_booking.TokenNumber',
                'token_booking.doctor_id',
                'token_booking.BookedPerson_id',
                'token_booking.PatientName',
                'token_booking.age',
                'token_booking.date',
                'token_booking.TokenTime',
                'token_booking.Appoinmentfor_id',
                'token_booking.whenitstart',
                'token_booking.whenitcomes',
                'token_booking.attachment',
                'token_booking.notes',
                'new_tokens.clinic_id',
                'new_tokens.doctor_id as newDoctorId',
                'new_tokens.patient_id',
                'token_booking.height',
                'token_booking.weight',
                'token_booking.temperature',
                'token_booking.temperature_type',
                'token_booking.spo2',
                'token_booking.sys',
                'token_booking.dia',
                'token_booking.heart_rate',
                'patient.mediezy_patient_id',
                'patient.user_image',
                'token_booking.is_reached',
                'token_booking.schedule_type'
            )
                ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
                ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
                ->where('new_tokens.clinic_id', $clinicId)
                ->where('new_tokens.doctor_id', $doctor_id)
                ->whereDate('new_tokens.token_scheduled_date', $date)
                ->whereRaw('CAST(new_tokens.token_number AS SIGNED) > 0')
                ->where('new_tokens.token_booking_status', 1)
                ->where('new_tokens.is_checkedout', 0)
                ->orderByRaw('CAST(new_tokens.token_number AS SIGNED) ASC');

            if ($schedule_type != 0) {
                $query->where('token_booking.schedule_type', $schedule_type);
            }

            $appointments = $query->get();
            $appointmentsWithDetails = [];
            $anyCheckedIn = false;
            // $anyCheckedIn = $appointments->contains('is_checkedin', 1);

            foreach ($appointments as $appointment) {
                $patient = Patient::find($appointment->patient_id);



                $booking = [];
                if ($patient) {
                    $dob = new DateTime($patient->dateofbirth);
                    $now = new DateTime();
                    $diff = $now->diff($dob);
                    $ageInMonths = $diff->y * 12 + $diff->m;
                    $displayAge = ($ageInMonths < 12) ? "$ageInMonths months old" : "{$diff->y} years old";
                    $booking['patient'] = [
                        'dateofbirth' => $patient->dateofbirth,
                        'age' => $displayAge,
                    ];
                } else {
                    $booking['patient'] = null;
                }

                $appointmentForIds = json_decode($appointment->Appoinmentfor_id, true);
                $Main_Symptoms = $this->getMainSymptoms(MainSymptom::class, $appointmentForIds['Appoinmentfor1'] ?? []);
                $Other_Symptoms = $this->getSymptoms(Symtoms::class, $appointmentForIds['Appoinmentfor2'] ?? []);

                $appointment->Main_Symptoms = $Main_Symptoms ?: [['id' => null, 'Mainsymptoms' => null]];
                $appointment->Other_Symptoms = $Other_Symptoms ?: [['id' => null, 'name' => null]];

                // $medicine = Medicine::where('docter_id', $appointment->doctor_id)

                //     ->where('user_id', $appointment->BookedPerson_id)
                //     ->get();
                $medicine = Medicine::leftJoin('new_tokens', 'new_tokens.token_id', '=', 'medicalprescription.token_id')
                    ->where('medicalprescription.docter_id', $appointment->doctor_id)
                    ->where('medicalprescription.user_id', $appointment->BookedPerson_id)
                    ->where('medicalprescription.token_id', $appointment->token_id)
                    ->get();


                $vitals = $this->getVitals($appointment);

                $patient_allergy_data = Patient::find($appointment->patient_id);
                $appointment['treatment_taken'] = $patient_allergy_data->treatment_taken_details ?? null;
                $appointment['surgery_name'] = $this->parseArrayField($patient_allergy_data->surgery_name);
                $appointment['treatment_taken'] = $this->parseArrayField($patient_allergy_data->treatment_taken);
                $appointment['surgery_details'] = $patient_allergy_data->surgery_details ?? null;
                $appointment['treatment_taken_details'] = $patient_allergy_data->treatment_taken_details ?? null;
                $appointment['allergies_details'] = $this->getAllergiesDetails($appointment->patient_id);

                $userImage = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
                $medicine_details = $this->getMedicineDetails($appointment->patient_id);
                if ($appointment->BookedPerson_id != $appointment->doctor_id) {
                    $onlineStatus = "online";
                } else {
                    $onlineStatus = "offline";
                }
                $patientDetails = [

                    'is_checkedout' => $appointment->is_checkedout,
                    'is_checkedin' => $appointment->is_checkedin,
                    'token_id' => $appointment->token_id,
                    'token_booking_id' => $appointment->token_booking_id,
                    'TokenNumber' => $appointment->TokenNumber,
                    'doctor_id' => $appointment->doctor_id,
                    'BookedPerson_id' => $appointment->BookedPerson_id,
                    'PatientName' => $appointment->PatientName,
                    'age' => $appointment->age,
                    'date' => $appointment->date,
                    'TokenTime' => $appointment->TokenTime,
                    'Appoinmentfor_id' => $appointment->Appoinmentfor_id,
                    'whenitstart' => $appointment->whenitstart,
                    'whenitcomes' => $appointment->whenitcomes,
                    'attachment' => $appointment->attachment,
                    'notes' => $appointment->notes,
                    'clinic_id' => $appointment->clinic_id,
                    'newDoctorId' => $appointment->newDoctorId,
                    'patient_id' => $appointment->patient_id,
                    'patient' => [
                        'dateofbirth' => $patient->dateofbirth,
                        'age' => $displayAge,
                    ],
                    'Main_Symptoms' => $Main_Symptoms,
                    'Other_Symptoms' => $Other_Symptoms,
                    'medicine' => $medicine,
                    'vitals' => $vitals,
                    'online_status' => $onlineStatus,
                    'treatment_taken' => $appointment['treatment_taken'],
                    'surgery_name' => $appointment['surgery_name'],
                    'surgery_details' => $appointment['surgery_details'],
                    'treatment_taken_details' => $appointment['treatment_taken_details'],
                    'allergies_details' => $appointment['allergies_details'],
                    'doctor_app_booking' => ($appointment->BookedPerson_id == $appointment->doctor_id),
                    'medicine_details' => $medicine_details,
                    'mediezy_patient_id' => $patient->mediezy_patient_id,
                    'user_image' => $userImage
                ];

                ///old
                $checkin_check = NewTokens::where('new_tokens.clinic_id', $clinicId)
                    ->where('new_tokens.doctor_id', $doctor_id)
                    ->whereDate('new_tokens.token_scheduled_date', $date)
                    ->get();

                foreach ($checkin_check as $checkin) {
                    if ($checkin->is_checkedin == 1) {
                        $anyCheckedIn = true;
                        break;
                    }
                }

                $appointmentsWithDetails[] = $patientDetails;
            }
            foreach ($appointmentsWithDetails as $index => &$details) {
                $details['first_index_status'] = ($index == 0 && !$anyCheckedIn) ? 1 : 0;
            }
            //////

            return response()->json(['status' => true, 'booking_data' => $appointmentsWithDetails, 'message' => 'Success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    ///check code
    // public function getAllUserAppointments($userId, $date, $clinicId, $schedule_type)
    // {
    //     try {
    //         $doctor = Docter::where('UserId', $userId)->first();

    //         if (!$doctor) {
    //             return response()->json(['message' => 'Doctor not found.'], 404);
    //         }

    //         $doctor_id = $doctor->id;

    //         $query = NewTokens::select(
    //             'new_tokens.is_checkedout',
    //             'new_tokens.is_checkedin',
    //             'new_tokens.token_id',
    //             'new_tokens.token_booking_id',
    //             'token_booking.TokenNumber',
    //             'token_booking.doctor_id',
    //             'token_booking.BookedPerson_id',
    //             'token_booking.PatientName',
    //             'token_booking.age',
    //             'token_booking.date',
    //             'token_booking.TokenTime',
    //             'token_booking.Appoinmentfor_id',
    //             'token_booking.whenitstart',
    //             'token_booking.whenitcomes',
    //             'token_booking.attachment',
    //             'token_booking.notes',
    //             'new_tokens.clinic_id',
    //             'new_tokens.doctor_id as newDoctorId',
    //             'new_tokens.patient_id',
    //             'token_booking.height',
    //             'token_booking.weight',
    //             'token_booking.temperature',
    //             'token_booking.temperature_type',
    //             'token_booking.spo2',
    //             'token_booking.sys',
    //             'token_booking.dia',
    //             'token_booking.heart_rate',
    //             'patient.mediezy_patient_id',
    //             'patient.user_image',
    //             'token_booking.is_reached',
    //             'token_booking.schedule_type'
    //         )
    //             ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
    //             ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
    //             ->where('new_tokens.clinic_id', $clinicId)
    //             ->where('new_tokens.doctor_id', $doctor_id)
    //             ->whereDate('new_tokens.token_scheduled_date', $date)
    //             ->whereRaw('CAST(new_tokens.token_number AS SIGNED) > 0')
    //             ->where('new_tokens.token_booking_status', 1)
    //             ->where('new_tokens.is_checkedout', 0)
    //             ->orderByRaw('CAST(new_tokens.token_number AS SIGNED) ASC');

    //         if ($schedule_type != 0) {
    //             $query->where('token_booking.schedule_type', $schedule_type);
    //         }

    //         $appointments = $query->get();
    //     $appointmentsWithDetails = [];
    //     $anyCheckedIn = false;

    //         // $anyCheckedIn = $appointments->contains('is_checkedin', 1);

    //         foreach ($appointments as $appointment) {
    //             $patient = Patient::find($appointment->patient_id);



    //             $booking = [];
    //             if ($patient) {
    //                 $dob = new DateTime($patient->dateofbirth);
    //                 $now = new DateTime();
    //                 $diff = $now->diff($dob);
    //                 $ageInMonths = $diff->y * 12 + $diff->m;
    //                 $displayAge = ($ageInMonths < 12) ? "$ageInMonths months old" : "{$diff->y} years old";
    //                 $booking['patient'] = [
    //                     'dateofbirth' => $patient->dateofbirth,
    //                     'age' => $displayAge,
    //                 ];
    //             } else {
    //                 $booking['patient'] = null;
    //             }

    //             $appointmentForIds = json_decode($appointment->Appoinmentfor_id, true);
    //             $Main_Symptoms = $this->getMainSymptoms(MainSymptom::class, $appointmentForIds['Appoinmentfor1'] ?? []);
    //             $Other_Symptoms = $this->getSymptoms(Symtoms::class, $appointmentForIds['Appoinmentfor2'] ?? []);

    //             $appointment->Main_Symptoms = $Main_Symptoms ?: [['id' => null, 'Mainsymptoms' => null]];
    //             $appointment->Other_Symptoms = $Other_Symptoms ?: [['id' => null, 'name' => null]];

    //             // $medicine = Medicine::where('docter_id', $appointment->doctor_id)

    //             //     ->where('user_id', $appointment->BookedPerson_id)
    //             //     ->get();
    //             $medicine = Medicine::leftJoin('new_tokens', 'new_tokens.token_id', '=', 'medicalprescription.token_id')
    //                 ->where('medicalprescription.docter_id', $appointment->doctor_id)
    //                 ->where('medicalprescription.user_id', $appointment->BookedPerson_id)
    //                 ->where('medicalprescription.token_id', $appointment->token_id)
    //                 ->get();


    //             $vitals = $this->getVitals($appointment);

    //             $patient_allergy_data = Patient::find($appointment->patient_id);
    //             $appointment['treatment_taken'] = $patient_allergy_data->treatment_taken_details ?? null;
    //             $appointment['surgery_name'] = $this->parseArrayField($patient_allergy_data->surgery_name);
    //             $appointment['treatment_taken'] = $this->parseArrayField($patient_allergy_data->treatment_taken);
    //             $appointment['surgery_details'] = $patient_allergy_data->surgery_details ?? null;
    //             $appointment['treatment_taken_details'] = $patient_allergy_data->treatment_taken_details ?? null;
    //             $appointment['allergies_details'] = $this->getAllergiesDetails($appointment->patient_id);

    //             $userImage = $patient->user_image ? asset("UserImages/{$patient->user_image}") : null;
    //             $medicine_details = $this->getMedicineDetails($appointment->patient_id);
    //             if ($appointment->BookedPerson_id != $appointment->doctor_id) {
    //                 $onlineStatus = "online";
    //             } else {
    //                 $onlineStatus = "offline";
    //             }
    //             $patientDetails = [

    //                 'is_checkedout' => $appointment->is_checkedout,
    //                 'is_checkedin' => $appointment->is_checkedin,
    //                 'schedule_type' => $appointment->schedule_type,
    //                 'token_id' => $appointment->token_id,
    //                 'token_booking_id' => $appointment->token_booking_id,
    //                 'TokenNumber' => $appointment->TokenNumber,
    //                 'doctor_id' => $appointment->doctor_id,
    //                 'BookedPerson_id' => $appointment->BookedPerson_id,
    //                 'PatientName' => $appointment->PatientName,
    //                 'age' => $appointment->age,
    //                 'date' => $appointment->date,
    //                 'TokenTime' => $appointment->TokenTime,
    //                 'Appoinmentfor_id' => $appointment->Appoinmentfor_id,
    //                 'whenitstart' => $appointment->whenitstart,
    //                 'whenitcomes' => $appointment->whenitcomes,
    //                 'attachment' => $appointment->attachment,
    //                 'notes' => $appointment->notes,
    //                 'clinic_id' => $appointment->clinic_id,
    //                 'newDoctorId' => $appointment->newDoctorId,
    //                 'patient_id' => $appointment->patient_id,
    //                 'patient' => [
    //                     'dateofbirth' => $patient->dateofbirth,
    //                     'age' => $displayAge,
    //                 ],
    //                 'Main_Symptoms' => $Main_Symptoms,
    //                 'Other_Symptoms' => $Other_Symptoms,
    //                 'medicine' => $medicine,
    //                 'vitals' => $vitals,
    //                 'online_status' => $onlineStatus,
    //                 'treatment_taken' => $appointment['treatment_taken'],
    //                 'surgery_name' => $appointment['surgery_name'],
    //                 'surgery_details' => $appointment['surgery_details'],
    //                 'treatment_taken_details' => $appointment['treatment_taken_details'],
    //                 'allergies_details' => $appointment['allergies_details'],
    //                 'doctor_app_booking' => ($appointment->BookedPerson_id == $appointment->doctor_id),
    //                 'medicine_details' => $medicine_details,
    //                 'mediezy_patient_id' => $patient->mediezy_patient_id,
    //                 'user_image' => $userImage
    //             ];

    //             ///old
    //             $checkin_check = NewTokens::where('new_tokens.clinic_id', $clinicId)
    //                 ->where('new_tokens.doctor_id', $doctor_id)
    //                 ->whereDate('new_tokens.token_scheduled_date', $date)
    //                 ->get();

    //             foreach ($checkin_check as $checkin) {
    //                 if ($checkin->is_checkedin == 1) {
    //                     $anyCheckedIn = true;
    //                     break;
    //                 }
    //             }

    //             $appointmentsWithDetails[] = $patientDetails;
    //         }
    //         foreach ($appointmentsWithDetails as $index => &$details) {
    //             $details['first_index_status'] = ($index == 0 && !$anyCheckedIn) ? 1 : 0;
    //         }
    //         //////

    //         return response()->json(['status' => true, 'booking_data' => $appointmentsWithDetails, 'message' => 'Success']);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => $e->getMessage()], 500);
    //     }
    // }

    private function getMainSymptoms($model, $ids)
    {
        return $model::whereIn('id', $ids)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->Mainsymptoms,
            ];
        })->toArray();
    }
    private function getSymptoms($model, $ids)
    {
        return $model::whereIn('id', $ids)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->symtoms,
            ];
        })->toArray();
    }

    private function getVitals($appointment)
    {
        $vitals = [
            'height' => $appointment->height,
            'weight' => $appointment->weight,
            'temperature' => $appointment->temperature,
            'temperature_type' => $appointment->temperature_type,
            'spo2' => $appointment->spo2,
            'sys' => $appointment->sys,
            'dia' => $appointment->dia,
            'heart_rate' => $appointment->heart_rate,
        ];

        return array_filter($vitals, function ($value) {
            return !is_null($value);
        }) ?: null;
    }

    function parseArrayField($field)
    {
        if (!$field) {
            return null;
        }
        $field = substr($field, 1, -1);
        return explode(',', $field);
    }

    private function getAllergiesDetails($patient_id)
    {
        $allergies = PatientAllergies::where('patient_id', $patient_id)->get();

        return $allergies->map(function ($allergy) {
            $allergy_details = Allergy::find($allergy->allergy_id);
            return $allergy_details ? [
                'allergy_id' => $allergy_details->id,
                'allergy_name' => $allergy_details->allergy,
                'allergy_details' => $allergy->allergy_details,
            ] : null;
        })->filter()->toArray();
    }

    private function getMedicineDetails($patient_id)
    {
        return Medicine::where('patient_id', $patient_id)
            //->where('medicine_type', 2)
            ->where('docter_id', 0)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($medicine) {
                return [
                    'medicine_name' => $medicine->medicineName,
                    'illness' => $medicine->illness,
                ];
            })->toArray();
    }
}
