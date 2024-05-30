<?php

namespace App\Http\Controllers\API\UserTokensETA;

use App\Http\Controllers\Controller;
use App\Models\DoctorReschedule;
use App\Models\NewTokens;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserTokensETAController extends Controller
{
    public function updateEstimateTimeIfCheckin(Request $request)
    {
        $rules = [
            'token_id' => 'required|integer'
        ];

        $messages = [
            'token_id.required' => 'Token ID is required',
            'token_id.integer' => 'Token ID must be an integer'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $token_id = $request->token_id;

        // try {
        $current_token_data = NewTokens::where('token_id', $token_id)->first();

        Log::info("current token number for checkin is: " . $current_token_data->token_number);

        if (isset($current_token_data)) {
            $schedule_id = $current_token_data ? $current_token_data->schedule_id : NULL;
            $permanent_checkin_time = Carbon::parse($current_token_data->token_start_time);
            Log::info("permanent checkin time " . $permanent_checkin_time);

            $current_checkin_time = Carbon::now();

            if ($permanent_checkin_time > $current_checkin_time) {

                Log::info("permanent checkin time is greater than the current checkin time ");
                $checkin_difference = $permanent_checkin_time->diffInMinutes($current_checkin_time);
                Log::info("checkin difference is $checkin_difference");
                $estimated_time_of_next_patient = $permanent_checkin_time->addMinutes($current_token_data->assigned_token_duration);
                Log::info("estimated_time_of_next_patient after adding token duration: " . $estimated_time_of_next_patient);
                $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes(20);
                Log::info("estimated_time_of_next_patient after substarcting 20 mins: " . $estimated_time_of_next_patient);
                $estimated_time_of_next_patient = Carbon::parse($estimated_time_of_next_patient)->subMinutes($checkin_difference);
                Log::info("estimated_time_of_next_patient after substarcting checkin difference: " . $estimated_time_of_next_patient);
            } else {
                Log::info("permanent checkin time is less than the current checkin time ");
                $checkin_difference = $current_checkin_time->diffInMinutes($permanent_checkin_time);
                Log::info("checkin difference is $checkin_difference");
                $tokenStartTime = Carbon::parse($current_token_data->token_start_time);
                $estimated_time_of_next_patient = $tokenStartTime->addMinutes($current_token_data->assigned_token_duration);
                Log::info("estimated_time_of_next_patient after adding token duration: " . $estimated_time_of_next_patient);
                $estimated_time_of_next_patient = $estimated_time_of_next_patient->subMinutes(20);
                Log::info("estimated_time_of_next_patient after substarcting 20 mins: " . $estimated_time_of_next_patient);
                $estimated_time_of_next_patient = $estimated_time_of_next_patient->addMinutes($checkin_difference);
                Log::info("estimated_time_of_next_patient after substarcting checkin difference: " . $estimated_time_of_next_patient);
            }

            $next_token = NewTokens::where('schedule_id', $schedule_id)
                ->where('token_booking_status', 1)
                ->where('is_checkedout', 0)
                ->where('is_checkedin', 0)
                ->orderBy('token_number', 'asc')
                ->first();


            if ($next_token) {

                Log::info("next token exists -> next token number is " . $next_token->token_number);
                $next_token->estimate_checkin_time = $estimated_time_of_next_patient;
                $next_token->update();
                Log::info("next token exists -> next token estimate time " . $estimated_time_of_next_patient);

                $next_token_number = $next_token->token_number;
                $current_date = Carbon::now()->format('Y-m-d');
                $upcoming_tokens = NewTokens::where('schedule_id', $schedule_id)
                    ->where('token_booking_status', 1)
                    ->whereDate('token_scheduled_date', $current_date)
                    ->where('is_checkedin', 0)
                    ->where('is_checkedout', 0)
                    ->where('token_number', '>', $next_token_number)
                    ->orderBy('token_number', 'asc')
                    ->get();

                $each_token_duration = $current_token_data->assigned_token_duration;

                Log::info("each token duration of upcoming tokens" . $each_token_duration);
                $current_time = Carbon::now()->format('H:i:s');

                foreach ($upcoming_tokens as $key => $upcoming_token) {
                    $total_duration = ($key + 1) * $each_token_duration;
                    $upcoming_token->estimate_checkin_time = $estimated_time_of_next_patient->copy()->addMinutes($total_duration);
                    Log::info("Upcoming tokens loop started");
                    Log::info("upcoming token number in loop" . $upcoming_token->token_number);
                    Log::info("estimate time" . $upcoming_token->estimate_checkin_time);
                    $upcoming_token->update();
                }


                $current_date_time = Carbon::now()->format('H:i:s');

                $break = DoctorReschedule::where('schedule_id', $schedule_id)
                    ->where('reschedule_type', 3)
                    // ->where('reschedule_start_datetime', '>', $current_date_time)
                    ->whereTime('break_start_time', '>', $current_date_time)
                    ->whereDate('reschedule_start_datetime', $current_date)
                    ->whereTime('break_start_time', '>', $current_time)
                    ->get();

                // $existing_checkout = NewTokens::where('schedule_id', $schedule_id)
                //     ->whereDate('token_scheduled_date', $current_date)
                //     ->where('token_booking_status', 1)
                //     ->where('is_checkedout', 0)
                //     ->orderBy('token_number', 'asc')
                //     ->whereDate('')


                if ($break) {

                    Log::info(" break exists");
                    foreach ($break as $key => $brk) {

                        $break_start_time = Carbon::parse($brk->reschedule_start_datetime)->format('H:i');

                        Log::info('break_start_time' . $break_start_time);

                        $next_token_s_t = NewTokens::where('schedule_id', $schedule_id)
                            ->where('token_booking_status', 1)
                            ->whereDate('token_scheduled_date', $current_date)
                            ->whereTime('token_start_time', '>', $break_start_time)
                            ->first();


                        $token_after_break = NewTokens::where('schedule_id', $schedule_id)
                            ->where('token_booking_status', 1)
                            ->where('is_checkedout', 0)
                            ->where('is_checkedin', 0)
                            ->whereDate('token_scheduled_date', $current_date)
                            ->whereTime('token_start_time', '>', $break_start_time)
                            ->orderBy('token_number', 'asc')
                            ->first();



                        if ($token_after_break) {

                            Log::info("token number after break is " . $token_after_break->token_number);
                            $token_after_break->estimate_checkin_time = Carbon::parse($next_token_s_t->token_start_time)
                                ->subMinutes(20)
                                ->format('Y-m-d H:i:s');
                            Log::info("token number after break estimate" . $token_after_break->estimate_checkin_time);
                            $token_after_break->update();
                            $estimate_time_of_token_after_brk = Carbon::parse($token_after_break->estimate_checkin_time);
                        }

                        $tokens_after_break = NewTokens::where('schedule_id', $schedule_id)
                            ->where('token_booking_status', 1)
                            ->where('is_checkedout', 0)
                            ->where('is_checkedin', 0)
                            ->where('token_number', '>', $token_after_break->token_number)
                            ->whereDate('token_scheduled_date', $current_date)
                            ->whereTime('token_start_time', '>', $break_start_time)
                            ->orderBy('token_number', 'asc')
                            ->get();

                        if ($tokens_after_break) {

                            foreach ($tokens_after_break as $key => $brk_tokens) {

                                $total_duration = ($key + 1) * $each_token_duration;
                                $brk_tokens->estimate_checkin_time = $estimate_time_of_token_after_brk->copy()->addMinutes($total_duration);
                                $brk_tokens->update();
                            }
                        }
                    }
                }

                return response()->json(['message' => 'Estimated time updated successfully'], 200);
            } else {
                return response()->json(['message' => 'Estimated time updated successfully'], 200);
            }
        } else {
            return response()->json(['message' => 'token not found'], 200);
        }
        // } catch (\Exception $e) {
        //     return response()->json(['message' => $e->getMessage()], 500);
        // }
    }
}
