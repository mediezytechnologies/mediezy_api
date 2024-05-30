<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Docter;
use App\Models\DocterLeave;
use App\Models\MainSymptom;
use App\Models\NewDoctorSchedule;
use App\Models\NewTokens;
use App\Models\Patient;
use App\Models\RescheduleTokens;
use App\Models\Symtoms;
use App\Models\TokenBooking;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;

class BookingEstimationController extends Controller
{
    public function rescheduleTokensCheck($patient_user_id)
    {
        $reschedule_tokens = RescheduleTokens::where('booked_user_id', $patient_user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $upcoming_appointments = [];
        foreach ($reschedule_tokens as $token) {

            $patient_name = Patient::where('id', $token->patient_id)->select('firstname', 'lastname')->first();

            $patient_full_name = $patient_name->firstname . ' ' . $patient_name->lastname;

            //extra data
            $doctor_details = Docter::select('firstname', 'lastname', 'docter_image')
                ->where('id', $token->doctor_id)
                ->first();

            $doctor_name = $doctor_details->firstname . ' ' . $doctor_details->lastname;
            $doctor_image = $doctor_details->docter_image ? asset("DocterImages/images/{$doctor_details->docter_image}") : null;

            //clinic
            $clinic_name = Clinic::where('clinic_id', $token->clinic_id)->value('clinic_name');
            $clinic_doctor_id =  $token->doctor_id;

            $clinics = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')
                ->where('docter.id', $clinic_doctor_id)
                ->distinct()
                ->get();

            $clinicData = [];
            foreach ($clinics as $clinic) {

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
            $current_time = now();
            //next available tokens
            $next_available_tokens = NewTokens::select('token_number', 'token_start_time')
                ->where(function ($query) {
                    $query->whereNull('token_booking_status')
                        ->orWhere('token_booking_status', 0);
                })
                ->where('doctor_id', $token->doctor_id)
                ->where('is_reserved', 0)
                ->where('token_start_time', '>', $current_time)
                ->orderBy('token_start_time', 'asc')
                ->first();

            //next available token details

            $format_token_date = $next_available_tokens ? Carbon::parse($next_available_tokens->token_start_time)->format('d-m-Y') : null;
            $format_token_time = $next_available_tokens ? Carbon::parse($next_available_tokens->token_start_time)->format('h:i a') : null;
            $next_available_token_date_time = $next_available_tokens ? $format_token_date . ' - ' . $format_token_time : null;
            $next_available_token_number = $next_available_tokens ? $next_available_tokens->token_number : 0;
            $next_available_token_date = $next_available_tokens ? $next_available_token_date_time : null;

            $dateTime = new DateTime($token['token_start_time']);
            $formattedTime = $dateTime->format('h:i A');
            ////
            $appointment_info = [
                'token_number' => $token->token_number,
                'patient_id' => $token->patient_id,
                'token_scheduled_date' =>  $token->token_schedule_date,
                'doctor_late_time' => 0,
                'doctor_early_time' => 0,
                'doctor_break_time' => 0,
                'is_checkedin' => 0,
                'is_checkedout' => 0,
                'checkin_time' => null,
                'checkout_time' => null,
                'extra_time_taken' => null,
                'less_time_taken' => null,
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
                'token_start_time' => $formattedTime,
                'schedule_late_message' => null,
                'next_available_token_number'  => $next_available_token_number,
                'next_available_token_date'  => $next_available_token_date,
                'mediezy_doctor_id' => 'MDD98722',
                'token_id' => 0,
                'is_reached' => 0,
                'is_reserved' => 0,
                'main_symptoms' => null,
                'other_symptom' => [
                    'symptoms' => "",
                ],
                'clinics' => $clinicData,

            ];

            $upcoming_appointments[] = $appointment_info;
        }
        $today = date('Y-m-d');

        foreach ($upcoming_appointments as $key => $appointment_info) {
            $token_scheduled_date = $appointment_info['token_scheduled_date'];
            if ($token_scheduled_date < $today) {
                unset($upcoming_appointments[$key]);
            }
        }
        return $upcoming_appointments;
    }

    public function upcomingEstimateCalculation($patient_user_id)
    {
        $patient_id = Patient::where('UserId', $patient_user_id)->value('id');

        if (!$patient_id) {
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
            ->orderBy('token_scheduled_date', 'ASC')
            ->distinct()
            ->get();

        //$filtered_tokens_array = [];

        foreach ($booked_schedules as $schedule) {
            $schedule_id_all = $schedule->schedule_id;
            $today_booked_tokens_array = $this->LiveTokenEstimatecalculate($patient_user_id, $schedule_id_all);

            $filtered_tokens_array = array_merge($filtered_tokens_array, array_filter($today_booked_tokens_array, function ($token_data) use ($patient_user_id) {
                return $token_data['booked_user_id'] == $patient_user_id && $token_data['is_checkedout'] != 1;
            }));
        }

        foreach ($filtered_tokens_array as &$token_data) {

            unset($token_data['checkin_difference']);
            unset($token_data['booked_user_id']);
            unset($token_data['schedule_type']);
            unset($token_data['break_start_time']);
            unset($token_data['break_end_time']);
            unset($token_data['deleted_at']);
        }
        ///unset old upcoming appoitments
        $today = date('Y-m-d');

        foreach ($filtered_tokens_array as $key => $token_data_1) {
            $token_scheduled_date = $token_data_1['token_scheduled_date'];
            if ($token_scheduled_date < $today) {
                unset($filtered_tokens_array[$key]);
            }
        }

        usort($filtered_tokens_array, function ($a, $b) {
            $dateComparison = strcmp($a['token_scheduled_date'], $b['token_scheduled_date']);

            if ($dateComparison == 0) {
                return $a['token_number'] - $b['token_number'];
            }

            return $dateComparison;
        });



        return response()->json([
            'status' => true,
            'message' => 'Upcoming details fetched successfully',
            'upcoming appointments' => array_values($filtered_tokens_array)
        ], 200);
    }

    public function LiveTokenEstimatecalculate($patient_user_id, $schedule_id_all)
    {

        Log::channel('doctor_schedules')->info("//////////////patientLiveTokenEstimate/////////////////");
        $today_booked_tokens = NewTokens::where('token_booking_status', 1)
            ->where('schedule_id', $schedule_id_all)
            ->orderBy('token_start_time', 'ASC')
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
        $extra_time_late  = 0;

        foreach ($today_booked_tokens_array as $key => &$token) {
            $extraTime = $token['extra_time_taken'];
            $lessTime = $token['less_time_taken'];
            $less_checkin_time = $token['checkin_difference'];
            $extra_checkin_time = $token['late_checkin_duration'];
            if ($extraTime !== null) {
                $totalExtraTime += $extraTime;
            }
            if ($lessTime !== null) {
                $totalLessTime += $lessTime;
            }
            if ($less_checkin_time !== null) {
                $total_checkin_time += $less_checkin_time;
            }

            if ($extra_checkin_time !== null) {
                $extra_time_late += $extra_checkin_time;
            }

            $estimateTime = Carbon::parse($token['token_start_time'])
                ->addMinutes($totalExtraTime - $totalLessTime);
            $estimateTime->subMinutes($total_checkin_time);
            $estimateTime->addMinutes($extra_time_late);
            // sub 20 minutes from the final estimate time
            $estimateTimeWithDefaultTime = $estimateTime->subMinutes(20);
            $token['estimate_time'] = $estimateTimeWithDefaultTime->toDateTimeString();
            $token['new_estimate_time'] = $token['estimate_of_next_token'];

            if ($token['new_estimate_time'] == null) {

                $token['new_estimate_time'] = Carbon::parse($token['estimate_checkin_time'])->format('h:i A');
            }

            $new_token_start_time = Carbon::parse($token['token_start_time'])->format('H:i');
            $token_new_estimate = Carbon::parse($token['new_estimate_time'])->format('H:i');


            if ($token_new_estimate == $new_token_start_time) {
                $token['new_estimate_time'] = Carbon::parse($token['estimate_checkin_time'])->subMinutes(20)->format('h:i A');
            }
            $token_doctor_early_time = $token['doctor_early_time'];
            $token_doctor_late_time = $token['doctor_late_time'];
            $token_doctor_break_time = $token['doctor_break_time'];


            if ($token_doctor_late_time != null) {
                $new_estimate_time = Carbon::parse($token['estimate_checkin_time'])->addMinutes($token_doctor_late_time);
                $token['new_estimate_time'] = $new_estimate_time->format('h:i A');
            }

            if ($token_doctor_early_time != null) {
                $new_estimate_time = Carbon::parse($token['estimate_checkin_time'])->subMinutes($token_doctor_early_time);
                $token['new_estimate_time'] = $new_estimate_time->format('h:i A');
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
            $bookingTimeString = $token_booking_patient_details->Bookingtime ?? NULL;

            $bookingTime = DateTime::createFromFormat('Y-m-d H:i:s', $bookingTimeString);

            if (isset($token_booking_patient_details)) {
                $token['token_booked_date'] = $bookingTime->format('y:m:d h:i A');
            } else {
                $token['token_booked_date'] = null;
            }

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
            $token['mediezy_doctor_id'] = $doctor_details->mediezy_doctor_id;

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
            if (isset($other_symptom_id)) {
                $other_symptom_json = json_decode($other_symptom_id->Appoinmentfor_id, true);
            }


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
            // $token['next_available_token_number'] = 0;
            // $token['next_available_token_date'] = NULL;
            //next available tokens
            $next_available_tokens = NewTokens::select('token_number', 'token_start_time')
                ->where(function ($query) {
                    $query->whereNull('token_booking_status')
                        ->orWhere('token_booking_status', 0);
                })
                ->where('doctor_id', $clinic_doctor_id)
                ->where('is_reserved', 0)
                ->where('token_start_time', '>', $current_time)
                ->orderBy('token_start_time', 'asc')
                ->first();

            //next available token details

            $format_token_date = $next_available_tokens ? Carbon::parse($next_available_tokens->token_start_time)->format('d-m-Y') : null;
            $format_token_time = $next_available_tokens ? Carbon::parse($next_available_tokens->token_start_time)->format('h:i a') : null;
            $next_available_token_date_time = $next_available_tokens ? $format_token_date . ' - ' . $format_token_time : null;
            $next_available_token_number = $next_available_tokens ? $next_available_tokens->token_number : 0;
            $next_available_token_date = $next_available_tokens ? $next_available_token_date_time : null;
            $token['next_available_token_number'] = $next_available_token_number;
            $token['next_available_token_date'] = $next_available_token_date;




            ////////////////////////////////////////////////////////////
            $doctorID = Docter::where('id', $token['doctor_id'])->pluck('UserId')->first();
            $token['doctor_id'] = $doctorID;
            $token['clinics'] = $clinicData;
            $other_symptom = Symtoms::select('symtoms')
                ->where('id', $other_symptom_array_value)
                ->first();
            $token['other_symptom'] = $other_symptom;

            $current_date = Carbon::today()->format(('Y-m-d'));
            $token_booking_date = Carbon::parse($token['token_scheduled_date'])->format(('Y-m-d'));

            $estimate_time_data = Carbon::parse($token['estimate_arrival_time']);
            $token_start_datetime_data = Carbon::parse($token['token_start_time']);

            $doctor_early_time = $token['doctor_early_time'];

            if ($doctor_early_time != NULL) {

                $token_start_datetime_data->addMinutes($doctor_early_time);
            }

            $token_date = $token_start_datetime_data->format('Y-m-d');

            if ($estimate_time_data > $token_start_datetime_data) {
                $time_check = $estimate_time_data;
            } else if ($estimate_time_data < $token_start_datetime_data) {
                $time_check = $token_start_datetime_data;
            }

            $check_current_time = Carbon::now();

            $token['patient_absent'] = false;

            // if ($token['is_checkedin'] == 1 && $token['is_checkedout'] == 0) {
            //     $token['patient_absent'] = false;
            // } else {
            //     if ($current_date <= $token_date && $check_current_time >= $time_check && $token['is_reached'] != 1) {
            //         $token['patient_absent'] = true;
            //     } elseif ($current_date != $token_booking_date) {
            //         $token['patient_absent'] = false;
            //     }
            // }

            if ($token['is_reached'] === null) {
                $token['is_reached'] = 0;
            }

            //////////////////////////////


            $schedule_delay = NewTokens::select('token_start_time')
                ->where('schedule_id', $token['schedule_id'])
                ->first();

            if ($schedule_delay) {
                $token_start_time = Carbon::parse($schedule_delay->token_start_time);
                $delay_time = $token_start_time->diffInMinutes();

                $is_schedule_started = NewTokens::where('schedule_id', $token['schedule_id'])
                    ->where('token_scheduled_date', $token['token_scheduled_date'])
                    ->where('is_checkedin', 1)
                    ->exists();

                $token['schedule_late_message'] = $is_schedule_started
                    ? "Schedule late by $delay_time minutes"
                    : null;
            } else {
                $token['schedule_late_message'] = null;
            }



            ////////////////unsett unnessary data/////////////

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
            unset($token['late_checkin_duration']);
            unset($token['estimate_time']); //unformatted////
        }

        return $today_booked_tokens_array;
    }
}
