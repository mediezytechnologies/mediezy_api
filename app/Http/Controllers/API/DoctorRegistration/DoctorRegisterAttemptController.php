<?php

namespace App\Http\Controllers\API\DoctorRegistration;

use App\Http\Controllers\Controller;
use App\Models\DoctorRegisterAttempt;
use Illuminate\Http\Request;

class DoctorRegisterAttemptController extends Controller
{
    public function doctorRegistration(Request $request)
    {


        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'mobile_number' => 'required',
        ]);
        try {

            $temp_user = new DoctorRegisterAttempt();
            $temp_user->name = $request->name;
            $temp_user->email = $request->email;
            $temp_user->mobile_number = $request->mobile_number;
            $temp_user->save();

            return response()->json(['staus' => true, 'message' => 'User created successfully', 'user' => $temp_user]);
        } catch (\Exception $e) {
            return response()->json(['staus' => false, 'message' => 'Internal Server Error']);
        }
    }
}
