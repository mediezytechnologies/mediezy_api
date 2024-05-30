<?php

namespace App\Http\Controllers;


use App\Models\Clinic;
use App\Models\ClinicConsultation;
use App\Models\Docter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClinicConsultationFeeController extends Controller
{

    public function clinicwiseConsultation()
    {
        $doctors = Docter::select('id', 'firstname')->get();
        $clinics = Clinic::select('clinic_id', 'clinic_name')->get();
        return view('clinic-consultation.create', compact('doctors', 'clinics'));
    }

    // public function store(Request $request)
    // {
    //     Log::info($request->all());
    //     $request->validate([
    //         // 'doctor_id' => 'required|exists:docter,id',
    //         // 'clinic_id' => 'required|exists:clinics,clinic_id',
    //         // 'consultation_fee' => 'required|numeric',
    //             'doctor_id' => 'required|exists:docter,id|unique:clinic_consultation,doctor_id,NULL,id,clinic_id,' . $request->clinic_id,
    //             'clinic_id' => 'required|exists:clinics,clinic_id',
    //             'consultation_fee' => 'required|numeric',

    //     ]);

    //     ClinicConsultation::create([
    //         'doctor_id' => $request->doctor_id,
    //         'clinic_id' => $request->clinic_id,
    //         'consultation_fee' => $request->consultation_fee,
    //     ]);

    //     return redirect()->back()->with('success', 'Consultation fee added successfully!');
    // }
    public function clinicwiseConsultationFees(Request $request)
    {
        Log::info($request->all());
        $rules = [
            'doctor_id' => [
                'required', 'exists:docter,id',
                Rule::unique('clinic_consultation')->where(function ($query) use ($request) {
                    return $query->where('clinic_id', $request->clinic_id);
                }),
            ],
            'clinic_id' => 'required|exists:clinics,clinic_id',
            'consultation_fee' => 'required|numeric',
        ];
        $messages = [
            'doctor_id.unique' => 'The consultation fee for the specified doctor and clinic already exists.',
        ];
        $validatedData = Validator::make($request->all(), $rules, $messages);
        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData->errors())->withInput();
        }
        ClinicConsultation::create([
            'doctor_id' => $request->doctor_id,
            'clinic_id' => $request->clinic_id,
            'consultation_fee' => $request->consultation_fee,
        ]);
        return redirect()->back()->with('success', 'Consultation fee added successfully!');
    }
}
