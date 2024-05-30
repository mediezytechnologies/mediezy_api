<?php

namespace App\Http\Controllers\API\UserLocation;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Docter;
use App\Models\NewTokens;
use App\Models\Specialize;
use App\Models\Specification;
use App\Models\Subspecification;
use App\Models\User;
use App\Models\UserLocations;
use App\Services\DistanceMatrixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class UserLocationController extends Controller
{

    public function addUserLocations(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
            'latitude'      => 'required',
            'longitude'  => 'required',
            'city'         => 'sometimes',
            'district' => 'sometimes',
            'location_address' => 'required',
        ];
        $messages = [
            'user_id.required' => 'user_id is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => $validation->errors()->first()]);
        }

        $user_check = User::where('id', $request->user_id)->first();
        if (!$user_check) {

            return response()->json(['status' => false, 'message' => 'User not found']);
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

            return response()->json(['status' => true, 'data' => $user_location_data, 'message' => 'User location saved.']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal server error']);
        }
    }
    public function patientListNearbyDoctors(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
        ];
        $messages = [
            'user_id.required' => 'user_id is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        // try {
        ////////////////////////////////////////////////////////////////////
        $user_location_data = UserLocations::where('user_id', $request->user_id)
            ->latest()
            ->first();

        if (!$user_location_data) {
            return response()->json(['status' => true, 'response' => 'No user location data found']);
        }

        $patient_user_lati = $user_location_data ? $user_location_data->latitude : null;
        $patient_user_long = $user_location_data ? $user_location_data->longitude : null;

        $doctor_location_data = Docter::select('UserId', 'id', 'firstname', 'lastname', 'latitude', 'longitude')->get();

        foreach ($doctor_location_data as $doc_location) {
            $doc_user_latitude = $doc_location->latitude;
            $doc_user_longitude = $doc_location->longitude;
            // $apiKey = env('GMAP_API_KEY');
            $apiKey = 'AIzaSyCA2yqbro5BkjC8xEaAAeWiNWcpaAqmfMo';

            $service = new DistanceMatrixService($apiKey);
            $distance = $service->getDistance($patient_user_lati, $patient_user_long, $doc_user_latitude, $doc_user_longitude);

           return $distance;
        }
        //
    }

    // public function getDistance(Request $request)
    // {
    //     $validation = Validator::make($request->all(), ['user_id' => 'required'], ['user_id.required' => 'user_id is required']);
    //     if ($validation->fails()) {
    //         return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
    //     }
    //     $user_location = UserLocations::where('user_id', $request->user_id)->latest()->first();
    //     if (!$user_location) {
    //         return response()->json(['status' => true, 'response' => 'No user location data found']);
    //     }
    //     $clinics = Clinic::select('clinic_id', 'clinic_name', 'address', 'latitude', 'longitude')->get();
    //     $apiKey = 'AIzaSyCA2yqbro5BkjC8xEaAAeWiNWcpaAqmfMo';
    //     $service = new DistanceMatrixService($apiKey);
    //     $distances = [];

    //     foreach ($clinics as $clinic) {
    //         $distance = $service->getDistance($user_location->latitude, $user_location->longitude, $clinic->latitude, $clinic->longitude);
    //         $distances[] = [
    //             'clinic_id' => $clinic->clinic_id,
    //             'clinic_name' => $clinic->clinic_name,
    //             'address' => $clinic->address,
    //             'distance' => $distance
    //         ];
    //     }

    //     return response()->json(['status' => true, 'distances' => $distances]);
    // }

    public function getDistance(Request $request)
    {
        $validation = Validator::make($request->all(), ['user_id' => 'required'], ['user_id.required' => 'User ID is required']);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $user_location = UserLocations::where('user_id', $request->user_id)->latest()->first();
        if (!$user_location) {
            return response()->json(['status' => true, 'response' => 'No user location data found']);
        }

        $clinics = Clinic::select('clinic_id', 'clinic_name', 'address', 'latitude', 'longitude')->get();
        $apiKey = config('services.google.api_key');
        $service = new DistanceMatrixService($apiKey);
        $distances = [];

        foreach ($clinics as $clinic) {
            if (is_null($clinic->latitude) || is_null($clinic->longitude)) {
                $geocoded = $this->geocodeAddress($clinic->address, $apiKey);
                if ($geocoded['status'] === 'OK') {
                    $clinic->latitude = $geocoded['latitude'];
                    $clinic->longitude = $geocoded['longitude'];
                } else {
                    Log::error('Geocoding failed: ' . $geocoded['message']);
                    continue;
                }
            }

            $distance = $service->getDistances($user_location->latitude, $user_location->longitude, $clinic->latitude, $clinic->longitude);
            $distances[] = [
                'clinic_id' => $clinic->clinic_id,
                'clinic_name' => $clinic->clinic_name,
                'address' => $clinic->address,
                'distance' => $distance
            ];
        }

        return response()->json(['status' => true, 'distances' => $distances]);
    }

    private function geocodeAddress($address, $apiKey)
    {
        $geocodingApiUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key=" . $apiKey;
        $response = file_get_contents($geocodingApiUrl);
        $data = json_decode($response, true);

        if ($data['status'] === 'OK') {
            return [
                'status' => 'OK',
                'latitude' => $data['results'][0]['geometry']['location']['lat'],
                'longitude' => $data['results'][0]['geometry']['location']['lng']
            ];
        } else {
            return [
                'status' => 'ERROR',
                'message' => $data['error_message'] ?? 'Failed to geocode address'
            ];
        }
    }

}
