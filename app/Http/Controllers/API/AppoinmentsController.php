<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Clinic;
use App\Models\MainSymptom;
use App\Models\DocterLeave;
use App\Models\DocterAvailability;
use App\Models\Patient;
use App\Models\schedule;
use App\Models\Docter;
use App\Models\Laboratory;
use App\Models\Medicalshop;
use App\Models\Medicine;
use App\Models\NewDoctorSchedule;
use App\Models\NewTokens;
use App\Models\RescheduleTokens;
use App\Models\Symtoms;
use App\Models\TodaySchedule;
use App\Models\TokenBooking;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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


                Log::info('newkey: ' . json_encode($newkey));
                Log::info('foundKey: ' . json_encode($foundKey));

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
                    Log::info('patientsDelay: ' . json_encode($patientsDelay));
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
                Log::info('DelayTime: ' . $DelayTime);
                Log::info('estimateTime  without delay difference: ' . $estimateTime);



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


    public function rescheduleTokensCheck($patient_user_id)
    {
        $reschedule_tokens = RescheduleTokens::where('booked_user_id', $patient_user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $upcoming_appointments = [];

        foreach ($reschedule_tokens as $token) {

            //patirnt data

            $patient_name = Patient::where('UserId', $patient_user_id)->select('firstname', 'lastname')->first();
            $patient_full_name = $patient_name->firstname . ' ' . $patient_name->lastname;


            //extra data
            $doctor_details = Docter::select('firstname', 'lastname', 'docter_image')
                ->where('id', $token->doctor_id)
                ->first();

            $doctor_name = $doctor_details->firstname . ' ' . $doctor_details->lastname;
            $doctor_image = $doctor_details->docter_image ? asset("DocterImages/images/{$doctor_details->docter_image}") : null;

            //clinic
            $clinic_name = Clinic::where('clinic_id', $token->clinic_id)->value('clinic_name');
            ///

            //extra clinic details
            /////////////////////////////////////////////////////////////////////
            //all clinics 12-03

            $clinic_doctor_id =  $token->doctor_id;

            $clinics = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
                ->where('docter.id', $clinic_doctor_id)
                ->distinct()
                ->get();

            $clinicData = [];
            foreach ($clinics as $clinic) {

                /// token cound and avalilability data
                $current_date = Carbon::now()->toDateString();
                $current_time = Carbon::now()->toDateTimeString();


                $total_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('doctor_id', $clinic_doctor_id)
                    ->count();

                $available_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('token_booking_status', NULL)
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $clinic_doctor_id)
                    ->count();

                //schedule details

                // $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

                $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $clinic_doctor_id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'ASC')
                    ->first();


                $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $clinic_doctor_id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'DESC')
                    ->first();

                if ($schedule_start_data && $schedule_end_data) {
                    $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                    $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
                } else {

                    $start_time = null;
                    $end_time = null;
                }

                //
                $clinicData[] = [
                    'clinic_id' => $clinic->clinic_id,
                    'clinic_name' => $clinic->clinic_name,
                    'clinic_start_time' => $start_time,
                    'clinic_end_time' => $end_time,
                    'clinic_address' => $clinic->address,
                    'clinic_location' => $clinic->location,
                    'clinic_main_image' => isset($clinic->clinic_main_image) ? asset("clinic_images/{$clinic->clinic_main_image}") : null,
                    'clinic_description' => $clinic->clinic_description,
                    'total_token_Count' => $total_token_count,
                    'available_token_count' => $available_token_count,
                ];
            }
            $doctorID = Docter::where('id', $token->doctor_id)->pluck('UserId')->first();

            ////
            $appointment_info = [
                'token_number' => $token->token_number,
                'token_scheduled_date' =>  $token->token_schedule_date,
                //'token_start_time' => "",
                // 'schedule_type' => null,
                'doctor_late_time' => 0,
                'doctor_early_time' => 0,
                'doctor_break_time' => 0,
                // 'break_start_time' => null,
                // 'break_end_time' => null,
                'is_checkedin' => 0,
                'is_checkedout' => 0,
                'checkin_time' => null,
                'checkout_time' => null,
                'extra_time_taken' => null,
                'less_time_taken' => null,
                // 'is_reserved' => 0,
                'reschedule_type' => $token->reschedule_type,
                'schedule_start_time' => "",
                'leave_status' => 0,
                'token_booked_date' => "",
                'live_token' => 0,
                'doctor_id' => $doctorID,
                'doctor_name' => $doctor_name,
                'doctor_image' => $doctor_image,
                'clinic_name' => $clinic_name,
                'estimate_arrival_time' => '00:00 AM',
                'patient_name' => $patient_full_name,
                'token_start_time' => "",
                'main_symptoms' => [
                    'Mainsymptoms' => "",
                ],
                'other_symptom' => [
                    'symptoms' => "",
                ],
                'clinics' => $clinicData,
            ];

            $upcoming_appointments[] = $appointment_info;
        }
        return $upcoming_appointments;
    }



    public function patientLiveTokenEstimate2($patient_user_id)
    {

        try{
        Log::channel('doctor_schedules')->info("estimate time started");

        $patient_id = Patient::where('UserId', $patient_user_id)->value('id');
        Log::channel('doctor_schedules')->info("patient_id: $patient_id");

        if (!$patient_id) {
            Log::channel('doctor_schedules')->info("Patient not found");
            return response()->json(['error' => 'Patient not found'], 404);
        }

        $filtered_tokens_array = [];

        $reschedule_check = $this->rescheduleTokensCheck($patient_user_id);
        if ($reschedule_check) {
            $filtered_tokens_array = array_merge($filtered_tokens_array, $reschedule_check);
        }

        $booked_schedules = NewTokens::where('token_booking_status', 1)
            ->where('booked_user_id', $patient_user_id)
            ->select('schedule_id')
            ->distinct()
            ->get();

        foreach ($booked_schedules as $schedule) {
            $schedule_id_all = $schedule->schedule_id;
            $today_booked_tokens_array = $this->patientLiveTokenEstimatecalculate($patient_user_id, $schedule_id_all);

            $filtered_tokens_array = array_merge($filtered_tokens_array, array_filter($today_booked_tokens_array, function ($token_data) use ($patient_user_id) {
                return $token_data['booked_user_id'] == $patient_user_id && $token_data['is_checkedout'] != 1;
            }));
        }

        foreach ($filtered_tokens_array as &$token_data) {
            unset($token_data['patient_id']);
            unset($token_data['checkin_difference']);
            unset($token_data['booked_user_id']);
            unset($token_data['schedule_type']);
            unset($token_data['is_reserved']);
            unset($token_data['break_start_time']);
            unset($token_data['break_end_time']);
            unset($token_data['deleted_at']);
        }

        Log::channel('doctor_schedules')->info("Upcoming details fetched successfully");

        return response()->json([
            'status' => true,
            'message' => 'Upcoming details fetched successfully',
            'upcoming appointments' => array_values($filtered_tokens_array)
        ], 200);

    } catch (\Exception $e) {
       
        \Log::error('An error occurred: ' . $e->getMessage());
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }




    public function patientLiveTokenEstimatecalculate($patient_user_id, $schedule_id_all)
    {

        Log::channel('doctor_schedules')->info("//////////////patientLiveTokenEstimate/////////////////");
        $today_booked_tokens = NewTokens::where('token_booking_status', 1)
            ->where('schedule_id', $schedule_id_all)
            ->orderBy('token_scheduled_date', 'ASC')
            ->orderBy('token_number', 'DESC')
            ->get();


        if ($today_booked_tokens->isEmpty()) {
            Log::channel('doctor_schedules')->info('Booked Tokens Not Found');
            return response()->json([
                'status' => true,
                'message' => 'Booked Tokens Not Found',
                'appointments' => NULL
            ], 200);
        }
        $today_booked_tokens_array = $today_booked_tokens->toArray();
        $totalExtraTime = 0;
        $totalLessTime = 0;
        $total_checkin_time = 0;
        $extra_time_late = 0;

        foreach ($today_booked_tokens_array as $key => &$token) {
            $tokenDate = Carbon::parse($token['token_start_time'])->format('Y-m-d');
            $currentDate = Carbon::now()->format('Y-m-d');
            if ($tokenDate > $currentDate) {

                $estimateTime = Carbon::parse($token['token_start_time'])->subMinutes(20);
                $token['estimate_time'] = $estimateTime->toDateTimeString();
            } else {
                $extraTime = $token['extra_time_taken'];
                $lessTime = $token['less_time_taken'];
                $less_checkin_time = $token['checkin_difference'];
                $extra_checkin_time = $token['late_checkin_duration'];
                if ($extraTime !== null) {
                    $totalExtraTime += $extraTime;
                    Log::channel('doctor_schedules')->info("totalExtraTime  = $totalExtraTime");
                }
                if ($lessTime !== null) {
                    $totalLessTime += $lessTime;
                }
                if ($less_checkin_time !== null) {
                    $total_checkin_time += $less_checkin_time;
                }
                if ($extra_checkin_time !== null) {
                    $extra_time_late -= $extra_checkin_time;
                }


                $estimateTime = Carbon::parse($token['token_start_time'])
                    ->addMinutes($totalExtraTime - $totalLessTime);
                $estimateTime->subMinutes($total_checkin_time);
                $estimateTime->addMinutes($extra_time_late);


                // sub 20 minutes from the final estimate time

                $estimateTimeWithDefaultTime = $estimateTime->subMinutes(20);
                $token['estimate_time'] = $estimateTimeWithDefaultTime->toDateTimeString();
                Log::channel('doctor_schedules')->info('Estimate time for token ' . $token['token_id'] . ': ' . $token['estimate_time']);
            }
            ///////////////////////////////////  EXTRA DATA /////////////////////////////////////
            ///// schedule details
            $estimate_schedule_id =   $token['schedule_id'];
            $schedule_details = NewDoctorSchedule::where('schedule_id', $estimate_schedule_id)->first();

            if ($schedule_details) {
                $schedule_start_time = Carbon::parse($schedule_details->start_time)->format('h:i A');
                $token['schedule_start_time'] = $schedule_start_time;
            } else {
                $token['schedule_start_time'] = NULL;
            }

            $token['doctor_late_time'] = $token['doctor_late_time'] ?? 0;
            $token['doctor_early_time'] = $token['doctor_early_time'] ?? 0;
            $token['doctor_break_time'] = $token['doctor_break_time'] ?? 0;


            /////////////////////////
            $current_date = Carbon::now()->format('Y-m-d');
            $token_data = NewTokens::where('schedule_id', $schedule_id_all)
                ->where('token_scheduled_date', $current_date)
                ->where('is_checkedin', 1)
                ->orderBy('checkin_time', 'DESC')
                ->first();


            if (!$token_data) {
                $live_token_number = 0;
            } else {
                $live_token_number = $token_data->token_number;
            }

            $new_token_id = $token['token_id'];
            $token_booking_patient_details = TokenBooking::select('PatientName', 'Appoinmentfor_id', 'Bookingtime')
                ->where('new_token_id', $new_token_id)
                ->first();
            $patient_name = $token_booking_patient_details->PatientName ?? NULL;

            ///doc leave check
            $leave_clinic_id = $token['clinic_id'];
            $leave_doctor_id = $token['doctor_id'];

            $doctor_leave_check_details = Docter::select('UserId')->where('id', $leave_doctor_id)->first();
            $doctor_user_id = $doctor_leave_check_details->UserId;

            $leave_current_date = Carbon::now()->format('Y-m-d');
            $leave_check = DocterLeave::where('docter_id', $doctor_user_id)
                ->where('hospital_id', $leave_clinic_id)
                ->where('date', $leave_current_date)
                ->first();
            if (!$leave_check) {
                $token['leave_status'] = 0;
            } else {
                $token['leave_status'] = 1;
            }
            $token['created_at'] = Carbon::parse($token['created_at']);
            $bookingTimeString = $token_booking_patient_details->Bookingtime;

            $bookingTime = DateTime::createFromFormat('Y-m-d H:i:s', $bookingTimeString);

            $token['token_booked_date'] = $bookingTime->format('y:m:d h:i A');
            $token['live_token'] = $live_token_number;
            $token['patient_name'] = $patient_name;
            if (is_string($token['token_start_time']) && !empty($token['token_start_time'])) {
                $dateTime = new DateTime($token['token_start_time']);
                $formattedTime = $dateTime->format('h:i A');
                $token['token_start_time'] = $formattedTime;
            }
            $estimate_doctor_id = $token['doctor_id'];
            $doctor_details = Docter::where('id', $estimate_doctor_id)->first();

            $doctor_image = $doctor_details->docter_image;

            $token['doctor_name'] = $doctor_details->firstname . ' ' . $doctor_details->lastname;
            $userImage = $doctor_image ? asset("DocterImages/images/{$doctor_image}") : null;
            $token['doctor_image'] = $userImage;
            $estimate_clinic_id = $token['clinic_id'];
            // $clinic_details = DocterAvailability::where('id', $estimate_clinic_id)->first();
            // $clinic_name = $clinic_details->hospital_Name;
            $clinic_details = Clinic::where('clinic_id', $estimate_clinic_id)->first();
            $clinic_name = $clinic_details->clinic_name;
            $token['clinic_name'] = $clinic_name;
            //////////////time format conversions/////////////////
            $formated_estimate_time = Carbon::parse($token['estimate_time'])->format('h:i A');
            $token['estimate_arrival_time'] =  $formated_estimate_time;


            //main symptoms

            $token_number_loop  = $token['token_number'];
            $ClinicId = $token['clinic_id'];

            $main_symptoms = MainSymptom::select('Mainsymptoms')
                ->where('user_id', $patient_user_id)
                ->where('TokenNumber', $token_number_loop)
                ->where('clinic_id', $ClinicId)
                ->first();
            $token['main_symptoms'] = $main_symptoms;


            //other symptoms
            $booked_user_id = $token['booked_user_id'];
            $booked_token_number = $token['token_number'];
            $booked_clinic_id = $token['clinic_id'];

            $other_symptom_id = TokenBooking::select('Appoinmentfor_id')
                ->where('BookedPerson_id', $booked_user_id)
                ->where('TokenNumber', $booked_token_number)
                ->where('clinic_id', $booked_clinic_id)
                ->orderBy('created_at', 'DESC')->first();

            // $token['other_symptom_id'] = $other_symptom_id;
            $other_symptom_json = json_decode($other_symptom_id->Appoinmentfor_id, true);

            $other_symptom_array_value = [];

            if (isset($other_symptom_json['Appoinmentfor2'])) {
                $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
            }

            /////////////////////////////////////////////////////////////////////
            //all clinics 12-03

            $clinic_doctor_id =  $token['doctor_id'];

            $clinics = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
                ->where('docter.id', $clinic_doctor_id)
                ->distinct()
                ->get();

            $clinicData = [];
            foreach ($clinics as $clinic) {

                /// token cound and avalilability data
                $current_date = Carbon::now()->toDateString();
                $current_time = Carbon::now()->toDateTimeString();


                $total_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('doctor_id', $clinic_doctor_id)
                    ->count();

                $available_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->where('token_booking_status', NULL)
                    ->where('token_start_time', '>', $current_time)
                    ->where('doctor_id', $clinic_doctor_id)
                    ->count();

                //schedule details

                // $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

                $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $clinic_doctor_id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'ASC')
                    ->first();


                $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                    ->where('doctor_id', $clinic_doctor_id)
                    ->where('clinic_id', $clinic->clinic_id)
                    ->where('token_scheduled_date', $current_date)
                    ->orderBy('token_start_time', 'DESC')
                    ->first();

                if ($schedule_start_data && $schedule_end_data) {
                    $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                    $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
                } else {

                    $start_time = null;
                    $end_time = null;
                }

                //
                $clinicData[] = [
                    'clinic_id' => $clinic->clinic_id,
                    'clinic_name' => $clinic->clinic_name,
                    'clinic_start_time' => $start_time,
                    'clinic_end_time' => $end_time,
                    'clinic_address' => $clinic->address,
                    'clinic_location' => $clinic->location,
                    'clinic_main_image' => isset($clinic->clinic_main_image) ? asset("clinic_images/{$clinic->clinic_main_image}") : null,
                    'clinic_description' => $clinic->clinic_description,
                    'total_token_Count' => $total_token_count,
                    'available_token_count' => $available_token_count,
                ];
            }
            ////////////////////////////////////////////////////////////////////

            $token['clinics'] = $clinicData;
            $other_symptom = Symtoms::select('symtoms')
                ->where('id', $other_symptom_array_value)
                ->first();
            $token['other_symptom'] = $other_symptom;
            ////////////////unsett unnessary data/////////////
            unset($token['token_id']);

            unset($token['clinic_id']);
            unset($token['schedule_id']);
            unset($token['actual_token_duration']);
            unset($token['assigned_token_duration']);
            unset($token['token_up_to']);
            unset($token['created_at']);
            unset($token['updated_at']);
            unset($token['token_booking_status']);
            unset($token['doctor_user_id']);
            unset($token['checkin_difference']);
            unset($token['token_booking_id']);
            unset($token['token_end_time']);

            unset($token['estimate_time']); //unformatted
        }
        Log::channel('doctor_schedules')->info("Today Booked Tokens For Live Token Estimate :: ", $today_booked_tokens_array);

        return $today_booked_tokens_array;
        // return response()->json([
        //     'status' => true,
        //     'message' => 'Upcoming details fetched successfully',
        //     'upcoming appointments' => $today_booked_tokens_array
        // ], 200);
    }


    public function getSortedPatientAppointments($patient_id, $userId)
    {

        try{
        $patient_data = Patient::where('UserId', $userId)->first();

        if (!$patient_data) {
            return response()->json([
                'status' => true,
                'completed_appointments' => NULL,
                'message' => 'Patient not found'
            ]);
        }

        $completed_token_details = NewTokens::select(
            'token_id',
            'doctor_id',
            'clinic_id',
            'token_number',
            'token_start_time'
        )
           // ->where('booked_user_id', $userId)
            ->where('is_checkedout', 1)
            ->where('patient_id', $patient_id)
            ->orderBy('updated_at', 'DESC')
            ->get();

        if (!$completed_token_details) {
            return response()->json([
                'status' => true,
                'completed_appointments' => NULL,
                'message' => 'No completed tokens found'
            ]);
        }

        $completed_appointments = [];
        foreach ($completed_token_details as $completed_tokens) {
            $appointment = [
                'token_id' => $completed_tokens->token_id,
                'doctor_id' => $completed_tokens->doctor_id,
                'clinic_id' => $completed_tokens->clinic_id,
                'token_number' => $completed_tokens->token_number,
                'token_start_time' => $completed_tokens->token_start_time,
            ];
            //doctor details
            $doctor_id = $completed_tokens->doctor_id;
            $doctor_details = Docter::select('firstname', 'lastname', 'docter_image')
                ->where('id', $doctor_id)
                ->first();
            if (!$doctor_details) {
                return response()->json(['message' => 'Doctor not found.'], 404);
            }

            $appointment['doctor_name'] = $doctor_details->firstname . ' ' . $doctor_details->lastname;

            //$appointment['doctor_image'] = $doctor_details->docter_image;
            $doctor_image = $doctor_details->docter_image;
            $userImage = $doctor_image ? asset("DocterImages/images/{$doctor_image}") : null;
            $appointment['doctor_image'] = $userImage;
            //clinic details

            $clinic_id = $completed_tokens->clinic_id;
            $clinic_details = Clinic::where('clinic_id', $clinic_id)->first();
            $appointment['clinic_name'] = $clinic_details ? $clinic_details->clinic_name : null;

            //symptoms
            $token_number_loop  = $completed_tokens->token_number;
            $ClinicId = $completed_tokens->clinic_id;

            $main_symptoms = MainSymptom::select('Mainsymptoms')
                ->where('user_id', $userId)
                ->where('TokenNumber', $token_number_loop)
                ->where('clinic_id', $ClinicId)
                ->first();
            $appointment['main_symptoms'] = $main_symptoms;
            $other_symptom_id = TokenBooking::select('Appoinmentfor_id')
                ->where('BookedPerson_id', $userId)
                ->where('TokenNumber', $token_number_loop)
                ->where('clinic_id', $ClinicId)
                ->orderBy('created_at', 'DESC')->first();

            $other_symptom_json = json_decode($other_symptom_id->Appoinmentfor_id, true);

            $other_symptom_array_value = [];

            if (isset($other_symptom_json['Appoinmentfor2'])) {
                $other_symptom_array_value = $other_symptom_json['Appoinmentfor2'];
            }
            $other_symptom = Symtoms::select('symtoms')
                ->where('id', $other_symptom_array_value)
                ->first();
            $appointment['other_symptom'] = $other_symptom;

            /////patient data
            $patient_details_data = TokenBooking::select(
                'PatientName',
                'notes',
                'ReviewAfter',
                'labtest',
                'Tokentime',
                'date',
                'prescription_image',
                'lab_id',
                'id',
                'medicalshop_id'
            )
                ->where('BookedPerson_id', $userId)
                ->where('TokenNumber', $token_number_loop)
                ->where('clinic_id', $ClinicId)
                ->orderBy('created_at', 'DESC')->first();

            $appointment['patient_name'] = $patient_details_data->PatientName;
            $appointment['notes'] = $patient_details_data->notes;
            $appointment['review_after'] = $patient_details_data->ReviewAfter;
            $appointment['lab_test'] = $patient_details_data->labtest;
            $appointment['token_time'] = $patient_details_data->Tokentime;
            $appointment['token_date'] = $patient_details_data->date;
            // $appointment['prescription_image'] = $patient_details_data->prescription_image;
            $appointment['prescription_image'] = $patient_details_data->prescription_image ? asset("LabImages/prescription/{$patient_details_data->prescription_image}") : null;

            //   $userImage = $doctor_image ? asset("DocterImages/images/{$doctor_image}") : null;

            //lab details
            $lab_data = Laboratory::select('firstname')->where('id', $patient_details_data->lab_id)->first();
            $appointment['lab_name'] = isset($lab_data->firstname) ? $lab_data->firstname : null;
            // Medical store
            $medical_store_data = Medicalshop::select('firstname')
                ->where('id', $patient_details_data->medicalshop_id)
                ->first();
            $appointment['medical_store_name'] = isset($medical_store_data->firstname) ? $medical_store_data->firstname : null;

            // Medical prescriptions
            $medical_prescriptions = Medicine::select('medicineName', 'Dosage', 'NoofDays', 'Noon', 'night', 'morning', 'type')
                ->where('user_id', $userId)
                ->where('token_id', $completed_tokens->token_id)
                ->get();

            $prescriptions_data = [];

            foreach ($medical_prescriptions as $medical_prescription) {
                $prescription_data = [
                    'medicine_name' => $medical_prescription->medicineName,
                    'medicine_dosage' => $medical_prescription->Dosage,
                    'medicine_number_ofdays' => $medical_prescription->NoofDays,
                    'Noon' => $medical_prescription->Noon,
                    'night' => $medical_prescription->night,
                    'morning' => $medical_prescription->morning,
                    'medicine_type' => $medical_prescription->type,
                ];


                $prescriptions_data[] = $prescription_data;
            }


            $appointment['medical_prescriptions'] = $prescriptions_data;
            $completed_appointments[] = $appointment;
        }

        return response()->json(['status' => true, 'completed_appointments' => $completed_appointments]);
    } catch (\Exception $e) {
       
        \Log::error('An error occurred: ' . $e->getMessage());
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }

    public function addVitals(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'token_id' => 'required',
            'height' => 'sometimes',
            'weight' => 'sometimes',
            'temperature' => 'required_with:temperature_type',
            'temperature_type' => 'required_with:temperature',
            'spo2' => 'sometimes',
            'sys' => 'sometimes',
            'dia' => 'sometimes',
            'heart_rate' => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        try {
            $vitalSaving = TokenBooking::where('new_token_id', '=', $request->token_id)->first();

            if ($vitalSaving) {
                $vitalSaving->height = $request->height;
                $vitalSaving->weight = $request->weight;
                $vitalSaving->temperature = $request->temperature;
                $vitalSaving->temperature_type = $request->temperature_type;
                $vitalSaving->spo2 = $request->spo2;
                $vitalSaving->sys = $request->sys;
                $vitalSaving->dia = $request->dia;
                $vitalSaving->heart_rate = $request->heart_rate;
                $vitalSaving->save();

                return response()->json(['status' => true, 'message' => "Vital add Successfully"]);
            } else {
                return response()->json(['status' => false, 'message' => "Token ID not found"]);
            }
        } catch (Exception $e) {

            return response()->json(['message' => 'Internal Server Error'],500);
        }
    }

    public function editVitals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_id' => 'required',
            'height' => 'sometimes',
            'weight' => 'sometimes',
            'temperature' => 'required_with:temperature_type',
            'temperature_type' => 'required_with:temperature',
            'spo2' => 'sometimes',
            'sys' => 'sometimes',
            'dia' => 'sometimes',
            'heart_rate' => 'sometimes'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        try {
            $vitalEditing = TokenBooking::where('new_token_id', $request->token_id)->first();

            if ($vitalEditing) {
                $vitalEditing->height = $request->height ?? $vitalEditing->height;
                $vitalEditing->weight = $request->weight ?? $vitalEditing->weight;
                $vitalEditing->temperature = $request->temperature ?? $vitalEditing->temperature;
                $vitalEditing->temperature_type = $request->temperature_type ?? $vitalEditing->temperature_type;
                $vitalEditing->spo2 = $request->spo2 ?? $vitalEditing->spo2;
                $vitalEditing->sys = $request->sys ?? $vitalEditing->sys;
                $vitalEditing->dia = $request->dia ?? $vitalEditing->dia;
                $vitalEditing->heart_rate = $request->heart_rate ?? $vitalEditing->heart_rate;
                $vitalEditing->save();

                return response()->json(['status' => true, 'message' => "Vital edited successfully"]);
            } else {
                return response()->json(['status' => false, 'message' => "Token ID not found"]);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Internal Server Error'],500);
        }
    }


    public function deleteVitals(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }

        try {
            $vitalDeleting = TokenBooking::where('new_token_id', $request->token_id)->first();

            if ($vitalDeleting) {
                $vitalDeleting->height = null;
                $vitalDeleting->weight = null;
                $vitalDeleting->temperature = null;
                $vitalDeleting->temperature_type = null;
                $vitalDeleting->spo2 = null;
                $vitalDeleting->sys  = null;
                $vitalDeleting->dia = null;
                $vitalDeleting->heart_rate = null;

                $vitalDeleting->save();
                return response()->json(['status' => true, 'message' => "Vital deleted successfully"]);
            } else {
                return response()->json(['status' => false, 'message' => "Token ID not found"]);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Internal Server Error'],500);
        }
    }
}
