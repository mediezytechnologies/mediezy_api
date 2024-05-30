<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Docter;
use App\Models\DoctorClinicSpecialization;
use App\Models\Specialize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorClinicSpecializationController extends Controller
{
    public function manageSpecializationsView()
    {
        $doctor_data = Docter::select('id', 'firstname')->get();
        $clinic_data = Clinic::select('clinic_id', 'clinic_name')->get();
        $specialization_data = Specialize::select('id', 'specialization')->get();

        return view('backend.relation_management.doctor_clinic_specialization', [
            'doctor_data' => $doctor_data,
            'clinic_data' => $clinic_data,
            'specilization_data' => $specialization_data,
        ]);
    }

    public function saveDoctorClinicSpecializations(Request $request)
    {
        try {
            $request->validate([
                'special_id' => 'required',
                'clinic_ids' => 'required|array',
            ]);

            $specialization_id = $request->input('special_id');
            $clinicIds = $request->input('clinic_ids');

            foreach ($clinicIds as $clinicId) {
                DoctorClinicSpecialization::create([
                    'specialization_id' => $specialization_id,
                    'clinic_id' => $clinicId,

                ]);
            }

            return redirect()->route('manageSpecializationsView')->with('success', 'Doctor to Clinic specializations saved successfully!');
        } catch (\Exception $e) {
            Log::error('Error in saveDoctorClinicSpecializations: ' . $e->getMessage());
            return redirect()->route('manageSpecializationsView')->with('error', 'An error occurred while saving Doctor to Clinic specializations.');
        }
    }

    public function saveClinicDoctorSpecializations(Request $request)
    {
        // try {
        $request->validate([
            'specialization_id_doctor' => 'required',
            'doctor_ids' => 'required|array',
        ]);

        Log::info('Validation passed for specialization_id: ' . $request->input('specialization_id_doctor'));

        $specialization_id = $request->input('specialization_id_doctor');
        $doctorIds = $request->input('doctor_ids');

        Log::info('Specialization ID: ' . $specialization_id . ', Doctor IDs: ' . implode(', ', $doctorIds));

        foreach ($doctorIds as $doctorId) {
            Log::info('Creating DoctorClinicSpecialization record for doctor_id: ' . $doctorId);

            DoctorClinicSpecialization::create([
                'doctor_id' => $doctorId,
                'specialization_id' => $specialization_id,
            ]);

            Log::info('DoctorClinicSpecialization record created successfully for doctor_id: ' . $doctorId);
        }

        Log::info('Redirecting to manageRelationsView with success message');
        return redirect()->route('manageSpecializationsView')->with('success', 'Clinic to Doctor specializations saved successfully!');
        // } catch (\Exception $exception) {
        //     Log::error('An error occurred: ' . $exception->getMessage());

        //     Log::info('Redirecting to manageRelationsView with error message');
        //     return redirect()->route('manageSpecializationsView')->with('error', 'An error occurred while saving Clinic to Doctor relations.');
        // }
    }
}
