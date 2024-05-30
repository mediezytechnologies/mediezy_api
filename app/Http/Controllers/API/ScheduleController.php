<?php

namespace App\Http\Controllers\API;

use App\Helpers\PushNotificationHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Docter;
use App\Models\schedule;
use App\Models\DocterLeave;
use App\Models\DoctorReschedule;
use App\Models\MainSymptom;
use App\Models\Medicine;
use App\Models\NewDoctorSchedule;
use App\Models\NewTokens;
use App\Models\RescheduleTokens;
use App\Models\TokenBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DateInterval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleController extends BaseController
{


    public function deleteAllAppointments(Request $request)
    {
        // Validate the request parameters
        $validatedData = $request->validate([
            'secret_key' => 'required|string',
            'doctor_user_id' => 'required',
        ]);

        try {

            $doctorID = Docter::where('UserId', $request->doctor_user_id)->pluck('id')->first();

            if ($request->secret_key == 'FREAK_AKBER') {
                NewTokens::where('doctor_id', $doctorID)->withTrashed()->delete();
                TokenBooking::where('doctor_id', $request->doctor_user_id)->delete();
                NewDoctorSchedule::where('doctor_id', $doctorID)->delete();
                MainSymptom::where('doctor_id', $request->doctor_user_id)->delete();
                Medicine::where('docter_id', $request->doctor_user_id)->delete();
                // MainSymptom::truncate();
                // Medicine::truncate();
                DoctorReschedule::truncate();
                RescheduleTokens::truncate();

                return response()->json(['message' => 'All appointments deleted']);
            } else {
                // Return error message for invalid secret key
                return response()->json(['message' => 'Invalid Secret Key.']);
            }
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function generateTokenSchedule(Request $request)
    {
        Log::channel('doctor_schedules')->info("------------------------api=/schedule-------------------------------");
        /////////////////////validate req////////////////////////
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|integer',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'start_date' => 'required',
            'end_date' => 'required|date|after_or_equal:start_date',
            // 'end_date' => 'required|after:start_date',
            'each_token_duration' => 'required',
            'selected_days' => 'required',
            'clinic_id' => 'required',
            'schedule_type' => 'required|in:1,2,3',
        ]);

        $validator->setCustomMessages([
            'end_date.after' => 'The end date must be after the start date.',
            'end_time.after' => 'The end time field must be later than the start time.',
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();

            return response()->json([
                'status' => false,
                'message' => $errorMessage,

            ], 422);
        }

        $doctorID = Docter::where('UserId', $request->doctor_id)->pluck('id')->first(); //doctor id is userid of doc
        if (is_null($doctorID)) {
            return response()->json([
                'status' => true,
                'schedule' => null,
                'message' => 'Doctor not found for the given doctor user id',
            ]);
        }


        // check for existing schedules   - req change 04-03

        $existing_tokens = NewTokens::select('token_start_time', 'token_end_time')
            ->where('doctor_id', $doctorID)
            ->where('clinic_id', $request->clinic_id)
            ->where('schedule_type', '!=', $request->schedule_type)
            ->get();

        foreach ($existing_tokens as $existing_token) {
            $start_time_cal = Carbon::parse($existing_token->token_start_time)->format('H:i:s');
            $end_time_cal = Carbon::parse($existing_token->token_end_time)->format('H:i:s');

            $request_start_time = Carbon::parse($request->start_time)->format('H:i:s');
            $request_end_time = Carbon::parse($request->end_time)->format('H:i:s');

            if (
                ($request_start_time >= $start_time_cal && $request_start_time < $end_time_cal) ||
                ($request_end_time > $start_time_cal && $request_end_time <= $end_time_cal) ||
                ($request_start_time < $start_time_cal && $request_end_time > $end_time_cal)
            ) {
                return response()->json([
                    'message' => 'Schedule already exists in this timingss.',
                    'status' => false,
                ], 422);
            }
        }

        ///
        $new_doctor_schedule = new NewDoctorSchedule();
        $new_doctor_schedule->doctor_id = $doctorID;
        $new_doctor_schedule->start_time = $request->start_time;
        $new_doctor_schedule->end_time = $request->end_time;
        $new_doctor_schedule->start_date = $request->start_date;
        $new_doctor_schedule->end_date = $request->end_date;
        $new_doctor_schedule->each_token_duration = $request->each_token_duration;
        $new_doctor_schedule->selected_days = implode(", ", $request->selected_days);
        $new_doctor_schedule->clinic_id = $request->clinic_id;
        $new_doctor_schedule->schedule_type = $request->schedule_type;
        $new_doctor_schedule->save();

        Log::channel('doctor_schedules')->info("New Doctor Schedule created::doctor_id=$doctorID |clinic_id=$request->clinic_id");
        $schedule_start_time =  $request->start_time;
        $schedule_end_time = $request->end_time;
        $each_token_duration = $request->each_token_duration;
        $clinic_id = $request->clinic_id;
        $new_doctor_schedule_id = $new_doctor_schedule->id;
        $schedule_start_date = $request->start_date;
        $schedule_end_date = $request->end_date;
        $schedule_type = $request->schedule_type;
        $selected_days = $request->selected_days;
        $doctor_user_id = $request->doctor_id;
        Log::channel('doctor_schedules')->info("Tokens Generated: doctor_id: $doctorID, clinic_id: $clinic_id, schedule_start_time: $schedule_start_time, schedule_end_time: $schedule_end_time, each_token_duration: $each_token_duration, new_doctor_schedule_id: $new_doctor_schedule_id,
         schedule_start_date: $schedule_start_date, schedule_end_date: $schedule_end_date, schedule_type: $schedule_type");
        ///////////////////generateTokenCards/////////////////////
        $generated_tokens = $this->generateTokensForSchedule(
            $schedule_start_time,
            $schedule_end_time,
            $each_token_duration,
            $doctorID,
            $clinic_id,
            $new_doctor_schedule_id,
            $schedule_start_date,
            $schedule_end_date,
            $schedule_type,
            $selected_days,
            $doctor_user_id
        );

        Log::channel('doctor_schedules')->info("Tokens Generated : doctor_id :$doctorID in clinic_id:$clinic_id");
        return response()->json([
            'message' => 'success',
            'generated_tokens' => $generated_tokens,
        ]);
    }

    ///////////////////generateTokenCards based on schedule//////////////////////////
    public function generateTokensForSchedule(
        $schedule_start_time,
        $schedule_end_time,
        $each_token_duration,
        $doctorID,
        $clinic_id,
        $new_doctor_schedule_id,
        $schedule_start_date,
        $schedule_end_date,
        $schedule_type,
        $selected_days,
        $doctor_user_id
    ) {
        try {
            if (
                isset(
                    $schedule_start_time,
                    $schedule_end_time,
                    $each_token_duration,
                    $doctorID,
                    $clinic_id,
                    $new_doctor_schedule_id,
                    $schedule_start_date,
                    $schedule_end_date,
                    $schedule_type,
                    $doctor_user_id
                )
            ) {
                Log::channel('doctor_schedules')->info("..............generateTokenCards....................");
                //////////////generate tokens and save to token table//////////////////////
                Log::channel('doctor_schedules')->info("..............START generate tokens and save to token table....................");
                $start_date = Carbon::parse($schedule_start_date);
                $end_date = Carbon::parse($schedule_end_date);
                $start_time = Carbon::parse($schedule_start_time);
                $end_time = Carbon::parse($schedule_end_time);
                $num_tokens_per_day = ceil(($end_time->diffInMinutes($start_time)) / $each_token_duration);
                $token_entries = [];

                // add reschedule option for booked tokens
                $booked_tokens = NewTokens::select(
                    'token_id',
                    'doctor_id',
                    'patient_id',
                    'clinic_id',
                    'token_number',
                    'token_scheduled_date',
                    'schedule_type',
                    'booked_user_id',
                    'token_start_time'
                )
                    ->where('token_booking_status', 1)
                    ->where('doctor_id', $doctorID)
                    ->where('clinic_id', $clinic_id)
                    ->where('schedule_type', $schedule_type)
                    ->where('is_checkedout', 0)
                    ->whereBetween('token_scheduled_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                    ->get();
                Log::channel('doctor_schedules')->info("..............booked_tokens  $booked_tokens....................");
                foreach ($booked_tokens as $booked_token) {


                    $reschedule_token = new RescheduleTokens();
                    $reschedule_token->reschedule_type  = 1;  //( 1 for doctor reschedules)
                    $reschedule_token->doctor_id  = $doctorID;
                    $reschedule_token->patient_id = $booked_token->patient_id;
                    $reschedule_token->clinic_id  = $booked_token->clinic_id;
                    $reschedule_token->token_number = $booked_token->token_number;
                    $reschedule_token->token_schedule_date = $booked_token->token_scheduled_date;
                    $reschedule_token->booked_user_id = $booked_token->booked_user_id;
                    // $reschedule_token->patient_name = $patient_name;
                    $reschedule_token->token_start_time = $booked_token->token_start_time;
                    $reschedule_token->save();

                    ///////////////////
                    /////send push notifiction alert
                    $user_id = $booked_token->booked_user_id;

                    $userIds = [$user_id];

                    $token_number = $booked_token->token_number;

                    $time = Carbon::parse($booked_token->token_start_time)->format('h:i A');

                    $title = "Appointment Cancellation Notice";

                    $message = "We regret to inform you that your appointment with token number $token_number scheduled for $time has been cancelled due to unforeseen circumstances with the doctor. Please reschedule your appointment at your earliest convenience.";

                    $type = "doctor-reschedules";

                    $notificationHelper = new PushNotificationHelper();

                    $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);

                    ////////////////////////////


                }

                //delete existing schedules
                $deleted_schedule_id = NewTokens::where('doctor_id', $doctorID)
                    ->where('clinic_id', $clinic_id)
                    ->where('schedule_type', $schedule_type)
                    ->whereBetween('token_scheduled_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                    ->pluck('schedule_id');



                NewTokens::where('doctor_id', $doctorID)
                    ->where('clinic_id', $clinic_id)
                    ->where('schedule_type', $schedule_type)
                    ->whereBetween('token_scheduled_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                    // ->where('token_scheduled_date', '>', $end_date->format('Y-m-d'))
                    ->forceDelete();

                TokenBooking::where('doctor_id', $doctor_user_id)
                    ->where('clinic_id', $clinic_id)
                    ->where('schedule_type', $schedule_type)
                    //->where('Is_completed', 0)
                    ->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                    ->delete();

                NewDoctorSchedule::whereIn('schedule_id', $deleted_schedule_id)
                    ->delete();

                DoctorReschedule::where('doctor_id', $doctorID)
                    ->where('clinic_id', $clinic_id)
                    ->where('schedule_type', $schedule_type)
                    ->delete();


                for ($current_date = $start_date; $current_date <= $end_date; $current_date->addDay()) {
                    if (in_array($current_date->format('l'), $selected_days)) {
                        $current_start_time = $current_date->copy()->setTimeFromTimeString($start_time->toTimeString());
                        $previous_schedule_type = $schedule_type - 1;
                        $last_token_number = NewTokens::where('doctor_id', $doctorID)
                            ->where('clinic_id', $clinic_id)
                            ->where('token_scheduled_date', $current_date->format('Y-m-d'))
                            ->where('schedule_type', $previous_schedule_type)
                            ->max('token_number') ?? 0;

                        for ($j = 1; $j <= $num_tokens_per_day; $j++) {
                            // start the new token_number from 1 if schedule_type is 1, otherwise from the last token_number + 1
                            $token_number = ($schedule_type == 1) ? $j : ($last_token_number + $j);
                            $token_start_time = $current_start_time->copy()->addMinutes(($j - 1) * $each_token_duration);
                            $token_end_time = $token_start_time->copy()->addMinutes($each_token_duration);
                            $token_entries[] = [
                                'token_number' => $token_number,
                                'start_time' => $token_start_time->format('Y-m-d H:i:s'),
                                'end_time' => $token_end_time->format('Y-m-d H:i:s'),
                            ];

                            NewTokens::create([
                                'token_number' => $token_number,
                                'token_start_time' => $token_start_time->format('Y-m-d H:i:s'),
                                'token_end_time' => $token_end_time->format('Y-m-d H:i:s'),
                                'doctor_id' => $doctorID,
                                'clinic_id' => $clinic_id,
                                'schedule_id' => $new_doctor_schedule_id,
                                'token_scheduled_date' => $current_date->format('Y-m-d'),
                                'assigned_token_duration' => $each_token_duration,
                                'schedule_type' => $schedule_type,
                            ]);
                        }

                        $next_schedule_token_number = $token_number + 1;
                        $next_schedule_type = $schedule_type + 1;

                        NewTokens::where('doctor_id', $doctorID)
                            ->where('clinic_id', $clinic_id)
                            ->where('token_scheduled_date', $current_date->format('Y-m-d'))
                            ->where('schedule_type', $next_schedule_type)
                            ->orderBy('created_at')
                            ->get()
                            ->each(function ($token) use (&$next_schedule_token_number) {
                                $token->update(['token_number' => $next_schedule_token_number++]);
                            });

                        //third schedule  - req change 22-02
                        $upcoming_schedule_token_number = NewTokens::where('clinic_id', $clinic_id)
                            ->where('token_scheduled_date', $current_date->format('Y-m-d'))
                            ->where('schedule_type', $next_schedule_type)
                            ->max('token_number');

                        $incremented_upcoming_token_number =  $upcoming_schedule_token_number  + 1;
                        $upcoming_schedule_type = $next_schedule_type + 1;

                        NewTokens::where('doctor_id', $doctorID)
                            ->where('clinic_id', $clinic_id)
                            ->where('token_scheduled_date', $current_date->format('Y-m-d'))
                            ->where('schedule_type', $upcoming_schedule_type)
                            ->orderBy('created_at')
                            ->get()
                            ->each(function ($token) use (&$incremented_upcoming_token_number) {
                                $token->update(['token_number' => $incremented_upcoming_token_number++]);
                            });
                    }
                }
            } else {
                Log::channel('doctor_schedules')->error('One or more required parameters are missing.');
                return response()->json([
                    'message' => 'error',
                    'error' => 'One or more required parameters are missing.',
                ]);
            }

            Log::channel('doctor_schedules')->info("..............END generate tokens and save to token table....................");
            return $token_entries;
        } catch (\Exception $e) {
            Log::channel('doctor_schedules')->error("Error in generateTokensForSchedule: " . $e->getMessage());
        }
    }


    public function getDoctorTokenDetails($date, $clinic_id, $user_id)
    {
        try {
            if (isset($date, $clinic_id, $user_id)) {
                Log::channel('doctor_schedules')->info("getDoctorTokenDetails date:$date clinic_id $clinic_id $user_id user_id..............");
                $doctor_id = Docter::where('UserId', $user_id)->pluck('id')->first();
                //$doctor_id = 1;
                if (is_null($doctor_id)) {
                    return response()->json([
                        'status' => true,
                        'schedule' => null,
                        'message' => 'Doctor not found for the given doctor user id',
                    ]);
                }
                Log::channel('doctor_schedules')->info(".....doctor id........$doctor_id...................");
                $doctor_leave_check = DocterLeave::where('docter_id', $user_id)
                    ->where('hospital_id', $clinic_id)
                    ->where('date', $date)
                    ->exists();
                if ($doctor_leave_check) {
                    return response()->json([
                        'status' => true,
                        'schedule' => null,
                        'message' => 'Doctor is on Leave',
                    ]);
                }
                Log::channel('doctor_schedules')->info("doctor_leave_check completed proceeed.");
                $formatted_token_date = Carbon::parse($date)->toDateString();

                $all_tokens = NewTokens::select('token_id', 'schedule_id', 'token_number', 'token_start_time', 'token_end_time', 'schedule_type', 'is_reserved')
                    ->where('doctor_id', $doctor_id)
                    ->where('clinic_id', $clinic_id)
                    ->where('token_scheduled_date', $formatted_token_date)
                    ->get();

                Log::channel('doctor_schedules')->info("........All Tokens  $all_tokens..........");
                if ($all_tokens->isEmpty()) {
                    Log::channel('doctor_schedules')->info("........No tokens found on the selected date.......");
                    return response()->json([
                        'status' => true,
                        'schedule' => null,
                        'message' => 'No tokens found on the selected date.',
                    ]);
                }
                $booked_tokens = NewTokens::select('token_id', 'token_number', 'token_start_time', 'token_end_time', 'schedule_id', 'schedule_type')
                    ->where('doctor_id', $doctor_id)
                    ->where('clinic_id', $clinic_id)
                    ->whereDate('token_scheduled_date', $date)
                    ->where('token_booking_status', 1)
                    ->get();



                Log::channel('doctor_schedules')->info(".............booked tokens $booked_tokens...........");
                $all_tokens_array = $all_tokens->toArray();
                $booked_tokens_array = $booked_tokens->toArray();
                foreach ($all_tokens_array as $key => &$token) {
                    $token_number = $token['token_number'];
                    $booked_token = array_values(array_filter($booked_tokens_array, function ($bookedToken) use ($token_number) {
                        return $bookedToken['token_number'] == $token_number;
                    }))[0] ?? null;
                    if ($booked_token && $booked_token['schedule_type'] == $token['schedule_type']) {
                        $token['is_booked'] = 1;
                    } else {
                        $token['is_booked'] = 0;
                    }

                    $tokenModel = NewTokens::find($token['token_id']);
                    if ($tokenModel && $tokenModel->trashed()) {
                        $token['is_deleted'] = 1;
                    } else {
                        $token['is_deleted'] = 0;
                    }

                    $current_time = now();
                    if (is_string($token['token_start_time'])) {
                        $token['formatted_start_time'] = Carbon::parse($token['token_start_time']);
                    }
                    $token['is_timeout'] = (strtotime($token['token_start_time']) <= strtotime($current_time)) ? 1 : 0;
                    $token['formatted_start_time'] = $token['formatted_start_time']->format('h:i A');
                    unset($token['token_start_time']);
                    unset($token['token_end_time']);
                }

                Log::channel('doctor_schedules')->info(".............end foreach..................");
                $grouped_tokens = collect($all_tokens_array)->groupBy(function ($token) {
                    return 'schedule_' . $token['schedule_type'];
                })->toArray();
                $lastGroupKey = key(last($grouped_tokens));
                $lastTokens = $grouped_tokens[$lastGroupKey] ?? [];
                if (count($lastTokens) > 1) {
                    $lastToken = end($lastTokens);
                    if ($lastToken === end($all_tokens_array)) {
                        array_pop($grouped_tokens[$lastGroupKey]);
                    }
                }
                return response()->json([
                    'status' => true,
                    'schedule' => $grouped_tokens,
                    'message' => 'Schedules Fetched Successfully',
                ]);

                //////////////////////////////////////
            } else {
                return response()->json(['error' => 'Required parameters are missing.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function getPatientTokenDetails($date, $clinic_id, $user_id)
    {
        try {
            if (isset($date, $clinic_id, $user_id)) {
                $doctor_id = Docter::where('UserId', $user_id)->pluck('id')->first();

                if (is_null($doctor_id)) {
                    return response()->json([
                        'status' => true,
                        'schedule' => null,
                        'message' => 'Doctor not found for the given doctor user id',
                    ]);
                }
                Log::channel('patient_schedules')->info("getPatientTokenDetails date:$date clinic_id $clinic_id doctor_id : $doctor_id");
                $doctor_leave_check = DocterLeave::where('docter_id', $user_id)
                    ->where('hospital_id', $clinic_id)
                    ->where('date', $date)
                    ->exists();
                if ($doctor_leave_check) {
                    return response()->json([
                        'status' => true,
                        'schedule' => null,
                        'message' => 'Doctor is on Leave',
                    ]);
                }
                Log::channel('patient_schedules')->info("doctor_leave_check completed proceeed.");
                $formatted_token_date = Carbon::parse($date)->toDateString();
                $all_tokens = NewTokens::select('token_id', 'schedule_id', 'token_number', 'token_start_time', 'token_end_time', 'schedule_type', 'is_reserved')
                    ->where('doctor_id', $doctor_id)
                    ->where('clinic_id', $clinic_id)
                    ->where('token_scheduled_date', $formatted_token_date)
                    ->get();
                Log::channel('patient_schedules')->info("........All Tokens  $all_tokens..........");

                if ($all_tokens->isEmpty()) {
                    Log::channel('patient_schedules')->info("........No tokens found on the selected date.......");
                    return response()->json([
                        'status' => true,
                        'schedule' => null,
                        'message' => 'No tokens found on the selected date.',
                    ]);
                }
                $booked_tokens = NewTokens::select('token_number', 'token_start_time', 'token_end_time', 'schedule_type')
                    ->where('doctor_id', $doctor_id)
                    ->where('clinic_id', $clinic_id)
                    ->whereDate('token_scheduled_date', $date)
                    ->where('token_booking_status', 1)
                    ->get();

                Log::channel('doctor_schedules')->info(".............booked tokens $booked_tokens...........");
                $all_tokens_array = $all_tokens->toArray();
                $booked_tokens_array = $booked_tokens->toArray();
                foreach ($all_tokens_array as $key => &$token) {
                    $token_number = $token['token_number'];
                    $booked_token = array_values(array_filter($booked_tokens_array, function ($bookedToken) use ($token_number) {
                        return $bookedToken['token_number'] == $token_number;
                    }))[0] ?? null;
                    if ($booked_token && $booked_token['schedule_type'] == $token['schedule_type']) {
                        $token['is_booked'] = 1;
                    } else {
                        $token['is_booked'] = 0;
                    }
                    $current_time = now();
                    if (is_string($token['token_start_time'])) {
                        $token['formatted_start_time'] = Carbon::parse($token['token_start_time']);
                        $token['estimate_start_time'] = Carbon::parse($token['token_start_time'])->subMinutes(20)->format('h:i A');
                    }
                    $token['is_timeout'] = (strtotime($token['token_start_time']) <= strtotime($current_time)) ? 1 : 0;
                    $token['formatted_start_time'] = $token['formatted_start_time']->format('h:i A');
                    unset($token['token_start_time']);
                    unset($token['token_end_time']);
                }


                $grouped_tokens = collect($all_tokens_array)->groupBy(function ($token) {
                    return 'schedule_' . $token['schedule_type'];
                })->toArray();
                $lastGroupKey = key(last($grouped_tokens));
                $lastTokens = $grouped_tokens[$lastGroupKey] ?? [];
                if (count($lastTokens) > 1) {
                    $lastToken = end($lastTokens);
                    if ($lastToken === end($all_tokens_array)) {
                        array_pop($grouped_tokens[$lastGroupKey]);
                    }
                }
                return response()->json([
                    'status' => true,
                    'schedule' => $grouped_tokens,
                    'message' => 'Schedules Fetched Successfully',
                ]);
                //////////////////////////////////////
            } else {
                return response()->json(['error' => 'Required parameters are missing.'], 400);
            }
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $specialization = schedule::find($id);

        $input = $request->all();

        $validator = Validator::make($input, [
            'docter_id' => ['required', 'max:25'],
            'session_title' => ['max:250'],
            'date' => ['required', 'max:25'],
            'startingTime' => ['max:250'],
            'endingTime' => ['required', 'max:25'],
            'token' => ['max:250'],
            'timeduration' => ['required', 'max:25'],
            'format' => ['max:250'],

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        } else {
            $specialization->specialization = $input['specialization'];

            $specialization->save();
            return $this->sendResponse("specialization", $specialization, '1', 'specialization Updated successfully');
        }
    }


    public function destroy($id)
    {
        $schedule = schedule::find($id);

        if (is_null($schedule)) {
            return $this->sendError('specialization not found.');
        }

        $schedule->delete();
        return $this->sendResponse("schedule", $schedule, '1', 'schedule Deleted successfully');
    }

    public function store()
    {
    }




    public function calculateMaxTokens(Request $request)
    {
        try {
            $startDateTime = $request->input('startingTime');
            $endDateTime = $request->input('endingTime');
            $duration = $request->input('timeduration');

            $startTime = Carbon::createFromFormat('H:i', $startDateTime);
            $endTime = Carbon::createFromFormat('H:i', $endDateTime);
            $timeInterval = new DateInterval('PT' . $duration . 'M');

            $maxTokenCount = 0;
            $currentTime = $startTime;

            while ($currentTime <= $endTime) {
                $maxTokenCount++;
                $currentTime->add($timeInterval);
            }

            return response()->json(['max_token_count' => $maxTokenCount], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function doctorRequestForlate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            'schedule_date' => 'required',
            'late_time_duration' => 'required',
            'schedule_type' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            Log::channel('doctor_schedules')->info('Input Validation failed', ['error' => $firstError]);

            return response()->json([
                'success' => false,
                'message' => $firstError,
            ], 422);
        }

        $doctor_id = $request->doctor_id;
        $clinic_id = $request->clinic_id;
        $schedule_date = $request->schedule_date;
        $schedule_type = $request->schedule_type;
        $late_time_duration = $request->late_time_duration;

        try {
            Log::channel('doctor_schedules')->info("//////////////doctorRequestForlate/////////////////");
            $doctorID = Docter::where('UserId', $doctor_id)->value('id');

            Log::channel('doctor_schedules')->info("Token details logged at doctor_id " . $doctor_id);
            $token_details = NewTokens::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->where('token_scheduled_date', $schedule_date)
                ->where('schedule_type', $schedule_type)
                ->get();

            //check for checkin checkout req change 12-03
            $checked_data =  NewTokens::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->where('token_scheduled_date', $schedule_date)
                ->where('is_checkedin', 1)
                ->where('schedule_type', $schedule_type)
                ->first();

            if ($checked_data) {
                return response()->json(['message' => 'Schedule already started.Cannot process late.'], 400);
            }

            if ($token_details->isNotEmpty()) {

                // break late early req change 12-03
                $doctor_reschedule_data = new DoctorReschedule();
                $doctor_reschedule_data->doctor_id =  $doctorID;
                $doctor_reschedule_data->clinic_id =  $clinic_id;
                $doctor_reschedule_data->reschedule_type = 1;  // one for late
                $doctor_reschedule_data->reschedule_duration = $late_time_duration;
                $doctor_reschedule_data->reschedule_start_datetime =  $schedule_date;
                $doctor_reschedule_data->reschedule_end_datetime =   $schedule_date;
                $doctor_reschedule_data->schedule_type = $schedule_type;
                $doctor_reschedule_data->save();
                //

                Log::channel('doctor_schedules')->info("Token details logged at " . json_encode($late_time_duration));
                foreach ($token_details as $token) {
                    $token_start_time = Carbon::parse($token->token_start_time)->addMinutes($late_time_duration);
                    $token_end_time = Carbon::parse($token->token_end_time)->addMinutes($late_time_duration);
                    $token->doctor_late_time = $late_time_duration;

                    $token->token_start_time = $token_start_time->format('Y-m-d H:i:s');
                    $token->token_end_time = $token_end_time->format('Y-m-d H:i:s');
                    $token->save();

                    ////
                    $token_booking = TokenBooking::where('new_token_id', $token->token_id)->first();
                    if ($token_booking) {

                        $token_booking->TokenTime = $token_start_time->format('h:i A');
                        $token_booking->save();
                    }

                    /////send push notifiction alert
                    $user_id = $token->booked_user_id;

                    $userIds = [$user_id];

                    $title = "Schedule late by $late_time_duration minutes";

                    $doctor = Docter::select('firstname', 'lastname')->where('id', $doctorID)->first();
                    $doctor_name = $doctor->firstname . " " . $doctor->lastname;

                    $token_start_time = $token_start_time->format('M j g:i a');
                    $type = "doctor-reschedules";

                    $message = "Dr $doctor_name is late by $late_time_duration minutes for your appointment schedule. Your updated token start time is $token_start_time";

                    $notificationHelper = new PushNotificationHelper();

                    $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);

                    /////////////////////////////////////////////
                }


                Log::channel('doctor_schedules')->info("Token details logged at " . json_encode($token_details));

                Log::channel('doctor_schedules')->info("//////////////late processed successfully/////////////////");
                return response()->json(['message' => 'late processed successfully'], 200);
            } else {
                Log::channel('doctor_schedules')->info("//////////////late processed failed/////////////////");
                return response()->json(['message' => 'No schedule details found'], 400);
            }
        } catch (\Exception $e) {
            Log::channel('doctor_schedules')->error("Exception: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    public function doctorRequestForEarly(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            'schedule_date' => 'required',
            'early_time_duration' => 'required',
            'schedule_type' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            Log::channel('doctor_schedules')->info('Input Validation failed', ['error' => $firstError]);

            return response()->json([
                'success' => false,
                'message' => $firstError,
            ], 422);
        }


        $doctor_id = $request->doctor_id;
        $clinic_id = $request->clinic_id;
        $schedule_date = $request->schedule_date;
        $schedule_type = $request->schedule_type;
        $early_time_duration = $request->early_time_duration;


        try {
            Log::channel('doctor_schedules')->info("//////////////doctorRequestForEarly/////////////////");
            $doctorID = Docter::where('UserId', $doctor_id)->value('id');
            $token_details = NewTokens::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->where('token_scheduled_date', $schedule_date)
                ->where('schedule_type', $schedule_type)
                ->get();


            ///check for checkin checkout req change 12-03
            $checked_data =  NewTokens::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->where('token_scheduled_date', $schedule_date)
                ->where('is_checkedin', 1)
                ->where('schedule_type', $schedule_type)
                ->first();

            if ($checked_data) {
                return response()->json(['message' => 'Schedule already started.Cannot process early.'], 400);
            }

            $schedule_data = NewDoctorSchedule::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->whereDate('start_date', '<=', $schedule_date)
                ->whereDate('end_date', '>=', $schedule_date)
                ->where('schedule_type', $schedule_type)
                ->first();

            if ($schedule_data) {
                $current_time = date('H:i');
                $scheduleStartTime = $schedule_data->start_time;

                $schedule_date_check = Carbon::parse($schedule_date);

                $doctor_early_time_check = NewTokens::where('schedule_id', $schedule_data->schedule_id)->first();
                $doctor_early_tym = $doctor_early_time_check->doctor_early_time;

                $scheduleStartTimeCarbon = Carbon::createFromFormat('H:i', $scheduleStartTime);

                $scheduleStartTimeCarbon->addMinutes($doctor_early_tym);

                if ($scheduleStartTimeCarbon->lessThan(Carbon::now()) && $schedule_date_check->isToday()) {
                    return response()->json(['message' => 'Schedule already started. Cannot process early.'], 400);
                }

                $current_date = Carbon::now()->format('Y-m-d');

                if ($schedule_date == $current_date) {
                    $current_time_format = Carbon::now();
                    $schedule_start_time = Carbon::createFromFormat('H:i', $schedule_data->start_time);

                    $minutes_diff = $current_time_format->diffInMinutes($schedule_start_time);

                    if ($minutes_diff < $early_time_duration) {
                        return response()->json(['message' => 'Sorry you can only apply early for ' . $minutes_diff . ' minutes'], 400);
                    }
                }
            }
            ////

            if ($token_details->isNotEmpty()) {
                $doctor_early_time = $early_time_duration;

                // break late early req change 12-03
                $doctor_reschedule_data = new DoctorReschedule();
                $doctor_reschedule_data->doctor_id =  $doctorID;
                $doctor_reschedule_data->clinic_id =  $clinic_id;
                $doctor_reschedule_data->reschedule_type = 2;  // two for early
                $doctor_reschedule_data->reschedule_duration = $early_time_duration;
                $doctor_reschedule_data->reschedule_start_datetime =  $schedule_date;
                $doctor_reschedule_data->reschedule_end_datetime =   $schedule_date;
                $doctor_reschedule_data->schedule_type = $schedule_type;
                $doctor_reschedule_data->save();
                //

                foreach ($token_details as $token) {
                    $token_start_time = Carbon::parse($token->token_start_time)->subMinutes($doctor_early_time);
                    $token_end_time = Carbon::parse($token->token_end_time)->subMinutes($doctor_early_time);
                    if (isset($token->doctor_early_time)) {
                        $token->doctor_early_time += $doctor_early_time;
                    } else {
                        $token->doctor_early_time = $doctor_early_time;
                    }
                    $token->token_start_time = $token_start_time->format('Y-m-d H:i:s');
                    $token->token_end_time = $token_end_time->format('Y-m-d H:i:s');
                    $token->save();
                    //////////
                    $token_booking = TokenBooking::where('new_token_id', $token->token_id)->first();
                    if ($token_booking) {
                        $tokenTime = Carbon::parse($token->token_start_time)->addMinutes($token->token_end_time);
                        $token_booking->TokenTime = $tokenTime->format('h:i A');
                        $token_booking->save();
                    }
                    ///////////
                    /////send push notifiction alert
                    $user_id = $token->booked_user_id;

                    $userIds = [$user_id];

                    $title = "Schedule early by $early_time_duration minutes";

                    $doctor = Docter::select('firstname', 'lastname')->where('id', $doctorID)->first();
                    $doctor_name = $doctor->firstname . " " . $doctor->lastname;

                    $token_start_time = $token_start_time->format('M j g:i a');
                    $type = "doctor-reschedules";

                    $message = "Dr $doctor_name is early by $early_time_duration minutes for your appointment schedule. Your updated token start time is $token_start_time";

                    $notificationHelper = new PushNotificationHelper();

                    $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);
                }
                Log::channel('doctor_schedules')->info("//////////////Early processed successfully/////////////////");
                return response()->json(['message' => 'Early processed successfully'], 200);
            } else {
                return response()->json(['message' => 'No matching token details found.'], 404);
            }
        } catch (\Exception $e) {
            Log::channel('doctor_schedules')->error("Exception: " . $e->getMessage());
            return response()->json(['message' =>  $e->getMessage()], 500);
        }
    }

    // public function doctorRequestForBreak(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'doctor_id' => 'required',
    //         'clinic_id' => 'required',
    //         'break_start_date' => 'required',
    //         'break_end_date' => 'required',
    //         'break_start_time' => 'required',
    //         'break_end_time' => 'required',
    //         'schedule_type' => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         $firstError = $validator->errors()->first();

    //         Log::channel('doctor_schedules')->info('Input Validation failed', ['error' => $firstError]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => $firstError,
    //         ], 422);
    //     }

    //     if ($request->break_start_time == $request->break_end_time) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Opps.Invalid break interval.',

    //         ], 422);
    //     }

    //     try {

    //         $doctor_id = $request->doctor_id;
    //         $clinic_id = $request->clinic_id;
    //         $break_start_date = $request->break_start_date;
    //         $break_end_date = $request->break_end_date;
    //         $break_start_time = $request->break_start_time;
    //         $break_end_time = $request->break_end_time;
    //         $schedule_type = $request->schedule_type;

    //         $doctorID = Docter::where('UserId', $doctor_id)->value('id');
    //         $reschedule_start_time = Carbon::parse($break_start_time);
    //         $reschedule_end_time = Carbon::parse($break_end_time);
    //         $reschedule_extra_required_time = $reschedule_end_time->diffInMinutes($reschedule_start_time);
    //         $reschedule_start_datetime = Carbon::parse($break_start_date . ' ' . $break_start_time);
    //         $reschedule_end_datetime = Carbon::parse($break_end_date . ' ' . $break_end_time);

    //         /////////////////////////////EXISTING TOKENS CHECK////////////////////////////////////

    //         $existing_token_check = NewTokens::where('doctor_id', $doctorID)
    //             ->where('clinic_id', $clinic_id)
    //             ->whereDate('token_scheduled_date', '>=', $break_start_date)
    //             ->whereDate('token_scheduled_date', '<=', $break_end_date)
    //             ->whereTime('token_start_time', '>=', $break_start_time)
    //             ->whereTime('token_start_time', '<=', $break_end_time)
    //             ->where('schedule_type', $schedule_type)
    //             ->get();

    //         if ($existing_token_check->isEmpty()) {
    //             return response()->json(['message' => 'No Tokens found for the date'], 500);
    //         }


    //         $existing_break_check = DoctorReschedule::where('doctor_id', $doctorID)
    //             ->where('clinic_id', $clinic_id)
    //             ->where('reschedule_type', 3)
    //             ->where('schedule_type', $request->schedule_type)
    //             ->get();

    //         if ($existing_break_check->count() > 0) {

    //             foreach ($existing_break_check as $b_c) {

    //                 $existing_break_S_T = Carbon::parse($b_c->reschedule_start_datetime);
    //                 $existing_break_E_T = Carbon::parse($b_c->reschedule_end_datetime);

    //                 $request_break_start_time = Carbon::parse($request->break_start_time);
    //                 $request_break_end_time = Carbon::parse($request->break_end_time);

    //                 if (($request_break_start_time >= $existing_break_S_T && $request_break_start_time < $existing_break_E_T) ||
    //                     ($request_break_end_time > $existing_break_S_T && $request_break_end_time <= $existing_break_E_T) ||
    //                     ($request_break_start_time <= $existing_break_S_T && $request_break_end_time >= $existing_break_E_T)
    //                 ) {
    //                     return response()->json(['message' => 'Break already exists.'], 400);
    //                 }
    //             }
    //         }

    //         /////////////////////////////////////////////////////////////////////////////////////////

    //         $token_details = NewTokens::where('doctor_id', $doctorID)
    //             ->where('clinic_id', $clinic_id)
    //             ->where('token_start_time', '>', $break_start_time)
    //             ->whereBetween('token_scheduled_date', [$break_start_date, $break_end_date])
    //             ->where('schedule_type', $schedule_type)
    //             ->get();

    //         $first_token_details  = $token_details->first();

    //         $existing_token_start_time = $first_token_details ? Carbon::parse($first_token_details->token_start_time)->format('H:i') : null;



    //         $doctor_reschedule_data = new DoctorReschedule();
    //         $doctor_reschedule_data->doctor_id =  $doctorID;
    //         $doctor_reschedule_data->clinic_id =  $clinic_id;
    //         $doctor_reschedule_data->reschedule_type = 3;  // three for break
    //         $doctor_reschedule_data->reschedule_duration = $reschedule_extra_required_time;
    //         $doctor_reschedule_data->reschedule_start_datetime =  $reschedule_start_datetime;
    //         $doctor_reschedule_data->reschedule_end_datetime =   $reschedule_end_datetime;
    //         $doctor_reschedule_data->schedule_type = $schedule_type;
    //         $doctor_reschedule_data->save();

    //         if ($token_details) {
    //             $start_time = Carbon::parse($break_start_time);
    //             $end_time = Carbon::parse($break_end_time);
    //             $extra_required_time = $end_time->diffInMinutes($start_time);

    //             foreach ($token_details as $token) {
    //                 $token_start_time = Carbon::parse($token->token_start_time);
    //                 $token_end_time = Carbon::parse($token_start_time->format('H:i:s'));

    //                 $estimate_time = $token->estimate_checkin_time ? $token->estimate_checkin_time : NULL;

    //                 $estimate_time = Carbon::parse($estimate_time);

    //                 if ($token_start_time->copy()->setDate(2000, 1, 1)->greaterThanOrEqualTo($start_time->copy()->setDate(2000, 1, 1))) {
    //                     $token->token_start_time = $token_start_time->addMinutes($extra_required_time)->format('Y-m-d H:i:s');
    //                     $token->token_end_time = $token_end_time->addMinutes($extra_required_time)->format('Y-m-d H:i:s');
    //                     $token->doctor_break_time = $extra_required_time;
    //                     $token->break_start_time = $start_time;
    //                     $token->estimate_checkin_time = $estimate_time ? $estimate_time->addMinutes($token->doctor_break_time) : $estimate_time;
    //                     $token->break_end_time = $end_time;
    //                     $token->save();


    //                     $token_booking = TokenBooking::where('new_token_id', $token->token_id)->first();

    //                     if ($token_booking) {
    //                         $tokenTime = Carbon::parse($token->token_start_time)->addMinutes($token->token_end_time);
    //                         $token_booking->TokenTime = $tokenTime->format('h:i A');
    //                         $token_booking->save();

    //                         ////////////////////
    //                         /////send push notifiction alert
    //                         $user_id = $token_booking->BookedPerson_id;

    //                         $userIds = [$user_id];

    //                         $title = "Doctor on Break";

    //                         $doctor = Docter::select('firstname', 'lastname')->where('id', $doctorID)->first();
    //                         $doctor_name = $doctor->firstname . " " . $doctor->lastname;

    //                         $token_start_time = $token_start_time->format('M j g:i a');
    //                         $type = "doctor-reschedules";
    //                         $message = "Dr. $doctor_name is on break. Your updated start time is " . $token_start_time . ". We apologize for any inconvenience caused.";
    //                         $notificationHelper = new PushNotificationHelper();

    //                         $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);

    //                         ////////
    //                     }
    //                     ////////////
    //                 }
    //             }
    //             return response()->json(['message' => 'Break process Success'], 200);
    //         } else {
    //             return response()->json(['message' => 'No Tokens found for the date'], 500);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'Internal Server Error'], 500);
    //     }
    // }
    public function doctorRequestForBreak(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            'break_start_date' => 'required',
            'break_end_date' => 'required',
            'break_start_time' => 'required',
            'break_end_time' => 'required',
            'schedule_type' => 'required',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            Log::channel('doctor_schedules')->info('Input Validation failed', ['error' => $firstError]);

            return response()->json([
                'success' => false,
                'message' => $firstError,
            ], 422);
        }

        Log::info('Validation passed');

        if ($request->break_start_time == $request->break_end_time) {
            Log::info('Invalid break interval');

            return response()->json([
                'success' => false,
                'message' => 'Oops. Invalid break interval.',
            ], 422);
        }

        try {

            $doctor_id = $request->doctor_id;
            $clinic_id = $request->clinic_id;
            $break_start_date = $request->break_start_date;
            $break_end_date = $request->break_end_date;
            $break_start_time = $request->break_start_time;
            $break_end_time = $request->break_end_time;
            $schedule_type = $request->schedule_type;


            $doctorID = Docter::where('UserId', $doctor_id)->value('id');

            $reschedule_start_time = Carbon::parse($break_start_time);
            $reschedule_end_time = Carbon::parse($break_end_time);
            $reschedule_extra_required_time = $reschedule_end_time->diffInMinutes($reschedule_start_time);
            $reschedule_start_datetime = Carbon::parse($break_start_date . ' ' . $break_start_time);
            $reschedule_end_datetime = Carbon::parse($break_end_date . ' ' . $break_end_time);


            $existing_token_check = NewTokens::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->whereDate('token_scheduled_date', '>=', $break_start_date)
                ->whereDate('token_scheduled_date', '<=', $break_end_date)
                ->whereTime('token_start_time', '>=', $break_start_time)
                ->whereTime('token_start_time', '<=', $break_end_time)
                ->where('schedule_type', $schedule_type)
                ->get();


            if ($existing_token_check->isEmpty()) {
                Log::info('No tokens found for the date');

                return response()->json(['message' => 'No Tokens found for the date'], 500);
            }

            $existing_break_check = DoctorReschedule::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->where('reschedule_type', 3)
                ->where('schedule_type', $request->schedule_type)
                ->get();

            Log::info('Checked for existing breaks', ['existing_break_check' => $existing_break_check]);

            if ($existing_break_check->count() > 0) {
                foreach ($existing_break_check as $b_c) {
                    $existing_break_S_T = Carbon::parse($b_c->reschedule_start_datetime);
                    $existing_break_E_T = Carbon::parse($b_c->reschedule_end_datetime);

                    $request_break_start_time = Carbon::parse($request->break_start_time);
                    $request_break_end_time = Carbon::parse($request->break_end_time);

                    if (($request_break_start_time >= $existing_break_S_T && $request_break_start_time < $existing_break_E_T) ||
                        ($request_break_end_time > $existing_break_S_T && $request_break_end_time <= $existing_break_E_T) ||
                        ($request_break_start_time <= $existing_break_S_T && $request_break_end_time >= $existing_break_E_T)
                    ) {
                        Log::info('Break conflict detected');

                        return response()->json(['message' => 'Break already exists.'], 400);
                    }
                }
            }


            $token_details = NewTokens::where('doctor_id', $doctorID)
                ->where('clinic_id', $clinic_id)
                ->whereTime('token_start_time', '>=', $break_start_time)
                ->whereBetween('token_scheduled_date', [$break_start_date, $break_end_date])
                ->where('schedule_type', $schedule_type)
                ->get();

            $schedule_id = $token_details->first()->schedule_id ?? null;

            $b_s_t = Carbon::parse($break_start_time)->format('H:i:s');
            $b_e_t = Carbon::parse($break_end_time)->format('H:i:s');



            $doctor_reschedule_data = new DoctorReschedule();
            $doctor_reschedule_data->doctor_id = $doctorID;
            $doctor_reschedule_data->clinic_id = $clinic_id;
            $doctor_reschedule_data->reschedule_type = 3;  // three for break
            $doctor_reschedule_data->reschedule_duration = $reschedule_extra_required_time;
            $doctor_reschedule_data->reschedule_start_datetime = $reschedule_start_datetime;
            $doctor_reschedule_data->reschedule_end_datetime = $reschedule_end_datetime;
            $doctor_reschedule_data->schedule_type = $schedule_type;
            $doctor_reschedule_data->schedule_id = $schedule_id;
            $doctor_reschedule_data->break_start_time = $b_s_t;
            $doctor_reschedule_data->break_end_time = $b_e_t;
            $doctor_reschedule_data->save();

            Log::info('Saved doctor reschedule data', ['doctor_reschedule_data' => $doctor_reschedule_data]);
            if ($token_details) {
                $start_time = Carbon::parse($break_start_time);
                $end_time = Carbon::parse($break_end_time);
                $extra_required_time = $end_time->diffInMinutes($start_time);


                foreach ($token_details as $token) {
                    $token_start_time = Carbon::parse($token->token_start_time);
                    $token_end_time = Carbon::parse($token_start_time->format('H:i:s'));

                    $estimate_time = $token->estimate_checkin_time ? $token->estimate_checkin_time : null;
                    $estimate_time = Carbon::parse($estimate_time);

                    if ($token_start_time->copy()->setDate(2000, 1, 1)->greaterThanOrEqualTo($start_time->copy()->setDate(2000, 1, 1))) {
                        $token->token_start_time = $token_start_time->addMinutes($extra_required_time)->format('Y-m-d H:i:s');
                        $token->token_end_time = $token_end_time->addMinutes($extra_required_time)->format('Y-m-d H:i:s');
                        $token->doctor_break_time = $extra_required_time;
                        $token->break_start_time = $start_time;
                        $token->estimate_checkin_time = $estimate_time ? $estimate_time->addMinutes($token->doctor_break_time) : $estimate_time;
                        $token->break_end_time = $end_time;
                        $token->save();


                        $token_booking = TokenBooking::where('new_token_id', $token->token_id)->first();
                        if ($token_booking) {
                            $tokenTime = Carbon::parse($token->token_start_time)->addMinutes($token->token_end_time);
                            $token_booking->TokenTime = $tokenTime->format('h:i A');
                            $token_booking->save();


                            // Send push notification
                            $user_id = $token_booking->BookedPerson_id;
                            $userIds = [$user_id];

                            $title = "Doctor on Break";
                            $doctor = Docter::select('firstname', 'lastname')->where('id', $doctorID)->first();
                            $doctor_name = $doctor->firstname . " " . $doctor->lastname;

                            $token_start_time = $token_start_time->format('M j g:i a');
                            $type = "doctor-reschedules";
                            $message = "Dr. $doctor_name is on break. Your updated start time is " . $token_start_time . ". We apologize for any inconvenience caused.";

                            $notificationHelper = new PushNotificationHelper();
                            $response = $notificationHelper->sendPushNotifications($userIds, $title, $message, $type);

                            Log::info('Sent push notification', ['response' => $response]);
                        }
                    }
                }

                Log::info('Break process successful');
                return response()->json(['message' => 'Break process Success'], 200);
            } else {
                Log::info('No tokens found for the date');
                return response()->json(['message' => 'No Tokens found for the date'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Internal Server Error', ['exception' => $e]);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    //get doctor reschedules
    public function getAllDoctorReschedules($doctor_user_id, $clinic_id, $reschedule_type)
    {
        // 1= late 2= early
        $doctor_id = Docter::where('UserId', $doctor_user_id)->value('id');
        $todays_date = now()->toDateString();

        $doctor_reschedule_data = DoctorReschedule::select(
            'doctor_id',
            'reschedule_id',
            'clinic_id',
            'reschedule_type',
            'reschedule_duration',
            'reschedule_start_datetime',
            'schedule_type'
        )
            ->where('doctor_id', $doctor_id)
            ->where('clinic_id', $clinic_id)
            ->where('reschedule_start_datetime', '>=', $todays_date)
            ->where('reschedule_type', $reschedule_type)
            ->get();

        if (!$doctor_reschedule_data) {
            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'No late requests found'
            ]);
        }

        $formatted_reschedules = [];

        foreach ($doctor_reschedule_data as $reschedule) {
            $formatted_reschedules[] = [
                'reschedule_duration' => $reschedule->reschedule_duration,
                'schedule_date' => Carbon::parse($reschedule->reschedule_start_datetime)->format('d-m-Y'),
                'schedule_type' => $reschedule->schedule_type,
                'reschedule_id' => $reschedule->reschedule_id,
                'reschedule_type' => $reschedule->reschedule_type
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $formatted_reschedules,
            'message' => 'Late requests fetched successfully.'
        ]);
    }

    public function getAllDoctorBreakRequests(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'doctor_user_id' => 'required',
            'clinic_id' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Input Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $doctor_id = Docter::where('UserId', $request->doctor_user_id)->value('id');
        $doctor_break_data = DoctorReschedule::select(
            'reschedule_id',
            'reschedule_type',
            'reschedule_duration',
            'doctor_id',
            'clinic_id',
            'reschedule_start_datetime',
            'reschedule_end_datetime',
            'schedule_type'
        )
            ->where('doctor_id', $doctor_id)
            ->where('clinic_id', $request->clinic_id)
            ->where('reschedule_type', 3)
            ->get();


        $formatted_requests = [];
        foreach ($doctor_break_data as $break) {
            $formatted_requests[] = [
                'doctor_break_time' => $break->reschedule_duration,
                'break_from_date' => date('d-m-Y', strtotime($break->reschedule_start_datetime)),
                'break_to_date' => date('d-m-Y', strtotime($break->reschedule_end_datetime)),
                'break_start_time' => date('h:i A', strtotime($break->reschedule_start_datetime)),
                'break_end_time' => date('h:i A', strtotime($break->reschedule_end_datetime)),
                'schedule_type' => $break->schedule_type,
                'reschedule_id' => $break->reschedule_id,
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $formatted_requests,
            'message' => 'Success'
        ]);
    }


    public function deleteDoctorBreakRequests(Request $request)
    {

        $request->validate([
            'reschedule_id' => 'required|integer',
        ]);

        $reschedule_id = $request->input('reschedule_id');

        if (isset($reschedule_id)) {

            $break_reschedule_data = DoctorReschedule::where('reschedule_id', $request->reschedule_id)
                ->where('reschedule_type', 3)->first();

            if (!$break_reschedule_data) {

                return response()->json([
                    'status' => true,
                    'data' => null,
                    'message' => 'No Break request found.'
                ]);
            }

            $doctor_id = $break_reschedule_data->doctor_id;
            $clinic_id = $break_reschedule_data->clinic_id;
            $reschedule_type = $break_reschedule_data->reschedule_type;
            $reschedule_duration = $break_reschedule_data->reschedule_duration;
            $reschedule_start_datetime = $break_reschedule_data->reschedule_start_datetime;
            $reschedule_end_datetime = $break_reschedule_data->reschedule_end_datetime;
            $schedule_type = $break_reschedule_data->schedule_type;

            $schedule_start_date = Carbon::parse($reschedule_start_datetime)->format('Y-m-d');
            $schedule_end_date = Carbon::parse($reschedule_end_datetime)->format('Y-m-d');


            ////old code
            $upcoming_breaks = NewTokens::where('clinic_id', $clinic_id)
                ->whereBetween('token_scheduled_date', [$schedule_start_date, $schedule_end_date])
                ->where('doctor_id', $doctor_id)
                ->whereNotNull('doctor_break_time')
                ->where('schedule_type', $schedule_type)
                ->get();

            if (!$upcoming_breaks) {
                return response()->json([
                    'status' => true,
                    'data' => null,
                    'message' => 'No Break request found on the selected date'
                ]);
            }

            foreach ($upcoming_breaks as $break) {

                $tokenStartTime = Carbon::parse($break->token_start_time);
                $tokenEndTime = Carbon::parse($break->token_end_time);
                $tokenStartTime->subMinutes($break->doctor_break_time);
                $tokenEndTime->subMinutes($break->doctor_break_time);
                $break->token_start_time = $tokenStartTime->toDateTimeString();
                $break->token_end_time = $tokenEndTime->toDateTimeString();
                $break->doctor_break_time = null;
                $break->update();
            }

            DoctorReschedule::where('reschedule_id', $reschedule_id)->delete();

            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'Break deleted successfully'
            ]);
        } else {

            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'Required parameters are missing.'
            ]);
        }
    }


    public function deleteDoctorLateReschedules(Request $request)
    {
        $request->validate([
            'reschedule_id' => 'required',
        ]);

        $doc_reschedule_data = DoctorReschedule::where('reschedule_id', $request->reschedule_id)->first();

        if (!$doc_reschedule_data) {
            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'No late requests found'
            ]);
        }

        $date = Carbon::parse($doc_reschedule_data->reschedule_start_datetime)->format('Y-m-d');
        $schedule_type = $doc_reschedule_data->schedule_type;
        $doctor_id = $doc_reschedule_data->doctor_id;
        $clinic_id = $doc_reschedule_data->clinic_id;


        $doctor_late_time = $doc_reschedule_data->reschedule_duration;

        $upcoming_reschedules = NewTokens::where('clinic_id', $clinic_id)
            //->where('schedule_id', $schedule_id)
            ->where('schedule_type', $schedule_type)
            ->where('token_scheduled_date', $date)
            ->where('doctor_id', $doctor_id)
            ->whereNotNull('doctor_late_time')
            ->get();

        if ($upcoming_reschedules->isEmpty()) {
            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'No Late request found on the selected date'
            ]);
        }

        foreach ($upcoming_reschedules as $reschedule) {

            $startTime = Carbon::parse($reschedule->token_start_time);
            $endTime = Carbon::parse($reschedule->token_end_time);
            $startTime->subMinutes($doctor_late_time);
            $endTime->subMinutes($doctor_late_time);
            $reschedule->token_start_time = $startTime;
            $reschedule->token_end_time = $endTime;
            $reschedule->doctor_late_time -= $doctor_late_time;
            $reschedule->save();
        }

        DoctorReschedule::where('reschedule_id', $request->reschedule_id)->delete();
        return response()->json([
            'status' => true,
            'data' => null,
            'message' => 'Doctor late request deleted successfully'
        ]);
    }

    public function deleteDoctorEarlyReschedules(Request $request)
    {
        $request->validate([
            'reschedule_id' => 'required',
        ]);

        try {

            $doc_reschedule_data = DoctorReschedule::where('reschedule_id', $request->reschedule_id)->first();

            if (!$doc_reschedule_data) {
                return response()->json([
                    'status' => true,
                    'data' => null,
                    'message' => 'No late requests found'
                ]);
            }

            $date = Carbon::parse($doc_reschedule_data->reschedule_start_datetime)->format('Y-m-d');
            $schedule_type = $doc_reschedule_data->schedule_type;
            $doctor_id = $doc_reschedule_data->doctor_id;
            $clinic_id = $doc_reschedule_data->clinic_id;


            $doctor_early_time = $doc_reschedule_data->reschedule_duration;


            $upcoming_reschedules = NewTokens::where('clinic_id', $clinic_id)
                //->where('schedule_id', $schedule_id)
                ->where('schedule_type', $schedule_type)
                ->whereDate('token_scheduled_date', $date)
                ->where('doctor_id', $doctor_id)
                ->whereNotNull('doctor_early_time')
                ->get();


            if ($upcoming_reschedules->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'data' => null,
                    'message' => 'No early request found on the selected date'
                ]);
            }

            foreach ($upcoming_reschedules as $reschedule) {

                $startTime = Carbon::parse($reschedule->token_start_time);
                $endTime = Carbon::parse($reschedule->token_end_time);
                $startTime->addMinutes($doctor_early_time);
                $endTime->addMinutes($doctor_early_time);
                $reschedule->token_start_time = $startTime;
                $reschedule->token_end_time = $endTime;
                if ($reschedule->doctor_early_time !== null) {
                    $reschedule->doctor_early_time -= $doctor_early_time;
                }
                $reschedule->save();
            }
            DoctorReschedule::where('reschedule_id', $request->reschedule_id)->delete();
            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'Doctor early request deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::info("error = $e");
        }
    }


    public function doctorReserveTokens(Request $request)
    {

        $rules = [
            'token_number' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'clinic_id' => 'required'
        ];
        $messages = [
            'token_number.required' => 'Token number is required',
            'from_date.required' => 'From date is required',
            'to_date.required' => 'To date is required',
            'clinic_id.required' => 'Clinic ID is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {

            $doctor_user_id = Auth::user()->id;
            $doctorID = Docter::where('UserId', $doctor_user_id)->pluck('id')->first();
            // $doctorID = 35;

            $request_tokens = json_decode($request->token_number);

            if (empty($request_tokens)) {
                return response()->json(['status' => false, 'message' => 'Please select at least one token'], 400);
            }


            $tokens = NewTokens::whereIn('token_number', $request_tokens)
                ->where('clinic_id', $request->clinic_id)
                ->where('doctor_id', $doctorID)
                ->whereBetween('token_scheduled_date', [$request->from_date, $request->to_date])
                ->get();

            if ($tokens->isEmpty()) {
                return response()->json(['status' => false, 'response' => 'No matching tokens found for the specified date range']);
            }

            foreach ($tokens as $token) {
                if ($token->is_reserved == 1) {
                    $response = 'Token reservation canceled successfully.';
                } else {
                    $token->update(['is_reserved' => 1]);
                    $response = 'Token reserved successfully.';
                }
            }
            return response()->json(['status' => true, 'message' => $response]);
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }

    //going to work it out
    public function getDeletedTokens(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            //'date'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'response' => $validator->errors()->first()]);
        }

        try {

            $current_date = Carbon::now()->format('Y-m-d');
            $datesToken = NewTokens::onlyTrashed()
                ->select('token_start_time', 'token_id', 'token_number')
                ->leftJoin('docter as d', 'd.id', "=", "new_tokens.doctor_id")
                ->where('d.UserId', '=', $request->doctor_id)
                ->where('clinic_id', '=', $request->clinic_id)
                ->where('token_scheduled_date', '>=', $current_date)
                ->distinct()
                ->get();

            $datesToken = $datesToken->toArray();

            $formattedArray = [];
            foreach ($datesToken as $item) {
                $dateTime = new \DateTime($item['token_start_time']);
                $formattedArray[] = [
                    'format date' => $dateTime->format('d-m-Y'),
                    'date' => $dateTime->format('Y-m-d'),
                    "time" => $this->formatTime($item["token_start_time"]),
                    "token_id" => $item["token_id"],
                    "token_number" => $item["token_number"]
                ];
            }

            return response()->json(['status' => true, 'data' => $formattedArray]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal sever error'], 500);
        }
    }


    public function getAllDatesOfDeletedTokens(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required',
            'clinic_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        try {

            $datesToken = NewTokens::onlyTrashed()
                ->select('token_scheduled_date')
                ->leftJoin('docter as d', 'd.id', "=", "new_tokens.doctor_id")
                ->where('d.UserId', '=', $request->doctor_id)
                ->where('clinic_id', '=', $request->clinic_id)
                ->distinct()
                ->get();

            $datesToken = $datesToken->toArray();


            $result = [];

            foreach ($datesToken as $item) {
                $dateTime = new \DateTime($item['token_scheduled_date']);
                $formattedDate = $dateTime->format('Y-m-d');
                $month = $dateTime->format('M');
                $dayInText = $dateTime->format('D');
                $day = $dateTime->format('d');

                $result[] = [
                    "full_date" => $formattedDate,
                    "month" => $month,
                    "dayInText" => $dayInText,
                    "day" => (int)$day
                ];
            }

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    function formatTime($timeString)
    {
        return date("g:i A", strtotime($timeString));
    }

    public function restoreTokens(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_id' => 'required',
            // 'token_id.*' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        try {
            $restoreToken = NewTokens::onlyTrashed()->where('token_id', $request->token_id)->first();
            // foreach($restoreToken as $token)
            if ($restoreToken) {
                $restoreToken->deleted_at = null;
                $restoreToken->save();
            } else {
                return response()->json(['status' => false, 'message' => "Token ID not found"]);
            }

            return response()->json(['status' => true, 'message' => 'Token restored successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }



    public function GetReservedTokensDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required',
            'doctor_id' => 'required',
            'token_start_time' => 'required|date',
            'token_end_time' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 400);
        }

        try {

            $clinic_id = $request->input('clinic_id');
            $UserId = $request->input('doctor_id');
            $doctorID = Docter::where('UserId', $UserId)->pluck('id')->first();
            $token_start_time = $request->input('token_start_time');
            $token_end_time = $request->input('token_end_time');

            $Tokens = DB::table('new_tokens')

                ->select('new_tokens.token_id', 'new_tokens.token_number', 'new_tokens.token_start_time', 'new_tokens.token_end_time', 'new_tokens.doctor_id')
                ->where('new_tokens.clinic_id', $clinic_id)
                ->where('new_tokens.doctor_id', $doctorID)
                ->whereDate('new_tokens.token_start_time', '>=', $token_start_time)
                ->whereDate('new_tokens.token_end_time', '<=', $token_end_time)
                ->where('new_tokens.is_reserved', 1)
                ->get();

            if ($Tokens->isEmpty()) {
                return response()->json(['status' => true, 'message' => 'No reserved tokens are available.', 'getTokenDetails' => $Tokens]);
            }
            $tokenDetails = [];
            foreach ($Tokens as $token) {
                $startTime = Carbon::parse($token->token_start_time)->format('h:i A');

                $tokenDetails[$token->token_number] = [
                    'token_id' => (string) $token->token_id,
                    'token_number' => (string) $token->token_number,
                    'token_start_time' => $startTime,
                ];
            }

            $tokenDetails = array_values($tokenDetails);

            return response()->json(['status' => true, 'message' => 'Reserved tokens retrieved successfully', 'getTokenDetails' => $tokenDetails]);
        } catch (\Exception $e) {


            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }
    public function UnreserveToken(Request $request)
    {
        $rules = [
            'token_number' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'clinic_id' => 'required'
        ];
        $messages = [
            'token_number.required' => 'Token number is required',
            'from_date.required' => 'From date is required',
            'to_date.required' => 'To date is required',
            'clinic_id.required' => 'Clinic ID is required',
        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {

            $doctor_user_id = Auth::user()->id;
            $doctorID = Docter::where('UserId', $doctor_user_id)->pluck('id')->first();
            // $doctorID = 35;
            if (!$doctorID) {
                return response()->json(['status' => false, 'response' => 'No token found for the logged in user.']);
            }

            //  $request_tokens = json_decode($request->token_number);
            $request_tokens = $request->token_number;
            $tokens = NewTokens::where('token_number', $request_tokens)
                ->where('clinic_id', $request->clinic_id)
                ->where('doctor_id', $doctorID)
                ->whereBetween('token_scheduled_date', [$request->from_date, $request->to_date])
                ->get();

            if ($tokens->isEmpty()) {
                return response()->json(['status' => false, 'response' => 'No matching tokens found for the specified date range']);
            }

            foreach ($tokens as $token) {
                if ($token->is_reserved == 1) {
                    $token->update(['is_reserved' => 0]);
                    $response = 'Token reservation cancelled successfully.';
                }
            }
            return response()->json(['status' => true, 'message' => $response]);
            ////////////////////////////////////////////////////////////
        } catch (\Exception $e) {

            Log::error('An error occurred: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function sendNotification(Request $request)
    {

        $rules = [
            'fcm_token' => 'required',

        ];
        $messages = [
            'fcm_token.required' => 'fcm_token is required',

        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $firebaseToken = $request->fcm_token;


        $title = "Sample Notification";

        $message = "This is a test push notification.";

        $SERVER_API_KEY = env('FIREBASE_API_KEY');

        $data = [
            "registration_ids" => [$firebaseToken],
            "notification" => [
                "title" => $title,
                "body" => $message,
            ],
            "data" => [
                "title" => $title,
                "body" => $message,
            ]
        ];

        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);

        // $data = json_decode($response, true);
        return response()->json(['status' => false, 'response' => $response]);
    }
}
