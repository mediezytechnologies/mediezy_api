<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Specialize;
use App\Models\Symtoms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class specializeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $specializations = Specialize::all();
        $specializationDetails = [];

        foreach ($specializations as $specialization) {
            $specializationDetails[] = [
                'id' => $specialization->id,
                'specialization' => $specialization->specialization,
                'specializeimage' => asset("specializationimages/{$specialization->specializeimage}"),
            ];
        }

        return $this->sendResponse("specializations", $specializationDetails, '1', 'Specialization retrieved successfully');
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
        // Image upload
        if ($request->hasFile('specialization_image')) {
            $imageFile = $request->file('specialization_image');

            if ($imageFile->isValid()) {
                $imageName = $imageFile->getClientOriginalName();
                $imageFile->move(public_path('specializationimages'), $imageName);
            }
        } else {
            // Handle case where image is not provided
            return $this->sendError('Validation Error', 'Image is required');
        }

        $checkExists = Specialize::select('specialization')->where(['specialization' => $request->input('specialization')])->get();

        if (count($checkExists) > 0) {
            // Delete the uploaded image if specialization already exists
            Storage::disk('public')->delete($imageName);

            return $this->sendResponse("specialization", 'Exists', '0', 'Specialization already exists');
        } else {
            $specialization = new Specialize([
                'specialization' => $request->input('EnquiryName'),
                'specializeimage' => $imageName,
            ]);

            $specialization->save();

            $symptomsData = json_decode($request->input('symtomslist'), true);

            // Save symptoms for the specialization
            foreach ($symptomsData as $symptom) {
                $symptomModel = new Symtoms([
                    'symtoms' => $symptom,
                    'specialization_id' => $specialization->id,
                ]);

                $symptomModel->save();
            }

            return $this->sendResponse("specialization", $specialization->id, '1', 'Specialization and symptoms created successfully');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $specialization = Specialize::find($id);

        if (is_null($specialization)) {
            return $this->sendError('specialization not found.');
        }

        return $this->sendResponse("specialization", $specialization, '1', 'specialization retrieved successfully.');
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
        $specialization = Specialize::find($id);

        $input = $request->all();

        $validator = Validator::make($input, [
            'specialization' => ['required', 'max:25'],
            'remark' => ['max:250'],

        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors());
        } else {
            $specialization->specialization = $input['specialization'];

            $specialization->save();
            return $this->sendResponse("specialization", $specialization, '1', 'specialization Updated successfully');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $specialization = Specialize::find($id);

        if (is_null($specialization)) {
            return $this->sendError('specialization not found.');
        }

        $specialization->delete();
        return $this->sendResponse("specialization", $specialization, '1', 'specialization Deleted successfully');
    }
    public function Specialize(Request $request)
    {
        $rules = [
            'specialization' => 'required',
            'specializeimage' => 'required',
            'specialize_Icon' => 'required',

        ];

        $validation = Validator::make($request->all(), $rules);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }

        $specialize = new Specialize();
        $specialize->specialization = $request->specialization;

        if ($request->hasFile('specializeimage')) {
            $imageFile = $request->file('specializeimage');

            if ($imageFile->isValid()) {
                $imageName = $imageFile->getClientOriginalName();
                $imageFile->move(public_path('specializeimage'), $imageName);
                $specialize->specializeimage = $imageName;
            }
        }

        if ($request->hasFile('specialize_Icon')) {
            $iconFile = $request->file('specialize_Icon');

            if ($iconFile->isValid()) {
                $iconName = $iconFile->getClientOriginalName();
                $iconFile->move(public_path('specializeIcon'), $iconName);
                $specialize->specialize_icon = $iconName;
            }
        }

        $specialize->save();

        // return $this->sendResponse("Specialize", $specialize, '1', 'specialization created successfully.');
        return response()->json(['status' => '1', 'data' => $specialize, 'message' => 'Specialization created successfully.']);
    }
}
