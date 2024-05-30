<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use App\Models\Allergy;
use App\Models\CompletedAppointments;
use App\Models\DischargeSummary;
use App\Models\Docter;
use Illuminate\Http\Request;
use App\Models\TokenBooking;
use App\Models\Laboratory;
use App\Models\Medicalshop;
use App\Models\Symtoms;
use App\Models\Medicine;
use App\Models\LabReport;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Models\NewTokens;
use App\Models\MainSymptom;
use App\Models\PatientAllergies;
use App\Models\PatientPrescriptions;
use App\Models\ScanReport;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;


class DoctorDashboardController extends BaseController
{
    // Get Prevoius Appointments
    // public function getPreviousAppointments(Request $request)
    // {
    //     $request->validate([
    //         'doctor_id' => 'required|integer',
    //         'clinic_id' => 'required|integer',
    //         'date' => 'required|date',
    //     ]);

    //     $doctorId = $request->input('doctor_id');
    //     $clinicId = $request->input('clinic_id');
    //     $date = $request->input('date');

    //     $currentDate = Carbon::now()->toDateString();

    //     if ($date >= $currentDate) {
    //         return response()->json(['success' => true, 'Previous Appointments' => [], 'code' => 1, 'message' => 'Please select the Day before the current Date'], 200);
    //     }
    //     $previousAppointments = TokenBooking::leftJoin('laboratory', 'token_booking.lab_id', '=', 'laboratory.id')
    //         ->leftJoin('medicalshop', 'token_booking.medicalshop_id', '=', 'medicalshop.id')
    //         ->leftJoin('patient', 'token_booking.BookedPerson_id', '=', 'patient.UserId')
    //         ->with([
    //             'patientDetails' => function ($query) {
    //                 $query->where('user_type', 1)->select('id', 'UserId');
    //             },
    //             'medicines'
    //         ])
    //         ->where('token_booking.clinic_id', $clinicId)
    //         ->whereDate('token_booking.date', $date)
    //         ->orderBy('token_booking.date', 'desc')
    //         ->get([
    //             'token_booking.*',
    //             'laboratory.firstname as laboratory_name',
    //             'medicalshop.firstname as medicalshop_name',
    //             'patient.user_image'
    //         ]);


    //     if ($previousAppointments->isEmpty()) {
    //         return response()->json(['success' => true, 'code' => 1, 'message' => 'No details available for the specified date', 'Previous Appointments' => []], 200);
    //     }
    //     foreach ($previousAppointments as $appointment) {
    //         if ($appointment->prescription_image == null) {
    //             $appointment->prescription_image = null;
    //         } else {
    //             $appointment->prescription_image = asset('LabImages/prescription/' . $appointment->prescription_image);
    //         }
    //         if ($appointment->user_image == null) {
    //             $appointment->user_image = null;
    //         } else {
    //             $appointment->user_image = asset('UserImages/' . $appointment->user_image);
    //         }

    //         $appoinmentforId = json_decode($appointment->Appoinmentfor_id, true);
    //         foreach ($appoinmentforId as $key => $symptomsIds) {
    //             $symptomsDetails = Symtoms::whereIn('id', $symptomsIds)->get();
    //             $formattedSymptoms = $symptomsDetails->map(function ($symptom) {
    //                 return [
    //                     'symtoms' => $symptom->symtoms,
    //                 ];
    //             });
    //             $appointment->$key = $formattedSymptoms;
    //             unset($appointment['Is_checkIn']);
    //             unset($appointment['Is_completed']);
    //             unset($appointment['Is_canceled']);
    //             unset($appointment['created_at']);
    //             unset($appointment['updated_at']);
    //             unset($appointment['Appoinmentfor_id']);
    //         }
    //     }

    //     return response()->json(['success' => true, 'Previous Appointments' => $previousAppointments, 'code' => 1, 'message' => 'Appointments retrieved successfully'], 200);
    // }

    ///latest code
    public function getPreviousAppointments(Request $request)
    {
        $rules = [
            'doctor_id'  => 'required',
            'date'     => 'required|date',
            'clinicId' => 'required',
        ];

        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json(['success' => false, 'message' => $validation->errors()->first()], 400);
        }

        $userId = $request->doctor_id;
        $date = $request->date;
        $clinicId = $request->clinicId;
        try {
            $doctor = Docter::where('UserId', $userId)->first();

            if (!$doctor) {
                return response()->json(['message' => 'Doctor not found.'], 404);
            }
            $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

            if ($date === now()->toDateString()) {
                return response()->json([ "success" => true,
                "Previous Appointments"=> [],
                "code"=> "1","message"=> "Previous Appointments retrieved successfully"], 200);
            }
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
                'completed_appointments.date',
                'completed_appointments.appointment_for',
                'completed_appointments.appointment_id',
                'completed_appointments.patient_id',
                'completed_appointments.clinic_id',
                'completed_appointments.new_token_id',
                DB::raw('GROUP_CONCAT(DISTINCT token_booking.BookedPerson_id) as BookedPerson_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT token_booking.doctor_id) as doctor_ids')
            )
            ->leftJoin('patient', 'patient.id', '=', 'completed_appointments.patient_id')
            ->leftJoin('token_booking', 'token_booking.patient_id', '=', 'patient.id')
            ->where('completed_appointments.clinic_id', $clinicId)
            ->where('completed_appointments.doctor_id', $doctor_id)
            ->whereDate('completed_appointments.date', $date)
            ->groupBy(
                'completed_appointments.appointment_id',
                'completed_appointments.patient_id',
                'patient.mediezy_patient_id',
                'patient.firstname',
                'completed_appointments.token_number',
                'completed_appointments.booked_user_id',
                'patient.user_image',
                'patient.age',
                'completed_appointments.schedule_type',
                'completed_appointments.token_start_time',
                'completed_appointments.booked_user_id',
                'completed_appointments.date',
                'completed_appointments.appointment_for',
                'completed_appointments.clinic_id',
                'completed_appointments.new_token_id'
            )
            ->orderBy('completed_appointments.checkout_time', 'desc')
            ->get();

            $appointmentsWithDetails = [];
            foreach ($appointments as $appointment) {
                $userImage = $appointment->user_image ? asset("UserImages/{$appointment->user_image}") : null;
                $patient_user_id = Patient::where('id', $appointment->patient_id)->pluck('UserId')->first();
                ///main symptoms
                $main_symptom_id = CompletedAppointments::select('appointment_for')
                ->where('booked_user_id', $patient_user_id)
                ->where('token_number',  $appointment->token_number)
                ->where('clinic_id', $appointment->clinic_id)
                ->orderBy('created_at', 'DESC')->first();
            if ((isset($main_symptom_id))) {
                $main_symptom_json = json_decode($main_symptom_id->appointment_for, true);
            }
            $main_symptom_array_value = [];
            if (isset($main_symptom_json['Appoinmentfor1'])) {
                $main_symptom_array_value = $main_symptom_json['Appoinmentfor1'];
            }
            $main_symptom = MainSymptom::select('mainsymptoms')
                ->whereIn('id', $main_symptom_array_value)
                ->get()->toArray();
                ///other symptoms
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

                $patientDetails = [
                    'appointment_id' => $appointment->appointment_id,
                    'date' => Carbon::parse($appointment->date)->format('d-m-Y'),
                    'mediezy_patient_id' => $appointment->mediezy_patient_id,
                    'PatientName' => $appointment->patient_name,
                    'TokenNumber' => $appointment->token_number,
                    'patient_id' => $appointment->patient_id,
                    'user_image' => $userImage,
                    'Age' => $appointment->age,
                    'schedule_type' => $appointment->schedule_type,
                    'Startingtime' => Carbon::parse($appointment->token_start_time)->format('h:i A'),
                    'main_symptoms' => $main_symptom,
                    'other_symptoms' => $other_symptom,
                ];
                $patientDetails = array_merge($patientDetails);
                $appointmentsWithDetails[] = $patientDetails;
            }
            return $this->sendResponse('Previous Appointments', $appointmentsWithDetails, '1', 'Previous Appointments retrieved successfully.');
        }
        catch (\Exception $e) {
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }

///previous appointment details screen
// public function previousPatientAppoitmentsDetails(Request $request)
// {
//     $rules = [
//         'appointment_id' => 'required',
//         'booked_user_id' => 'required',
//     ];

//     $messages = [
//         'appointment_id.required' => 'Appointment ID is required.',
//     ];

//     $validator = Validator::make($request->all(), $rules, $messages);

//     if ($validator->fails()) {
//         return response()->json(['status' => false, 'response' => $validator->errors()->first()]);
//     }

//     try {
//         $appointment_id = $request->appointment_id;
//         $completed_appointments = CompletedAppointments::where('appointment_id', $appointment_id);


//         if (!$completed_appointments->exists()) {
//             return response()->json(['status' => false, 'message' => 'Appointment not found.'], 200);
//         }

//         $appoitment_data = $completed_appointments->first();
//         $vitals_array = array();
//         $vitals_array['height'] = $appoitment_data->height;
//         $vitals_array['weight'] = $appoitment_data->weight;
//         $vitals_array['temperature'] = $appoitment_data->temperature;
//         $vitals_array['spo2'] = $appoitment_data->spo2;
//         $vitals_array['sys'] = $appoitment_data->sys;
//         $vitals_array['dia'] = $appoitment_data->dia;
//         $vitals_array['heart_rate'] = $appoitment_data->heart_rate;
//         $vitals_array['temperature_type'] = $appoitment_data->temperature_type;
//         if (
//             $vitals_array['height'] === null &&
//             $vitals_array['weight'] === null &&
//             $vitals_array['temperature'] === null &&
//             $vitals_array['spo2'] === null &&
//             $vitals_array['sys'] === null &&
//             $vitals_array['dia'] === null &&
//             $vitals_array['heart_rate'] === null &&
//             $vitals_array['temperature_type'] === null
//         )
//         {
//             $vitals_array = [];
//         }
//         if (empty($vitals_array)) {
//             $vitals_array = null;
//         }
//         //allergy
//         $allergy_details = PatientAllergies::where('patient_id', $appoitment_data->patient_id)->get();
//         $allergy_array = [];
//         foreach ($allergy_details as $allergy_detail) {
//             $allergy = Allergy::where('id', $allergy_detail->allergy_id)->pluck('allergy')->first();
//             $allergy_array[] = [
//                 'allergy_name' => $allergy,
//                 'allergy_details' => $allergy_detail->allergy_details
//             ];
//         }
//         //medicine
//         $patient_user_id =  Patient::where('id', $appoitment_data->patient_id)->value('UserId');
//         $patient_data = Patient::where('id', $appoitment_data->patient_id)->first();
//         $token_number = $appoitment_data->token_number;
//         $patient_medicines = Medicine::select('medicineName', 'illness')
//            // ->where('docter_id', 0)
//            ->where('medicine_type', NULL)
//             ->where('patient_id', $appoitment_data->patient_id)
//             ->orderBy('created_at', 'DESC')
//             ->get();
//         $patient_medicines_array = [];
//         foreach ($patient_medicines as $patient_medicine) {
//             $patient_medicines_array[] = [
//                 'medicine_name' => $patient_medicine->medicineName,
//                 'illness'   => $patient_medicine->illness
//             ];
//         }
//         $medicine_details = Medicine::where('patient_id', $appoitment_data->patient_id)
//            // ->where('docter_id', $request->user_id)
//            ->where('patient_id', $appoitment_data->patient_id)
//             ->where('medicine_type', 2)
//             ->where('token_number', $token_number)
//             ->get();
//         $doctor_medicine_array = [];
//         foreach ($medicine_details as $medicine_detail) {
//             $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
//             $medical_shop_name = $medicalshops ?  $medicalshops->firstname : NULL;
//             $doctor_medicine_array[] = [
//                 'medicine_name' => $medicine_detail->medicineName,
//                 'Dosage' => $medicine_detail->Dosage,
//                 'NoOfDays' => $medicine_detail->NoOfDays,
//                 'Noon' => $medicine_detail->Noon,
//                 'night' => $medicine_detail->night,
//                 'evening' => $medicine_detail->evening,
//                 'morning' => $medicine_detail->morning,
//                 'type' => $medicine_detail->type,
//                 'notes' => $medicine_detail->notes,
//                 'illness'   => $medicine_detail->illness,
//                 'medical_store_name' => $medical_shop_name,
//                 'interval' => $medicine_detail->interval,
//                 'time_section' => $medicine_detail->time_section,
//             ];
//         }
//         $main_symptoms = MainSymptom::select('Mainsymptoms')
//             ->where('user_id', $patient_user_id)
//             ->where('TokenNumber', $appoitment_data->token_number)
//             ->where('clinic_id',  $appoitment_data->clinic_id)
//             ->first();
//         $other_symptom_id = CompletedAppointments::select('appointment_for')
//             ->where('booked_user_id', $patient_user_id)
//             ->where('token_number',  $appoitment_data->token_number)
//             ->where('clinic_id', $appoitment_data->clinic_id)
//             ->orderBy('created_at', 'DESC')->first();
//         $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);
//         $other_symptom_array_value = [];

//         if (isset($other_symptom_json['Appoinmentfor2'])) {
//             $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
//         }
//         $other_symptom = Symtoms::select('symtoms')
//             ->whereIn('id', $other_symptom_array_value)
//             ->get()->toArray();
//         $age = $patient_data->age;
//         if (!$age) {
//             $dob = $patient_data->dateofbirth;
//             $dobCarbon = Carbon::parse($dob);
//             $age = $dobCarbon->age;
//         }
//         if ($patient_data) {
//             if ($patient_data->treatment_taken_details !== null) {
//                 $booking_treatment_taken = $patient_data->treatment_taken_details;
//             } else {
//                 $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;

//                 $booking_treatment_taken = $treatment_taken;
//             }
//             $patient_allergy_data = Patient::where('id', $appoitment_data->patient_id)
//                 ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
//                 ->first();
//             $patient_allergy_data = Patient::where('id', $appoitment_data->patient_id)
//                 ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
//                 ->first();
//             $patient_allergy_id = $patient_allergy_data ? $patient_allergy_data->allergy_id : null;
//             $allergy_data = $patient_allergy_id ? Allergy::where('id', $patient_allergy_id)->first() : null;
//             $booking_surgery_name = $patient_allergy_data ?
//                 ($patient_allergy_data->surgery_name === 'Other' ? $patient_allergy_data->surgery_details : $patient_allergy_data->surgery_name) : null;
//             if (!empty($booking_surgery_name)) {
//                 $booking_surgery_name = explode(',', $booking_surgery_name);
//                 $booking_surgery_name = array_map(function ($surgery) {
//                     return trim($surgery, " \t\n\r\0\x0B[]");
//                 }, $booking_surgery_name);
//             }
//             $booking_treatment_taken = $patient_allergy_data ?
//                 ($patient_allergy_data->treatment_taken === 'Other' ? $patient_allergy_data->treatment_details : $patient_allergy_data->treatment_taken) : null;
//             if (!empty($booking_treatment_taken)) {
//                 $booking_treatment_taken = explode(',', $booking_treatment_taken);
//                 $booking_treatment_taken = array_map(function ($treatmenttaken) {
//                     return trim($treatmenttaken, " \t\n\r\0\x0B[]");
//                 }, $booking_treatment_taken);
//             }
//             $booking['surgery_details'] = $patient_allergy_data ? $patient_allergy_data->surgery_details : null;
//             $booking['treatment_taken_details'] = $patient_allergy_data ? $patient_allergy_data->treatment_taken_details : null;
//         } else {
//             $booking_treatment_taken = null;
//             $booking_surgery_name = null;
//         }
//         if ($appoitment_data->lab_id) {
//             $lab_scan_id = $appoitment_data->lab_id;
//         } elseif ($appoitment_data->scan_id) {
//             $lab_scan_id = $appoitment_data->scan_id;
//         } else {
//             $lab_scan_id =  null;
//         }
//         $lab_scan_model = Laboratory::select('firstname')->where('id', $lab_scan_id)->first();
//         if ($lab_scan_model) {
//             $scan = $lab_scan_model->where('Type', 1)->first();
//             $lab = $lab_scan_model->where('Type', 2)->first();
//         } else {
//             $scan = null;
//             $lab = null;
//         }
//         $scan_name = $scan ? $scan->firstname : null;
//         $lab_name = $lab ? $lab->firstname : null;
//         $appointment_details[] = [
//             'token_number' => $appoitment_data->token_number,
//             'date' => Carbon::parse($appoitment_data->date)->format('d-m-Y'),
//             'token_start_time' => Carbon::parse($appoitment_data->token_start_time)->format('h:i:A'),
//             'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
//             'symptom_start_time' => $appoitment_data->symptom_start_time,
//             'symptom_frequency' => $appoitment_data->symptom_frequency,
//             'prescription_image' => $appoitment_data->prescription_image  ? asset("LabImages/prescription/{$appoitment_data->prescription_image}") : null,
//             'schedule_type' => $appoitment_data->schedule_type,
//             'notes' => $appoitment_data->notes,
//             'patient_name' => $patient_data->firstname,
//             'patient_age' => $age,
//             'patient_id' => $patient_data->id,
//             'patient_user_id' => $patient_user_id,
//             'main_symptoms' => $main_symptoms ?? null,
//             'other_symptoms' => $other_symptom ?? null,
//             'treatment_taken' => $booking_treatment_taken,
//             'lab_name' => $lab_name,
//             'lab_test' => $appoitment_data->labtest ?? null,
//             'scan_name' => $scan_name ?? null,
//             'scan_test' => $appoitment_data->scan_test ?? null,
//             'surgery_name' => $booking_surgery_name,
//             'mediezy_patient_id' => $patient_data->mediezy_patient_id,
//             'patient_user_image' => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
//             'vitals' => $vitals_array,
//             'allergies' => $allergy_array,
//             'doctor_medicines' => $doctor_medicine_array,
//             'patient_medicines' => $patient_medicines_array,

//         ];
//         return response()->json(['status' => true, 'previous appointment details' => $appointment_details], 200);
//     } catch (\Exception $e) {
//         return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
//     }
// }

public function previousPatientAppoitmentsDetails(Request $request)
    {
        $rules = [
            'appointment_id' => 'required',
            'booked_user_id' => 'required',
        ];

        $messages = [
            'appointment_id.required' => 'Appointment ID is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'response' => $validator->errors()->first()]);
        }

        try {
            $appointment_id = $request->appointment_id;
            $completed_appointments = CompletedAppointments::where('appointment_id', $appointment_id);


            if (!$completed_appointments->exists()) {
                return response()->json(['status' => false, 'message' => 'Appointment not found.'], 200);
            }

            $appoitment_data = $completed_appointments->first();
            $vitals_array = array();
            $vitals_array['height'] = $appoitment_data->height;
            $vitals_array['weight'] = $appoitment_data->weight;
            $vitals_array['temperature'] = $appoitment_data->temperature;
            $vitals_array['spo2'] = $appoitment_data->spo2;
            $vitals_array['sys'] = $appoitment_data->sys;
            $vitals_array['dia'] = $appoitment_data->dia;
            $vitals_array['heart_rate'] = $appoitment_data->heart_rate;
            $vitals_array['temperature_type'] = $appoitment_data->temperature_type;
            if (
                $vitals_array['height'] === null &&
                $vitals_array['weight'] === null &&
                $vitals_array['temperature'] === null &&
                $vitals_array['spo2'] === null &&
                $vitals_array['sys'] === null &&
                $vitals_array['dia'] === null &&
                $vitals_array['heart_rate'] === null &&
                $vitals_array['temperature_type'] === null
            )
            {
                $vitals_array = [];
            }
            if (empty($vitals_array)) {
                $vitals_array = null;
            }
            //allergy
            $allergy_details = PatientAllergies::where('patient_id', $appoitment_data->patient_id)->get();
            $allergy_array = [];
            foreach ($allergy_details as $allergy_detail) {
                $allergy = Allergy::where('id', $allergy_detail->allergy_id)->pluck('allergy')->first();
                $allergy_array[] = [
                    'allergy_name' => $allergy,
                    'allergy_details' => $allergy_detail->allergy_details
                ];
            }
            //medicine
            $patient_user_id =  Patient::where('id', $appoitment_data->patient_id)->value('UserId');
            $patient_data = Patient::where('id', $appoitment_data->patient_id)->first();
            $token_number = $appoitment_data->token_number;
            $patient_medicines = Medicine::select('medicineName', 'illness')
               // ->where('docter_id', 0)
               ->where('medicine_type', NULL)
                ->where('patient_id', $appoitment_data->patient_id)
                ->orderBy('created_at', 'DESC')
                ->get();
            $patient_medicines_array = [];
            foreach ($patient_medicines as $patient_medicine) {
                $patient_medicines_array[] = [
                    'medicine_name' => $patient_medicine->medicineName,
                    'illness'   => $patient_medicine->illness,

                ];
            }
            $medicine_details = Medicine::where('patient_id', $appoitment_data->patient_id)
               // ->where('docter_id', $request->user_id)
               ->where('patient_id', $appoitment_data->patient_id)
                ->where('medicine_type', 2)
                ->where('token_number', $token_number)
                ->get();
            $doctor_medicine_array = [];
            foreach ($medicine_details as $medicine_detail) {
                $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
                $medical_shop_name = $medicalshops ?  $medicalshops->firstname : NULL;
                $doctor_medicine_array[] = [
                    'medicine_name' => $medicine_detail->medicineName,
                    'Dosage' => $medicine_detail->Dosage,
                    'NoOfDays' => $medicine_detail->NoOfDays,
                    'Noon' => $medicine_detail->Noon,
                    'night' => $medicine_detail->night,
                    'evening' => $medicine_detail->evening,
                    'morning' => $medicine_detail->morning,
                    'type' => $medicine_detail->type,
                    'notes' => $medicine_detail->notes,
                    'illness'   => $medicine_detail->illness,
                    'medical_store_name' => $medical_shop_name,
                    'interval' => $medicine_detail->interval,
                    'time_section' => $medicine_detail->time_section,

                ];
            }
            $main_symptoms = MainSymptom::select('Mainsymptoms')
                ->where('user_id', $patient_user_id)
                ->where('TokenNumber', $appoitment_data->token_number)
                ->where('clinic_id',  $appoitment_data->clinic_id)
                ->first();
            $other_symptom_id = CompletedAppointments::select('appointment_for')
                ->where('booked_user_id', $patient_user_id)
                ->where('token_number',  $appoitment_data->token_number)
                ->where('clinic_id', $appoitment_data->clinic_id)
                ->orderBy('created_at', 'DESC')->first();
            $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);
            $other_symptom_array_value = [];

            if (isset($other_symptom_json['Appoinmentfor2'])) {
                $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
            }
            $other_symptom = Symtoms::select('symtoms')
                ->whereIn('id', $other_symptom_array_value)
                ->get()->toArray();
            $age = $patient_data->age;
            if (!$age) {
                $dob = $patient_data->dateofbirth;
                $dobCarbon = Carbon::parse($dob);
                $age = $dobCarbon->age;
            }
            if ($patient_data) {
                if ($patient_data->treatment_taken_details !== null) {
                    $booking_treatment_taken = $patient_data->treatment_taken_details;
                } else {
                    $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;

                    $booking_treatment_taken = $treatment_taken;
                }
                $patient_allergy_data = Patient::where('id', $appoitment_data->patient_id)
                    ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
                    ->first();
                $patient_allergy_data = Patient::where('id', $appoitment_data->patient_id)
                    ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
                    ->first();
                $patient_allergy_id = $patient_allergy_data ? $patient_allergy_data->allergy_id : null;
                $allergy_data = $patient_allergy_id ? Allergy::where('id', $patient_allergy_id)->first() : null;
                $booking_surgery_name = $patient_allergy_data ?
                    ($patient_allergy_data->surgery_name === 'Other' ? $patient_allergy_data->surgery_details : $patient_allergy_data->surgery_name) : null;
                if (!empty($booking_surgery_name)) {
                    $booking_surgery_name = explode(',', $booking_surgery_name);
                    $booking_surgery_name = array_map(function ($surgery) {
                        return trim($surgery, " \t\n\r\0\x0B[]");
                    }, $booking_surgery_name);
                }
                $booking_treatment_taken = $patient_allergy_data ?
                    ($patient_allergy_data->treatment_taken === 'Other' ? $patient_allergy_data->treatment_details : $patient_allergy_data->treatment_taken) : null;
                if (!empty($booking_treatment_taken)) {
                    $booking_treatment_taken = explode(',', $booking_treatment_taken);
                    $booking_treatment_taken = array_map(function ($treatmenttaken) {
                        return trim($treatmenttaken, " \t\n\r\0\x0B[]");
                    }, $booking_treatment_taken);
                }
                $booking['surgery_details'] = $patient_allergy_data ? $patient_allergy_data->surgery_details : null;
                $booking['treatment_taken_details'] = $patient_allergy_data ? $patient_allergy_data->treatment_taken_details : null;
            } else {
                $booking_treatment_taken = null;
                $booking_surgery_name = null;
            }
            if ($appoitment_data->lab_id) {
                $lab_scan_id = $appoitment_data->lab_id;
            } elseif ($appoitment_data->scan_id) {
                $lab_scan_id = $appoitment_data->scan_id;
            } else {
                $lab_scan_id =  null;
            }
            $lab_scan_model = Laboratory::select('firstname')->where('id', $lab_scan_id)->first();
            if ($lab_scan_model) {
                $scan = $lab_scan_model->where('Type', 1)->first();
                $lab = $lab_scan_model->where('Type', 2)->first();
            } else {
                $scan = null;
                $lab = null;
            }
            $scan_name = $scan ? $scan->firstname : null;
            $lab_name = $lab ? $lab->firstname : null;
            $appointment_details[] = [
                'token_number' => $appoitment_data->token_number,
                'date' => Carbon::parse($appoitment_data->date)->format('d-m-Y'),
                'token_start_time' => Carbon::parse($appoitment_data->token_start_time)->format('h:i:A'),
                'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
                'symptom_start_time' => $appoitment_data->symptom_start_time,
                'symptom_frequency' => $appoitment_data->symptom_frequency,
                'prescription_image' => $appoitment_data->prescription_image  ? asset("LabImages/prescription/{$appoitment_data->prescription_image}") : null,
                'schedule_type' => $appoitment_data->schedule_type,
                'notes' => $appoitment_data->notes,
                'patient_name' => $patient_data->firstname,
                'patient_age' => $age,
                'patient_id' => $patient_data->id,
                'patient_user_id' => $patient_user_id,

                'main_symptoms' => $main_symptoms ?? null,
                'other_symptoms' => $other_symptom ?? null,
                'treatment_taken' => $booking_treatment_taken,
                'surgery_details'=>$patient_data->surgery_details,
                'treatment_taken_details'=>$patient_data->treatment_taken_details,
                'lab_name' => $lab_name,
                'lab_test' => $appoitment_data->labtest ?? null,
                'scan_name' => $scan_name ?? null,
                'scan_test' => $appoitment_data->scan_test ?? null,
                'surgery_name' => $booking_surgery_name,
                'mediezy_patient_id' => $patient_data->mediezy_patient_id,
                'patient_user_image' => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
                'vitals' => $vitals_array,
                'allergies' => $allergy_array,
                'doctor_medicines' => $doctor_medicine_array,
                'patient_medicines' => $patient_medicines_array,

            ];
            return response()->json(['status' => true, 'previous appointment details' => $appointment_details], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // Health Record
    public function getHealthRecord(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'document_id' => 'required',
            'type'        => 'required|in:1,2,3,4',
        ];

        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $document = PatientDocument::where('id', $request->document_id)
                ->where('type', $request->type)
                ->where('user_id', $request->user_id)
                ->first();

            if (!$document) {
                return response()->json(['status' => false, 'response' => 'Document not found']);
            }

            $imageURL = url('user/documents/' . $document->document);
            $healthRecord = $this->getHealthRecordDetails($request->type, $request->document_id);

            // merge imageURL into health_record
            $healthRecord['document'] = $imageURL;

            return response()->json(['status' => true, 'response' => 'Health record retrieved successfully', 'health_record' => $healthRecord]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    private function getHealthRecordDetails($type, $documentId)
    {
        if ($type == '1' || $type == '2' || $type == '3' || $type == '4') {
            $model = ($type == '1') ? LabReport::class : (($type == '2') ? PatientPrescriptions::class : (($type == '3') ? DischargeSummary::class : ScanReport::class));
            $record = $model::where('document_id', $documentId)->first();
            return $record;
        }
        //

        return null;
    }

    public function getCompletedAppointments(Request $request)
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
                'token_booking.regularmedicine',
                'token_booking.ReviewAfter',
                'token_booking.Reviewdate',
                'token_booking.lab_id',
                'token_booking.labtest',
                'token_booking.medicalshop_id',
                'token_booking.prescription_image',
                'new_tokens.doctor_id as newDoctorId',
                'new_tokens.token_booking_id',
                'new_tokens.patient_id',
                'token_booking.*',
                'new_tokens.token_id',
                'new_tokens.token_booking_id',
                'laboratory.firstname as lab_name',
                'medicalshop.id as medicalshop_id',
                'medicalshop.firstname as medicalshop_name'

            )
                ->leftJoin('token_booking', 'new_tokens.token_booking_id', '=', 'token_booking.id')
                ->leftJoin('laboratory', 'token_booking.lab_id', '=', 'laboratory.id')
                ->leftJoin('medicalshop', 'token_booking.medicalshop_id', '=', 'medicalshop.id')
                ->where('new_tokens.token_id', $tokenId)
                ->whereDate('token_booking.date', now()->toDateString())
                ->first();

            if (!$booking) {
                return response()->json(['status' => false, 'response' => "Booking not found"]);
            }

            // $userImage = Patient::where('UserId', $booking->BookedPerson_id)->value('user_image');
            // $userImage = $userImage ? asset("UserImages/{$userImage}") : null;
            $patient_id = $booking->patient_id;
            $patientUserId = Patient::where('id', $patient_id)->value('id');
            $mediezyPatientId = Patient::where('id', $booking->patient_id)->value('mediezy_patient_id');
            $booking['mediezy_patient_id'] = $mediezyPatientId;
            $UserImage = Patient::where('id', $booking->patient_id)->value('user_image');
            $userImage = $UserImage ? asset("UserImages/{$UserImage}") : null;
            $booking['user_image'] = $userImage;



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

            $symptoms = json_decode($booking->Appoinmentfor_id, true);
            $mainSymptoms = MainSymptom::select('id', 'Mainsymptoms AS symtoms')
                ->where('user_id', $booking->BookedPerson_id ?? $booking->UserId)
                ->where('doctor_id', $booking->doctor_id)
                ->where('clinic_id', $booking->clinic_id)
                ->where('date', $booking->date)
                ->where('TokenNumber', $booking->TokenNumber)
                ->get()
                ->toArray();
            $patient_id = $booking->patient_id;
            $patient_allergy_data = Patient::where('id', $patient_id)->first();

            if ($patient_allergy_data) {
                $treatment_taken = $patient_allergy_data->treatment_taken ?? null;
                $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;
                $booking['treatment_taken'] = $treatment_taken;

                $surgery_name = $patient_allergy_data->surgery_name ?? null;
                $surgery_name = isset($surgery_name) ? implode(', ', array_map('trim', explode(',', trim($surgery_name, '[]')))) : null;
                $booking['surgery_name'] = $surgery_name;

                $booking['allergy'] = $patient_allergy_data->allergy ?? null;
                $booking['allergy_name'] = $patient_allergy_data->allergy_name ?? null;
                // $booking['Medicine_Taken'] = $patient_allergy_data->Medicine_Taken ?? null;
                // $booking['regularMedicine'] = $patient_allergy_data->regularMedicine ?? null;
                $booking['illness'] = $patient_allergy_data->illness ?? null;
            } else {
                $booking['treatment_taken'] = null;
                $booking['surgery_name'] = null;
                $booking['allergy'] = null;
                $booking['allergy_name'] = null;
                // $booking['Medicine_Taken'] = null;
                // $booking['regularMedicine'] = null;
                $booking['illness'] = null;
            }

            // Fetch medicines
            $doctorId = $booking->doctor_id;
            $bookedPersonId = $booking->BookedPerson_id;
            $medicines = Medicine::where('token_id', $request->token_id)
                ->where('docter_id', $doctorId)
                ->where('user_id', $bookedPersonId)
                ->get();
            $prescriptionImage = $booking->prescription_image ? asset("LabImages/prescription/{$booking->prescription_image}") : null;
            $patient_id = $booking->patient_id;

            //medicine details
            $medicine_data = Medicine::where('patient_id', $patient_id)->get();

            $medicine_details = [];

            foreach ($medicine_data as $medicine) {
                $medicine_details[] = [
                    'regularMedicine' => $medicine->medicineName,
                    'illness' => $medicine->illness
                ];
            }

            // $booking['medicine_details'] = $medicine_details;



            return response()->json([
                'status' => true,
                'booking_data' => [
                    'token_id' => $booking->token_id,
                    'token_booking_id' => $booking->token_booking_id,
                    'TokenNumber' => $booking->TokenNumber,
                    'doctor_id' => $booking->doctor_id,
                    'BookedPerson_id' => $booking->BookedPerson_id,
                    'PatientName' => $booking->PatientName,
                    'age' => $booking->age,
                    'date' => $booking->date,
                    'TokenTime' => $booking->TokenTime,
                    'Appoinmentfor_id' => $booking->Appoinmentfor_id,
                    'whenitstart' => $booking->whenitstart,
                    'whenitcomes' => $booking->whenitcomes,
                    'attachment' => $booking->attachment,
                    'notes' => $booking->notes,
                    'clinic_id' => $booking->clinic_id,
                    // 'regularmedicine' => $booking->regularmedicine,
                    'ReviewAfter' => $booking->ReviewAfter,
                    'lab_id' => $booking->lab_id,
                    'labtest' => $booking->labtest,
                    'medicalshop_id' => $booking->medicalshop_id,
                    'lab_name' => $booking->lab_name,
                    'medicalshop_name' => $booking->medicalshop_name,
                    'prescription_image' => $booking->prescription_image,
                    'prescription_image' => $prescriptionImage,
                    'Reviewdate' => $booking->Reviewdate,
                    'user_image' => $userImage,
                    'mediezy_patient_id' => $mediezyPatientId,

                    'main_symptoms' => $mainSymptoms,
                    'other_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray(),
                    'medicine' => $medicines,
                    'PatientId' => $patientUserId,
                    'treatment_taken' => $booking['treatment_taken'],
                    'surgery_name' => $booking['surgery_name'],
                    'allergy' => $booking['allergy'],
                    'allergy_name' => $booking['allergy_name'],
                    // 'Medicine_Taken' => $booking['Medicine_Taken'],
                    // 'regularMedicine' => $booking['regularMedicine'],
                    'illness' => $booking['illness'],
                    'vitals' => $vitals,
                    'medicine_details' => $medicine_details
                ],
                //'booking_data' => $booking,
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function updateDocument1(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'type'        => 'required|in:1,2',
            'document_id' => 'required|exists:patient_documents,id,user_id,' . $request->user_id,
            'document'    => 'required|mimes:doc,docx,pdf,jpeg,png,jpg',
        ];

        $messages = [
            'document.required' => 'Document is required',
            'type.in'           => 'Invalid document type',
            'document_id.exists' => 'Invalid document id',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false,  'code' => 1, 'message' => $validation->errors()->first()]);
        }


        try {
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'code' => 1, 'message' => "User not Found"]);
            }

            $patient_doc = PatientDocument::find($request->document_id);
            if (!$patient_doc) {
                return response()->json(['status' => false, 'code' => 1, 'message' => "Document not found"]);
            }

            // Check if the document belongs to the requesting user
            if ($patient_doc->user_id != $request->user_id) {
                return response()->json(['status' => false, 'code' => 1, 'message' => "You don't have permission to update this document."]);
            }


            if ($patient_doc->id != $request->document_id) {
                return response()->json(['status' => false, 'response' => "Invalid id"]);
            }



            $patient_doc->user_id = $request->user_id;

            if ($request->hasFile('document')) {
                $imageFile = $request->file('document');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('user/documents'), $imageName);
                    $patient_doc->document = $imageName;
                }
            }

            // $patient_doc->type = $request->type;

            $patient_doc->save();
            $patient_doc->document = asset('user/documents') . '/' . $patient_doc->document;
            return response()->json(['success' => true, 'code' => 1, 'message' => 'Update Success', 'document' => $patient_doc], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false,  'code' => 1, 'message' => "Internal Server Error"]);
        }
    }

}
