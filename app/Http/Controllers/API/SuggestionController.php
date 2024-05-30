<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DoctorSuggestion;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SuggestionController extends Controller
{
    public function addSuggestion(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'user_id' => 'required|numeric|exists:users,id',
        ]);
    
        try {
          
            $data = $request->all();
            $suggestion = Suggestion::create($data);
            $user = User::find($request->user_id);
            $name = $user->firstname;
    
            return response()->json([
                'status' => '1',
                'message' => "Thank you for your feedback, $name. This will help us improve.",
                'data' => $suggestion,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }
    public function suggestDoctor(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'name' => 'required',
            'place' => 'required',
            'clinic_name' => 'sometimes',
            'specialization' => 'sometimes',
            'mobile_number' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {

            $suggestions_data = new DoctorSuggestion();
            $suggestions_data->user_id = $request->user_id ?? null;
            $suggestions_data->name = $request->name ?? null;
            $suggestions_data->mobile_number = $request->mobile_number ?? null;
            $suggestions_data->place = $request->place ?? null;
            $suggestions_data->clinic_name = $request->clinic_name ?? null;
            $suggestions_data->specialization = $request->specialization ?? null;
            $suggestions_data->save();

            return response()->json(['message' => 'Doctor recommended successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal Server error'], 500);
        }
    }
}
