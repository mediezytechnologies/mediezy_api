<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Docter;
use App\Models\NewTokens;
use App\Models\schedule;
use App\Models\TodaySchedule;
use App\Models\TodayShedule;
use App\Models\TokenBooking;
use Carbon\Carbon;
use DateInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TokenGenerationController extends BaseController
{

    public function generateTokenCards(Request $request)
    {
        try {
            $cards = [];
            $counter = 1; // Initialize the counter before the loop
            if ($request->has('startingMorningTime') && $request->has('endingMorningTime') && $request->has('morningTimeDuration')) {
                $startMorningTime = $request->startingMorningTime;
                $endMorningTime = $request->endingMorningTime;
                $durationMorning = $request->morningTimeDuration;


                // Use Carbon to parse input times for morning section
                $startTimeMorning = Carbon::createFromFormat('H:i', $startMorningTime);
                $endTimeMorning = Carbon::createFromFormat('H:i', $endMorningTime);

                // Calculate the time interval based on the duration for morning section
                $timeIntervalMorning = new DateInterval('PT' . $durationMorning . 'M');

                // Generate tokens for morning section at regular intervals
                $currentTimeMorning = $startTimeMorning;

                while ($currentTimeMorning <= $endTimeMorning) {
                    $cards[] = [
                        'Number' => $counter, // Use the counter for auto-incrementing 'Number'
                        'Time' => $currentTimeMorning->format('H:i'),
                        'Tokens' => $currentTimeMorning->add($timeIntervalMorning)->format('H:i'),
                        'is_booked' => 0,
                        'is_cancelled' => 0
                    ];

                    $counter++; // Increment the counter for the next card
                }
            }
            // Check if evening section is present
            if ($request->has('startingEveningTime') && $request->has('endingEveningTime') && $request->has('eveningTimeDuration')) {
                $startingNumberEvening = ($counter == 1) ? 1 : $counter;

                // Reset the counter for the evening section
                $counter = $startingNumberEvening;
                $startEveningTime = $request->startingEveningTime;
                $endEveningTime = $request->endingEveningTime;
                $durationEvening = $request->eveningTimeDuration;

                // Use Carbon to parse input times for evening section
                $startTimeEvening = Carbon::createFromFormat('H:i', $startEveningTime);
                $endTimeEvening = Carbon::createFromFormat('H:i', $endEveningTime);

                // Calculate the time interval based on the duration for evening section
                $timeIntervalEvening = new DateInterval('PT' . $durationEvening . 'M');

                // Generate tokens for evening section at regular intervals
                $currentTimeEvening = $startTimeEvening;

                while ($currentTimeEvening <= $endTimeEvening) {
                    $cards[] = [
                        'Number' => $counter, // Use the counter for auto-incrementing 'Number'
                        'Time' => $currentTimeEvening->format('H:i'),
                        'Tokens' => $currentTimeEvening->add($timeIntervalEvening)->format('H:i'),
                        'is_booked' => 0,
                        'is_cancelled' => 0
                    ];

                    $counter++; // Increment the counter for the next card
                }
            }
            return $cards;
            return response()->json(['cards' => $cards], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }





    public function getTodayTokens(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        if (!$user) {
            return $this->sendError('Authentication Error', 'User not authenticated', 401);
        }
        $today = now()->toDateString();

        $schedules = Schedule::whereHas('docter', function ($query) use ($user) {
            $query->where('UserId', $user->id);
        })->where('date', $today)->get();


        $tokensByClinic = [];

        foreach ($schedules as $schedule) {
            $doctor = $schedule->doctor;

            // Assuming you have a relationship set up for the clinics in Doctor model
            $clinics = $doctor->clinics;

            foreach ($clinics as $clinic) {
                $clinicId = $clinic->hospital_Id;

                if (!isset($tokensByClinic[$clinicId])) {
                    $tokensByClinic[$clinicId] = [];
                }

                // Decode the JSON data
                $tokens = json_decode($schedule->tokens);

                $tokensByClinic[$clinicId][] = [
                    'clinic' => $clinic,
                    'tokens' => $tokens,
                ];
            }
        }


        if (!empty($tokensByClinic)) {
            return $this->sendResponse('todaytokens', $tokensByClinic, '1', 'Today\'s tokens retrieved successfully');
        } else {
            return $this->sendError('No Schedule Found', 'No schedule for today found for the logged-in user');
        }
    }



    public function todayTokenSchedule(Request $request)
    {
        $rules = [
            'doctor_id'     => 'required',
            'hospital_id'   => 'required',
            'date'          => 'required',
            'delay_type'    => 'required|in:1,2,3', //2 for late, 1 for early
            'custome_time'  => 'required_if:delay_type,1,2',
            'start_time'    => 'required_if:delay_type,3',
            'end_time'      => 'required_if:delay_type,3|after:start_time',
            'section'       => 'required_if:delay_type,1,2|in:morning,evening',
        ];
        $messages = [
            'date.required' => 'Date is required',
            'section.required_if' => 'The section field is required for early or late scheduling',
            'section.in' => 'Invalid section provided. Valid values are morning and evening.',
        ];
        if ($request->delay_type === 3) {
            unset($rules['section']);
            unset($messages['section.required_if']);
            unset($messages['section.in']);
        }
        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {
            $doctor = Docter::where('id', $request->doctor_id)->first();

            if (!$doctor) {
                return response()->json(['status' => false, 'message' => 'Doctor not found']);
            }

            $schedule = schedule::where('docter_id', $doctor->id)
                ->where('hospital_Id', $request->hospital_id)
                ->first();

            $requestDate = Carbon::parse($request->date);
            $dayOfWeek = $requestDate->format('l');

            $jsonString = str_replace("'", "\"", $schedule->selecteddays);
            $allowedDaysArray = json_decode($jsonString);

            if (!in_array($dayOfWeek, $allowedDaysArray)) {
                return response()->json(['status' => false, 'message' => 'Selected day not found on your scheduled days']);
            }


            $morningEvneningTokens = $schedule->tokens;


            $tokens = json_decode($morningEvneningTokens, true);

            $morningTokens = [];
            $eveningTokens = [];

            foreach ($tokens as $token) {
                $appointmentTime = strtotime($token['Time']);

                if ($appointmentTime < strtotime('13:00:00')) {
                    // Morning appointment
                    $morningTokens[] = $token;
                } else {
                    // Evening appointment
                    $eveningTokens[] = $token;
                }
            }


            if ($request->section == 'morning') {
                $startTime = Carbon::parse($schedule->startingTime);

                $endTime = Carbon::parse($schedule->endingTime);

                $lastMorningTokenNumber = 0;
            } else {
                $startTime = Carbon::parse($schedule->eveningstartingTime);

                $endTime = Carbon::parse($schedule->eveningendingTime);


                $lastMorningTokenNumber = !empty($morningTokens) ? end($morningTokens)['Number'] : 0;
            }


            $timeSlots = [];

            if ($request->section == 'morning') {
                if ($request->delay_type == '2') {
                    $startTime->addMinutes($request->custome_time);
                    $endTime->addMinutes($request->custome_time);
                }
                if ($request->delay_type == '1') {
                    $startTime->subMinutes($request->custome_time);
                    $endTime->subMinutes($request->custome_time);
                }
            } elseif ($request->section == 'evening') {
                if ($request->delay_type == '2') {
                    $startTime->addMinutes($request->custome_time);
                    $endTime->addMinutes($request->custome_time);
                }
                if ($request->delay_type == '1') {
                    $startTime->subMinutes($request->custome_time);
                    $endTime->subMinutes($request->custome_time);
                }
            }


            $excludeStartTime = null;
            $excludeEndTime = null;

            if ($request->delay_type == 3) {
                $excludeStartTime = Carbon::parse($request->start_time);
                $excludeEndTime = Carbon::parse($request->end_time);
            }

            while ($startTime <= $endTime) {
                $slotStartTime = $startTime->format('H:i');
                if ($request->section == 'morning') {
                    $startTime->addMinutes($schedule->timeduration);
                } elseif ($request->section == 'evening') {
                    $startTime->addMinutes($schedule->eveningTimeDuration);
                }


                if ($excludeStartTime && $excludeEndTime && $startTime >= $excludeStartTime && $startTime <= $excludeEndTime) {
                    continue;
                }

                $slotEndTime = $startTime->format('H:i');

                // Check the section and time to determine morning or evening
                if ($request->section == 'morning' && $startTime->format('H:i') <= '13:00') {
                    $timeSlots[] = [
                        'Number' => ++$lastMorningTokenNumber,
                        'Time' => $slotStartTime,
                        'Tokens' => $slotEndTime,
                        'is_booked' => 0,
                        'is_cancelled' => 0,
                    ];
                } elseif ($request->section == 'evening' && $startTime->format('H:i') >= '13:00') {

                    $timeSlots[] = [
                        'Number' => ++$lastMorningTokenNumber,
                        'Time' => $slotStartTime,
                        'Tokens' => $slotEndTime,
                        'is_booked' => 0,
                        'is_cancelled' => 0,
                    ];
                }
            }

            if ($request->section == 'morning') {
                $tokensData = array_merge($timeSlots, $eveningTokens);
            } elseif ($request->section == 'evening') {
                $tokensData = array_merge($morningTokens, $timeSlots);
            }
            $checkIfexist = TodaySchedule::where('docter_id', $doctor->id)
                ->whereDate('date', $request->date)
                ->where('hospital_Id', $request->hospital_id)
                ->first();

            if ($checkIfexist) {
                $schedule = $checkIfexist;
            } else {
                $schedule = new TodaySchedule();
            }
            $schedule->section = $request->section;
            $schedule->docter_id = $request->doctor_id;
            $schedule->hospital_id = $request->hospital_id;
            $schedule->date = $request->date;
            $schedule->delay_time = $request->custome_time;
            $schedule->delay_type = $request->delay_type;
            $schedule->tokens = json_encode($tokensData);
            $schedule->save();

            return response()->json(['status' => true, 'message' => 'success']);
        } catch (\Exception $e) {
            dd($e->getMessage());
            return response()->json(['status' => false, 'message' => 'Internal Server Error']);
        }
    }



    public function deleteToken(Request $request)
    {
        $rules = [
            'token_id'     => 'required',
        ];
        $messages = [
            'token_id.required' => 'token_id required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        $request_tokens = json_decode($request->token_id);
        if (!$request->has('token_id') || empty($request_tokens)) {
            return response()->json(['status' => false, 'message' => 'Please select at least one token'],400);
        }

       
        NewTokens::whereIn('token_id', $request_tokens)
            ->delete();

        return response()->json(['status' => true, 'message' => 'Tokens deleted successfully'],200);
    }
}
