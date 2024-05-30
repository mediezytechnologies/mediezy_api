<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Allergy;
use App\Models\Clinic;
use App\Models\CompletedAppointments;
use App\Models\Docter;
use App\Models\Laboratory;
use App\Models\MainSymptom;
use App\Models\Medicalshop;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientAllergies;
use App\Models\Symtoms;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompletedAppointmentsController extends Controller
{

    public function listCompletedAppoitmentsDetails(Request $request)
    {
        $rules = [
            'appointment_id' => 'required',
            'user_id' => 'required',
        ];

        $messages = [
            'appointment_id.required' => 'Appointment ID is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'response' => $validator->errors()->first()]);
        }

        try {

            /////////////////////////////////////////////////////////////////////////////
            $appointment_id = $request->appointment_id;
            $completed_appointments = CompletedAppointments::where('appointment_id', $appointment_id);


            if (!$completed_appointments->exists()) {
                return response()->json(['status' => false, 'message' => 'Appointment not found.'], 200);
            }

            $appoitment_data = $completed_appointments->first();

            //vitals details
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
            ) {
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

            //  $patient_user_id = $patient_data->id;
            //$doctor_user_id =  Docter::where('id', $appoitment_data->patient_id)->value('UserId');


            ///////////////////////
            $token_number = $appoitment_data->token_number;

            $patient_medicines = Medicine::select('medicineName', 'illness')
                ->where('medicine_type', NULL)
                ->where('patient_id', $appoitment_data->patient_id)
                ->orderBy('created_at', 'DESC')
                ->get();

            $patient_medicines_array = [];
            foreach ($patient_medicines as $patient_medicine) {
                $patient_medicines_array[] = [
                    'medicine_name' => $patient_medicine->medicineName,
                    'illness'   => $patient_medicine->illness
                ];
            }
            $medicine_details = Medicine::where('patient_id', $appoitment_data->patient_id)
                ->where('patient_id', $appoitment_data->patient_id)
                ->where('medicine_type', 2)
                ->where('token_number', $token_number)
                ->get();
            $doctor_medicine_array = [];

            // foreach ($medicine_details as $medicine_detail) {

            //     $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
            //     $medical_shop_name = $medicalshops ?  $medicalshops->firstname : NULL;
            $medical_shop_name = null;
            foreach ($medicine_details as $medicine_detail) {
                $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
                if ($medicalshops && !$medical_shop_name) {
                    $medical_shop_name = $medicalshops->firstname;
                }
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

            //main and other symptoms
            $main_symptoms = MainSymptom::select('Mainsymptoms')
                ->where('user_id', $patient_user_id)
                ->where('TokenNumber', $appoitment_data->token_number)
                ->where('clinic_id',  $appoitment_data->clinic_id)
                ->first();

            // $appointment['main_symptoms'] = $main_symptoms;
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
            //patient age

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
                // Allergies

                $patient_allergy_data = Patient::where('id', $appoitment_data->patient_id)
                    ->select('allergy_id', 'allergy_name', 'surgery_name', 'treatment_taken', 'Medicine_Taken', 'surgery_details', 'treatment_taken_details')
                    ->first();


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
            $lab = Laboratory::select('firstname')
                ->where('id', $appoitment_data->lab_id)
                // ->where('Type', 1)
                ->first();
            $scan = Laboratory::select('firstname')
                ->where('id', $appoitment_data->scan_id)
                //  ->where('Type', 2)
                ->first();
            $scan_name = $scan ? $scan->firstname : null;
            $lab_name = $lab ? $lab->firstname : null;

            $appointment_details[] = [
                'token_number' => $appoitment_data->token_number,
                'date'      => Carbon::parse($appoitment_data->date)->format('d-m-Y'),
                'token_start_time' => Carbon::parse($appoitment_data->token_start_time)->format('h:i:A'),
                'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
                'symptom_start_time' => $appoitment_data->symptom_start_time,
                'symptom_frequency' => $appoitment_data->symptom_frequency,
                'prescription_image' => $appoitment_data->prescription_image  ? asset("LabImages/prescription/{$appoitment_data->prescription_image}") : null,
                'schedule_type' => $appoitment_data->schedule_type,
                'review_after' => $appoitment_data->review_after,
                'notes' => $appoitment_data->notes,
                'medical_shop_name' => $medical_shop_name,
                'patient_name' => $patient_data->firstname,
                'patient_age' => $age,
                'patient_id' => $patient_data->id,
                'patient_user_id' => $patient_user_id,
                'treatment_taken' => $booking_treatment_taken,
                'treatment_taken_details' => $patient_allergy_data->treatment_taken_details,
                'lab_name' => $lab_name,
                'lab_test' => $appoitment_data->labtest ?? null,
                'scan_name' => $scan_name ?? null,
                'scan_test' => $appoitment_data->scan_test ?? null,
                'surgery_name' => $booking_surgery_name,
                'surgery_details' => $patient_allergy_data->surgery_details,
                'mediezy_patient_id' => $patient_data->mediezy_patient_id,
                'patient_user_image' => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
                'vitals' => $vitals_array,
                'allergies' => $allergy_array,
                'doctor_medicines' => $doctor_medicine_array,
                'patient_medicines' => $patient_medicines_array,
                'main_symptoms' => $main_symptoms ?? null,
                'other_symptoms' => $other_symptom ?? null,


            ];

            return response()->json(['status' => true, 'appointment_details' => $appointment_details], 200);
            /////////////////////////////////////////////////////////////////////////////


        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    ///live code
    public function getPatientCompletedAppointments(Request $request)
    {
        $rules = [
            //'appointment_id' => 'required',
            'patient_user_id' => 'required',
        ];

        $messages = [
            'patient_user_id.required' => 'Appointment ID is required.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'response' => $validator->errors()->first()]);
        }

        // try {
        $patient_id = Patient::where('UserId', $request->patient_user_id)->pluck('id')->first();
        /////////////////////////////////////////////////////////////////////////////
        // $appointment_id = $request->appointment_id;
        // $completed_appointments = ModelsCompletedAppointments::where('patient_id', $patient_id);
        $completed_appointments = CompletedAppointments::where('booked_user_id', $request->patient_user_id);


        if (!$completed_appointments->exists()) {
            return response()->json(['status' => false, 'message' => 'Appointment not found.', 'appointment_details' => NULL], 200);
        }

        // $appoitment_datas = $completed_appointments->get();
        ////sort by last checkout
        $appoitment_datas = $completed_appointments->orderBy('checkout_time', 'DESC')->get();
        ////vitals
        foreach ($appoitment_datas as $appoitment_data) {
            $vitals_array = array();
            $vitals_array['height'] = $appoitment_data->height;
            $vitals_array['weight'] = $appoitment_data->weight;
            $vitals_array['temperature'] = $appoitment_data->temperature;
            $vitals_array['spo2'] = $appoitment_data->spo2;
            $vitals_array['sys'] = $appoitment_data->sys;
            $vitals_array['dia'] = $appoitment_data->dia;
            $vitals_array['heart_rate'] = $appoitment_data->heart_rate;
            $vitals_array['temperature_type'] = $appoitment_data->temperature_type;
            if (array_filter($vitals_array)) {

                $vitals_array = [$vitals_array];
            } else {
                $vitals_array = [];
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

            $doctor_user_id = Docter::where('id', $appoitment_data->doctor_id)->value('UserId');

            //  $patient_user_id = $patient_data->id;
            //$doctor_user_id =  Docter::where('id', $appoitment_data->patient_id)->value('UserId');


            ///////////////////////
            $token_number = $appoitment_data->token_number;

            $patient_medicines = Medicine::select('medicineName', 'illness')
                ->where('docter_id', 0)
                ->where('patient_id', $appoitment_data->patient_id)
                ->orderBy('created_at', 'DESC')
                ->get();

            $patient_medicines_array = [];
            foreach ($patient_medicines as $patient_medicine) {
                $patient_medicines_array[] = [
                    'medicine_name' => $patient_medicine->medicineName,
                    'illness'   => $patient_medicine->illness
                ];
            }
            $medicine_details = Medicine::where('patient_id', $appoitment_data->patient_id)
                ->where('docter_id', $doctor_user_id)
                ->where('medicine_type', 2)
                ->where('token_number', $token_number)
                ->get();

            $doctor_medicine_array = [];

            foreach ($medicine_details as $medicine_detail) {

                $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
                $medical_shop_name = $medicalshops->firstname ?? NULL;
                $doctor_medicine_array[] = [
                    'medicine_name' => $medicine_detail->medicineName,
                    'Dosage' => $medicine_detail->Dosage,
                    'NoOfDays' => $medicine_detail->NoOfDays,
                    'Noon' => $medicine_detail->Noon,
                    'night' => $medicine_detail->night,
                    'evening' => $medicine_detail->evening,
                    'morning' => $medicine_detail->morning,
                    'type' => $medicine_detail->type,
                    'interval' => $medicine_detail->interval,
                    'time_section' => $medicine_detail->time_section,
                    'medical_store_name' => $medical_shop_name,


                ];
            }

            //main and other symptoms
            // $appoitment_for = json_decode($appoitment_data->appoitment_for, true);
            // $appoitment_for_2 = isset($appoitment_for['Appoinmentfor2']) ? $appoitment_for['Appoinmentfor2'] : [];
            // $main_symptoms = MainSymptom::select('Mainsymptoms')
            //     ->where('user_id', $patient_user_id)
            //     ->where('TokenNumber', $appoitment_data->token_number)
            //     ->where('clinic_id', $appoitment_data->clinic_id)
            //     ->first();

            // $other_symptom = Symtoms::select('symtoms')
            //     ->where('id', $appoitment_for_2)
            //     ->first();

            // $symptoms[] = [

            //     'main_symptom' => $main_symptoms ? $main_symptoms->Mainsymptoms : 'No symptoms.',
            //     'other_symptom' => $other_symptom ? $other_symptom : 'No symptoms.',
            // ];
            $main_symptoms = MainSymptom::select('Mainsymptoms')
                ->where('user_id', $patient_user_id)
                ->where('TokenNumber', $appoitment_data->token_number)
                ->where('clinic_id',  $appoitment_data->clinic_id)
                ->first();

            // $appointment['main_symptoms'] = $main_symptoms;
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
            // $appointment['other_symptom'] = $other_symptom;


            //patient age

            $age = $patient_data->age;

            if (!$age) {
                $dob = $patient_data->dateofbirth;
                $dobCarbon = Carbon::parse($dob);
                $age = $dobCarbon->age;
            }
            //////////////////////////////////
            if ($patient_data) {
                if ($patient_data->treatment_taken_details !== null) {

                    $treatment_taken_data = $patient_data->treatment_taken_details;
                } else {

                    $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;

                    $treatment_taken_data = $treatment_taken;
                }

                if ($patient_data->surgery_details !== null) {
                    $surgery_details_data = $patient_data->surgery_details;
                } else {
                    if ($patient_data->surgery_name !== null) {
                        $surgery_details_data = $patient_data->surgery_name;
                    } else {
                        $surgery_name = isset($surgery_name) ? implode(', ', array_map('trim', explode(',', trim($surgery_name, '[]')))) : null;
                        $surgery_details_data = $surgery_name;
                    }
                }
            } else {
                $treatment_taken_data = null;
                $surgery_details_data = null;
            }
            //print_r($appoitment_data->lab_id);exit;

            /////////////////////////////////////
            // $lab_name = Laboratory::select('firstname')->where('id', $appoitment_data->lab_id)
            //     ->where('Type', 1)->first();
            // $scan_name = Laboratory::select('firstname')->where('id', $appoitment_data->scan_id)
            //     ->where('Type', 2)->first();

            // if ($appoitment_data->lab_id) {
            //     $lab_scan_id = $appoitment_data->lab_id;
            // } elseif ($appoitment_data->scan_id) {
            //     $lab_scan_id = $appoitment_data->scan_id;
            // } else {
            //     $lab_scan_id =  null;
            // }

            // $lab_scan_model = Laboratory::select('firstname')->where('id', $lab_scan_id)->first();
            // if ($lab_scan_model) {
            //     $scan = $lab_scan_model->where('Type', 1)->first();
            //     $lab = $lab_scan_model->where('Type', 2)->first();
            // } else {
            //     $scan = null;
            //     $lab = null;
            // }
            ///////Lab and Scan
            $lab = Laboratory::select('firstname')
                ->where('id', $appoitment_data->lab_id)
                // ->where('Type', 1)
                ->first();
            $scan = Laboratory::select('firstname')
                ->where('id', $appoitment_data->scan_id)
                //  ->where('Type', 2)
                ->first();

            $scan_name = $scan ? $scan->firstname : null;
            $lab_name = $lab ? $lab->firstname : null;


            /////////////////////
            $doctor_data = Docter::select('firstname', 'lastname', 'docter_image')->where('id', $appoitment_data->doctor_id)->first();

            $doctor_image =  $doctor_data ? asset("DocterImages/images/{$doctor_data->docter_image}") : null;

            $doctor_name = $doctor_data ? $doctor_data->firstname . ' ' . $doctor_data->lastname : null;

            $clinic_data = Clinic::select('clinic_name')->where('clinic_id', $appoitment_data->clinic_id)->first();

            $clinic_name = $clinic_data ? $clinic_data->clinic_name : null;
            $appointment_details[] = [
                'appointment_id' => $appoitment_data->appointment_id,
                'token_number' => $appoitment_data->token_number,
                'date'      => Carbon::parse($appoitment_data->date)->format('d-m-Y'),
                'token_start_time' => Carbon::parse($appoitment_data->token_start_time)->format('h:i:A'),
                'symptom_start_time' => $appoitment_data->symptom_start_time,
                'symptom_frequency' => $appoitment_data->symptom_frequency,
                //////
                'check_in_time' => Carbon::parse($appoitment_data->check_in_time)->format('h:i:A'),
                'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
                'feedback_status' => $appoitment_data->feedback_status,
                ///
                'prescription_image' => $appoitment_data->prescription_image  ? asset("LabImages/prescription/{$appoitment_data->prescription_image}") : null,
                'schedule_type' => $appoitment_data->schedule_type,
                'notes' => $appoitment_data->notes,
                'review_after' => $appoitment_data->review_after,
                'patient_name' => $patient_data->firstname,
                'patient_age' => $age,
                'patient_id' => $patient_data->id,
                'patient_user_id' => $patient_user_id,
                'treatment_taken' => $treatment_taken_data,
                'doctor_image' => $doctor_image,
                'doctor_name' => $doctor_name,
                'clinic_name' => $clinic_name,
                'lab_name' => $lab_name,
                'lab_test' => $appoitment_data->labtest ?? null,
                'scan_name' => $scan_name ?? null,
                'scan_test' => $appoitment_data->scan_test ?? null,
                'surgery_name' => $surgery_details_data,
                'mediezy_patient_id' => $patient_data->mediezy_patient_id,
                'patient_user_image' => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
                'vitals' => $vitals_array,
                'allergies' => $allergy_array,
                'doctor_medicines' => $doctor_medicine_array,
                //'patient_medicines' => $patient_medicines_array,
                'main_symptoms' => $main_symptoms ?? null,
                'other_symptoms' => $other_symptom ?? null,

            ];
        }

        return response()->json(['status' => true, 'appointment_details' => $appointment_details], 200);
        /////////////////////////////////////////////////////////////////////////////


        // } catch (\Exception $e) {
        //     return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        // }
    }


    public function getSortedPatientAppointments($patient_id, $userId)
    {

        try {

            /////////////////////////////////////////////////////////////////////////////
            // $appointment_id = $request->appointment_id;
            $completed_appointments = CompletedAppointments::where('patient_id', $patient_id)
                ->orderBy('date', 'DESC')
                ->orderBy('token_start_time', 'DESC');




            if (!$completed_appointments->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment not found.',
                    'appointment_details' => null
                ], 200);
            }

            $appoitment_datas = $completed_appointments->get();


            foreach ($appoitment_datas as $appoitment_data) {
                $vitals_array = array();
                $vitals_array['height'] = $appoitment_data->height;
                $vitals_array['weight'] = $appoitment_data->weight;
                $vitals_array['temperature'] = $appoitment_data->temperature;
                $vitals_array['spo2'] = $appoitment_data->spo2;
                $vitals_array['sys'] = $appoitment_data->sys;
                $vitals_array['dia'] = $appoitment_data->dia;
                $vitals_array['heart_rate'] = $appoitment_data->heart_rate;
                $vitals_array['temperature_type'] = $appoitment_data->temperature_type;
                if (array_filter($vitals_array)) {

                    $vitals_array = [$vitals_array];
                } else {
                    $vitals_array = [];
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

                $doctor_user_id = Docter::where('id', $appoitment_data->doctor_id)->value('UserId');

                //  $patient_user_id = $patient_data->id;
                //$doctor_user_id =  Docter::where('id', $appoitment_data->patient_id)->value('UserId');


                ///////////////////////
                $token_number = $appoitment_data->token_number;

                $patient_medicines = Medicine::select('medicineName', 'illness')
                    ->where('docter_id', 0)
                    ->where('patient_id', $appoitment_data->patient_id)
                    ->orderBy('created_at', 'DESC')
                    ->get();

                $patient_medicines_array = [];
                foreach ($patient_medicines as $patient_medicine) {
                    $patient_medicines_array[] = [
                        'medicine_name' => $patient_medicine->medicineName,
                        'illness'   => $patient_medicine->illness
                    ];
                }
                $medicine_details = Medicine::where('patient_id', $appoitment_data->patient_id)
                    ->where('docter_id', $doctor_user_id)
                    ->where('medicine_type', 2)
                    ->where('token_number', $token_number)
                    ->get();

                $doctor_medicine_array = [];

                foreach ($medicine_details as $medicine_detail) {

                    $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
                    $medical_shop_name = $medicalshops->firstname ?? NULL;
                    $doctor_medicine_array[] = [
                        'medicine_name' => $medicine_detail->medicineName,
                        'Dosage' => $medicine_detail->Dosage,
                        'NoOfDays' => $medicine_detail->NoOfDays,
                        'Noon' => $medicine_detail->Noon,
                        'night' => $medicine_detail->night,
                        'evening' => $medicine_detail->evening,
                        'morning' => $medicine_detail->morning,
                        'interval' => $medicine_detail->interval,
                        'time_section' => $medicine_detail->time_section,
                        'type' => $medicine_detail->type,
                        'medical_store_name' => $medical_shop_name,


                    ];
                }


                $main_symptoms = MainSymptom::select('Mainsymptoms')
                    ->where('user_id', $patient_user_id)
                    ->where('TokenNumber', $appoitment_data->token_number)
                    ->where('clinic_id',  $appoitment_data->clinic_id)
                    ->first();

                // $appointment['main_symptoms'] = $main_symptoms;
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
                // $appointment['other_symptom'] = $other_symptom;


                //patient age

                $age = $patient_data->age;

                if (!$age) {
                    $dob = $patient_data->dateofbirth;
                    $dobCarbon = Carbon::parse($dob);
                    $age = $dobCarbon->age;
                }
                //////////////////////////////////
                if ($patient_data) {
                    if ($patient_data->treatment_taken_details !== null) {

                        $treatment_taken_data = $patient_data->treatment_taken_details;
                    } else {

                        $treatment_taken = isset($treatment_taken) ? implode(', ', array_map('trim', explode(',', trim($treatment_taken, '[]')))) : null;

                        $treatment_taken_data = $treatment_taken;
                    }

                    if ($patient_data->surgery_details !== null) {
                        $surgery_details_data = $patient_data->surgery_details;
                    } else {
                        if ($patient_data->surgery_name !== null) {
                            $surgery_details_data = $patient_data->surgery_name;
                        } else {
                            $surgery_name = isset($surgery_name) ? implode(', ', array_map('trim', explode(',', trim($surgery_name, '[]')))) : null;
                            $surgery_details_data = $surgery_name;
                        }
                    }
                } else {
                    $treatment_taken_data = null;
                    $surgery_details_data = null;
                }

                $lab = Laboratory::select('firstname')
                    ->where('id', $appoitment_data->lab_id)
                    // ->where('Type', 1)
                    ->first();
                $scan = Laboratory::select('firstname')
                    ->where('id', $appoitment_data->scan_id)
                    //  ->where('Type', 2)
                    ->first();
                $scan_name = $scan ? $scan->firstname : null;
                $lab_name = $lab ? $lab->firstname : null;

                /////////////////////
                $doctor_data = Docter::select('firstname', 'lastname', 'docter_image')->where('id', $appoitment_data->doctor_id)->first();

                $doctor_image =  $doctor_data ? asset("DocterImages/images/{$doctor_data->docter_image}") : null;

                $doctor_name = $doctor_data ? $doctor_data->firstname . ' ' . $doctor_data->lastname : null;

                $clinic_data = Clinic::select('clinic_name')->where('clinic_id', $appoitment_data->clinic_id)->first();

                $clinic_name = $clinic_data ? $clinic_data->clinic_name : null;


                $appointment_details[] = [

                    'token_number' => $appoitment_data->token_number,
                    'date'      => Carbon::parse($appoitment_data->date)->format('d-m-Y'),
                    'token_start_time' => Carbon::parse($appoitment_data->token_start_time)->format('h:i:A'),
                    'symptom_start_time' => $appoitment_data->symptom_start_time,
                    'symptom_frequency' => $appoitment_data->symptom_frequency,
                    'prescription_image' => $appoitment_data->prescription_image  ? asset("LabImages/prescription/{$appoitment_data->prescription_image}") : null,
                    'schedule_type' => $appoitment_data->schedule_type,
                    'notes' => $appoitment_data->notes,
                    'patient_name' => $patient_data->firstname,
                    'check_in_time' => Carbon::parse($appoitment_data->check_in_time)->format('h:i:A'),
                    'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
                    'feedback_status' => $appoitment_data->feedback_status,
                    'patient_age' => $age,
                    'patient_id' => $patient_data->id,
                    'patient_user_id' => $patient_user_id,
                    'treatment_taken' => $treatment_taken_data,
                    'doctor_image' => $doctor_image,
                    'doctor_name' => $doctor_name,
                    'clinic_name' => $clinic_name,
                    'lab_name' => $lab_name,
                    'lab_test' => $appoitment_data->labtest ?? null,
                    'scan_name' => $scan_name ?? null,
                    'scan_test' => $appoitment_data->scan_test ?? null,
                    'surgery_name' => $surgery_details_data,
                    'mediezy_patient_id' => $patient_data->mediezy_patient_id,
                    'patient_user_image' => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
                    'vitals' => $vitals_array,
                    'allergies' => $allergy_array,
                    'doctor_medicines' => $doctor_medicine_array,
                    //'patient_medicines' => $patient_medicines_array,
                    // 'medical_store_name' => $medical_shop_name,
                    'main_symptoms' => $main_symptoms ?? null,
                    'other_symptoms' => $other_symptom ?? null,

                ];
            }


            usort($appointment_details, function ($a, $b) {
                $dateA = strtotime($a['date']);
                $dateB = strtotime($b['date']);
                $timeA = strtotime($a['token_start_time']);
                $timeB = strtotime($b['token_start_time']);

                if ($dateA == $dateB) {
                    return $timeB - $timeA;
                }

                return $dateB - $dateA;
            });


            return response()->json(['status' => true, 'appointment_details' => $appointment_details], 200);
            /////////////////////////////////////////////////////////////////////////////


        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSortedDoctorPatientAppointments($patient_id, $doctor_user_id)
    {
        try {


            $doctor_user_id = Docter::where('UserId', $doctor_user_id)->pluck('id')->first();


            if (!$doctor_user_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Doctor not found.',
                    'appointment_details' => null
                ], 200);
            }

            // Fetch completed appointments for the specified patient and doctor, sorted by date in descending order
            $completed_appointments = CompletedAppointments::where('patient_id', $patient_id)
                ->where('doctor_id', $doctor_user_id) // Add this line to filter by doctor_id
                ->orderBy('date', 'DESC');
            // ->orderBy('check_in_time', 'DESC');

            if (!$completed_appointments->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Appointment not found.',
                    'appointment_details' => null
                ], 200);
            }

            $appoitment_datas = $completed_appointments->get();
            $appointment_details = [];

            foreach ($appoitment_datas as $appoitment_data) {
                $vitals_array = array_filter([
                    'height' => $appoitment_data->height,
                    'weight' => $appoitment_data->weight,
                    'temperature' => $appoitment_data->temperature,
                    'spo2' => $appoitment_data->spo2,
                    'sys' => $appoitment_data->sys,
                    'dia' => $appoitment_data->dia,
                    'heart_rate' => $appoitment_data->heart_rate,
                    'temperature_type' => $appoitment_data->temperature_type
                ]);

                $vitals_array = $vitals_array ? [$vitals_array] : [];

                // Fetch allergy details
                $allergy_details = PatientAllergies::where('patient_id', $appoitment_data->patient_id)->get();
                $allergy_array = [];
                foreach ($allergy_details as $allergy_detail) {
                    $allergy = Allergy::where('id', $allergy_detail->allergy_id)->pluck('allergy')->first();
                    $allergy_array[] = [
                        'allergy_name' => $allergy,
                        'allergy_details' => $allergy_detail->allergy_details
                    ];
                }

                // Fetch patient data
                $patient_data = Patient::where('id', $appoitment_data->patient_id)->first();
                $patient_user_id = $patient_data->UserId;

                // Fetch doctor user ID
                $doctor_user_id = $appoitment_data->doctor_id;

                // Fetch patient medicines
                $patient_medicines = Medicine::select('medicineName', 'illness')
                    ->where('docter_id', 0)
                    ->where('patient_id', $appoitment_data->patient_id)
                    ->orderBy('created_at', 'DESC')
                    ->get();

                $patient_medicines_array = [];
                foreach ($patient_medicines as $patient_medicine) {
                    $patient_medicines_array[] = [
                        'medicine_name' => $patient_medicine->medicineName,
                        'illness' => $patient_medicine->illness
                    ];
                }

                // Fetch doctor prescribed medicines
                $medicine_details = Medicine::where('patient_id', $appoitment_data->patient_id)
                    ->where('docter_id', $doctor_user_id)
                    ->where('medicine_type', 2)
                    ->where('token_number', $appoitment_data->token_number)
                    ->get();

                $doctor_medicine_array = [];
                foreach ($medicine_details as $medicine_detail) {
                    $medicalshops = Medicalshop::where('id', $medicine_detail->medical_shop_id)->first();
                    $medical_shop_name = $medicalshops->firstname ?? null;
                    $doctor_medicine_array[] = [
                        'medicine_name' => $medicine_detail->medicineName,
                        'Dosage' => $medicine_detail->Dosage,
                        'NoOfDays' => $medicine_detail->NoOfDays,
                        'Noon' => $medicine_detail->Noon,
                        'night' => $medicine_detail->night,
                        'evening' => $medicine_detail->evening,
                        'morning' => $medicine_detail->morning,
                        'interval' => $medicine_detail->interval,
                        'time_section' => $medicine_detail->time_section,
                        'type' => $medicine_detail->type,
                        'medical_store_name' => $medical_shop_name
                    ];
                }

                // Fetch main and other symptoms
                $main_symptoms = MainSymptom::select('Mainsymptoms')
                    ->where('user_id', $patient_user_id)
                    ->where('TokenNumber', $appoitment_data->token_number)
                    ->where('clinic_id', $appoitment_data->clinic_id)
                    ->first();

                $other_symptom_id = CompletedAppointments::select('appointment_for')
                    ->where('booked_user_id', $patient_user_id)
                    ->where('token_number', $appoitment_data->token_number)
                    ->where('clinic_id', $appoitment_data->clinic_id)
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $other_symptom_json = json_decode($other_symptom_id->appointment_for, true);
                $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'] ?? [];
                $other_symptom = Symtoms::select('symtoms')
                    ->whereIn('id', $other_symptom_array_value)
                    ->get()->toArray();

                // Calculate patient age
                $age = $patient_data->age ?? Carbon::parse($patient_data->dateofbirth)->age;

                // Fetch treatment and surgery details
                $treatment_taken_data = $patient_data->treatment_taken_details ?? implode(', ', array_map('trim', explode(',', trim($treatment_taken ?? '', '[]'))));
                $surgery_details_data = $patient_data->surgery_details ?? $patient_data->surgery_name ?? implode(', ', array_map('trim', explode(',', trim($surgery_name ?? '', '[]'))));

                // Fetch lab and scan details
                $lab_name = Laboratory::select('firstname')->where('id', $appoitment_data->lab_id)->pluck('firstname')->first();
                $scan_name = Laboratory::select('firstname')->where('id', $appoitment_data->scan_id)->pluck('firstname')->first();

                // Fetch doctor and clinic details
                $doctor_data = Docter::select('firstname', 'lastname', 'docter_image')->where('id', $appoitment_data->doctor_id)->first();
                $doctor_image = $doctor_data ? asset("DocterImages/images/{$doctor_data->docter_image}") : null;
                $doctor_name = $doctor_data ? $doctor_data->firstname . ' ' . $doctor_data->lastname : null;
                $clinic_name = Clinic::select('clinic_name')->where('clinic_id', $appoitment_data->clinic_id)->pluck('clinic_name')->first();

                $appointment_details[] = [
                    'token_number' => $appoitment_data->token_number,
                    'date' => Carbon::parse($appoitment_data->date)->format('d-m-Y'),
                    'token_start_time' => Carbon::parse($appoitment_data->token_start_time)->format('h:i:A'),
                    'symptom_start_time' => $appoitment_data->symptom_start_time,
                    'symptom_frequency' => $appoitment_data->symptom_frequency,
                    'prescription_image' => $appoitment_data->prescription_image ? asset("LabImages/prescription/{$appoitment_data->prescription_image}") : null,
                    'schedule_type' => $appoitment_data->schedule_type,
                    'notes' => $appoitment_data->notes,
                    'patient_name' => $patient_data->firstname,
                    'check_in_time' => Carbon::parse($appoitment_data->check_in_time)->format('h:i:A'),
                    'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
                    'feedback_status' => $appoitment_data->feedback_status,
                    'patient_age' => $age,
                    'patient_id' => $patient_data->id,
                    'patient_user_id' => $patient_user_id,
                    'treatment_taken' => $treatment_taken_data,
                    'doctor_image' => $doctor_image,
                    'doctor_name' => $doctor_name,
                    'clinic_name' => $clinic_name,
                    'lab_name' => $lab_name,
                    'lab_test' => $appoitment_data->labtest ?? null,
                    'scan_name' => $scan_name ?? null,
                    'scan_test' => $appoitment_data->scan_test ?? null,
                    'surgery_name' => $surgery_details_data,
                    'mediezy_patient_id' => $patient_data->mediezy_patient_id,
                    'patient_user_image' => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
                    'vitals' => $vitals_array,
                    'allergies' => $allergy_array,
                    'doctor_medicines' => $doctor_medicine_array,
                    'main_symptoms' => $main_symptoms ?? null,
                    'other_symptoms' => $other_symptom ?? null
                ];
            }


            usort($appointment_details, function ($a, $b) {
                return strtotime($b['check_in_time']) - strtotime($a['check_in_time']);
            });

            return response()->json(['status' => true, 'appointment_details' => $appointment_details], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
