<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientAllergies;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FamilyMemberController extends Controller
{
    public function createFamilyMember(Request $request)
    {

        $rules = [
            'user_id'        => 'required',
            'full_name'      => 'required',
            'mobile_number'  => 'required',
            'gender'         => 'required|in:1,2,3',
            'date_of_birth'  => 'required|date_format:Y-m-d',
            'user_image'  => 'sometimes',
            'medicines' => 'sometimes|array',
            'allergies' => 'sometimes|array',
        ];

        $messages = [
            'user_id.required' => 'user_id is required',
            'date_of_birth.date_format' => 'Date of birth should be in Y-m-d format',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => $validation->errors()->first()]);
        }

        try {

            $existing_patient_check = Patient::where('dateofbirth', $request->date_of_birth)
                ->where('firstname', $request->full_name)
                ->where('mobileNo', $request->mobile_number);

            if ($existing_patient_check->exists()) {
                return response()->json(['status' => false, 'message' => "Patient already exists."], 400);
            }
            /////

            $new_patient_data = new Patient();
            $new_patient_data->firstname = $request->full_name;
            $new_patient_data->mobileNo = $request->mobile_number;
            $new_patient_data->gender = $request->gender;
            $new_patient_data->dateofbirth = $request->date_of_birth;
            $new_patient_data->UserId = $request->user_id;
            $new_patient_data->mediezy_patient_id = $this->generatePatientUniqueId();
            $new_patient_data->regularMedicine = $request->regularMedicine;
            //////////////

            $surgeryDetails = null;
            // if ($request->surgery_name === '[Other]' && $request->has('surgery_details')) {
            $new_patient_data->surgery_name = $request->surgery_name;
            $surgeryDetails = $request->surgery_details;
            // } else {
            // $new_patient_data->surgery_name = $request->surgery_name;
            // $surgeryDetails = null;
            // }
            $new_patient_data->surgery_details = $surgeryDetails;


            /////////
            $treatmenttakenDetails = null;
            // if ($request->treatment_taken === '[Other]' && $request->has('treatment_taken_details')) {
            $new_patient_data->treatment_taken = $request->treatment_taken;
            $treatmenttakenDetails = $request->treatment_taken_details;
            // } else {

            // $new_patient_data->treatment_taken = $request->treatment_taken;
            // $treatmenttakenDetails = null;
            // }
            $new_patient_data->treatment_taken_details = $treatmenttakenDetails;
            $TreatmentString = is_array($request->treatment_taken) ? implode(', ', $request->treatment_taken) : $request->treatment_taken;
            $new_patient_data->treatment_taken = $TreatmentString;
            $new_patient_data->user_type = 2;
            $new_patient_data->save();
            $medicines = $request->medicines;


            // if (count($medicines) === 0) {

            $new_patient_data->medicines = [];
            if (($medicines)) {
                foreach ($medicines as $medicine) {
                    $newMedicine = new Medicine();
                    $newMedicine->user_id = $request->user_id;
                    $newMedicine->patient_id = $new_patient_data->id;
                    $newMedicine->medicineName = $medicine['medicineName'];
                    $newMedicine->illness = $medicine['illness'];
                    $newMedicine->save();
                    $new_patient_data->medicines = $newMedicine;
                }
            }
            // }
            $new_patient_data->allergies = [];
            $allergies = $request->allergies;
            if ($allergies) {
                foreach ($allergies as $allergy) {
                    $newAllergy = new PatientAllergies();
                    $newAllergy->patient_id = $new_patient_data->id;
                    $newAllergy->allergy_id = $allergy['allergy_id'];
                    $newAllergy->allergy_details = $allergy['allergy_details'];
                    $newAllergy->save();
                    $new_patient_data->allergies = $newAllergy;
                }
            }

            return response()->json(['status' => true, 'patient_id' => $new_patient_data->id, 'patient_data' => $new_patient_data, 'message' => 'Family member added.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => "Internal Server Error"], 500);
        }
    }

    public function savePatientImage(Request $request)
    {

        $rules = [
            'patient_id'        => 'required',
            'user_image'      => 'sometimes',

        ];

        $messages = [
            'patient_id.required' => 'patient_id is required',

        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try {

            $patient_data = Patient::where('id', $request->patient_id)->first();
            $patient_id = $patient_data->id ?? null;
            if (!$patient_data) {
                return response()->json(['status' => false, 'error' => "Patient not found."], 400);
            }

            if (isset($request->user_image)) {
                if ($request->hasFile('user_image')) {
                    $imageFile = $request->file('user_image');

                    if ($imageFile->isValid()) {
                        $imageName = $imageFile->getClientOriginalName();
                        $imageFile->move(public_path('UserImages'), $imageName);
                        $patient_data->user_image = $imageName;
                        $patient_data->save();
                        return response()->json(['status' => true, 'message' => 'Image updated successfully', 'patient_id' => $patient_id], 200);
                    } else {
                        return response()->json(['status' => false, 'error' => "Invalid image format"], 400);
                    }
                } else {
                    return response()->json(['status' => false, 'error' => "Invalid image"], 400);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error'],500);
        }
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

    ////edit family member 30-03

    public function editFamilyMember(Request $request)
    {
        $rules = [
            'patient_id'        => 'required',
            'full_name'      => 'sometimes',
            'mobile_number'      => 'sometimes',
            'gender'         => 'required|in:1,2,3',
            'date_of_birth'  => 'required|date_format:Y-m-d',
            'regularMedicine' => 'sometimes',
            'Medicine_Taken' => 'sometimes',
            'illness' => 'sometimes',
            'medicines' => 'sometimes|array',
            'allergies' => 'sometimes|array',
            'surgery_name' => 'sometimes',
            'surgery_details' => 'sometimes',
            'treatment_taken' => 'sometimes',
            'treatment_taken_details' => 'sometimes'
        ];

        $messages = [
            'patient_id.required' => 'patient_id is required',
            'date_of_birth.date_format' => 'Date of birth should be in Y-m-d format',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => $validation->errors()->first()]);
        }

        try {
            ////////////////////////////////////////

            $patient_data = Patient::where('id', $request->patient_id)->first();

            if (!($patient_data)) {
                return response()->json(['status' => false, 'message' => 'No patient found']);
            }

            ///////////
            $patient_data->firstname = $request->full_name;
            if (isset($request->mobile_number)) {
                $patient_data->mobileNo = $request->mobile_number;
            }
            $patient_data->gender = $request->gender;
            $patient_data->dateofbirth = $request->date_of_birth;
            $patient_data->regularMedicine = $request->regularMedicine;
            if ($request->regularMedicine == 'Yes') {
                $patient_data->illness = $request->illness;
                $patient_data->Medicine_Taken = $request->Medicine_Taken;
            }

            if ($request->allergies) {
                $allergies = $request->allergies;
            

                $existing_allergies = PatientAllergies::where('patient_id', $patient_data->id)->get();
                $existing_allergy_ids = $existing_allergies->pluck('allergy_id')->toArray();
            
                foreach ($allergies as $allergy) {
                    $index = array_search($allergy['allergy_id'], $existing_allergy_ids);
            
                    if ($index !== false) {
                        $existing_allergy = $existing_allergies[$index];
                        $existing_allergy->allergy_details = $allergy['allergy_details'];
                        $existing_allergy->save();
            
                        unset($existing_allergy_ids[$index]);
                    } else {
                        $new_allergy = new PatientAllergies();
                        $new_allergy->patient_id = $patient_data->id;
                        $new_allergy->allergy_id = $allergy['allergy_id'];
                        $new_allergy->allergy_details = $allergy['allergy_details'];
                        $new_allergy->save();
                    }
                }
                PatientAllergies::where('patient_id', $patient_data->id)
                                ->whereIn('allergy_id', $existing_allergy_ids)
                                ->delete();
            }
            
            $medicines = $request->medicines;

            if ($medicines) {
                foreach ($medicines as $medicine) {
                    $existing_medicines = Medicine::where('user_id', $request->user_id)
                        ->where('patient_id', $patient_data->id)
                        ->where('medicineName', $medicine['medicineName'])
                        ->where('illness', $medicine['illness'])
                        ->get();

                    if ($existing_medicines->count() > 0) {
                        foreach ($existing_medicines as $existing_medicine) {
                            $existing_medicine->updated_at = now();
                            $existing_medicine->medicineName = $medicine['medicineName'];
                            $existing_medicine->illness = $medicine['illness'];
                            $existing_medicine->save();
                        }
                    } else {
                        $newMedicine = new Medicine();
                        $newMedicine->user_id = $request->user_id;
                        $newMedicine->patient_id = $patient_data->id;
                        $newMedicine->medicineName = $medicine['medicineName'];
                        $newMedicine->illness = $medicine['illness'];
                        $newMedicine->save();
                    }
                }
            }

            //surgery treatment taken
            $surgeryDetails = null;
            // if ($request->surgery_name === '[Other]' && $request->has('surgery_details')) {
            $patient_data->surgery_name = $request->surgery_name;
            $surgeryDetails = $request->surgery_details;
            // } else {
            //     $patient_data->surgery_name = $request->surgery_name;
            //     $surgeryDetails = null;
            // }
            $patient_data->surgery_details = $surgeryDetails;
            $treatmenttakenDetails = null;
            // if ($request->treatment_taken === '[Other]' && $request->has('treatment_taken_details')) {
            $patient_data->treatment_taken = $request->treatment_taken;
            $treatmenttakenDetails = $request->treatment_taken_details;
            // } else {

            //     $patient_data->treatment_taken = $request->treatment_taken;
            //     $treatmenttakenDetails = null;
            // }
            $patient_data->treatment_taken_details = $treatmenttakenDetails;
            $TreatmentString = is_array($request->treatment_taken) ? implode(', ', $request->treatment_taken) : $request->treatment_taken;
            $patient_data->treatment_taken = $TreatmentString;

            ////end 
            $patient_data->save();
            if (isset($existing_medicine)) {
                $patient_data->medicine = $existing_medicine;
            }
            if (isset($newMedicine)) {
                $patient_data->medicine = $newMedicine;
            }
            unset($patient_data->lastname);
            unset($patient_data->user_image);
            unset($patient_data->created_at);
            unset($patient_data->updated_at);
            return response()->json(['status' => true, 'message' => 'Patient details edited.', 'data' => $patient_data], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Internal server error.'],500);
        }
    }
}
