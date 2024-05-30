<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\CompletedAppointments;
use App\Models\FavouriteLab;
use App\Models\LabDocuments;
use App\Models\Laboratory;
use App\Models\TokenBooking;
use App\Models\patient;
use App\Models\LabTest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LabController extends BaseController
{


    public function Labregister(Request $request)
    {

        try {
            $input = $request->all();

            $rules = [
                'firstname' => 'required|string|max:255',
                'lab_image' => 'sometimes|image',
                'mobileNo'  => 'required|numeric|digits:10',
                'location'  => 'required|string|max:255',
                'email'     => 'required|email|unique:laboratory,email|unique:users,email',
                'address'   => 'required|string|max:255',
                'password'  => 'required|string|min:8',
                'Type'      => 'sometimes|in:1,2,3'
            ];

            $messages = [
                'firstname.required' => 'First name is required',
                'lab_image.required' =>  'The lab image needed',
                'mobileNo.required'  => 'Mobile number is required',
                'mobileNo.numeric'   => 'Mobile number must be numeric',
                'mobileNo.digits'    => 'Mobile number must be 10 digits long',
                'location.required'  => 'Location is required',
                'email.required'     => 'Email is required',
                'email.email'        => 'Invalid email format',
                'email.unique'       => 'Email already exists',
                'address.required'   => 'Address is required',
                'password.required'  => 'Password is required',
                'password.min'       => 'Password must be at least 8 characters long',
                'Type.in'            => 'Invalid type selected'
            ];

            $validator = Validator::make($input, $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            DB::beginTransaction();
            $password = Hash::make($request->password);
            $userId = DB::table('users')->insertGetId([
                'firstname' => $input['firstname'],
                'email' => $input['email'],
                'password' => $password,
                'user_role' => 4,
            ]);

            // Create Laboratory record

            $lab = new Laboratory();
            $lab->firstname = $request->input('firstname');
            $lab->mobileNo = $request->input('mobileNo');
            $lab->location = $request->input('location');
            $lab->email = $request->input('email');
            $lab->UserId = $userId;
            $lab->address = $request->input('address');
            $lab->Type = $request->input('Type', null);

            $imageName = null;

            if ($request->hasFile('lab_image')) {
                $imageFile = $request->file('lab_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('LabImages/images'), $imageName);
                }
            }


            $lab->lab_image = $imageName ? $imageName : null;
            $lab->save();

            DB::commit();
            unset($lab->password);

            return response()->json(['status' => true, 'message' => 'Laboratory created successfully', 'Lab' => $lab], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }


    public function LabTest(Request $request)
    {
        try {
            // Validate request data
            $this->validate($request, [
                'lab_id' => 'required',
                'TestName' => 'required',
                'TestDescription' => 'sometimes',
                'Test_price' => 'required',
                'discount' => 'sometimes',
            ]);

            // Extract data from the request
            $lab_id = $request->input('lab_id');
            $TestName = $request->input('TestName');
            $TestDescription = $request->input('TestDescription');
            $Test_price = $request->input('Test_price');
            $discount = $request->input('discount');

            // Check if discount is provided
            if ($discount !== null) {
                $Total_price = $Test_price - ($Test_price * $discount / 100);
            } else {
                $Total_price = $Test_price;
            }

            $MedicineData = [
                'lab_id' => $lab_id,
                'TestName' => $TestName,
                'TestDescription' => $TestDescription,
                'Test_price' => $Test_price,
                'discount' => $discount,
                'Total_price' => $Total_price,
            ];

            // Upload and save the image if provided
            if ($request->hasFile('test_image')) {
                $imageFile = $request->file('test_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('LabImages/Test'), $imageName);

                    $MedicineData['test_image'] = $imageName;
                }
            }

            // Save the data to the database
            $Medicine = new LabTest($MedicineData);
            $Medicine->save();

            // Return success response
            return $this->sendResponse('MedicineProduct', $MedicineData, '1', 'Medicine added successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }
    //get all the lab for docter App
    public function GetLabForDoctors()
    {
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;

            $Laboratories = Laboratory::where('Type', 1)->get();


            $LaboratoryDetails = [];

            foreach ($Laboratories as $Laboratory) {
                // Check favorite status for each laboratory
                $favoriteStatus = DB::table('favouriteslab')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('lab_id', $Laboratory->id)
                    ->exists();

                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'Laboratory retrieved successfully');
        }
    }

    public function GetScanningForDoctors()
    {
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;

            $Laboratories = Laboratory::where('Type', 2)->get();
            $LaboratoryDetails = [];

            foreach ($Laboratories as $Laboratory) {
                // Check favorite status for each laboratory
                $favoriteStatus = DB::table('favouriteslab')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('lab_id', $Laboratory->id)
                    ->exists();

                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'Laboratory retrieved successfully');
        }
    }

    public function GetLabandScanForDoctors()
    {
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;

            // $Laboratories = Laboratory::where('Type', 1)->get();
            $Laboratories = Laboratory::all();

            $LaboratoryDetails = [];

            foreach ($Laboratories as $Laboratory) {
                // Check favorite status for each laboratory
                $favoriteStatus = DB::table('favouriteslab')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('lab_id', $Laboratory->id)
                    ->exists();

                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'Laboratory retrieved successfully');
        }
    }

    public function GetAllLabs()
    {
        $Laboratories = Laboratory::where('Type', 1)->get();
        $LaboratoryDetails = [];

        foreach ($Laboratories as $Laboratory) {

            $LaboratoryDetails[] = [
                'id' => $Laboratory->id,
                'UserId' => $Laboratory->UserId,
                'Laboratory' => $Laboratory->firstname,
                'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                'mobileNo' => $Laboratory->mobileNo,
                'location' => $Laboratory->location,

            ];
        }

        return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'Laboratory retrieved successfully');
    }


    public function addFavouirtesLab(Request $request)
    {
        $doctorId = $request->doctor_id;
        $labId = $request->lab_id;

        $laboratory = Laboratory::find($labId);

        if (!$laboratory) {
            return response()->json(['error' => 'Laboratory not found'], 404);
        }

        // Check if the laboratory is already a favorite for the doctor
        $existingFavorite = FavouriteLab::where('lab_id', $labId)
            ->where('doctor_id', $doctorId)
            ->first();

        if ($existingFavorite) {
            // Laboratory is already a favorite for the doctor
            return response()->json(['status' => false, 'message' => 'Laboratory is already saved as a favorite.']);
        }

        // If not already a favorite, add it to the favorites list
        $addFavorite = new FavouriteLab();
        $addFavorite->lab_id = $labId;
        $addFavorite->doctor_id = $doctorId;
        $addFavorite->save();

        return response()->json(['status' => true, 'message' => 'Laboratory added to favorites successfully.']);
    }

    public function RemoveFavouirtesLab(Request $request)
    {
        $docterId = $request->doctor_id;
        $LabId = $request->lab_id;
        $Laboratory = Laboratory::find($LabId);

        if (!$Laboratory) {
            return response()->json(['error' => 'Laboratory not found'], 404);
        }
        $existingFavourite = FavouriteLab::where('lab_id', $LabId)
            ->where('doctor_id', $docterId)
            ->first();

        if ($existingFavourite) {
            FavouriteLab::where('doctor_id', $docterId)->where('lab_id', $LabId)->delete();
            return response()->json(['status' => true, 'message' => 'favourite Removed successfully .']);
        }
    }

    public function getFavlab()
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;

            $favoriteLabs = FavouriteLab::leftJoin('laboratory', 'laboratory.id', '=', 'favouriteslab.lab_id')
                ->where('doctor_id', $loggedInDoctorId)
                ->select('laboratory.*')
                ->get();

            if (!$favoriteLabs) {
                return response()->json([
                    'status' => true,
                    'message' => 'No Favorite labs found',
                    'favoriteLabs' => NULL
                ]);
            }

            $LaboratoryDetails = [];

            foreach ($favoriteLabs as $Laboratory) {
                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                ];
            }

            return response()->json(['status' => true, 'message' => 'Favorite labs retrieved successfully.', 'favoriteLabs' => $LaboratoryDetails]);
        } else {
            return response()->json(['status' => false, 'message' => 'User not authenticated.']);
        }
    }



    public function searchLabByName(Request $request)
    {
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;



            $labName = $request->input('lab_name');

            $Laboratories = Laboratory::where('Type', 1)
                ->where('firstname', 'like', '%' . $labName . '%')
                ->get();

            $LaboratoryDetails = [];

            foreach ($Laboratories as $Laboratory) {
                // Check favorite status for each laboratory
                $favoriteStatus = DB::table('favouriteslab')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('lab_id', $Laboratory->id)
                    ->exists();

                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'Laboratory retrieved successfully');
        } else {
            return $this->sendError('User not authenticated.', [], 401);
        }
    }



    public function searchLabByNameScanningCenter(Request $request)
    {
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;



            $labName = $request->input('scanningCenter_name');

            $Laboratories = Laboratory::where('Type', 2)
                ->where('firstname', 'like', '%' . $labName . '%')
                ->get();

            $LaboratoryDetails = [];

            foreach ($Laboratories as $Laboratory) {
                // Check favorite status for each laboratory
                $favoriteStatus = DB::table('favouriteslab')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('lab_id', $Laboratory->id)
                    ->exists();

                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'ScanningCenter retrieved successfully');
        } else {
            return $this->sendError('User not authenticated.', [], 401);
        }
    }

    public function getLabdetails(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'lab_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 400);
        }

        $lab_id = $request->input('lab_id');

        $LabDetails = DB::table('token_booking')
            ->join('docter', 'token_booking.doctor_id', '=', 'docter.id')
            ->join('laboratory', 'token_booking.lab_id', '=', 'laboratory.id')
            ->join('clinics', 'token_booking.clinic_id', '=', 'clinics.clinic_id')
            ->select('token_booking.PatientName', 'token_booking.gender', 'token_booking.age', 'token_booking.mobileNo', 'token_booking.lab_id', 'token_booking.labtest', 'token_booking.clinic_id', 'clinics.clinic_name', 'docter.firstname as doctor_name', 'laboratory.firstname as laboratory_name')
            ->where('token_booking.lab_id', $lab_id)
            ->get();

        if ($LabDetails->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Corresponding id details are not available.'], 404);
        }

        return response()->json(['status' => true, 'message' => ' details retrieved successfully', 'Testdetails' => $LabDetails]);
    }
    public function searchLabandScan(Request $request)
    {
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;
            $labName = $request->input('lab_name');
            $Laboratories = Laboratory::where('firstname', 'like', '%' . $labName . '%')
                ->get();
            $LaboratoryDetails = [];
            foreach ($Laboratories as $Laboratory) {
                $favoriteStatus = DB::table('favouriteslab')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('lab_id', $Laboratory->id)
                    ->exists();
                $LaboratoryDetails[] = [
                    'id' => $Laboratory->id,
                    'UserId' => $Laboratory->UserId,
                    'Laboratory' => $Laboratory->firstname,
                    'Laboratoryimage' => asset("LabImages/{$Laboratory->lab_image}"),
                    'mobileNo' => $Laboratory->mobileNo,
                    'location' => $Laboratory->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }
            return $this->sendResponse("Laboratory", $LaboratoryDetails, '1', 'Laboratory retrieved successfully');
        } else {
            return $this->sendError('User not authenticated.', [], 401);
        }
    }
    public function LabDocumentUpload(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'lab_id' => 'required',
            'doctor_id' => 'required',
            'clinic_id' => 'required',
            'patient_id' => 'required',
            'token_id' => 'required',
            'UserId' => 'required',
            'document_upload' => 'required',
            'notes' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'error' => $validation->errors()->first()], 400);
        }

        $notes = $request->input('notes');
        $document_upload = $request->input('document_upload');

        $lab_documents = new LabDocuments();
        $lab_documents->lab_id = $request->lab_id;
        $lab_documents->doctor_id = $request->doctor_id;
        $lab_documents->clinic_id = $request->clinic_id;
        $lab_documents->patient_id = $request->patient_id;
        $lab_documents->token_id = $request->token_id;
        $lab_documents->UserId = $request->UserId;
        $lab_documents->status = 0;

        if ($request->hasFile('document_upload')) {
            $imageFile = $request->file('document_upload');
            if ($imageFile->isValid()) {
                $imageName = time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move(public_path('labdocuments'), $imageName);

                $lab_documents->document_upload = 'img/' . $imageName;
            }
        }
        $lab_documents->notes = $notes;
        $lab_documents->save();

        return $this->sendResponse("lab_documents", $lab_documents->toArray() + ['document_upload' => $lab_documents->labdocuments], '1', 'lab documents uploaded successfully.');
    }
    public function getUpComingLabdetails($lab_id)
    {

        $labDetails = DB::table('token_booking')
            ->select('token_booking.PatientName', 'token_booking.gender', 'token_booking.age', 'token_booking.mobileNo', 'token_booking.patient_id', 'token_booking.doctor_id', 'token_booking.lab_id', 'token_booking.labtest', 'token_booking.clinic_id', 'clinics.clinic_name', 'docter.firstname as doctor_name',)
            ->where('token_booking.lab_id', $lab_id)
            ->leftJoin('docter', 'token_booking.doctor_id', '=', 'docter.UserId')
            ->leftJoin('laboratory', 'token_booking.lab_id', '=', 'laboratory.id')
            ->leftJoin('clinics', 'token_booking.clinic_id', '=', 'clinics.clinic_id')
            ->leftJoin('lab_documents', 'token_booking.patient_id', '=', 'lab_documents.patient_id')
            ->distinct()
            ->get();

        if ($labDetails->isEmpty()) {
            return response()->json(['status' => true, 'message' => 'Corresponding details are not available.', 'Testdetails' => $labDetails]);
        }

        return response()->json(['status' => true, 'message' => 'Details retrieved successfully', 'Testdetails' => $labDetails]);
    }
    public function getCompletedLabdetails($lab_id)
    {


        $labDetails = CompletedAppointments::select(
            'completed_appointments.appointment_id',
            'completed_appointments.lab_id',
            'lab_documents.patient_id',
            'token_booking.PatientName',
            'token_booking.gender',
            'token_booking.age',
            'token_booking.mobileNo',
            'token_booking.labtest',
            'token_booking.clinic_id',
            'clinics.clinic_name',
            'lab_documents.doctor_id',
            'docter.firstname as doctor_name',
            'lab_documents.document_upload',
            'lab_documents.notes'
        )
            ->leftJoin('token_booking', 'completed_appointments.lab_id', '=', 'token_booking.lab_id')
            ->leftJoin('docter', 'token_booking.doctor_id', '=', 'docter.UserId')
            ->leftJoin('laboratory', 'token_booking.lab_id', '=', 'laboratory.id')
            ->leftJoin('clinics', 'token_booking.clinic_id', '=', 'clinics.clinic_id')
            ->leftJoin('lab_documents', 'completed_appointments.lab_id', '=', 'lab_documents.lab_id')
            ->where('completed_appointments.lab_id', $lab_id)
            ->distinct('lab_id')
            ->distinct('completed_appointments.lab_id')
            ->get();

        if ($labDetails->isEmpty()) {
            return response()->json(['status' => true, 'message' => 'Corresponding id details are not available.', 'Testdetails' => $labDetails]);
        }

        return response()->json(['status' => true, 'message' => 'Completed appointments', 'Testdetails' => $labDetails]);
    }
}
