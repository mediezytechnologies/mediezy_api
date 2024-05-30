<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Docter;
use App\Models\DoctorClinicRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DoctorClinicRelationController extends Controller
{
    public function manageRelationsView()
    {
        $doctor_data = Docter::select('id', 'firstname')->get();
        $clinic_data = Clinic::select('clinic_id', 'clinic_name')->get();

        return view('backend.relation_management.manage_doctor_clinic', [
            'doctor_data' => $doctor_data,
            'clinic_data' => $clinic_data,
        ]);
    }

    public function saveDoctorClinicRelations(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required',
            'clinic_ids' => 'required|array',

        ]);

        $doctorId = $request->input('doctor_id');
        $clinicIds = $request->input('clinic_ids');


        foreach ($clinicIds as $clinicId) {

            $existingRelation = DoctorClinicRelation::where('doctor_id', $doctorId)
                ->where('clinic_id', $clinicId)
                ->first();

            if (!$existingRelation) {

                DoctorClinicRelation::create([
                    'doctor_id' => $doctorId,
                    'clinic_id' => $clinicId,
                ]);
            }
        }

        return redirect()->route('manageRelationsView')->with('success', 'Doctor to Clinic relations saved successfully!');
    }

    public function saveClinicDoctorRelations(Request $request)
    {
        try {
            $request->validate([
                'clinic_id' => 'required',
                'doctor_ids' => 'required|array',
            ]);

            $clinicId = $request->input('clinic_id');
            $doctorIds = $request->input('doctor_ids');

            foreach ($doctorIds as $doctorId) {

                // DoctorClinicRelation::create([
                //     'doctor_id' => $doctorId,
                //     'clinic_id' => $clinicId,
                // ]);
                $existingRelation = DoctorClinicRelation::where('doctor_id', $doctorId)
                    ->where('clinic_id', $clinicId)
                    ->first();

                if (!$existingRelation) {

                    DoctorClinicRelation::create([
                        'doctor_id' => $doctorId,
                        'clinic_id' => $clinicId,
                    ]);
                }
            }

            return redirect()->route('manageRelationsView')->with('success', 'Clinic to Doctor relations saved successfully!');
        } catch (\Exception $exception) {

            return redirect()->route('manageRelationsView')->with('error', 'An error occurred while saving Clinic to Doctor relations.');
        }
    }
    
}
