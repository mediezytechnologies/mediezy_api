<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicineController extends BaseController
{



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
            'docter_id' => ['max:25'],
            'user_id' => ['max:25'],
            'medicineName' => ['max:250'],
            'Dosage' => ['required', 'max:25'],
            'NoOfDays' => ['max:250'],
            'MorningBF' => ['required', 'max:25'],
            'MorningAF' => ['max:250'],
            'Noon' => ['required', 'max:25'],
            'night' => ['max:250'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        }





        // Create a new schedule record
        $medicine = new Medicine;
        $medicine->user_id = $request->user_id;
        $medicine->docter_id = $request->docter_id;
        $medicine->medicineName = $request->medicineName;
        $medicine->Dosage = $request->Dosage;
        $medicine->NoOfDays = $request->NoOfDays;
        $medicine->MorningBF = $request->MorningBF;
        $medicine->MorningAF = $request->MorningAF;
        $medicine->Noon = $request->Noon;
        $medicine->night = $request->night;


        // Save the schedule record
        $medicine->save();

        return $this->sendResponse("medicine", $medicine, '1', 'Medicine Added successfully');
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $medicines = Medicine::find($id);

        if (is_null($medicines)) {
            return $this->sendError('medicines not found.');
        }

        return $this->sendResponse("medicines", $medicines, '1', 'medicines retrieved successfully.');
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
        $medicines = Medicine::find($id);

        $input = $request->all();

        $validator = Validator::make($input, [
            'docter_id' => ['max:25'],
            'user_id' => ['max:25'],
            'medicineName' => ['max:250'],
            'Dosage' => ['required', 'max:25'],
            'NoOfDays' => ['max:250'],
            'MorningBF' => ['required', 'max:25'],
            'MorningAF' => ['max:250'],
            'Noon' => ['required', 'max:25'],
            'night' => ['max:250'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        } else {

            $medicines->user_id = $input['user_id'];
            $medicines->docter_id = $input['user_id'];
            $medicines->medicineName = $input['medicineName'];
            $medicines->Dosage = $input['Dosage'];
            $medicines->NoOfDays = $input['NoOfDays'];
            $medicines->MorningBF = $input['MorningBF'];
            $medicines->MorningAF = $input['MorningAF'];
            $medicines->Noon = $input['Noon'];
            $medicines->night = $input['night'];
            $medicines->save();
            return $this->sendResponse("medicines", $medicines, '1', 'medicines Updated successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $medicines = Medicine::find($id);

        if (is_null($medicines)) {
            return $this->sendError('medicines not found.');
        }

        $medicines->delete();
        return $this->sendResponse("medicines", $medicines, '1', 'medicines Deleted successfully');
    }

    public function addPatientMedicine(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'user_id' => 'required',
                'medicine_name' => 'required',
                'illness' => 'required'
            ]);

            if ($validator->fails()) {
                $errorMessage = "Validation failed";
                return response()->json(['status' => false, 'message' => $errorMessage, ], 422);
            }
            $medicine_details = new Medicine();
            $medicine_details->user_id = $request->user_id;
            $medicine_details->medicineName = $request->medicine_name;
            $medicine_details->illness = $request->illness;
            $medicine_details->save();
            return response()->json([ 'status' => true,'message' => 'Medicine details added successfully',
           // 'medicine_details' => $medicine_details
        ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false,'message' => 'Internal server error'], 500);
        }
    }
    public function listPatientMedicines(Request $request)
    {
        try {
            $input = $request->all();

            $validator = Validator::make($input, [
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                $errorMessage = "Validation failed";
                return response()->json(['status' => false, 'message' => $errorMessage,], 422);
            }

            $medicine_date = Medicine::select('id', 'medicineName', 'illness')
                ->where('user_id', $request->user_id)->where('docter_id', '0')->get();
            return response()->json(['status' => true,'message' => 'Medicine details fetched.','medicine' => $medicine_date,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false,'message' => 'Internal Server Error '], 500);
        }
    }
    public function updateMedicine(Request $request)
    {
        $rules = [
            'id' => 'required',
            'patient_id' => 'required',
        ];
        try {
            $this->validate($request, $rules);
            $id = $request->id;
            $patientId = $request->patient_id;
            $medicine = Medicine::where('id', $id)->where('patient_id', $patientId)->first();
            if (!$medicine) {
                return response()->json(['status' => false, 'response' => 'Medicine not found']);
            }
            // Update the medicine fields
            $medicine->update([
                'medicineName' => $request->input('medicineName', $medicine->medicineName),
                'illness' => $request->input('illness', $medicine->illness),
            ]);
            return response()->json(['status' => true, 'response' => 'Medicine updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

public function deleteMedicine($id,$patientId)
{
    try {

       // $medicine = Medicine::find($id,$patientId);
       $medicine = Medicine::where('id', $id)->where('patient_id', $patientId)->first();
        if (!$medicine) {
            return response()->json(['status' => false, 'response' => 'Medicine not found']);
        }
        $medicine->delete();

        return response()->json(['status' => true, 'response' => 'Medicine deleted successfully']);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Internal Server Error']);
    }
}

public function getMedicine(Request $request, $patient_id)
{
    $medicines = Medicine::where('patient_id', $patient_id)->get();

    if ($medicines->isEmpty()) {
        return response()->json(['status' => false, 'response' => 'No medicines found for the patient','medicine_details' => []]);
    }

    try{

    $medicineDetails = $medicines->map(function ($medicine) {
        return [
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->medicineName,
            'illness' => $medicine->illness,
        ];
    });

    return response()->json(['status' => true, 'medicine_details' => $medicineDetails]);
} catch (\Exception $e) {
    return response()->json(['message' => 'Internal Server error'], 500);
}
}


}
