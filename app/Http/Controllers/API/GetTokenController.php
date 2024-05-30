<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\CompletedAppointments;
use App\Models\Docter;
use App\Models\Laboratory;
use App\Models\MainSymptom;
use App\Models\Medicalshop;
use App\Models\Medicine;
use App\Models\NewTokens;
use App\Models\Patient;
use App\Models\TokenBooking;
use Carbon\Carbon;
use App\Models\Symtoms;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\CommonMark\Extension\CommonMark\Renderer\Block\ThematicBreakRenderer;

class GetTokenController extends BaseController
{

    ///ashwin
    public function patientLiveTokenEstimate($patient_user_id)
    {
        try {

            $patient_id = Patient::where('UserId', $patient_user_id)->value('id');

            if (!$patient_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            Log::channel('doctor_schedules')->info("//////////////patientLiveTokenEstimate/////////////////");
            $today_booked_tokens = NewTokens::where('token_booking_status', 1)
                ->where('booked_user_id', $patient_user_id)
                ->orderBy('token_id', 'DESC')
                ->get();


            if ($today_booked_tokens->isEmpty()) {
                Log::channel('doctor_schedules')->info('Booked Tokens Not Found');
                return response()->json(['message' => 'Booked Tokens Not Found'], 200);
            }

            $tokenProperties = [
                'token_start_time', 'token_id', 'doctor_id', 'clinic_id', 'schedule_id',
                'token_number', 'token_end_time', 'token_scheduled_date', 'actual_token_duration',
                'assigned_token_duration', 'schedule_type', 'doctor_late_time', 'doctor_early_time',
                'doctor_break_time', 'token_up_to', 'created_at', 'updated_at',
                'token_booking_status', 'token_booked_date', 'checkin_time', 'checkout_time',
                'is_checkedin', 'is_checkedout',
            ];



            $allTokenDetails = [];

            foreach ($today_booked_tokens as $today_booked_token) {
                $tokenDetails = [];

                foreach ($tokenProperties as $property) {
                    $tokenDetails[$property] = $today_booked_token->$property ?? null;
                }

                $default_estimate_time = 20;
                $actual_token_start_time = $tokenDetails['token_start_time'];
                $booked_tokens_start_time = $actual_token_start_time;

                $estimate_token_start = Carbon::parse($booked_tokens_start_time);
                $sum_estimate_token_start = $estimate_token_start->subMinutes($default_estimate_time);
                $estimated_token_start_time = $sum_estimate_token_start;

                // Adjust estimated start time based on actual token duration
                $assigned_token_duration = $tokenDetails['assigned_token_duration'];
                $actual_token_duration = $tokenDetails['actual_token_duration'];

                if ($actual_token_duration < $assigned_token_duration) {
                    // Calculate the difference between assigned and actual durations
                    $time_difference = $assigned_token_duration - $actual_token_duration;

                    // Subtract the difference from the estimated start time
                    for ($i = 1; $i <= $tokenDetails['token_number']; $i++) {
                        $estimated_token_start_time->subMinutes($time_difference);
                    }
                } elseif ($actual_token_duration > $assigned_token_duration) {
                    // Calculate the difference between actual and assigned durations
                    $time_difference = $actual_token_duration - $assigned_token_duration;

                    // Add the difference to the estimated start time
                    for ($i = 1; $i <= $tokenDetails['token_number']; $i++) {
                        $estimated_token_start_time->addMinutes($time_difference);
                    }
                }


                //////////////////check for assigned and actual token durations
                // if (is_numeric($tokenDetails['actual_token_duration']) && is_numeric($tokenDetails['assigned_token_duration'])) {
                //     $actualTokenDuration = floatval($tokenDetails['actual_token_duration']);
                //     $assignedTokenDuration = floatval($tokenDetails['assigned_token_duration']);

                //     if ($actualTokenDuration > $assignedTokenDuration) {
                //         $tokenDetails['token_start_time'] += $assignedTokenDuration;
                //     }

                //     if ($actualTokenDuration < $assignedTokenDuration) {
                //         $tokenDetails['token_start_time'] -= $assignedTokenDuration;
                //     }
                // }
                //////////////////////////// additional details get//////////////////
                $token_number_loop  = $tokenDetails['token_number'];
                $ClinicId = $tokenDetails['clinic_id'];


                $main_symptoms = MainSymptom::select('Mainsymptoms')
                    ->where('user_id', $patient_user_id)
                    ->where('TokenNumber', $token_number_loop)
                    ->where('clinic_id', $ClinicId)
                    ->get();

                /////////////////////////
                $current_date = Carbon::now()->format('Y-m-d');
                $final_estimate_time = Carbon::parse($estimated_token_start_time)->format('H:i:A');
                $tokenDetails['estimated_token_start_time'] = $final_estimate_time;
                $tokenDetails['main_symptoms'] = $main_symptoms;
                $allTokenDetails[] = $tokenDetails;
            }
            $token_data = NewTokens::where('patient_id', $patient_id)
                ->where('token_scheduled_date', $current_date)
                ->where('is_checkedin', 1)
                ->orderBy('token_number', 'DESC')
                ->first();
            $live_token_number = $token_data->token_number;
            $tokenDetails['live_token'] = $live_token_number;

            return response()->json(['all_token_details' => $allTokenDetails], 200);
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }


    public function getCurrentDateTokens(Request $request)
    {
        $rules = [
            'clinic_id' => 'required',
            'schedule_type' => 'required',
            'Is_checkIn' => 'sometimes',
            'Is_completed' => 'sometimes',
            'TokenNumber' => 'sometimes',
        ];

        $validation = Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {

            $user = Auth::user();

            if (!$user) {

                return response()->json(['message' => 'User not authenticated.', 'tokens' => null], 401);
            }
            $currentDate = now()->toDateString();

            // if ($request->schedule_type == 0) {

            //     // $tokens = TokenBooking::where('doctor_id', $user->id)
            //     //     ->where('clinic_id', $request->clinic_id)

            //     //     // ->where('schedule_type', $request->schedule_type)
            //     //     ->whereDate('date', $currentDate)
            //     //     ->orderByRaw('CAST(TokenNumber AS SIGNED) ASC')
            //     //     ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
            //     //     ->select('token_booking.*', 'patient.mediezy_patient_id', 'patient.user_image')
            //     //     ->get();
            //     $tokens = TokenBooking::where('token_booking.doctor_id', $user->id)
            //         ->where('token_booking.clinic_id', $request->clinic_id)
            //         ->whereDate('token_booking.date', $currentDate)
            //         ->orderByRaw('CAST(TokenNumber AS SIGNED) ASC')
            //         ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
            //         ->join('new_tokens', 'token_booking.new_token_id', '=', 'new_tokens.token_id')
            //         ->select('token_booking.*', 'patient.mediezy_patient_id', 'patient.user_image', 'new_tokens.token_start_time')
            //         ->get();
            // } else {
            //     // $tokens = TokenBooking::where('doctor_id', $user->id)
            //     //     ->where('clinic_id', $request->clinic_id)
            //     //     ->where('schedule_type', $request->schedule_type)
            //     //     ->whereDate('date', $currentDate)
            //     //     ->orderByRaw('CAST(TokenNumber AS SIGNED) ASC')
            //     //     ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')

            //     //     ->select('token_booking.*', 'patient.mediezy_patient_id', 'patient.user_image')
            //     //     ->get();
            //     $tokens = TokenBooking::where('token_booking.doctor_id', $user->id)
            //         ->where('token_booking.clinic_id', $request->clinic_id)
            //         ->whereDate('token_booking.date', $currentDate)
            //         ->orderByRaw('CAST(TokenNumber AS SIGNED) ASC')
            //         ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
            //         ->join('new_tokens', 'token_booking.new_token_id', '=', 'new_tokens.token_id')
            //         ->select('token_booking.*', 'patient.mediezy_patient_id', 'patient.user_image', 'new_tokens.token_start_time')
            //         ->get();
            // }
            // if ($tokens->isEmpty()) {
            //     return response()->json(['message' => 'No tokens available for the current date.', 'tokens' => null], 200);
            // }

            $tokensQuery = TokenBooking::where('token_booking.doctor_id', $user->id)
                ->where('token_booking.clinic_id', $request->clinic_id)
                ->whereDate('token_booking.date', $currentDate)
                ->orderByRaw('CAST(TokenNumber AS SIGNED) ASC')
                ->leftJoin('patient', 'token_booking.patient_id', '=', 'patient.id')
                ->join('new_tokens', 'token_booking.new_token_id', '=', 'new_tokens.token_id')
                ->select('token_booking.*', 'patient.mediezy_patient_id', 'patient.user_image', 'new_tokens.token_start_time');

            if ($request->schedule_type != 0) {
                $tokensQuery->where('token_booking.schedule_type', $request->schedule_type);
            }

            $tokens = $tokensQuery->get();

            if ($tokens->isEmpty()) {
                return response()->json(['message' => 'No tokens available for the current date.', 'tokens' => null], 200);
            }
            foreach ($tokens as $appointment) {
                $userImage = $appointment->user_image ? asset("UserImages/{$appointment->user_image}") : null;

                $displayAge = "1";

                $date_of_birth_data  = Patient::select('dateofbirth')->where('id', $appointment->patient_id)->first();
                $date_of_birth = $date_of_birth_data->dateofbirth;
                if (isset($date_of_birth)) {
                    $dob = \Carbon\Carbon::parse($date_of_birth);
                    $now = \Carbon\Carbon::now();
                    $diff = $now->diffInMonths($dob);

                    if ($diff < 12) {
                        $displayAge = $diff . ' months old';
                    } else {
                        $years = floor($diff / 12);
                        $months = $diff % 12;
                        if ($months > 0) {
                            // $displayAge = $years . ' years ' . $months . ' months old';
                            $displayAge = $years . ' years old ';
                        } else {
                            $displayAge = $years . ' years old';
                        }
                    }
                }

                $TokenTime = $appointment->token_start_time;
                $formattedTokenStartTime = \Carbon\Carbon::parse($TokenTime)->format('h:i A');

                $patientDetails = [
                    'TokenTime' =>  $formattedTokenStartTime,
                    'mediezy_patient_id' => $appointment->mediezy_patient_id,
                    'user_image' => $userImage,
                    'displayAge' => $displayAge,
                ];
                // $patientDetails = [
                //     'mediezy_patient_id' => $appointment->mediezy_patient_id,
                //     'user_image' => $userImage,
                // ];


                $symptoms = json_decode($appointment->Appoinmentfor_id, true);

                if ($appointment->patient_id === null) {
                    $mediezyPatientId = $appointment->mediezy_patient_id;
                }

                $tokenBooking = TokenBooking::find($appointment->id);
                $token_id = $tokenBooking ? $tokenBooking->new_token_id : null;
                $bookedPersonId = $tokenBooking ? $tokenBooking->BookedPerson_id : null;

                if ($request->Is_checkIn) {
                    $tokenBooking->Is_checkIn = $request->Is_checkIn;
                    $tokenBooking->checkinTime = now();
                }

                if ($request->Is_completed) {
                    $tokenBooking->Is_completed = $request->Is_completed;
                    $tokenBooking->checkoutTime = now();
                }

                $tokenBooking->save();
                // medicine details req change 01-03
                $booking = [
                    'medicine' => [],
                    'patient_data' => [],
                ];

                // if ($request->Is_completed) {

                // print_r($token_id);exit;
                $booking['medicine'] = Medicine::where('token_id', $token_id)
                    ->where('docter_id', $user->id)
                    ->where('user_id', $bookedPersonId)
                    ->get()->toArray();



                $lab_name = Laboratory::where('id', $tokenBooking->lab_id)
                    ->pluck('firstname')
                    ->first();
                $scan_name = Laboratory::where('id', $tokenBooking->scan_id)
                    ->pluck('firstname')
                    ->first();

                $medical_shop_name = Medicalshop::where('id', $tokenBooking->medicalshop_id)
                    ->pluck('firstname')
                    ->first();

                $booking['patient_data'] = [
                    'lab_name' => $lab_name,
                    'medical_shop_name' => $medical_shop_name ? $medical_shop_name : null,
                    'notes' => $tokenBooking ? $tokenBooking->notes : null,
                    'prescription_image' => $tokenBooking ? $tokenBooking->prescription_image : null,
                    'labtest' => $tokenBooking ? $tokenBooking->labtest : null,
                    'review_after' => $tokenBooking ? $tokenBooking->ReviewAfter : null,
                    'scan_test' => $tokenBooking ? $tokenBooking->scan_test : null,
                    'scan_name' => $scan_name ? $scan_name : null,
                ];

                unset(
                    $tokenBooking->created_at,
                    $tokenBooking->updated_at,
                    $tokenBooking->amount,
                    $tokenBooking->attachment,
                    $tokenBooking->notes,
                    $tokenBooking->lab_id,
                    $tokenBooking->paymentmethod,
                    $tokenBooking->checkinTime,
                    $tokenBooking->checkoutTime,
                    $tokenBooking->EndTokenTime,
                    $tokenBooking->labtest,
                    $tokenBooking->medicalshop_id,
                    $tokenBooking->prescription_image,
                    $tokenBooking->Reviewdate,
                    $tokenBooking->ReviewAfter,
                    $tokenBooking->TokenTime

                );
                //   $tokenBooking['main_symptoms'] = Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor1'])->get()->toArray();
                $tokenBooking['main_symptoms'] = MainSymptom::select('id', 'Mainsymptoms')->whereIn('id', $symptoms['Appoinmentfor1'])->get()->toArray();
                $tokenBooking['other_symptoms'] = Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray();
                $tokenBooking['medicine'] =  $booking['medicine'];
                $tokenBooking['patient_data'] =  $booking['patient_data'];


                $combinedDetails = array_merge($tokenBooking->toArray(), $patientDetails);
                $appointmentsWithDetails[] = $combinedDetails;
            }

            // Return the array of updated tokens as JSON
            return response()->json(['message' => 'Current user tokens retrieved and updated successfully.', 'tokens' => $appointmentsWithDetails], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getTokensForCheckInAndComplete(Request $request)
    {
        $rules = [
            'clinic_id'   => 'required',
            'TokenNumber' => 'required',
            'Is_checkIn' => 'sometimes',
            'Is_completed' => 'sometimes',
        ];

        $validation = Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {

            // Get current date
            $currentDate = Carbon::now()->toDateString();
            $tokenNumber = $request->TokenNumber;
            $ClinicId = $request->clinic_id;


            // Fetch appointments for the current date and the logged-in doctor
            $appointments = DB::table('token_booking')
                ->whereDate('date', $currentDate)
                ->where('TokenNumber', $tokenNumber)
                ->where('clinic_id', $ClinicId)
                ->get();

            if ($appointments->isEmpty()) {
                return response()->json(['message' => 'No appointments for the current date.'], 200);
            }



            $checkinCompleted = false;
            $checkoutCompleted = false;

            foreach ($appointments as $appointment) {
                $tokenBooking = TokenBooking::find($appointment->id);

                if ($request->Is_checkIn && !$tokenBooking->Is_checkIn) {
                    $tokenBooking->Is_checkIn = $request->Is_checkIn;
                    $tokenBooking->checkinTime = now();
                    $checkinCompleted = true;
                }

                if ($request->Is_completed && !$tokenBooking->Is_completed) {
                    $tokenBooking->Is_completed = $request->Is_completed;
                    $tokenBooking->checkoutTime = now();
                    $checkoutCompleted = true;
                }

                $tokenBooking->save();
            }

            $responseMessage = '';

            if ($checkinCompleted) {
                $responseMessage .= 'Check-in completed. ';
            }

            if ($checkoutCompleted) {
                $responseMessage .= 'Check-out completed. ';
            }

            if (empty($responseMessage)) {
                $responseMessage = 'No actions performed.';
            }

            // Add the updated token details to the response if needed

            return response()->json(['message' => $responseMessage,], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
