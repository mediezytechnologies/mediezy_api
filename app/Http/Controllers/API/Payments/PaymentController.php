<?php

namespace App\Http\Controllers\API\Payments;

use App\Http\Controllers\Controller;
use App\Models\NewTokens;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function capturePayment(Request $request)
    {

        $rules = [

            'razorpay_payment_id' => 'required',
            'token_id' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'contact' => 'required',
            'email' => 'required',

        ];
        $messages = [
            'razorpay_payment_id.required' => 'razorpay_payment_id is required',

        ];

        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $token = NewTokens::where('token_id', $request->token_id)->first();

        if (!$token) {
            return response()->json([
                'status' => false,
                'response' => 'Token details not found'
            ]);
        }

        try {
            $capture_payment = new Payment();
            $capture_payment->razorpay_payment_id = $request->razorpay_payment_id;
            $capture_payment->token_id = $request->token_id;
            $capture_payment->amount = $request->amount;
            $capture_payment->currency = $request->currency;
            $capture_payment->contact = $request->contact;
            $capture_payment->email = $request->email;
            $capture_payment->user_id = Auth::user()->id;
            $capture_payment->save();


            $token->payment_id = $capture_payment->id;
            $token->save();



            return response()->json([
                'status' => true,
                'response' => 'Payment details saved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'response' => 'Internal server error'
            ], 500);
        }
    }
}
