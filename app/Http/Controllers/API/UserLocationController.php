<?php

namespace App\Http\Controllers\API\UserLocation;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLocations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserLocationController extends Controller
{
    public function addUserLocations(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
            'latitude'      => 'required',
            'longitude'  => 'required',
            'city'         => 'required',
            'district' => 'required',
            'location_address' => 'required',
        ];
        $messages = [
            'user_id.required' => 'user_id is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $user_check = User::where('id', $request->user_id)->first();
        if (!$user_check) {

            return response()->json(['status' => false, 'response' => 'User not found']);
        }
        try {
            ///////////////////////////////
            $user_location_data = new UserLocations();
            $user_location_data->user_id = $request->user_id;
            $user_location_data->latitude = $request->latitude;
            $user_location_data->longitude = $request->longitude;
            $user_location_data->city = $request->city;
            $user_location_data->district = $request->district;
            $user_location_data->location_address = $request->location_address;
            $user_location_data->save();

            return response()->json(['status' => true, 'data' => $user_location_data, 'response' => 'User location saved.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => 'Internal server error']);
        }
    }
}