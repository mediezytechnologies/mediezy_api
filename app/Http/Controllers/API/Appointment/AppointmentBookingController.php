<?php

namespace App\Http\Controllers\API\Appointment;


use App\Http\Controllers\Controller;
use App\Models\Docter;
use App\Models\NewTokens;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AppointmentBookingController extends Controller
{
    public function validateTokenBooking(Request $request)
    {

        $this->validate($request, [
            'BookedPerson_id' => 'required',
            'PatientName' => 'required',
            'gender' => 'required',
            'age' => 'required',
            'MobileNo' => 'required',
            'date' => 'required|date_format:Y-m-d',
            'TokenNumber' => 'required',
            'TokenTime' => 'required',
            'whenitstart' => 'required',
            'whenitcomes' => 'required',
            'doctor_id' => 'required',
            'Appoinmentfor1' => 'sometimes|array',
            'Appoinmentfor2' => 'sometimes|array',
            'clinic_id' => 'required',
            'Bookingtype' => 'sometimes|in:1,2,3', //one self 2 fm 3 other
            'schedule_type' => 'sometimes',
            'regular_medicine' => 'sometimes',
            'illness' => 'sometimes',
            'medicine_name' => 'sometimes',
            'allergy' => 'sometimes',
            'allergy_details' => 'sometimes',
            'surgery_details' => 'sometimes',
            'treatment_taken' => 'sometimes',
            'token_id' => 'sometimes',
            'normal_reschedule_token_id' => 'sometimes',
            'reschedule_type' => 'required|in:0,1,2'   // 0 = normal booking , 1= doctor reschedule booking , 2 = patinet reschedule

        ]);

        try {


            $doctor_user_id = $request->doctor_id;
            $doctor_id = Docter::where('UserId', $doctor_user_id)->pluck('id')->first();

            $existingBooking = NewTokens::where('doctor_id', $doctor_id)
                ->where('clinic_id', $request->input('clinic_id'))
                ->where('token_scheduled_date', $request->input('date'))
                ->where('token_number', $request->input('TokenNumber'))
                ->where('token_booking_status', 1)
                ->exists();

            if ($existingBooking) {
                return response()->json([
                    'status' => false,
                    'message' => 'This token has already been booked by someone. Please book another token.'
                ], 200);
            }

            $previous_bookings = NewTokens::select('token_start_time')
                ->where('patient_id', $request->patient_id)
                ->orderBy('token_start_time', 'desc')
                ->whereDate('token_scheduled_date', $request->date)
                ->get();

            if ($previous_bookings) {
                foreach ($previous_bookings as $previous_booking) {
                    $previous_booked_token_start_time = Carbon::parse($previous_booking->token_start_time)->format('H:i:s');
                    $requestTokenTime = Carbon::parse($request->TokenTime)->format('H:i:s');
                    $previous_start_time = Carbon::createFromFormat('H:i:s', $previous_booked_token_start_time);
                    $selected_start_time = Carbon::createFromFormat('H:i:s', $requestTokenTime);

                    // if ($previous_start_time > $selected_start_time) {
                    //     $time_difference_minutes = $previous_start_time->diffInMinutes($selected_start_time);
                    //     if ($time_difference_minutes <= 30) {
                    //         return response()->json([
                    //             'status' => false,
                    //             'message' => 'Nearby appointment already exists.'
                    //         ], 200);
                    //     }
                    // }
                    // if ($previous_start_time < $selected_start_time) {
                    //     $time_difference_minutes = $selected_start_time->diffInMinutes($previous_start_time);
                    //     if ($time_difference_minutes <= 60) {

                    //         return response()->json([
                    //             'status' => false,
                    //             'message' => 'Nearby appointment already exists.'
                    //         ], 200);
                    //     }
                    // }
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'validation completed'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal Server error'], 500);
        }
    }
}
