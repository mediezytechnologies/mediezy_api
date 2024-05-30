<?php

namespace App\Http\Controllers\API;

use App\Helpers\PushNotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\CompletedAppointments;
use App\Models\Docter;
use App\Models\DoctorReschedule;
use App\Models\NewTokens;
use App\Models\TokenBooking;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckInCheckOutController extends Controller
{


    // public function getTokensCheckInCheckOut(Request $request)
    // {
    //     $rules = [

    //         'is_checkedin' => 'sometimes',
    //         'is_checkedout' => 'sometimes',
    //         'token_id' => 'required',
    //         'is_reached' => 'sometimes',
    //     ];
    //     $validation = Validator::make($request->all(), $rules);
    //     if ($validation->fails()) {

    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }
    //     try {
    //         //////////////////////////////////checkin checkout details///////////////////////////
    //         $doctor_user_id = Auth::user()->id;
    //         $doctor_id = Docter::where('UserId', $doctor_user_id)->pluck('id')->first();
    //         if (!$doctor_id) {
    //             return response()->json(['message' => 'No  User Details Found'], 500);
    //         }
    //         $token_details = NewTokens::where('token_id', $request->token_id)
    //             ->first();
    //         if (!$token_details) {
    //             return response()->json(['message' => 'No Tokens Found'], 200);
    //         }

    //         $token_id = $request->token_id;
    //         /////////////////////////check in/////////////////////////////
    //         if ($token_details && $request->is_checkedin == 1) {

    //             $token_details->is_checkedin = 1;
    //             $token_details->checkin_time = now()->format('Y-m-d H:i:s');
    //             $token_details->update();
    //             $this->updateCheckinEstimateTimeCheckin($token_id);
    //         }

    //         /////////////////////////check out/////////////////////////////
    //         if ($token_details && $request->is_checkedout == 1) {
    //             $now = now()->format('Y-m-d H:i:s');

    //             $token_details->update([
    //                 'is_checkedout' => 1,
    //                 'checkout_time' => $now
    //             ]);

    //             $checkin_time = new DateTime($token_details->checkin_time);
    //             $checkout_time = new DateTime($now);
    //             $minutes_taken = $checkin_time->diff($checkout_time)->format('%i');
    //             $token_details->actual_token_duration = $minutes_taken;

    //             $assigned_duration = $token_details->assigned_token_duration;

    //             if ($assigned_duration > $minutes_taken) {
    //                 $token_details->less_time_taken = $assigned_duration - $minutes_taken;
    //             } elseif ($assigned_duration < $minutes_taken) {
    //                 $token_details->extra_time_taken = $minutes_taken - $assigned_duration;
    //             }

    //             $token_details->save();

    //             //update estimate time

    //             $this->updateCheckinEstimateTimeCheckout($token_id);

    //             ///add to completed appoitments

    //             $completed_appoitments = new CompletedAppointments();

    //             $completed_appoitments->clinic_id = $token_details->clinic_id ?? null;

    //             $completed_appoitments->doctor_id = $doctor_id ?? null;

    //             $completed_appoitments->token_number = $token_details->token_number ?? null;

    //             $completed_appoitments->date = $token_details->token_scheduled_date ?? null;

    //             $completed_appoitments->booked_user_id = $token_details->booked_user_id ?? null;

    //             $completed_appoitments->patient_id = $token_details->patient_id ?? null;

    //             $completed_appoitments->token_start_time = $token_details->token_start_time ?? null;

    //             $completed_appoitments->booking_time = $token_details->token_booked_time ?? null;

    //             $completed_appoitments->check_in_time = $token_details->checkin_time ?? null;

    //             $completed_appoitments->checkout_time = $token_details->checkout_time ?? null;

    //             $token_booking_data = TokenBooking::where('new_token_id', $request->token_id)->first();

    //             $completed_appoitments->symptom_start_time = $token_booking_data->whenitstart ?? null;

    //             $completed_appoitments->symptom_frequency = $token_booking_data->whenitcomes ?? null;

    //             $completed_appoitments->medical_shop_id = $token_booking_data->medicalshop_id ?? null;

    //             $completed_appoitments->prescription_image = $token_booking_data->prescription_image ?? null;

    //             $completed_appoitments->schedule_type = $token_booking_data->schedule_type ?? null;

    //             $completed_appoitments->height = $token_booking_data->height ?? null;

    //             $completed_appoitments->weight = $token_booking_data->weight ?? null;

    //             $completed_appoitments->temperature = $token_booking_data->temperature ?? null;

    //             $completed_appoitments->spo2 = $token_booking_data->spo2 ?? null;

    //             $completed_appoitments->sys = $token_booking_data->sys ?? null;

    //             $completed_appoitments->dia = $token_booking_data->dia ?? null;

    //             $completed_appoitments->heart_rate = $token_booking_data->heart_rate ?? null;

    //             $completed_appoitments->temperature_type = $token_booking_data->temperature_type ?? null;

    //             $completed_appoitments->appointment_for = $token_booking_data->Appoinmentfor_id ?? null;

    //             $completed_appoitments->notes = $token_booking_data->notes ?? null;

    //             $completed_appoitments->review_after = $token_booking_data->ReviewAfter ?? null;

    //             $completed_appoitments->labtest = $token_booking_data->labtest ?? null;

    //             $completed_appoitments->lab_id = $token_booking_data->lab_id ?? null;

    //             $completed_appoitments->scan_id = $token_booking_data->scan_id ?? null;

    //             $completed_appoitments->scan_test = $token_booking_data->scan_test ?? null;

    //             $completed_appoitments->new_token_id = $token_booking_data->token_id ?? null;

    //             $completed_appoitments->medical_shop_id = $token_booking_data->medicalshop_id ?? null;

    //             $completed_appoitments->save();
    //         }
    //         ////////////////////calculate actual token duration//////////////////

    //         if ($request->is_checkedin == 1) {


    //             $actual_checkin_time = Carbon::parse($token_details->checkin_time);
    //             $estimated_checkin_time = Carbon::parse($token_details->token_start_time);

    //             //add late and early doc checki req change 23-03
    //             if ($actual_checkin_time < $estimated_checkin_time) {
    //                 $checkin_difference = $estimated_checkin_time->diffInMinutes($actual_checkin_time);
    //                 $token_details->checkin_difference = $checkin_difference;
    //                 $token_details->save();
    //             } else if ($actual_checkin_time > $estimated_checkin_time) {
    //                 $late_checkin_difference = $actual_checkin_time->diffInMinutes($estimated_checkin_time);
    //                 $token_details->late_checkin_duration = $late_checkin_difference;
    //                 $token_details->save();
    //             }
    //         }
    //         if ($request->is_checkedin == 1) {
    //             $first_token_in_schedule = NewTokens::where('doctor_id', $doctor_id)
    //                 ->where('clinic_id', $request->clinic_id)
    //                 ->where('token_scheduled_date', Carbon::now())
    //                 ->orderBy('token_start_time', 'asc')
    //                 ->first();

    //             if ($first_token_in_schedule && $first_token_in_schedule->token_number == $request->TokenNumber) {

    //                 $actual_checkin_time = Carbon::parse($first_token_in_schedule->checkin_time);
    //                 $estimated_checkin_time = Carbon::parse($first_token_in_schedule->token_start_time);

    //                 if ($actual_checkin_time < $estimated_checkin_time) {
    //                     $checkin_difference = $actual_checkin_time->diffInMinutes($estimated_checkin_time);
    //                     $first_token_in_schedule->checkin_difference = $checkin_difference;
    //                     $first_token_in_schedule->save();
    //                 }
    //             }
    //         }


    //         /////////// update TokenBooking checkin and checkout
    //         $token_booking_data = TokenBooking::where('new_token_id', $request->token_id)->first();
    //         $token_booking_data->update(['Is_checkIn' => $request->is_checkedin, 'Is_completed' => $request->is_checkedout]);

    //         if ($request->is_reached == 1) {
    //             $token_booking_data->is_reached = 1;
    //             $token_booking_data->save();
    //             return response()->json(['message' => 'Reached Successfully'], 200);
    //         }

    //         return response()->json(['message' => 'Token Checkin Checkout details updated successfully'], 200);
    //         ////////////////////////////////////////////////////////////////////////////////
    //     } catch (\Exception $e) {
    //         Log::error('Error in processing: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
    //     }
    // }


    public function getTokensCheckInCheckOut(Request $request)
    {

        $current_time = Carbon::now()->format('h:i:s A');
        Log::info('getTokensCheckInCheckOut' . $current_time);
        Log::info('getTokensCheckInCheckOut ' . $current_time);
        Log::info('getTokensCheckInCheckOut' . $current_time);
        Log::info('getTokensCheckInCheckOut ' . $current_time);


        $rules = [
            'clinic_id'   => 'required',
            'TokenNumber' => 'required',
            'is_checkedin' => 'sometimes',
            'is_checkedout' => 'sometimes',
            'doctor_user_id' => 'required',
            'is_reached' => 'sometimes',
        ];
        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            Log::channel('doctor_schedules')->info("Request Input validation error");
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            //////////////////////////////////checkin checkout details///////////////////////////
            $doctor_id = Docter::where('UserId', $request->doctor_user_id)->pluck('id')->first();
            if (!$doctor_id) {
                Log::channel('doctor_schedules')->info("No Doctor User Details Found");
                return response()->json(['message' => 'No Doctor User Details Found'], 500);
            }

            Log::info("req type  if chkin  $request->is_checkedin");
            Log::info("req type  if checkout  $request->is_checkedout");
            $currentDate = now()->toDateString();
            $token_details = NewTokens::where('doctor_id', $doctor_id)
                ->where('clinic_id', $request->clinic_id)
                ->where('token_number', $request->TokenNumber)
                ->where('token_scheduled_date', $currentDate)
                ->first();
            Log::channel('doctor_schedules')->info("Token -> $token_details");
            if (!$token_details) {
                Log::channel('doctor_schedules')->info("No Tokens Found");
                return response()->json(['message' => 'No Tokens Found'], 200);
            }
            /////////////////////////check in/////////////////////////////
            if ($token_details && $request->is_checkedin == 1) {
                Log::channel('doctor_schedules')->info("token_details->is_checkedin = $request->is_checkedin");
                $update_checkin_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_number', $request->TokenNumber)
                    ->where('token_scheduled_date', $currentDate)
                    ->first();

                $token_id = $update_checkin_details->token_id;
                $schedule_id = $update_checkin_details->schedule_id;
                $update_checkin_details->is_checkedin = 1;
                $update_checkin_details->checkin_time = now()->format('Y-m-d H:i:s');
                $update_checkin_details->update();
                //$this->updateCheckinEstimateTimeCheckin($token_id);

                $this->sendScheduleStartedPushNotification($schedule_id);
            }

            /////////////////////////check out/////////////////////////////
            if ($token_details && $request->is_checkedout == 1) {
                $update_checkout_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_number', $request->TokenNumber)
                    ->where('token_scheduled_date', $currentDate)
                    ->first();

                $token_id = $update_checkout_details->token_id;

                $update_checkout_details->is_checkedout = 1;
                $update_checkout_details->checkout_time = now()->format('Y-m-d H:i:s');
                $update_checkout_details->update();
            }

            ////////////////////calculate actual token duration//////////////////
            if ($request->is_checkedout == 1) {
                $checkin_time = new DateTime($token_details->checkin_time);
                $checkout_time = new DateTime($token_details->checkout_time);
                $minutes_taken = $checkin_time->diff($checkout_time)->format('%i');
                $update_estimate_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->where('token_number', $request->TokenNumber)
                    ->first();

                $update_estimate_details->actual_token_duration = $minutes_taken;
                $update_estimate_details->save();
            }
            if ($request->is_checkedout == 1) {
                $update_estimate_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->where('token_number', $request->TokenNumber)
                    ->first();


                $actual_duration =  $update_estimate_details->actual_token_duration;
                $assigned_duration = $update_estimate_details->assigned_token_duration;

                if ($assigned_duration > $actual_duration) {
                    $less_time_taken = $assigned_duration - $actual_duration;
                    $update_estimate_details->less_time_taken = $less_time_taken;
                }

                if ($assigned_duration < $actual_duration) {
                    $extra_time_taken = $actual_duration - $assigned_duration;
                    $update_estimate_details->extra_time_taken = $extra_time_taken;
                }

                $update_estimate_details->save();
            }
            if ($request->is_checkedin == 1) {
                $update_estimate_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->where('token_number', $request->TokenNumber)
                    ->first();

                $actual_checkin_time = Carbon::parse($update_estimate_details->checkin_time);
                $estimated_checkin_time = Carbon::parse($update_estimate_details->token_start_time);

                //add late and early doc checki req change 23-03
                if ($actual_checkin_time < $estimated_checkin_time) {
                    $checkin_difference = $estimated_checkin_time->diffInMinutes($actual_checkin_time);
                    $update_estimate_details->checkin_difference = $checkin_difference;
                    $update_estimate_details->save();
                } else if ($actual_checkin_time > $estimated_checkin_time) {
                    $late_checkin_difference = $actual_checkin_time->diffInMinutes($estimated_checkin_time);
                    $update_estimate_details->late_checkin_duration = $late_checkin_difference;
                    $update_estimate_details->save();
                }
            }
            if ($request->is_checkedin == 1) {
                $first_token_in_schedule = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->orderBy('token_start_time', 'asc')
                    ->first();

                if ($first_token_in_schedule && $first_token_in_schedule->token_number == $request->TokenNumber) {


                    $actual_checkin_time = Carbon::parse($first_token_in_schedule->checkin_time);
                    $estimated_checkin_time = Carbon::parse($first_token_in_schedule->token_start_time);

                    if ($actual_checkin_time < $estimated_checkin_time) {
                        $checkin_difference = $actual_checkin_time->diffInMinutes($estimated_checkin_time);
                        $first_token_in_schedule->checkin_difference = $checkin_difference;
                        $first_token_in_schedule->save();
                    }
                }
            }


            /////////// update TokenBooking checkin and checkout
            if ($request->is_checkedin == 1) {
                $update_estimate_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->where('token_number', $request->TokenNumber)
                    ->first();

                $token_id = $update_estimate_details->token_id;

                $token_booking_data = TokenBooking::where('new_token_id', $token_id)->first();
                $token_booking_data->Is_checkIn = 1;
                // $token_booking_data->Is_completed = 0;
                $token_booking_data->save();
            }
            if ($request->is_checkedout == 1) {
                $update_estimate_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->where('token_number', $request->TokenNumber)
                    ->first();

                $token_id = $update_estimate_details->token_id;

                $token_booking_data = TokenBooking::where('new_token_id', $token_id)->first();
                $token_booking_data->Is_completed = 1;
                // $token_booking_data->Is_completed = 0;
                $token_booking_data->save();
            }


            if ($request->is_reached == 1) {
                $update_estimate_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_scheduled_date', $currentDate)
                    ->where('token_number', $request->TokenNumber)
                    ->first();

                $token_id = $update_estimate_details->token_id;

                $token_booking_data = TokenBooking::where('new_token_id', $token_id)->first();
                $token_booking_data->is_reached = 1;
                // $token_booking_data->Is_completed = 0;
                $token_booking_data->save();
                return response()->json(['message' => 'Reached Successfully'], 200);
            }

            if ($request->is_checkedout == 1) {
                $update_checkout_details = NewTokens::where('doctor_id', $doctor_id)
                    ->where('clinic_id', $request->clinic_id)
                    ->where('token_number', $request->TokenNumber)
                    ->where('token_scheduled_date', $currentDate)
                    ->first();

                $token_id = $update_checkout_details->token_id;

                ///add to completed appoitments

                $completed_appoitments = new CompletedAppointments();

                $completed_appoitments->clinic_id = $request->clinic_id ?? null;

                $completed_appoitments->doctor_id = $doctor_id ?? null;

                $completed_appoitments->token_number = $request->TokenNumber ?? null;

                $completed_appoitments->date = $currentDate ?? null;

                $completed_appoitments->booked_user_id = $update_checkout_details->booked_user_id ?? null;
                $completed_appoitments->patient_id = $update_checkout_details->patient_id ?? null;

                $completed_appoitments->token_start_time = $update_checkout_details->token_start_time ?? null;
                $completed_appoitments->booking_time = $update_checkout_details->booking_time ?? null;
                $completed_appoitments->check_in_time = $update_checkout_details->checkin_time ?? null;
                $completed_appoitments->checkout_time = $update_checkout_details->checkout_time ?? null;

                $token_booking_data = TokenBooking::where('new_token_id', $update_checkout_details->token_id)->first();

                $completed_appoitments->symptom_start_time = $token_booking_data->whenitstart;

                $completed_appoitments->symptom_frequency = $token_booking_data->whenitcomes;

                $completed_appoitments->medical_shop_id = $token_booking_data->medicalshop_id;

                $completed_appoitments->prescription_image = $token_booking_data->prescription_image;

                $completed_appoitments->schedule_type = $token_booking_data->schedule_type;

                $completed_appoitments->height = $token_booking_data->height;

                $completed_appoitments->weight = $token_booking_data->weight;

                $completed_appoitments->temperature = $token_booking_data->temperature;

                $completed_appoitments->spo2 = $token_booking_data->spo2;

                $completed_appoitments->sys = $token_booking_data->sys;

                $completed_appoitments->dia = $token_booking_data->dia;

                $completed_appoitments->heart_rate = $token_booking_data->heart_rate;

                $completed_appoitments->temperature_type = $token_booking_data->temperature_type;

                $completed_appoitments->appointment_for = $token_booking_data->Appoinmentfor_id;

                $completed_appoitments->notes = $token_booking_data->notes;

                $completed_appoitments->review_after = $token_booking_data->ReviewAfter;

                $completed_appoitments->labtest = $token_booking_data->labtest;

                $completed_appoitments->lab_id = $token_booking_data->lab_id;

                $completed_appoitments->scan_id = $token_booking_data->scan_id;

                $completed_appoitments->scan_test = $token_booking_data->scan_test;

                $completed_appoitments->new_token_id = $token_booking_data->token_id;

                $completed_appoitments->medical_shop_id = $token_booking_data->medicalshop_id;

                $completed_appoitments->save();


                try {
                    // $userId = Auth::user()->id;
                    $userr_id = $update_checkout_details->booked_user_id;
                    $userIds = [$userr_id];
                    $title = "Appointment Completed";
                    $doctor_name = Docter::select('firstname', 'lastname')->where('id', $doctor_id)->first();
                    $doctor_name = $doctor_name->firstname . " " . $doctor_name->lastname;
                    $message = "The appointment you scheduled with Dr. $doctor_name has been completed.";
                    $type = "appointment-completed-alert";

                    $notificationHelper = new PushNotificationHelper();
                    $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);
                } catch (\Exception $e) {
                    Log::error('Push notification error: ' . $e->getMessage());
                }
            }
            $response = ($request->is_checkedin == 1)
                ? 'Token checked in'
                : (($request->is_checkedout == 1)
                    ? 'Token checked out'
                    : '');


            return response()->json(['message' => $response], 200);

            ////////////////////////////////////////////////////////////////////////////////
        } catch (\Exception $e) {
            Log::error('Error in processing: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
    public function updateCheckinEstimateTimeCheckin($token_id) //checkin
    {
        try {
            $current_token_data = NewTokens::where('token_id', $token_id)->first();

            if (isset($current_token_data)) {
                $schedule_id = $current_token_data ? $current_token_data->schedule_id : NULL;
                $permanent_checkin_time = Carbon::parse($current_token_data->token_start_time);
                $current_checkin_time = Carbon::now();

                if ($permanent_checkin_time > $current_checkin_time) {
                    $checkin_difference = $permanent_checkin_time->diffInMinutes($current_checkin_time);
                    $estimated_time_of_next_patient = $permanent_checkin_time->addMinutes($current_token_data->assigned_token_duration);
                    $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes(20);
                    $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes($checkin_difference);
                } else {
                    $checkin_difference = $current_checkin_time->diffInMinutes($permanent_checkin_time);
                    $tokenStartTime = Carbon::parse($current_token_data->token_start_time);
                    $estimated_time_of_next_patient = $tokenStartTime->addMinutes($current_token_data->assigned_token_duration);
                    $estimated_time_of_next_patient = $estimated_time_of_next_patient->subMinutes(20);
                    $estimated_time_of_next_patient = $estimated_time_of_next_patient->addMinutes($checkin_difference);
                }

                $next_token = NewTokens::where('schedule_id', $schedule_id)
                    ->where('token_booking_status', 1)
                    ->where('is_checkedout', 0)
                    ->where('is_checkedin', 0)
                    ->orderBy('token_number', 'asc')
                    ->first();


                if ($next_token) {
                    $next_token->estimate_checkin_time = $estimated_time_of_next_patient;
                    $next_token->update();

                    $next_token_number = $next_token->token_number;

                    $upcoming_tokens = NewTokens::where('schedule_id', $schedule_id)
                        ->where('token_booking_status', 1)
                        ->where('is_checkedin', 0)
                        ->where('is_checkedout', 0)
                        ->where('token_number', '>', $next_token_number)
                        ->orderBy('token_number', 'asc')
                        ->get();

                    $each_token_duration = $current_token_data->assigned_token_duration;

                    $break = DoctorReschedule::where('schedule_id', $schedule_id)
                        ->where('reschedule_type', 3)
                        ->first();

                    foreach ($upcoming_tokens as $key => $upcoming_token) {
                        $total_duration = ($key + 1) * $each_token_duration;
                        $upcoming_token->estimate_checkin_time = $estimated_time_of_next_patient->copy()->addMinutes($total_duration);
                        $upcoming_token->update();

                        if ($break) {
                            $break_start_time = Carbon::parse($break->reschedule_start_datetime)->format('H:i');
                            $break_start_date = Carbon::parse($break->reschedule_start_datetime)->format('Y-m-d');
                            $break_end_date = Carbon::parse($break->reschedule_end_datetime)->format('Y-m-d');
                            $upcomings_start_time = Carbon::parse($upcoming_token->token_start_time)->format('H:i');
                            $next_token_start_time = Carbon::parse($next_token->token_start_time)->format('H:i');

                            $current_token_scheduled_date = Carbon::parse($current_token_data->token_scheduled_date);

                            while ($current_token_scheduled_date->between($break_start_date, $break_end_date) && $upcomings_start_time > $break_start_time) {
                                $upcoming_token->estimate_checkin_time = Carbon::parse($upcoming_token->token_start_time)->subMinutes(20)->format('Y-m-d H:i:s');
                                $upcoming_token->save();
                                if ($next_token_start_time > $upcomings_start_time) {
                                    $next_token->estimate_checkin_time = Carbon::parse($next_token->token_start_time)
                                        ->subMinutes(20)
                                        ->format('Y-m-d H:i:s');
                                    $next_token->save();
                                }
                            }
                        }
                    }
                } else {
                    Log::info('No next token found for schedule_id: ' . $schedule_id);
                }
            } else {
                Log::info('No current token data found for token_id: ' . $token_id);
            }
        } catch (Exception $e) {
            Log::error('Error in processing: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }

    // public function updateCheckinEstimateTimeCheckin($token_id) //checkin
    // {

    //     try {

    //         $current_token_data = NewTokens::where('token_id', $token_id)
    //             ->first();

    //         if (isset($current_token_data)) {

    //             $schedule_id = $current_token_data ? $current_token_data->schedule_id : NULL;
    //             $permanent_checkin_time = carbon::parse($current_token_data->token_start_time);
    //             $current_checkin_time = Carbon::now();

    //             if ($permanent_checkin_time > $current_checkin_time) {


    //                 $checkin_difference = $permanent_checkin_time->diffInMinutes($current_checkin_time);
    //                 $estimated_time_of_next_patient =  $permanent_checkin_time->addMinutes($current_token_data->assigned_token_duration);
    //                 $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes(20);
    //                 $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes($checkin_difference);
    //             }

    //             if ($current_checkin_time > $permanent_checkin_time) {

    //                 $checkin_difference = $current_checkin_time->diffInMinutes($permanent_checkin_time);
    //                 $tokenStartTime = Carbon::parse($current_token_data->token_start_time);
    //                 $estimated_time_of_next_patient = $tokenStartTime->addMinutes($current_token_data->assigned_token_duration);
    //                 $estimated_time_of_next_patient = $estimated_time_of_next_patient->subMinutes(20);
    //                 $estimated_time_of_next_patient = $estimated_time_of_next_patient->addMinutes($checkin_difference);
    //             }

    //             $next_token = NewTokens::where('schedule_id', $schedule_id)
    //                 ->where('token_booking_status', 1)
    //                 ->where('is_checkedout', 0)
    //                 ->where('is_checkedin', 0)
    //                 ->orderBy('token_number', 'asc')
    //                 ->first();

    //             $next_token->estimate_checkin_time = $estimated_time_of_next_patient;
    //             $next_token->update();

    //             $next_token_number = $next_token ? $next_token->token_number : NULL;

    //             if ($next_token) {
    //                 $upcoming_tokens = NewTokens::where('schedule_id', $schedule_id)
    //                     ->where('token_booking_status', 1)
    //                     ->where('is_checkedin', 0)
    //                     ->where('is_checkedout', 0)
    //                     ->where('token_number', '>', $next_token_number)
    //                     ->orderBy('token_number', 'asc')
    //                     ->get();

    //                 $each_token_duration = $current_token_data->assigned_token_duration;

    //                 $break = DoctorReschedule::where('schedule_id', $schedule_id)
    //                     ->where('reschedule_type', 3)
    //                     ->first();

    //                 if (isset($upcoming_tokens)) {
    //                     foreach ($upcoming_tokens as $key => $upcoming_token) {
    //                         $total_duration = ($key + 1) * $each_token_duration;
    //                         $upcoming_token->estimate_checkin_time = $estimated_time_of_next_patient->copy()->addMinutes($total_duration);
    //                         $upcoming_token->update();
    //                         if (isset($break)) {
    //                             $break_start_time = Carbon::parse($break->reschedule_start_datetime)->format('H:i');
    //                             $break_start_date = Carbon::parse($break->reschedule_start_datetime)->format('Y-m-d');
    //                             $break_end_date = Carbon::parse($break->reschedule_end_datetime)->format('Y-m-d');
    //                             $upcomings_start_time = Carbon::parse($upcoming_token->token_start_time)->format('H:i');


    //                             $current_token_scheduled_date = Carbon::parse($current_token_data->token_scheduled_date);

    //                             while ($current_token_scheduled_date->between($break_start_date, $break_end_date) && $upcomings_start_time > $break_start_time) {
    //                                 $upcoming_token->estimate_checkin_time = Carbon::parse($upcoming_token->token_start_time)->subMinutes(20)->format('Y-m-d H:i:s');
    //                                 $upcoming_token->save();
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }
    //     } catch (Exception $e) {
    //         Log::error('Error in processing: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
    //     }
    // }

    // public function updateCheckinEstimateTimeCheckin($token_id) //checkin
    // {

    //     try {

    //         $current_token_data = NewTokens::where('token_id', $token_id)
    //             ->first();

    //         if (isset($current_token_data)) {

    //             $schedule_id = $current_token_data ? $current_token_data->schedule_id : NULL;
    //             $permanent_checkin_time = carbon::parse($current_token_data->token_start_time);
    //             $current_checkin_time = Carbon::now();

    //             if ($permanent_checkin_time > $current_checkin_time) {

    //                 $checkin_difference = $permanent_checkin_time->diffInMinutes($current_checkin_time);
    //                 $estimated_time_of_next_patient =  $permanent_checkin_time->addMinutes($current_token_data->assigned_token_duration);
    //                 $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes(20);
    //                 $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes($checkin_difference);
    //             }

    //             if ($current_checkin_time > $permanent_checkin_time) {

    //                 $checkin_difference = $current_checkin_time->diffInMinutes($permanent_checkin_time);
    //                 $tokenStartTime = Carbon::parse($current_token_data->token_start_time);
    //                 $estimated_time_of_next_patient = $tokenStartTime->addMinutes($current_token_data->assigned_token_duration);
    //                 $estimated_time_of_next_patient = $estimated_time_of_next_patient->subMinutes(20);
    //                 $estimated_time_of_next_patient = $estimated_time_of_next_patient->addMinutes($checkin_difference);
    //             }


    //             ///////////////////////////////////////////////////////////////
    //             $data = NewTokens::where('schedule_id', $schedule_id)->first();
    //             $clinic_id = $data->clinic_id;
    //             $doctor_id = $data->doctor_id;
    //             Log::info('Estimate time clinic_id: ' . $clinic_id);
    //             $current_date = Carbon::today()->toDateString();
    //             Log::info('Estimate time current_date: ' . $current_date);
    //             $break_data = DoctorReschedule::where('doctor_id', $doctor_id)
    //                 ->where('clinic_id', $clinic_id)
    //                 ->where('reschedule_type', 3)
    //                 ->whereDate('reschedule_start_datetime', '<=', $current_date)
    //                 ->whereDate('reschedule_end_datetime', '>=', $current_date)
    //                 ->get();
    //             $break_sum_duration = 0;
    //             if ($break_data) {
    //                 Log::info('Estimate time break_data: exists' . $break_data);
    //                 foreach ($break_data as $b_d) {
    //                     Log::info('for each------------');
    //                     $break_duration = $b_d->reschedule_duration;
    //                     Log::info('Estimate time break_duration: ' . $break_duration);
    //                     $break_sum_duration = $break_sum_duration + $break_duration;
    //                     Log::info('Estimate time break_duration: ' . $break_sum_duration);
    //                 }
    //             }
    //             ////////////////////////////////////////////////////////////////

    //             $next_token = NewTokens::where('schedule_id', $schedule_id)
    //                 ->where('token_booking_status', 1)
    //                 ->where('is_checkedout', 0)
    //                 ->where('is_checkedin', 0)
    //                 ->orderBy('token_number', 'asc')
    //                 ->first();

    //             if ($next_token && isset($estimated_time_of_next_patient) && $estimated_time_of_next_patient) {
    //                 $next_patient_ET = $estimated_time_of_next_patient->copy()->addMinutes($break_sum_duration);

    //                 $next_token->estimate_checkin_time = $next_patient_ET;
    //                 $next_token->update();

    //                 $next_token_number = $next_token->token_number;
    //             } else {
    //                 $next_token_number = null;
    //             }

    //             $upcoming_tokens = NewTokens::where('schedule_id', $schedule_id)
    //                 ->where('token_booking_status', 1)
    //                 ->where('is_checkedin', 0)
    //                 ->where('is_checkedout', 0)
    //                 ->where('token_number', '>', $next_token_number)
    //                 ->orderBy('token_number', 'asc')
    //                 ->get();

    //             $each_token_duration = isset($current_token_data->assigned_token_duration) ? $current_token_data->assigned_token_duration : 0;

    //             if ($upcoming_tokens && isset($estimated_time_of_next_patient) && $estimated_time_of_next_patient) {
    //                 foreach ($upcoming_tokens as $key => $upcoming_token) {
    //                     $total_duration = ($key + 1) * $each_token_duration;
    //                     $estimate_checkin_time = $estimated_time_of_next_patient->copy()->addMinutes($total_duration);

    //                     $estimate_checkin_time = Carbon::parse($estimate_checkin_time)->addMinutes($break_sum_duration);
    //                     $upcoming_token->estimate_checkin_time = $estimate_checkin_time;
    //                     $upcoming_token->update();
    //                 }
    //             }
    //         }
    //     } catch (Exception $e) {
    //         Log::error('Error in processing: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
    //     }
    // }
    // public function updateCheckinEstimateTimeCheckin($token_id) //checkin
    // {
    //     try {
    //         Log::info('Starting updateCheckinEstimateTimeCheckin function', ['token_id' => $token_id]);

    //         $current_token_data = NewTokens::where('token_id', $token_id)->first();
    //         Log::info('Retrieved current token data', ['current_token_data' => $current_token_data]);

    //         if (isset($current_token_data)) {

    //             $schedule_id = $current_token_data ? $current_token_data->schedule_id : NULL;
    //             $permanent_checkin_time = carbon::parse($current_token_data->token_start_time);
    //             $current_checkin_time = Carbon::now();

    //             Log::info('Parsed times', [
    //                 'permanent_checkin_time' => $permanent_checkin_time,
    //                 'current_checkin_time' => $current_checkin_time
    //             ]);

    //             if ($permanent_checkin_time > $current_checkin_time) {
    //                 $checkin_difference = $permanent_checkin_time->diffInMinutes($current_checkin_time);
    //                 $estimated_time_of_next_patient =  $permanent_checkin_time->addMinutes($current_token_data->assigned_token_duration);
    //                 $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes(20);
    //                 $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes($checkin_difference);

    //                 Log::info('Calculated estimated time for next patient (future checkin)', [
    //                     'estimated_time_of_next_patient' => $estimated_time_of_next_patient,
    //                     'checkin_difference' => $checkin_difference
    //                 ]);
    //             }

    //             if ($current_checkin_time > $permanent_checkin_time) {
    //                 $checkin_difference = $current_checkin_time->diffInMinutes($permanent_checkin_time);
    //                 $tokenStartTime = Carbon::parse($current_token_data->token_start_time);
    //                 $estimated_time_of_next_patient = $tokenStartTime->addMinutes($current_token_data->assigned_token_duration);
    //                 $estimated_time_of_next_patient = $estimated_time_of_next_patient->subMinutes(20);
    //                 $estimated_time_of_next_patient = $estimated_time_of_next_patient->addMinutes($checkin_difference);

    //                 Log::info('Calculated estimated time for next patient (past checkin)', [
    //                     'estimated_time_of_next_patient' => $estimated_time_of_next_patient,
    //                     'checkin_difference' => $checkin_difference
    //                 ]);
    //             }

    //             $next_token = NewTokens::where('schedule_id', $schedule_id)
    //                 ->where('token_booking_status', 1)
    //                 ->where('is_checkedout', 0)
    //                 ->where('is_checkedin', 0)
    //                 ->orderBy('token_number', 'asc')
    //                 ->first();

    //             Log::info('Next token : ', [
    //                 'estimated_time_of_next_patient' => $estimated_time_of_next_patient,
    //                 'next_token->token_number ' => $next_token->token_number
    //             ]);

    //             $next_token->estimate_checkin_time = $estimated_time_of_next_patient;

    //             $next_token->update();

    //             Log::info('Updated next token estimate checkin time', ['next_token' => $next_token]);

    //             $next_token_number = $next_token ? $next_token->token_number : NULL;

    //             $n_t_actual_estimate_time = Carbon::parse($next_token->token_start_time)->subMinutes(20);

    //             $n_t_estimate_checkin_time = Carbon::parse($next_token->estimate_checkin_time);

    //             $n_t_checkin_difference = $n_t_actual_estimate_time->diffInMinutes($n_t_estimate_checkin_time, true);


    //             Log::info('Calculated checkin difference for next token', [
    //                 'n_t_checkin_difference' => $n_t_checkin_difference
    //             ]);

    //             $upcoming_tokens = NewTokens::where('schedule_id', $next_token->schedule_id)
    //                 ->where('token_booking_status', 1)
    //                 ->where('is_checkedin', 0)
    //                 ->where('is_checkedout', 0)
    //                 ->where('token_number', '>', $next_token_number)
    //                 ->orderBy('token_number', 'asc')
    //                 ->get();

    //             if ($n_t_checkin_difference != $checkin_difference) {

    //                 $skipped_data = NewTokens::where('schedule_id', $schedule_id)
    //                     ->where('token_scheduled_date', $current_token_data->token_scheduled_date)
    //                     ->orderBy('token_number', 'asc')
    //                     ->first();

    //                 if ($skipped_data) {
    //                     $skipped_data->token_skipped_queue++;
    //                     $skipped_data->save();
    //                 }

    //                 Log::info('n_t_checkin_difference != $checkin_difference', ['new_estimate_time' => $skipped_data]);
    //                 $final_token_skipped_count = $skipped_data->token_skipped_queue;

    //                 $minutes  = $final_token_skipped_count * $current_token_data->assigned_token_duration;
    //             } else {
    //                 $minutes = 0;
    //             }


    //             foreach ($upcoming_tokens as $token) {

    //                 $current_estimate_time = Carbon::parse($token->estimate_checkin_time);

    //                 Log::info('Current estimate time ', ['current_estimate_time' => $current_estimate_time]);

    //                 $permanent_estimate_time = Carbon::parse($token->token_start_time)->subMinutes(20);

    //                 Log::info('permanent_estimate_time', ['permanent_estimate_time' => $permanent_estimate_time]);



    //                 if ($n_t_checkin_difference < 0) {

    //                     $new_estimate_time = $permanent_estimate_time->addMinutes(abs($n_t_checkin_difference));

    //                     Log::info('n_t_checkin_difference < 0', ['new_estimate_time' => $new_estimate_time]);
    //                 } else {


    //                     $new_estimate_time = $permanent_estimate_time->subMinutes($n_t_checkin_difference);

    //                     Log::info('n_t_checkin_difference > 0', ['new_estimate_time' => $new_estimate_time]);
    //                 }

    //                 // $final_estimate = $new_estimate_time->subMinutes($minutes);
    //                 $final_estimate = $new_estimate_time;

    //                 /////////////////////////////////////////////////////////////////////////

    //                 $token->estimate_checkin_time = $final_estimate;;

    //                 $token->save();

    //                 Log::info('Updated upcoming token estimate checkin time', [
    //                     'token' => $token->token_number,
    //                     'new_estimate_time' => $final_estimate
    //                 ]);
    //             }
    //         }
    //     } catch (Exception $e) {
    //         Log::error('Error in processing: ' . $e->getMessage(), [
    //             'exception' => $e
    //         ]);
    //     }
    // }

    public function sendScheduleStartedPushNotification($schedule_id)
    {

        try {
            $today = Carbon::today()->toDateString();

            $tokens = NewTokens::where('schedule_id', $schedule_id)->where('token_scheduled_date', $today)->get();

            $checkedin_tokens = $tokens->where('is_checkedin', 1);

            $update_tokens = $tokens->where('is_checkedin', 0);

            if ($checkedin_tokens->count() == 1) {

                foreach ($update_tokens as $token) {

                    $user_id = $token->booked_user_id;
                    $userIds = [$user_id];

                    $token_number = $token->token_number;

                    $time = Carbon::parse($token->estimate_checkin_time)->format('h:i A');

                    $title = "Schedule Started: You are in Queue";

                    $message = "Your token number $token_number is scheduled to start at $time. Please be prepared.";

                    $type = "schedule-started-alert";

                    $notificationHelper = new PushNotificationHelper();

                    $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);
                }

                return true;
            } else {
            }
        } catch (\Exception $e) {
            Log::error('Error in processing: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
