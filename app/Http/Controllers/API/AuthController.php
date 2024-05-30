<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\API\BaseController;
use App\Jobs\SendGeneralCampaignMail;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends BaseController
{

    public function login(Request $req)
    {
        // validate inputs
        $rules = [
            'email' => 'required',
            'password' => 'required|string'
        ];
        $req->validate($rules);

        // find user email in doctors table
        $doctor = User::where('email', $req->email)
            ->where('user_role', 2) // 2 represents doctor
            ->first();

        // find user email in users table
        $user = User::where('email', $req->email)
            ->where('user_role', 3) // 3 represents regular user or patient
            ->first();


        // find user email in labs table
        $lab = User::where('email', $req->email)
            ->where('user_role', 4) // 4 represents lab
            ->first();

        // find user email in medicalshops table
        $medicalShop = User::where('email', $req->email)
            ->where('user_role', 5) // 5 represents medical shop
            ->first();

        $hospital = User::where('email', $req->email)
            ->where('user_role', 6) // 5 represents hospital
            ->first();
        // if user email found and password is correct
        if ($user && Hash::check($req->password, $user->password)) {
            $token = $user->createToken('Personal Access Token')->plainTextToken;
            $response = ['user' => $user, 'token' => $token, 'role' => 'user'];


            return response()->json($response, 200);
        } elseif ($doctor && Hash::check($req->password, $doctor->password)) {
            $token = $doctor->createToken('Personal Access Token')->plainTextToken;
            $response = ['doctor' => $doctor, 'token' => $token, 'role' => 'doctor'];
            return response()->json($response, 200);
        } elseif ($lab && Hash::check($req->password, $lab->password)) {
            $token = $lab->createToken('Personal Access Token')->plainTextToken;
            $response = [
                'status' => true,
                'message' => 'Login successful',
                'lab' => [
                    'id' => $lab->id,
                    'firstname' => $lab->firstname,
                    "mobileNo" => $lab->mobileNo,
                    'email' => $lab->email,
                    'user_role' => $lab->user_role,
                ],
                'token' => $token,
                'role' => 'lab',
            ];
            return response()->json($response, 200);
        } elseif ($medicalShop && Hash::check($req->password, $medicalShop->password)) {
            $token = $medicalShop->createToken('Personal Access Token')->plainTextToken;
            $response = [
                'medical_shop' => [
                    'id' => $medicalShop->id,
                    'firstname' => $medicalShop->firstname,
                    "mobileNo" => $medicalShop->mobileNo,
                    'email' => $medicalShop->email,
                    'user_role' => $medicalShop->user_role,
                ],
                'token' => $token,
                'role' => 'medicalShop',
            ];
            return response()->json($response, 200);
        }

        $response = ['status' => false, 'message' => 'Incorrect email or password'];
        return response()->json($response, 400);
    }


    public function socialAccountsAuth(Request $request)
    {
        $rules = [
            'access_token' => 'required',
            'mobile_number' => 'sometimes'
        ];

        $request->validate($rules);

        $token = $request->access_token;
        $provider = 'google';
        $providerUser = Socialite::driver($provider)->userFromToken($token);
        // $user = User::where('provider_name', $provider)->where('provider_id', $providerUser->id)->first();
        $user = User::where('email', $providerUser->email)->first();
        if (!empty($user) && empty($user->provider_id)) {
            $user->provider_name = $provider;
            $user->provider_id = $providerUser->id;
            $user->mobileNo = $request->mobile_number;
            $user->save();
        }

        if (!$user) {
            $new_user = new User();
            $new_user->firstname = $providerUser->name;
            $new_user->email = $providerUser->email;
            $new_user->user_role = 3;
            $new_user->provider_name = $provider;
            $new_user->provider_id = $providerUser->id;
            $new_user->mobileNo = $request->mobile_number;
            $new_user->save();
            $user  = $new_user;

            $mobile_number =  $request->mobile_number;

            $this->createPatient($providerUser, $new_user, $mobile_number);
        }

        $token = $user->createToken('Personal Access Token')->plainTextToken;

       
        $details = [
            'title' => '',
            'body' => ''
        ];
    
        SendGeneralCampaignMail::dispatch($user, $details);

        return response()->json([
            'status' => true,
            'response' => 'Login success',
            'token' => $token,
            'user' => $user
        ]);
    }

    function createPatient($providerUser, $new_user, $mobile_number)
    {
        $mediezy_patient_id = $this->generatePatientUniqueId();
        $patient_data = new Patient();
        $patient_data->firstname = $providerUser->name;
        $patient_data->mobileNo = $mobile_number;
        $patient_data->email = $providerUser->email;
        // $patient_data->user_image = $providerUser->image;
        $patient_data->UserId = $new_user->id;
        $patient_data->mediezy_patient_id = $mediezy_patient_id;
        $patient_data->save();
    }
    function generatePatientUniqueId()
    {
        $uniquePatientDetails = Patient::select('mediezy_patient_id')
            ->whereNotNull('mediezy_patient_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($uniquePatientDetails === null) {
            $uniquePatientDetails = [
                'mediezy_patient_id' => 'MAA00000',
            ];
        }
        $lastUniqueId = $uniquePatientDetails->mediezy_patient_id;
        $numericPart = (int) substr($lastUniqueId, 3) + 1;
        $newNumericPart = str_pad($numericPart, 5, '0', STR_PAD_LEFT);
        $newUniqueId = 'MAA' . $newNumericPart;
        return $newUniqueId;
    }

    public function recieveFCMToken(Request $request)
    {
        try {

            $rules = [
                'fcm_token' => 'required',
                'user_id' => 'required',

            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {


                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            $user_id = $request->user_id;

            if (!$user_id) {

                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $user_data = User::where('id', $user_id)->first();

            if (!$user_data) {


                return response()->json(['error' => 'User not found'], 404);
            }

            $user_data->fcm_token = $request->fcm_token;
            $user_data->save();

            return response()->json(['message' => 'FCM token updated successfully'], 200);
        } catch (\Exception $e) {

            Log::info("fcm token try cath error: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }
}
