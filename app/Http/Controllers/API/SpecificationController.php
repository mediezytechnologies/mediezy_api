<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Allergy;
use App\Models\Patient;
use App\Models\Specification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SpecificationController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'specification' => ['required', 'max:25'],
            'remark' => ['max:250'],

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }
        $CheckExists = Specification::select('specification')->where(['specification' => $input['specification']])->get();
        if (count($CheckExists) > 0) {
            return $this->sendResponse("Specification", 'Exists', '0', 'Specification Already Exists');
        } else {
            $Specification = Specification::create($input);
            return $this->sendResponse("Specification", $Specification, '1', 'Specification created successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Specification = Specification::find($id);

        if (is_null($Specification)) {
            return $this->sendError('Specification not found.');
        }

        return $this->sendResponse("Specification", $Specification, '1', 'Specification retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $Specification = Specification::find($id);

        $input = $request->all();

        $validator = Validator::make($input, [
            'specification' => ['required', 'max:25'],
            'remark' => ['max:250'],

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        } else {
            $Specification->specification = $input['specification'];
            $Specification->remark = $input['remark'];
            $Specification->save();
            return $this->sendResponse("Specification", $Specification, '1', 'Specification Updated successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Specification = Specification::find($id);

        if (is_null($Specification)) {
            return $this->sendError('Specification not found.');
        }

        $Specification->delete();
        return $this->sendResponse("Specification", $Specification, '1', 'Specification Deleted successfully');
    }

    public function getEditPatientDetails($patient_id)
    {

        try{

        $patient_data = Patient::where('id', $patient_id)->first();
        if (!$patient_data) {
            return $this->sendError('Patient not found');
        }
        $allergy_id = optional($patient_data)->allergy_id;
        $allergy_name = Allergy::find($allergy_id);
        // if (!$allergy_name) {
        //     return $this->sendError('Allergy not found');
        // }
        $surgery_name = $patient_data->surgery_name ? array_map('trim', explode(',', trim($patient_data->surgery_name, '[]'))) : [];
        $treatment_taken = $patient_data->treatment_taken ? array_map('trim', explode(',', trim($patient_data->treatment_taken, '[]'))) : [];

        $patient_data_array = [
            'patient_name' => $patient_data->firstname ?? null,
            'patient_age' => $patient_data->age ?? null,
            'patient_gender' => $patient_data->gender ?? null,
            'patient_mobile_number' => $patient_data->mobileNo ?? null,
            'allergy_id' => $patient_data->allergy_id ?? null,
            'allergy_name' => $allergy_name->allergy ?? null,
            'allergy_detail' => $patient_data->allergy_name ?? null,
            'regular_medicine' => ucfirst(strtolower($patient_data->regularMedicine ?? null)),
            'illness' => $patient_data->illness ?? null,
            'medicine_taken' => $patient_data->Medicine_Taken ?? null,
            'surgery_name' => $surgery_name ?? null,
            'treatment_taken' => $treatment_taken ?? null,
            'patient_image'   => $patient_data->user_image ? asset("UserImages/{$patient_data->user_image}") : null,
        ];

        return response()->json([
            'status' => true,
            'message' => 'success',
            'patient_data' => $patient_data_array,
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }

    //edit 
    public function editPatientDetails(Request $request)
    {
        $rules = [
            'patient_id' => 'required',
            'full_name' => 'sometimes',
            'age' => 'sometimes',
            'mobile_number' => 'sometimes',
            'gender' => 'sometimes',
            'allergy' => 'sometimes',
            'allergy_details' => 'sometimes',
            'surgery_name' => 'sometimes',
            'treatment_taken' => 'sometimes',
            'regular_medicine' => 'sometimes',
            'illness' => 'sometimes',
            'medicine_name' => 'sometimes',
            'patient_image' => 'sometimes',
        ];
        $messages = [
            'patient_id.required' => 'Patient id is required',
        ];
        $requestData = $request->all();

        Log::info('Request Data: ' . json_encode($requestData));

        $validation = Validator::make($requestData, $rules, $messages);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        try{
        /// edit patient details
        $patient_id = $request->patient_id;
        $patient_data = Patient::where('id', $patient_id)->first();

        if (!$patient_data) {

            return response()->json(['status' => true, 'data' => null, 'message' => 'Patient not found.']);
        }


        // Update
        if (isset($request->full_name)) {
            $patient_data->firstname = $request->full_name ?? $patient_data->firstname;
        }
        if (isset($request->age)) {
            $patient_data->age = $request->age ?? $patient_data->age;
        }
        if (isset($request->mobile_number)) {
            $patient_data->mobileNo = $request->mobile_number ?? $patient_data->mobileNumber;
        }
        if (isset($request->gender)) {
            $patient_data->gender = $request->gender ?? $patient_data->gender;
        }
        if (isset($request->allergy)) {
            $patient_data->allergy_id = $request->allergy ?? $patient_data->allergy_id;
        }

        $patient_data->allergy_name = isset($request->allergy_details) ? $request->allergy_details : $patient_data->allergy_name;
        $patient_data->surgery_name = isset($request->surgery_name) ? $request->surgery_name : $patient_data->surgery_name;
        $patient_data->treatment_taken = isset($request->treatment_taken) ? $request->treatment_taken : $patient_data->treatment_taken;
        $patient_data->regularMedicine = isset($request->regular_medicine) ? $request->regular_medicine : $patient_data->regularMedicine;
        $patient_data->illness = isset($request->illness) ? $request->illness : $patient_data->illness;
        $patient_data->Medicine_Taken = isset($request->medicine_name) ? $request->medicine_name : $patient_data->Medicine_Taken;


        if (isset($request->patient_image) && $request->hasFile('patient_image')) {
            $imageFile = $request->file('patient_image');

            if ($imageFile->isValid()) {
                $imageName = $imageFile->getClientOriginalName();
                $imageFile->move(public_path('UserImages'), $imageName);

                $patient_data->user_image = $imageName;
            }
        }

        $patient_data->save();

        return response()->json(['status' => true, 'data' => $patient_data, 'message' => 'Updated Successfully']);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Internal Server error'], 500);
    }
    }
    
}
