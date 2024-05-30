<?php

namespace App\Http\Controllers\Api;
use App\Models\User;
use App\Models\ContactUs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactusController extends Controller
{

public function ContactUs(Request $request)
{
    $rules = [
        'UserId' => 'required',
        'email' => 'required|email|regex:/^.+@gmail\.com$/i',
        'description' => 'required'
    ];

    $messages = [
        'email.required' => 'Email is required',
        'email.regex' => 'Invalid email format. Only Gmail addresses are allowed.',
        'description.required' => 'Description is required'
    ];

    $validation = Validator::make($request->all(), $rules, $messages);

    if ($validation->fails()) {
        return response()->json(['status' => false, 'response' => $validation->errors()->first()],400);
    }

    try{

    $user = User::find($request->UserId);

    if (!$user) {
        return response()->json(['status' => false, 'response' => 'User not found'],400);
    }

    $contactUs = new ContactUs();
    $contactUs->userId = $user->id;
    $contactUs->email = $request->email;
    $contactUs->description = $request->description;

    $contactUs->save();

    $contactDetails = ContactUs::select('id', 'userId', 'email', 'description')->get()->toArray();

    return response()->json(['status' => true, 'message' => 'Message uploaded successfully.']);
}catch(\Exception $e){
    return response()->json(['message' => 'Internal Server Error'], 500);
}
}

}
