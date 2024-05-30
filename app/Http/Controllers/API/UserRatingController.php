<?php

namespace App\Http\Controllers\Api;

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
use App\Models\User;
use App\Models\UserRating;
use Illuminate\Support\Facades\Log;
use App\Models\UserDoctorReview;
use App\Models\UserLocations;
use App\Models\userReview;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserRatingController extends Controller
{
    public function addUserRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_rate' => 'required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        try {
            $user_rate = new UserRating();
            $user_rate->user_rate = $request->user_rate ?? null;
            $user_rate->save();
            return response()->json(['message' => 'rate added successfully....'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }

    public function addUserReview(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'rating_id' => 'required',
                'user_comments' => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        try {
            $user_review_data = new userReview();
            $user_review_data->rating_id = $request->rating_id ?? null;
            $user_review_data->user_comments = $request->user_comments ?? null;
            $user_review_data->save();
            return response()->json(['message' => 'review details added successfully....'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }

    public function getUserRating($user_rating)
    {
        //$user_id = Auth::user()->id;
        $rating = UserRating::join('user_review', 'user_review.rating_id', '=', 'user_rating.rating_id')
            ->select(
                'user_review.rating_id',
                'user_review.review_id',
                'user_review.user_comments',
                'user_rating.rating_start',
                'user_rating.rating_end',
                'user_rating.heading'
            )
            ->where('user_rating.user_rate', $user_rating)
            ->get();
            $heading = $rating->first()->heading;

        if ($rating->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Rating not found'], 400);
        }
        $rating = $rating->map(function ($item) {
            unset($item->heading);
            return $item;
        });

        return response()->json([
            'heading' => $heading,
            'User_Rating' => $rating,
            'message' => 'Reviews details retrieved successfully'
        ]);
        //return response()->json(['User_Rating' => $rating, 'message' => 'Reviews details retrived successfully']);
    }

    public function addUserDoctorRating(Request $request)
    {

        // $user_id = Auth::user()->id;
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:completed_appointments,appointment_id',
            // 'review_id' => 'sometimes|exists:user_review,review_id',
            // 'rating_id' => 'sometimes|exists:user_rating,rating_id',
            'rating' => 'required',
            //  'user_comments' => 'sometimes',
            //  'doctor_recommendation' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 400);
        }
        try {
            $user_review = new UserDoctorReview();
            $user_review->review_id = $request->review_id ?? null;
            $user_review->appointment_id = $request->appointment_id ?? null;
            $user_review->rating_id = $request->rating_id ?? null;
            $user_review->rating = $request->rating ?? null;
            $user_review->doctor_recommendation = $request->doctor_recommendation ?? null;
            $user_review->user_comments = $request->user_comments ?? null;
            $user_review->save();
            $appointment = CompletedAppointments::find($request->appointment_id);
            if ($appointment) {
                $appointment->feedback_status = 1;
                $appointment->save();
            }
            return response()->json([
                'status' => true, 'message' => 'Review added successfully',
                'review_details' => [
                    'review_id' => (int)  $user_review->review_id,
                    'appointment_id' => (int)  $user_review->appointment_id,
                    'rating' => (float)  $user_review->rating,
                    'rating_id' => (int)  $user_review->rating_id,
                    'doctor_recommendation' => (int)  $user_review->doctor_recommendation,
                    'user_comments' => (int) $user_review->user_comments,
                    'feedback_status' => $appointment->feedback_status,
                ]
            ], 200);
        } catch (\PDOException $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Database error'], 500);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }



    public function userCompletedFeedback(Request $request)
    {
        $rules = [
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
        $completed_appointments = CompletedAppointments::where('booked_user_id', $request->patient_user_id);


        if (!$completed_appointments->exists()) {
            return response()->json(['status' => false, 'message' => 'Appointment not found.', 'appointment_details' => NULL], 200);
        }
        $appointment_details = [];
        ////sort by last checkout
        $appoitment_datas = $completed_appointments->where('feedback_status', 0)->orderBy('checkout_time', 'DESC')->get();
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
                'check_in_time' => Carbon::parse($appoitment_data->check_in_time)->format('h:i:A'),
                'checkout_time' => Carbon::parse($appoitment_data->checkout_time)->format('h:i:A'),
                'feedback_status' => $appoitment_data->feedback_status,
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
        // } catch (\Exception $e) {
        //     return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        // }
    }
}
