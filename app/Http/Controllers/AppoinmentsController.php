<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Banner;
use App\Models\MainSymptom;
use App\Models\DocterLeave;
use App\Models\DocterAvailability;
use App\Models\Patient;
use App\Models\schedule;
use App\Models\Docter;
use App\Models\Symtoms;
use App\Models\TodaySchedule;
use App\Models\TokenBooking;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Faker\Guesser\Name;
use Illuminate\Console\Scheduling\Schedule as SchedulingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Feature\ExampleTest;

class AppoinmentsController extends BaseController
{

    private function getClinics($doctorUserId)
    {
        $docter = Docter::select('id')->where('UserId', $doctorUserId)->first();

        $clinics = DocterAvailability::where('docter_id', $docter->id)
            ->get(['id', 'hospital_Name', 'startingTime', 'endingTime', 'address', 'location']);

        return $clinics;
    }


    public function GetUserAppointments(Request $request, $userId)
    {
        try {
            // Get the currently authenticated patient
            $patient = Patient::where('UserId', $userId)->first();

            if (!$patient) {
                return response()->json(['message' => 'Patient not found.'], 404);
            }

            // Validate the date format (if needed)

            // Get all appointments for the doctor on the selected date
            $appointments = Patient::join('token_booking', 'token_booking.BookedPerson_id', '=', 'patient.UserId')
                ->join('docter', 'docter.UserId', '=', 'token_booking.doctor_id') // Join the doctor table
                ->where('patient.UserId', $patient->UserId)
                ->orderBy('token_booking.date', 'asc')
                ->orderBy('token_booking.TokenTime', 'asc')
                ->orderBy('token_booking.TokenNumber', 'asc')
                ->where('Is_completed', 0)
                ->distinct()
                ->get(['token_booking.*', 'docter.*']);

            if ($appointments->isEmpty()) {

                return $this->sendResponse('Appointments', null, '1', 'No appointments found for the patient.');
            }

            // Initialize an array to store appointments along with doctor details
            $appointmentsWithDetails = [];
            $DocterEarly = 0;
            $DocterLate = 0;


            $firstAppointment = $appointments->first();
            $doctorId = $firstAppointment->doctor_id;
            $ClinicId = $firstAppointment->clinic_id;
            $currentDate = Carbon::now()->toDateString();



            // Get the doctor's schedule for the current date
            $doctorSchedule = Schedule::where('docter_id', $doctorId)->where('hospital_Id', $ClinicId)
                ->get();


            $tokensJson = $doctorSchedule->first()->tokens;

            $tokensArray = json_decode($tokensJson, true);


            $today_schedule = TodaySchedule::select('id', 'tokens', 'date', 'hospital_Id', 'delay_time', 'delay_type')
                ->where('docter_id',  $doctorId)
                ->where('hospital_Id', $ClinicId)
                ->where('date', $currentDate)
                ->first();


            if ($today_schedule) {
                $tokensArray = json_decode($today_schedule->tokens, true);
            }

            $firstToken = reset($tokensArray);
            $firstTime = $firstToken['Time'];


            $doctorSchedule = Schedule::where('docter_id', $doctorId)->where('hospital_Id', $ClinicId)->first();

            $morningEvneningTokens = $doctorSchedule->tokens;

            $today_schedules = TodaySchedule::select('id', 'tokens', 'date', 'hospital_Id', 'delay_time', 'delay_type')
                ->where('docter_id',  $doctorId)
                ->where('hospital_Id', $ClinicId)
                ->where('date', $currentDate)
                ->first();

            if ($today_schedules) {
                $morningEvneningTokens = $today_schedules->tokens;
            }


            $tokens = json_decode($morningEvneningTokens, true);

            $tokenCount = count($tokens);


            $TokenArray = TokenBooking::where('doctor_id', $doctorId)
                ->where('clinic_id', $ClinicId)
                ->whereDate('date', '=', $currentDate)
                ->select('checkinTime', 'checkoutTime')
                ->selectRaw('CAST(TokenNumber AS SIGNED) AS token')
                ->orderby('token', 'asc')
                ->get()->toArray();


            if (!empty($TokenArray)) {

                $existingTokens = array_column($TokenArray, "token");


                $unbookedTokens = [];

                //Iterate through all possible token numbers
                for ($i = 1; $i <= $tokenCount; $i++) {
                    // Check if the token number is not booked
                    if (!in_array($i, $TokenArray)) {
                        // If not booked, add it to the unbooked tokens array
                        $unbookedTokens[] = $i;
                    }
                }

                for ($i = 1; $i <= $tokenCount; $i++) {
                    if (!in_array($i, $existingTokens)) {
                        $TokenArray[] = [
                            "checkinTime" => null,
                            "checkoutTime" => null,
                            "token" => $i
                        ];
                    }
                }
                usort($TokenArray, function ($a, $b) {
                    return $a['token'] - $b['token'];
                });

                $foundKey = 0;

                foreach ($TokenArray as $key => $TokenArrays) {

                    if ($TokenArrays["checkinTime"] === null && $TokenArrays["checkoutTime"] === null) {
                        $foundKey = $key;
                        break;
                    }
                }

                $dateNow = date('Y-m-d');
                $newkey = $foundKey - 1;

                if ($newkey >= 0) {

                    $tokenStart = $tokens[$newkey]['Time'];

                    $tokenStartDateString = $dateNow . ' ' . $tokenStart;
                    $tokenEnd = $tokens[$newkey]['Tokens'];
                    $tokenEndDateString = $dateNow . ' ' . $tokenEnd;
                    $checkInString = $TokenArray[$newkey]['checkinTime'];
                    $checkOutString = $TokenArray[$newkey]['checkoutTime'];

                    $checkIn = Carbon::parse($checkInString);
                    $checkOut = Carbon::parse($checkOutString);
                    $tokenStartDate = Carbon::parse($tokenStartDateString);
                    $tokenEndDate = Carbon::parse($tokenEndDateString);


                    if ($checkInString !== null && $checkOutString == null) {

                        if ($tokenStartDate->greaterThan($checkIn)) {
                            $difference = -$tokenStartDate->diffInSeconds($checkIn);
                        } elseif ($tokenStartDate->lessThan($checkIn)) {
                            $difference = $checkIn->diffInSeconds($tokenStartDate);
                        } else {
                            $difference = 0;
                        }

                        $patientsDelay = $difference;
                        $endDifference = 0;
                    } elseif ($checkInString !== null && $checkOutString !== null) {
                        if ($tokenEndDate->greaterThan($checkOut)) {
                            $endDifference = -$tokenEndDate->diffInSeconds($checkOut);
                        } elseif ($tokenEndDate->lessThan($checkOut)) {
                            $endDifference = $checkOut->diffInSeconds($tokenEndDate);
                        } else {
                            $endDifference = 0;
                        }
                        //$patientsDelay += $endDifference;
                        $patientsDelay = $endDifference;
                        Log::info('patientsDelay: ' . json_encode($patientsDelay));
                    }
                } else {
                    $patientsDelay = 0;
                }
            } else {
                $patientsDelay = 0;
            }
            $appointmentsWithDetails = [];
            $DelayTime = 20 * 60;
            // Iterate through each appointment and add symptoms information
            foreach ($appointments as $key => $appointment) {



                $symptoms = json_decode($appointment->Appoinmentfor_id, true);
                $isDoctorOnLeave = DocterLeave::where('docter_id', $appointment->doctor_id)
                    ->where('hospital_id', $appointment->clinic_id)
                    ->where('date', $appointment->date)
                    ->exists();
                if ($isDoctorOnLeave) {
                    // Doctor is on leave
                    $leaveMessage = 'Doctor is on emergency leave.';
                } else {
                    $leaveMessage = ''; // No leave message if doctor is not on leave
                }


                $doctorId = $appointment->doctor_id;
                $clinicId = $appointment->clinic_id;

                $currentOngoingToken = TokenBooking::where('doctor_id', $doctorId)
                    ->where('clinic_id', $clinicId)
                    ->where('Is_checkIn', 1)
                    ->where('date', $currentDate)
                    ->orderBy('TokenNumber', 'ASC')
                    ->pluck('TokenNumber');

                if ($currentOngoingToken) {
                    $CurrentToken = $currentOngoingToken->max();
                } else {
                    $CurrentToken = 0;
                }


                if ($currentOngoingToken) {
                    $CurrentToken = $currentOngoingToken->max();
                } else {
                    $CurrentToken = 0;
                }
                $today_scheduleforlateanderaly = TodaySchedule::select('id', 'tokens', 'date', 'hospital_Id', 'delay_time', 'delay_type', 'section')
                    ->where('docter_id',  $appointment->doctor_id)
                    ->where('hospital_Id', $appointment->clinic_id)
                    ->where('date', $appointment->date)
                    ->get();


                $DoctorEarly = null;
                $DoctorLate = null;


                $estimateTime = Carbon::parse($appointment->TokenTime)
                    ->addSeconds($patientsDelay)
                    ->subSeconds($DelayTime);
                    Log::info('estimateTime  without delay difference: ' . json_encode($estimateTime));



                $delayDifference = 0;

                foreach ($today_scheduleforlateanderaly as $schedule) {
                    if ($schedule->delay_type === 1) {
                        $DoctorEarly = $schedule->delay_time;
                        // Accumulate early delay time
                        $delayDifference -= $schedule->delay_time;
                    } elseif ($schedule->delay_type === 2) {
                        $DoctorLate = $schedule->delay_time;
                        // Accumulate late delay time (subtract as negative value)
                        $delayDifference += $schedule->delay_time;
                    }
                }
                $estimateTime = $estimateTime->addMinutes($delayDifference);
                $estimatedTimeFormatted = $estimateTime->format('g:i A');

                Log::info('estimatedTimeFormatted: ' . json_encode($estimatedTimeFormatted));

                $mainSymptoms = MainSymptom::select('id', 'Mainsymptoms AS symtoms')
                    ->where('user_id', $appointment->BookedPerson_id)
                    ->where('doctor_id', $appointment->doctor_id)
                    ->where('clinic_id', $appointment->clinic_id)
                    ->where('date', $appointment->date)
                    ->where('TokenNumber', $appointment->TokenNumber)
                    ->get()
                    ->toArray();

                $appointmentDetails = [
                    'Doctor_id' => $appointment->doctor_id,
                    'TokenNumber' => $appointment->TokenNumber,
                    'Date' => $appointment->date,
                    'Startingtime' => Carbon::parse($appointment->TokenTime)->format('g:i'),
                    'PatientName' => $appointment->PatientName,
                    'main_symptoms' =>  $mainSymptoms,
                    'other_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray(),
                    'TokenBookingDate' => Carbon::parse($appointment->Bookingtime)->toDateString(),
                    'TokenBookingTime' => Carbon::parse($appointment->Bookingtime)->toTimeString(),
                    'ConsultationStartsfrom' => Carbon::parse($firstTime)->format('g:i'),
                    'DoctorEarlyFor' => intval($DoctorEarly),
                    'DoctorLateFor' => intval($DoctorLate),
                    'estimateTime' => $estimatedTimeFormatted,
                    'currentOngoingToken' => $CurrentToken,
                    'LeaveMessage' => $leaveMessage,
                ];

                $clinicName = DocterAvailability::where('id', $appointment->clinic_id)->value('hospital_Name');
                $doctorDetails = [
                    'firstname' => $appointment->firstname,
                    'secondname' => $appointment->lastname,
                    'Specialization' => $appointment->specialization,
                    'DocterImage' => asset("DocterImages/images/{$appointment->docter_image}"),
                    'Mobile Number' => $appointment->mobileNo,
                    'MainHospital' => $clinicName,
                    'subspecification_id' => $appointment->subspecification_id,
                    'specification_id' => $appointment->specification_id,
                    'specifications' => explode(',', $appointment->specifications),
                    'subspecifications' => explode(',', $appointment->subspecifications),
                    'clincs' => $this->getClinics($appointment->doctor_id),
                ];

                $combinedDetails = array_merge($appointmentDetails, $doctorDetails);

                $appointmentsWithDetails[] = $combinedDetails;
            }


            return $this->sendResponse('Appointments', ['appointmentsDetails' => $appointmentsWithDetails], '1', 'Appointments retrieved successfully.');
        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }

    public function liveTokenEstimate(Request $request)
    {

        $patient_user_id = $request->UserId;
        //patient_user_id = id in users table
        $currentDate = Carbon::now()->toDateString();

        //print_r($currentDate);exit;
        $patient_details = Patient::select('id', 'firstname', 'lastname', 'mediezy_patient_id')
            ->where('UserId', $patient_user_id)
            ->orderBy('created_at', 'desc')
            ->first();

        Log::info("patient_details =$patient_details");

        $token_details = TokenBooking::select('id', 'TokenNumber', 'doctor_id', 'date', 'TokenTime', 'clinic_id', 'Appoinmentfor_id', 'MobileNo')
            ->where('patient_id', $patient_user_id)
            ->where('date', $currentDate)
            //->where('hospital_Id', $clinic_id)
            // ->orderBy('created_at', 'desc')
            ->first();

        Log::info("token_details =$token_details");
        $doctor_id = $token_details->doctor_id;


        if ($token_details) {

            $doctor_id = $token_details->doctor_id;

            Log::info("doctor_id =$doctor_id");
        }


        $patient_details_array = [];
        if (isset($patient_details) && ($patient_details->count() > 0)) {
            foreach ($patient_details as $patient) {
                $token_details_array[] = [
                    'patient_id' => $patient->id,
                    'full_name' => $patient->firstname . ' ' . $patient->lastname,
                    'docter_image' => $patient->docter_image,
                    'mediezy_patient_id' => $patient->mediezy_patient_id,
                ];
            }
        }

        // Log::info("token_details_array =$patient_details_array");

        $token_details_array = [];
        if (isset($token_details) && ($token_details->count() > 0)) {
            foreach ($token_details as $booked_token) {
                if (is_object($booked_token)) {
                    $token_details_array[] = [

                        'booked_token_number' => $booked_token->TokenNumber,
                        'doctor_id' => $booked_token->doctor_id,
                        'token_booking_date' => $booked_token->date,
                        'token_booking_time' => $booked_token->TokenTime,
                    ];
                }
            }
        }
        Log::info("patient_details =" . json_encode($patient_details));
        Log::info("token_details =" . json_encode($token_details));

        // Log::info("token_details_array =$token_details_array");



        //////
        $current_ongoing_token_details = TokenBooking::select('checkinTime', 'TokenNumber')
            ->where('date', $currentDate)
            ->where('Is_checkIn', 1)
            ->where('Is_completed', 0)
            ->orderBy('created_at', 'desc')
            ->first();

        $lastTokenBooking = TokenBooking::select('checkoutTime', 'checkinTime')
            ->where('date', $currentDate)
            ->where('TokenNumber', $current_ongoing_token_details)
            ->orderBy('created_at', 'desc')
            ->first();

        $last_checkout_time = $lastTokenBooking->checkoutTime ?? null;
        $last_checkin_time = $lastTokenBooking->checkinTime ?? null;
        $current_ongoing_token = $current_ongoing_token_details->TokenNumber ?? null;

        //
        Log::info("current_ongoing_token =$current_ongoing_token");


        //here check for case if token taken time is grrater than 10





        ////



        $patient_mobile_number_details =  TokenBooking::select('id', 'TokenNumber', 'MobileNo')
            ->where('BookedPerson_id', $patient_user_id)
            ->whereDate('date', $currentDate)
            //->where('hospital_Id', $clinic_id)
            ->orderBy('created_at', 'desc')
            ->first();

        $patient_mobile_number = $patient_mobile_number_details->MobileNo;

        Log::info("patient_mobile_number =$patient_mobile_number");

        // print_r($patient_mobile_number);exit;
        $clinic_details = TokenBooking::select('clinic_id')->where('doctor_id', $doctor_id)
            ->where('MobileNo', $patient_mobile_number)
            ->orderByDesc('created_at')
            ->first();

        $clinic_id = $clinic_details ? $clinic_details->clinic_id : null;
        Log::info("clinic_id =$clinic_id");



        $all_current_date_token = schedule::select('tokens')->where('docter_id', $doctor_id)
            ->where('date', $currentDate)
            ->where('hospital_id', $clinic_id)
            ->first();

        //print_r($all_current_date_token);exit;

        $today_schedule_token = TodaySchedule::select('delay_time', 'delay_type', 'tokens')
            ->where('docter_id', $doctor_id)
            ->where('hospital_id', $clinic_id)
            ->where('date', $currentDate)
            ->first();



        $token_for_estimate = $today_schedule_token ? $today_schedule_token->tokens : $all_current_date_token->tokens;
        $token_for_estimate = json_decode($token_for_estimate, true);
        $delayTime = $today_schedule_token ? $today_schedule_token->delay_time : null;



        ///---------estimate time calculation-----------//

        // Sort the array based on the "Number" field in ascending order
        usort($token_for_estimate, function ($a, $b) {
            return $a['Number'] - $b['Number'];
        });

        // Get the first element after sorting
        $first_token = reset($token_for_estimate);

        // Assign the start time to $token_start_time
        $token_start_time = $first_token['Time'];
        $token_end_time = $first_token['Tokens'];


        $minutesToAdd = 20;   // default as per requirement

        // Creating a DateTime object from the original time
        $dateTime = new DateTime($token_start_time);

        // Adding minutes to the DateTime object
        $dateTime->add(new DateInterval('PT' . $minutesToAdd . 'M'));

        // Formatting the result
        $newTime = $dateTime->format('H:i:A');

        $estimatedTime = $newTime;


        if ($delayTime !== null) {
            $estimatedTime = $estimatedTime + $delayTime;
        }


        /////////////check if doc late , early , break and update estimatedTimeInMinutes ///////////////////
        $doc_delay_type = TodaySchedule::select('delay_type', 'delay_time', 'created_at')
            //->where('delay_type', 1)
            ->where('docter_id', $doctor_id)
            ->where('hospital_id', $clinic_id)
            ->where('date', $currentDate)
            ->orderBy('created_at', 'DESC')
            ->first();



        $break_request_timestamp = $doc_delay_type ? $doc_delay_type->created_at : null;




        if ($doc_delay_type) {
            $delay_type = $doc_delay_type->delay_type;
            $delay_time = $doc_delay_type->delay_time;

            if ($delay_type == 1) {
                $estimatedTime -= $delay_time;
            } elseif ($delay_type == 2) {
                $estimatedTime += $delay_time;
            } elseif ($delay_type == 3)
                $estimatedTime += $delay_time;
        }




        ///early doc check ///

        // $doc_early_check = TodaySchedule::select('delay_time', 'delay_type')
        //     ->where('delay_type', 1)
        //     ->where('docter_id', $doctor_id)
        //     ->where('hospital_id', $clinic_id)
        //     ->where('date', $currentDate)
        //     ->first();

        // $doc_late_check = TodaySchedule::select('delay_time', 'delay_type')
        //     ->where('delay_type', 2)
        //     ->where('doctor_id', $doctor_id)
        //     ->where('hospital_id', $clinic_id)
        //     ->where('date', $currentDate)
        //     ->first();

        // $doc_break_check = TodaySchedule::select('delay_time', 'delay_type')
        //     ->where('delay_type', 3)
        //     ->where('doctor_id', $doctor_id)
        //     ->where('hospital_id', $clinic_id)
        //     ->where('date', $currentDate)
        //     ->first();

        $estimate_details = [
            'current_ongoing_token' => $current_ongoing_token,
            'estimatedTime' => $estimatedTime,
        ];



        $response_data = [
            'patient_details' => $patient_details_array,
            'token_details' => $token_details_array,
            'estimate_details' => $estimate_details,
        ];

        return response()->json($response_data);
    }
}
