<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController;
use App\Models\Docter;
use App\Models\TokenBooking;
use App\Models\DocterAvailability;
use App\Models\Favouritestatus;
use App\Models\LabReport;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientPrescriptions;
use App\Models\Symtoms;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Specialize;
use App\Models\Specification;
use App\Models\Subspecification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use League\CommonMark\Node\Block\Document;

class UserController extends BaseController
{
    public function UserRegister(Request $request)
    {

        try {
            DB::beginTransaction();

            $input = $request->all();

            $validator = Validator::make($input, [
                'firstname' => 'required|string',
                'secondname' => 'required|string',
                'mobileNo' => 'required',
                'gender' => 'required',
                'age' => 'required',
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }
            $emailExists = Patient::where('email', $input['email'])->count();
            $emailExistsinUser = User::where('email', $input['email'])->count();

            if ($emailExists && $emailExistsinUser) {
                return $this->sendResponse("Docters", null, '3', 'Email already exists.');
            }

            $input['password'] = Hash::make($input['password']);

            $userId = DB::table('users')->insertGetId([
                'firstname' => $input['firstname'],
                'secondname' => $input['secondname'],
                'email' => $input['email'],
                'password' => $input['password'],
                'mobileNo' => $input['mobileNo'],
                'user_role' => 3,
            ]);

            $PatientData = [

                'firstname' => $input['firstname'],
                'lastname' => $input['secondname'],
                'mobileNo' => $input['mobileNo'],
                'email' => $input['email'],
                'location' => $input['location'],
                'age' => $input['age'],
                'gender' => $input['gender'],
                'UserId' => $userId,
            ];

            if ($request->hasFile('user_image')) {
                $imageFile = $request->file('user_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('UserImages'), $imageName);

                    $PatientData['user_image'] = $imageName;
                }
            }

            $patient = new Patient($PatientData);
            $patient->save();




            DB::commit();

            return $this->sendResponse("users", $patient, '1', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }
    public function updateUserDetails(Request $request, $userId)
    {
        try {
            DB::beginTransaction();
dd($request->getContent());
            // Check if the user exists
            $user = User::find($userId);

            if (!$user) {
                return $this->sendResponse(null, null, '2', 'User not found.');
            }

            // Update user details
            $user->firstname = $request->input('firstname') ?? $user->firstname;
            $user->secondname = $request->input('secondname') ?? $user->secondname;
            $user->email = $request->input('email') ?? $user->email;
            $user->mobileNo = $request->input('mobileNo') ?? $user->mobileNo;
            $user->save();

            // Update patient details
            $patient = Patient::where('UserId', $userId)->where('user_type', 1)->first();

            if (!$patient) {
                return $this->sendResponse(null, null, '3', 'Patient not found.');
            }

            $patient->firstname = $request->input('firstname') ?? $patient->firstname;
            $patient->lastname = $request->input('secondname') ?? $patient->lastname;
            $patient->mobileNo = $request->input('mobileNo') ?? $patient->mobileNo;
            $patient->email = $request->input('email') ?? $patient->email;

            // Check if the 'location' field exists in the request
            if ($request->has('location')) {
                $patient->location = $request->input('location');
            }

            // Check if the 'gender' field exists in the request
            if ($request->has('gender')) {
                $patient->gender = $request->input('gender') ?? $patient->gender;
            }

            // Check if the 'user_image' field exists in the request
            if ($request->hasFile('user_image')) {
                $imageFile = $request->file('user_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('UserImages'), $imageName);

                    $patient->user_image = $imageName;
                }
            }

            $patient->save();

            DB::commit();

            return $this->sendResponse("users", $patient, '1', 'User details updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $errorMessages = [], $code = 404);
        }
    }




    public function UserEdit($userId)
    {
        $userDetails = Patient::where('UserId', $userId)->where('user_type', 1)->first();
        if (!$userDetails) {
            $response = ['message' => 'User not found with the given UserId'];
            return response()->json($response, 404);
        }
        return $this->sendResponse('Userdetails', $userDetails, '1', 'User retrieved successfully.');
    }

    public function getallfavourites($id)
    {
        $specializeArray['specialize'] = Specialize::all();
        $specificationArray['specification'] = Specification::all();
        $subspecificationArray['subspecification'] = Subspecification::all();

        // Get all favorite doctors for the given user ID
        $favoriteDoctors = Favouritestatus::where('UserId', $id)->get();

        $favoriteDoctorsWithSpecifications = [];

        foreach ($favoriteDoctors as $favoriteDoctor) {
            // Fetch details for each favorite doctor
            $doctor = Docter::Leftjoin('docteravaliblity', 'docter.id', '=', 'docteravaliblity.docter_id')
                ->where('docter.UserId', $favoriteDoctor->doctor_id)
                ->first();
            $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);
            if ($doctor) {
                $id = $doctor->id;

                // Initialize doctor details if not already present
                if (!isset($favoriteDoctorsWithSpecifications[$id])) {
                    $favoriteDoctorsWithSpecifications[$id] = [
                        'id' => $id,
                        'UserId' => $doctor->UserId,
                        'firstname' => $doctor->firstname,
                        'secondname' => $doctor->lastname,
                        'Specialization' => $specialize ? $specialize['specialization'] : null,
                        'DocterImage' => asset("DocterImages/images/{$doctor->docter_image}"),
                        'Location' => $doctor->location,
                        'MainHospital' => $doctor->Services_at,

                    ];
                }
            }
        }

        // Format the output to match the expected structure
        $formattedOutput = array_values($favoriteDoctorsWithSpecifications);

        return $this->sendResponse('Favorite Doctors', $formattedOutput, '1', 'Favorite doctors retrieved successfully.');
    }


    public function UserLogin(Request $req)
    {
        // validate inputs
        $rules = [
            'email' => 'required',
            'password' => 'required|string'
        ];
        $req->validate($rules);
        // find user email in users table
        $user = User::where('email', $req->email)->first();

        // if user email found and password is correct
        if ($user && Hash::check($req->password, $user->password)) {
            $token = $user->createToken('Personal Access Token')->plainTextToken;
            $response = ['user' => $user, 'token' => $token];
            return response()->json($response, 200);
        }
        $response = ['message' => 'Incorrect email or password'];
        return response()->json($response, 400);
    }



    private function getClinics($doctorId)
    {
        // Replace this with your actual logic to retrieve clinic details from the database
        // You may use Eloquent queries or another method based on your application structure
        $clinics = DocterAvailability::where('docter_id', $doctorId)->get(['id', 'hospital_Name', 'startingTime', 'endingTime', 'address', 'location']);

        return $clinics;
    }




    public  function GetUserCompletedAppoinments(Request $request, $userId)
    {
        try {
            // Get the currently authenticated doctor
            $doctor = Patient::where('UserId', $userId)->first();


            if (!$doctor) {
                return response()->json(['message' => 'Patient not found.'], 404);
            }

            // Validate the date format (if needed)

            // Get all appointments for the doctor on the selected date
            $appointments = Patient::join('token_booking', 'token_booking.BookedPerson_id', '=', 'patient.UserId')
                ->join('docter', 'docter.UserId', '=', 'token_booking.doctor_id') // Join the doctor table
                ->where('patient.UserId', $doctor->UserId)
                ->orderByRaw('CAST(token_booking.TokenNumber AS SIGNED) ASC')
                ->where('Is_completed', 1)
                ->distinct()
                ->get(['token_booking.*', 'docter.*']);

            // Initialize an array to store appointments along with doctor details
            $appointmentsWithDetails = [];

            // Iterate through each appointment and add symptoms information
            foreach ($appointments as $appointment) {
                $symptoms = json_decode($appointment->Appoinmentfor_id, true);

                // Extract appointment details
                $appointmentDetails = [
                    'TokenNumber' => $appointment->TokenNumber,
                    'Date' => $appointment->date,
                    'Startingtime' => $appointment->TokenTime,
                    'PatientName' => $appointment->PatientName,
                    'main_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor1'])->get()->toArray(),
                    'other_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray(),
                ];

                // Extract doctor details from the first appointment (assuming all appointments have the same doctor details)
                $doctorDetails = [
                    'firstname' => $appointment->firstname,
                    'secondname' => $appointment->lastname,
                    'Specialization' => $appointment->specialization,
                    'DocterImage' => asset("DocterImages/images/{$appointment->docter_image}"),
                    'Mobile Number' => $appointment->mobileNo,
                    'MainHospital' => $appointment->Services_at,
                    'subspecification_id' => $appointment->subspecification_id,
                    'specification_id' => $appointment->specification_id,
                    'specifications' => explode(',', $appointment->specifications),
                    'subspecifications' => explode(',', $appointment->subspecifications),
                    'clincs' => [],
                ];

                // Assuming you have a way to retrieve and append clinic details
                // You need to implement a function like getClinics() based on your database structure
                $doctorDetails['clincs'] = $this->getClinics($appointment->clinic_id);

                // Combine appointment and doctor details
                $combinedDetails = array_merge($appointmentDetails, $doctorDetails);

                // Add to the array
                $appointmentsWithDetails[] = $combinedDetails;
            }

            // Return a success response with the appointments and doctor details
            return $this->sendResponse('Appointments', $appointmentsWithDetails, '1', 'Appointments retrieved successfully.');
        } catch (\Exception $e) {
            // Handle unexpected errors
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }

    public function favouritestatus(Request $request)
    {
        $userId = $request->user_id;
        $docterId = $request->docter_id;

        $docter = Docter::find($docterId);

        if (!$docter) {
            return response()->json(['error' => 'Doctor not found'], 404);
        }

        // Check if the user has already added the doctor to favorites
        $existingFavourite = Favouritestatus::where('UserId', $userId)
            ->where('doctor_id', $docterId)
            ->first();

        if ($existingFavourite) {
            Favouritestatus::where('doctor_id', $docterId)->where('UserId', $userId)->delete();
            return response()->json(['status' => true, 'message' => 'favourite Removed successfully .']);
        } else {
            // If not, create a new entry in the addfavourites table
            $addfav = new Favouritestatus();
            $addfav->UserId = $userId;
            $addfav->doctor_id = $docterId;
            $addfav->save();
        }

        return response()->json(['status' => true, 'message' => 'favourite added successfully .']);
    }



    public function uploadDocument(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'document'    => 'required|mimes:doc,docx,pdf,jpeg,png,jpg',

        ];
        $messages = [
            'document.required' => 'Document is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $patient_doc = new PatientDocument();
            $patient_doc->user_id = $request->user_id;
            if ($request->hasFile('document')) {
                $imageFile = $request->file('document');
                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('user/documents'), $imageName);
                    $patient_doc->document = $imageName;
                }
            }
            $patient_doc->patient_id = $request->patient_id;
            $patient_doc->save();
            $patient_doc->document = asset('user/documents') . '/' . $patient_doc->document;
            return response()->json(['status' => true, 'response' => "Uploading Success", 'document' => $patient_doc]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }


    public function updateDocument(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
            'document_id'    => 'required',
            'patient_id'     => 'required',
            'type'           => 'required|in:1,2',
            'test_name'      => 'required_if:type,1',
            'lab_name'       => 'required_if:type,1',
            'doctor_name'    => 'required_if:type,1,2',
            'date'           => 'required_if:type,1,2',
        ];
        $messages = [
            'document_id.required' => 'DocumentId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            DB::beginTransaction();
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }

            $document = PatientDocument::where('id', $request->document_id)->first();
            if (!$document) {
                return response()->json(['status' => false, 'response' => 'Document not found']);
            }
            $this->updateDocumentType($request, $document);
            DB::commit();
            return response()->json(['status' => true, 'response' => "File Updated"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    private function updateDocumentType(Request $request, PatientDocument $document)
    {
        $type = $request->type;

        if ($type == '1' || $type == '2') {
            $model = ($type == '1') ? LabReport::class : PatientPrescriptions::class;
            $record = $model::where('user_id', $request->user_id)->where('document_id', $request->document_id)->first();

            if (!$record) {
                $record = new $model();
            }

            $record->patient_id = $request->patient_id;
            $record->user_id = $request->user_id;
            $record->document_id = $request->document_id;
            $record->date = $request->date;
            $record->doctor_name = $request->doctor_name;

            if ($type == '1') {
                $record->test_name = $request->test_name;
                $record->lab_name  = $request->lab_name;
            }
            if ($request->notes) {
                $record->notes = $request->notes;
            }
            if ($request->file_name) {
                $this->updateDocumentFile($request, $document, $record);
            }
            $record->save();
            $document->patient_id = $request->patient_id;
            $document->status = 1;
            $document->type = $type;
            $document->save();
        }
    }

    private function updateDocumentFile(Request $request, PatientDocument $document, $record)
    {
        $oldFilePath = public_path('user/documents/' . $document->document);

        if (!File::exists($oldFilePath)) {
            return response()->json(['status' => false, 'response' => 'File not found']);
        }

        $newFileName = $request->file_name;
        $newFileNameWithExtension = $newFileName . '.' . pathinfo($oldFilePath, PATHINFO_EXTENSION);
        $newFilePath = public_path('user/documents/' . $newFileNameWithExtension);

        // Move the file to the new name
        File::move($oldFilePath, $newFilePath);

        // Update the file name in the database
        $record->file_name = $newFileName;
        $document->document = $newFileNameWithExtension;
        $document->save();
    }

    public function getUploadedDocuments(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'patient_id'  => 'required'
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $patient_doc = PatientDocument::select('id', 'user_id', 'status', 'patient_id', 'type', 'created_at', 'updated_at', DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path"))
                ->where('user_id', $request->user_id)
                ->where('patient_id', $request->patient_id);

            if ($request->type) {
                $patient_doc = $patient_doc->where('type', $request->type);
                if ($request->type == 2) {
                    $patient_doc = $patient_doc->with(['PatientPrescription:id,document_id,date,doctor_name'])->get();
                }
                if ($request->type == 1) {
                    $patient_doc = $patient_doc->with(['LabReport:id,document_id,date,test_name'])->get();
                }
            }
            foreach ($patient_doc as $patient_docment) {
                $today = Carbon::now();
                $patient_detail = Patient::where('id', $request->patient_id)->first();
                // Format the date
                $formattedDate = $today->format('Y/m/d');
                $patient_docment->hours_ago = 0;
                $patient_docment->date = $formattedDate;
                $patient_docment->patient = $patient_detail->firstname;
            }

            if ($patient_doc->isEmpty()) {
                $patient_doc = null;
            }

            return response()->json(['status' => true, 'document_data' => $patient_doc]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function ReportsTimeLine(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'patient_id'  => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        $user = User::where('id', $request->user_id)->first();

        try {
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $time_line = PatientDocument::select('id', 'user_id', 'status', 'created_at', DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path"))
                ->where('user_id', $request->user_id)
                ->where('type', 1)
                ->whereHas('LabReports', function ($query) use ($request) {
                    $query->where('patient_id', $request->patient_id);
                })
                ->with('LabReports')
                ->get();
            if (!$time_line) {
                return response()->json(['status' => true, 'time_line' => null]);
            }
            return response()->json(['status' => true, 'time_line' => $time_line]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function getPrescriptions(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $prescriptions = PatientDocument::select('id', 'user_id', 'status', 'created_at', DB::raw("CONCAT('" . asset('user/documents') . "', '/', document) AS document_path"))->where('user_id', $request->user_id)->where('type', 2)
                ->whereHas('PatientPrescriptions', function ($query) use ($request) {
                    $query->where('patient_id', $request->patient_id);
                })->with('PatientPrescriptions')->get();
            if (!$prescriptions) {
                return response()->json(['status' => true, 'prescriptions' => null]);
            }
            return response()->json(['status' => true, 'prescriptions' => $prescriptions]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function manageMembers(Request $request)
    {
        $rules = [
            'user_id'     => 'required',
            'first_name'  => 'required',
            'gender'      => 'required|in:1,2,3',
            'relation'    => 'required|in:1,2,3',
            'age' => 'required'
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            if ($request->relation == '1') {
                $patient_detail = Patient::where('user_type', 1)->first();
                if ($patient_detail) {
                    return response()->json(['status' => false, 'response' => "A profile is already in self"]);
                }
            }
            $user = User::where('id', $request->user_id)->first();
            if (!$user) {
                return response()->json(['status' => false, 'response' => "User not found"]);
            }
            $patient = new Patient();
            if ($request->patient_id) {
                $patient = Patient::find($request->patient_id);
                $msg = "Member update successfully";
            } else {
                $msg = "Member added successfully";
            }
            $patient->firstname = $request->first_name;
            $patient->lastname = $request->last_name;
            $patient->gender    = $request->gender;
            $patient->age    = $request->age;
            $patient->user_type = $request->relation;
            $patient->email     = $request->email;
            $patient->UserId    = $request->user_id;
            $patient->save();
            return response()->json(['status' => true, 'response' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }


    public function manageAddress(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
            'building_name'  => 'required',
            'area'           => 'required',
            'Landmark'       => 'required',
            'pincode'        => 'required',
            'city'           => 'required',
            'state'          => 'required'
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $address = new UserAddress();
            if ($request->id) {
                $address = UserAddress::find($request->id);
                $msg = "address update successfully";
            } else {
                $msg = "address added successfully";
            }
            $address->user_id       = $request->user_id;
            $address->building_name = $request->building_name;
            $address->area          = $request->area;
            $address->Landmark      = $request->Landmark;
            $address->pincode       = $request->pincode;
            $address->city          = $request->city;
            $address->state         = $request->state;
            $address->save();
            return response()->json(['status' => true, 'response' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function getUserAddresses(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $address = UserAddress::where('user_id', $request->user_id)->get();
            return response()->json(['status' => true, 'address_data' => $address]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function getPatients(Request $request)
    {
        $rules = [
            'user_id'        => 'required',
        ];
        $messages = [
            'user_id.required' => 'UserId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $patients = Patient::select('id', 'firstname', 'mobileNo', 'gender', 'email', 'age')->where('UserId', $request->user_id)->get();

            return response()->json(['status' => true, 'patients_data' => $patients]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function PatientHistory(Request $request)
    {
        $rules = [
            'patient_id'        => 'required',
        ];
        $messages = [
            'patient_id.required' => 'PatientId is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        try {
            $patientId = $request->patient_id;
            $history = PatientDocument::where('patient_id', $patientId)->with('LabReports', 'PatientPrescriptions')->first();
            if (!$history) {
                $history = null;
            }
            return response()->json(['status' => true, 'history_data' => $history]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
    public function editPatient(Request $request, $patientId)
    {
        try {
            $patient = Patient::find($patientId);

            if (!$patient) {
                return response()->json(['status' => false, 'response' => 'Patient not found']);
            }

            // You can customize the fields you want to include in the response
            $patientData = [
                'id'        => $patient->id,
                'firstname' => $patient->firstname,
                'gender'    => $patient->gender,
                'age'       => $patient->age,
            ];

            return response()->json(['status' => true, 'patient_data' => $patientData]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => 'Internal Server Error']);
        }
    }

    public function updatePatient(Request $request, $patientId)
    {


        try {
            // Find the patient by ID
            $patient = Patient::find($patientId);

            if (!$patient) {
                return response()->json(['status' => false, 'response' => 'Patient not found']);
            }

            // Update patient information
            $patient->update([
                'firstname' => $request->input('firstname') ?? $patient->firstname,
                'gender'    => $request->input('gender') ?? $patient->gender,
                'age'       => $request->input('age') ?? $patient->age,
            ]);

            return response()->json(['status' => true, 'response' => 'Patient updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }

    public function DeleteMemeber($patientId)
    {
        $Patient = Patient::find($patientId);

        if (is_null($Patient)) {
            return $this->sendError('Patient not found.');
        }

        $Patient->delete();
        return $this->sendResponse("Patient", $Patient, '1', 'Member Deleted successfully');
    }


    public function recentlyBookedDoctor(Request $request)
    {
        try {
            $authenticatedUserId = auth()->user()->id;
            $specializeArray['specialize'] = Specialize::all();
            $specificationArray['specification'] = Specification::all();
            $subspecificationArray['subspecification'] = Subspecification::all();

            // Retrieve the recently booked doctor details
            $recentBooking = TokenBooking::select('id', 'doctor_id', 'date', 'TokenTime')
                ->where('BookedPerson_id', $authenticatedUserId)
                ->latest('date')
                ->get();

            if ($recentBooking->isEmpty()) {
                return response()->json(['status' => true, 'doctor_data' => null, 'message' => 'No recent bookings found']);
            }

            $doctersWithSpecifications = [];

            foreach ($recentBooking as $booking) {
                $doctorId = $booking->doctor_id;

                $docters = Docter::join('docteravaliblity', 'docter.id', '=', 'docteravaliblity.docter_id')
                    ->select('docter.UserId', 'docter.id', 'docter.docter_image', 'docter.firstname', 'docter.lastname', 'docter.specialization_id', 'docter.subspecification_id', 'docter.specification_id', 'docter.about', 'docter.location', 'docteravaliblity.id as avaliblityId', 'docter.gender', 'docter.email', 'docter.mobileNo', 'docter.Services_at', 'docteravaliblity.hospital_Name', 'docteravaliblity.startingTime', 'docteravaliblity.endingTime', 'docteravaliblity.address', 'docteravaliblity.location')
                    ->where('UserId', $doctorId)
                    ->get();

                foreach ($docters as $doctor) {
                    $id = $doctor['id'];

                    // Check if the doctor's user ID is in the "add_favorite" table for the authenticated user
                    $favoriteStatus = DB::table('addfavourite')
                        ->where('UserId', $authenticatedUserId)
                        ->where('doctor_id', $doctor['UserId'])
                        ->exists();

                    if (!isset($doctersWithSpecifications[$id])) {
                        $specialize = $specializeArray['specialize']->firstWhere('id', $doctor['specialization_id']);

                        $doctersWithSpecifications[$id] = [
                            'id' => $id,
                            'UserId' => $doctor['UserId'],
                            'firstname' => $doctor['firstname'],
                            'secondname' => $doctor['lastname'],
                            'Specialization' => $specialize ? $specialize['specialization'] : null,
                            'DocterImage' => asset("DocterImages/images/{$doctor['docter_image']}"),
                            'About' => $doctor['about'],
                            'Location' => $doctor['location'],
                            'Gender' => $doctor['gender'],
                            'emailID' => $doctor['email'],
                            'Mobile Number' => $doctor['mobileNo'],
                            'MainHospital' => $doctor['Services_at'],
                            'subspecification_id' => $doctor['subspecification_id'],
                            'specification_id' => $doctor['specification_id'],
                            'specifications' => [],
                            'subspecifications' => [],
                            'clincs' => [],
                            'favoriteStatus' => $favoriteStatus ? 1 : 0, // Add favorite status
                        ];
                    }

                    $specificationIds = array_unique(explode(',', $doctor['specification_id']));
                    $subspecificationIds = array_unique(explode(',', $doctor['subspecification_id']));


                    $doctersWithSpecifications[$id]['specifications'] = array_merge(
                        $doctersWithSpecifications[$id]['specifications'],
                        array_map(function ($id) use ($specificationArray) {
                            return $specificationArray['specification']->firstWhere('id', $id)['specification'];
                        }, $specificationIds)
                    );

                    $doctersWithSpecifications[$id]['subspecifications'] = array_merge(
                        $doctersWithSpecifications[$id]['subspecifications'],
                        array_map(function ($id) use ($subspecificationArray) {
                            return $subspecificationArray['subspecification']->firstWhere('id', $id)['subspecification'];
                        }, $subspecificationIds)
                    );

                    $doctersWithSpecifications[$id]['clincs'][] = [
                        'id'  => $doctor['avaliblityId'],
                        'name' => $doctor['hospital_Name'],
                        'StartingTime' => $doctor['startingTime'],
                        'EndingTime' => $doctor['endingTime'],
                        'Address' => $doctor['address'],
                        'Location' => $doctor['location'],
                    ];
                }
            }
            $formattedOutput = array_values($doctersWithSpecifications);
            return response()->json(['status' => true, 'doctor_data' => $formattedOutput, 'message' => 'Success']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'response' => "Internal Server Error"]);
        }
    }
}
