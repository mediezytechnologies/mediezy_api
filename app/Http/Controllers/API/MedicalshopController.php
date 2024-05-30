<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\API\BaseController;
use App\Models\FavouriteShop;
use App\Models\MainSymptom;
use App\Models\Medicalshop;
use App\Models\Medicine;
use App\Models\MedicineOrders;
use App\Models\MedicineProduct;
use App\Models\Patient;
use App\Models\Symtoms;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MedicalshopController extends BaseController
{
    ///medicalshop register
    public function MedicalshopRegister(Request $request)
    {
        try {
            $input = $request->all();
            $rules = [
                'firstname' => 'required',
                'medicalshop_image' => 'sometimes',
                'mobileNo'  => 'required',
                'location'  => 'required|string|max:255',
                'email'     => 'required|email|unique:medicalshop,email|unique:users,email',
                'address'   => 'required',
                'password'  => 'required|string|min:6',
                'pincode'  => 'required',
            ];
            $messages = [
                'firstname.required' => 'First name is required',
                'lab_image.required' =>  'The lab image needed',
                'mobileNo.required'  => 'Mobile number is required',
                'mobileNo.numeric'   => 'Mobile number must be numeric',
                'mobileNo.digits'    => 'Mobile number must be 10 digits long',
                'location.required'  => 'Location is required',
                'pincode.required'  => 'pincode is required',
                'email.required'     => 'Email is required',
                'email.email'        => 'Invalid email format',
                'email.unique'       => 'Email already exists',
                'address.required'   => 'Address is required',
                'password.required'  => 'Password is required',
                'password.min'       => 'Password must be at least 6 characters long',
            ];

            $validator = Validator::make($input, $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['status' => false,'message' => $validator->errors()->first()], 400);
            }

            $emailExists = Medicalshop::where('email', $input['email'])->count();
            $emailExistsinUser = User::where('email', $input['email'])->count();

            if ($emailExists && $emailExistsinUser) {
                return $this->sendResponse("medicalshop", null, '3', 'Email already exists.');
            }

            $password = Hash::make($request->password);

            $save_user = new User();
            $save_user->firstname = $request->firstname;
            $save_user->email = $request->email;
            $save_user->mobileNo = $request->mobileNo;
            $save_user->password = $password;
            $save_user->user_role = "5";
            $save_user->save();
            $savedUserId = $save_user->id;

            $medicalshop = new Medicalshop();
            $medicalshop->firstname = $request->firstname;
            $medicalshop->mobileNo = $request->mobileNo;
            $medicalshop->email = $request->email;
            $medicalshop->location = $request->location;
            $medicalshop->pincode = $request->pincode;
            $medicalshop->address = $request->address;
            $medicalshop->UserId = $savedUserId;

            if ($request->hasFile('medicalshop_image')) {
                $imageFile = $request->file('medicalshop_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('shopImages/images'), $imageName);
                    $medicalshop->medicalshop_image = $imageName;
                }
            }
            $medicalshop->save();
            return response()->json(['status' => true, 'message' => 'Medicalshop Created Successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function MedicineProduct(Request $request)
    {
        try {
            // Validate request data
            $this->validate($request, [
                'medicalshop_id' => 'required',
                'MedicineName' => 'required',
                'product_description' => 'sometimes',
                'product_price' => 'required',
                'discount' => 'sometimes',
            ]);

            // Extract data from the request
            $medicalshop_id = $request->input('medicalshop_id');
            $MedicineName = $request->input('MedicineName');
            $product_description = $request->input('product_description');

            $discount = $request->input('discount');
            $product_price = str_replace(',', '', $request->input('product_price'));
            $product_price = floatval($product_price);

            // Check if discount is provided and is numeric
            if ($discount !== null && is_numeric($discount)) {
                $Total_price = $product_price - ($product_price * $discount / 100);
            } else {
                $Total_price = $product_price;
            }

            $MedicineData = [
                'medicalshop_id' => $medicalshop_id,
                'MedicineName' => $MedicineName,
                'product_description' => $product_description,
                'product_price' => $product_price,
                'discount' => $discount,
                'Total_price' => $Total_price,
            ];

            // Upload and save the image if provided
            if ($request->hasFile('product_image')) {
                $imageFile = $request->file('product_image');

                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('shopImages/medicine'), $imageName);

                    $MedicineData['product_image'] = $imageName;
                }
            }

            // Save the data to the database
            $Medicine = new MedicineProduct($MedicineData);
            $Medicine->save();

            // Return success response
            return $this->sendResponse('MedicineProduct', $MedicineData, '1', 'Medicine added successfully.');
        } catch (ValidationException $e) {
            // Handle validation errors
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (QueryException $e) {
            // Handle database query errors
            return $this->sendError('Database Error', $e->getMessage(), 500);
        } catch (\Exception $e) {
            // Handle other unexpected errors
            return $this->sendError('Error', $e->getMessage(), 500);
        }
    }


    public function GetMedicalShopForDoctors()
    {

        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;

            $Medicalshops = Medicalshop::all();
            $MedicalshopDetails = [];



            foreach ($Medicalshops as $Medicalshop) {

                $favoriteStatus = DB::table('favouirtes_shop')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('medicalshop_id', $Medicalshop->id)
                    ->exists();

                $MedicalshopDetails[] = [
                    'id' => $Medicalshop->id,
                    'MedicalShop' => $Medicalshop->firstname,
                   // 'MedicalShopimage' => asset("shopImages/{$Medicalshop->shop_image}"),
                   'MedicalShopimage' => $Medicalshop->medicalshop_image ? asset("shopImages/images/{$Medicalshop->medicalshop_image}") : "null",
                    'mobileNo' => $Medicalshop->mobileNo,
                    'location' => $Medicalshop->location,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }

            return $this->sendResponse("MedicalShop", $MedicalshopDetails, '1', 'MedicalShop retrieved successfully');
        }
    }
    public function GetAllMedicalShops()
    {


        $Medicalshops = Medicalshop::all();
        $MedicalshopDetails = [];



        foreach ($Medicalshops as $Medicalshop) {
            $MedicalshopDetails[] = [
                'id' => $Medicalshop->id,
                'MedicalShop' => $Medicalshop->firstname,
               // 'MedicalShopimage' => asset("shopImages/{$Medicalshop->shop_image}"),
               'MedicalShopimage' => $Medicalshop->medicalshop_image ? asset("shopImages/images/{$Medicalshop->medicalshop_image}") : null,
                'mobileNo' => $Medicalshop->mobileNo,
                'location' => $Medicalshop->location,
            ];
        }

        return $this->sendResponse("MedicalShop", $MedicalshopDetails, '1', 'MedicalShop retrieved successfully');
    }


    public function addFavouirtesshop(Request $request)
    {

        $docterId = $request->doctor_id;
        $MediShop = $request->medicalshop_id;
        $Medicalshop = Medicalshop::find($MediShop);

        if (!$Medicalshop) {
            return response()->json(['error' => 'Medicalshop not found'], 404);
        }

        $existingFavourite = FavouriteShop::where('medicalshop_id', $MediShop)
            ->where('doctor_id', $docterId)
            ->first();


        if ($existingFavourite) {
            // Laboratory is already a favorite for the doctor
            return response()->json(['status' => false, 'message' => 'Medicalshop is already saved as a favorite.']);
        }

        $addfav = new FavouriteShop();
        $addfav->medicalshop_id = $MediShop;
        $addfav->doctor_id = $docterId;
        $addfav->save();


        return response()->json(['status' => true, 'message' => 'favourite added successfully .']);
    }

    public function removeFavouirtesshop(Request $request)
    {
        $docterId = $request->doctor_id;
        $MediShop = $request->medicalshop_id;
        $Medicalshop = Medicalshop::find($MediShop);

        if (!$Medicalshop) {
            return response()->json(['error' => 'Medicalshop not found'], 404);
        }
        $existingFavourite = FavouriteShop::where('medicalshop_id', $MediShop)
            ->where('doctor_id', $docterId)
            ->first();

        if ($existingFavourite) {
            FavouriteShop::where('doctor_id', $docterId)->where('medicalshop_id', $MediShop)->delete();
            return response()->json(['status' => true, 'message' => 'favourite Removed successfully .']);
        }
    }


    public function getFavMedicalshop()
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            $loggedInDoctorId = Auth::user()->id;

            $favoritemedicalshop = FavouriteShop::leftJoin('medicalshop', 'medicalshop.id', '=', 'favouirtes_shop.medicalshop_id')
                ->where('doctor_id', $loggedInDoctorId)
                ->select('medicalshop.*')
                ->get();

            $medicalshopDetails = [];

            foreach ($favoritemedicalshop as $medicalshop) {
                $medicalshopDetails[] = [
                    'id' => $medicalshop->id,
                    'UserId' => $medicalshop->UserId,
                    'Laboratory' => $medicalshop->firstname,
                    'Laboratoryimage' => asset("LabImages/{$medicalshop->shop_image}"),
                    'mobileNo' => $medicalshop->mobileNo,
                    'location' => $medicalshop->location,
                ];
            }

            return response()->json(['status' => true, 'message' => 'Favorite medicalshops retrieved successfully.', 'favoritemedicalshop' => $medicalshopDetails]);
        } else {
            return response()->json(['status' => false, 'message' => 'User not authenticated.']);
        }
    }

    public function searchmedicalshop(Request $request)
    {
            $loggedInDoctorId = Auth::user()->id;
            $Medicalshops = Medicalshop::query();
            $Medicalshops->where('firstname', 'like', '%' . $request->searchTerm . '%')
                ->orWhere('location', 'like', '%' . $request->searchTerm . '%');
            $MedicalshopDetails = [];
            foreach ($Medicalshops->get() as $Medicalshop) {
                $favoriteStatus = DB::table('favouirtes_shop')
                    ->where('doctor_id', $loggedInDoctorId)
                    ->where('medicalshop_id', $Medicalshop->id)
                    ->exists();
                $MedicalshopDetails[] = [
                    'id' => $Medicalshop->id,
                    'MedicalShop' => $Medicalshop->firstname,
                   // 'MedicalShopimage' => asset("shopImages/{$Medicalshop->shop_image}"),
                    'MedicalShopimage' => $Medicalshop->shop_image ? asset("shopImages/{$Medicalshop->shop_image}") : null,
                    'mobileNo' => $Medicalshop->mobileNo,
                    'location' => $Medicalshop->location,
                    'address' => $Medicalshop->address,
                    'pincode' =>$Medicalshop->pincode,
                    'favoriteStatus' => $favoriteStatus ? 1 : 0,
                ];
            }
            return $this->sendResponse("MedicalShop", $MedicalshopDetails, '1', 'MedicalShop retrieved successfully');
    }

    //get medicine order
    public function getUpcomingOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medical_shop_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }

        $userId = $request->input('medical_shop_id');

        $medicalShop = Medicalshop::where('UserId', $userId)->first();

        if (!$medicalShop) {
            return $this->sendError('MedicalShop Not Found', [], 400);
        }

        $tokenBookings = DB::table('token_booking')
            ->join('new_tokens', 'token_booking.new_token_id', '=', 'new_tokens.token_id')
            ->join('patient', 'token_booking.patient_id', '=', 'patient.id')
            ->join('medicalprescription', 'new_tokens.token_id', '=', 'medicalprescription.token_id')
            ->join('medicalshop', 'medicalshop.id', '=', 'medicalprescription.medical_shop_id')
            ->join('users', 'users.id', '=', 'medicalshop.UserId')
            ->select(
                'token_booking.*',
                'new_tokens.*',
                'patient.firstname as patient_firstname',
                'patient.gender as patient_gender',
                'patient.age as patient_age',
                'patient.mobileNo as patient_mobileNo',
                'medicalshop.UserId as medicalshop_userId',
                'patient.mediezy_patient_id'
            )
            ->distinct('medicalprescription.token_id')
            ->where('medicalshop.UserId', $userId)
            ->get();

        $medicineDetails = [];
        foreach ($tokenBookings as $booking) {
            $medicines = Medicine::where('token_id', $booking->token_id)->get();
            $medicineDetails[$booking->token_id] = $medicines;
        }

        $responseData = [
            'status' => true,
            'Medicine Order' => $tokenBookings->map(function ($booking) use ($medicineDetails) {
                return [
                    'PatientName' => $booking->patient_firstname,
                    'mediezy_patient_id' => $booking->mediezy_patient_id,
                    'patient_id' => $booking->patient_id,
                    'User_id' => $booking->BookedPerson_id,
                    'gender' => $booking->patient_gender,
                    'age' => $booking->patient_age,
                    'MobileNo' => $booking->patient_mobileNo,
                    'Appoinmentfor_id' => $booking->Appoinmentfor_id,
                    'date' => $booking->date,
                    'TokenNumber' => $booking->TokenNumber,
                    'TokenTime' => $booking->TokenTime,
                    'medicalshop_userId' => $booking->medicalshop_userId,
                    'medicines' => isset($medicineDetails[$booking->token_id]) ? $medicineDetails[$booking->token_id] : [],
                ];
            }),
            "message" => "medicine order details retrived successfully",
        ];

        return response()->json($responseData);
    }

    //get medicine order details page

    public function getUpcomingOrderDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medical_shop_id' => 'required|exists:users,id',
            'patient_id' => 'required|exists:patient,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }

        $userId = $request->input('medical_shop_id');
        $patientId = $request->input('patient_id');
        $medicalShop = Medicalshop::where('UserId', $userId)->first();
        $patient = Patient::find($patientId);

        if (!$medicalShop) {
            return $this->sendError('MedicalShop Not Found', [], 400);
        }
        if (!$patient) {
            return $this->sendError('Patient Not Found', [], 400);
        }
        $tokenBookings = DB::table('token_booking')
            ->join('new_tokens', 'token_booking.new_token_id', '=', 'new_tokens.token_id')
            ->join('patient', 'token_booking.patient_id', '=', 'patient.id')
            ->join('medicalprescription', 'new_tokens.token_id', '=', 'medicalprescription.token_id')
            ->join('medicalshop', 'medicalshop.id', '=', 'medicalprescription.medical_shop_id')
            ->join('users', 'users.id', '=', 'medicalshop.UserId')
            ->select(
                'token_booking.*',
                'new_tokens.*',
                'patient.firstname as patient_firstname',
                'patient.gender as patient_gender',
                'patient.age as patient_age',
                'patient.mobileNo as patient_mobileNo',
                'patient.mediezy_patient_id',
                'token_booking.prescription_image',
                'medicalshop.UserId as medicalshop_userId'
            )
            ->distinct('medicalprescription.token_id')
            ->where('medicalshop.UserId', $userId)
            ->where('patient.id', $patientId)
            ->get();

        $medicineDetails = [];
        foreach ($tokenBookings as $booking) {
            $medicines = Medicine::where('token_id', $booking->token_id)->get();
            $medicineDetails[$booking->token_id] = $medicines;
        }

        $responseData = [
            'status' => true,
            'Medicine Order Details' => $tokenBookings->map(function ($booking) use ($medicineDetails) {
                $symptoms = json_decode($booking->Appoinmentfor_id, true);
                return [
                    'PatientName' => $booking->patient_firstname,
                    'mediezy_patient_id' => $booking->mediezy_patient_id,
                    'patient_id' => $booking->patient_id,
                    'doctor_id' => $booking->doctor_id,
                    'clinic_id' => $booking->clinic_id,
                    'User_id' => $booking->BookedPerson_id,
                    'gender' => $booking->patient_gender,
                    'age' => $booking->patient_age,
                    'MobileNo' => $booking->patient_mobileNo,
                    'Appoinmentfor_id' => $booking->Appoinmentfor_id, null,
                    'date' => $booking->date,
                    'token_id' => $booking->new_token_id,
                    'TokenNumber' => $booking->TokenNumber,
                    'medicalshop_id' => $booking->medicalshop_id,
                    'main_symptoms' => MainSymptom::select('id', 'Mainsymptoms')->whereIn('id', $symptoms['Appoinmentfor1'])->get()->toArray(),
                    'other_symptoms' => Symtoms::select('id', 'symtoms')->whereIn('id', $symptoms['Appoinmentfor2'])->get()->toArray(),
                    'prescription_image' => $booking->prescription_image ? asset("bookings/attachments/{$booking->prescription_image}") : null,
                    'TokenTime' => $booking->TokenTime,
                    'medicalshop_userId' => $booking->medicalshop_userId,
                    'medicines' => isset($medicineDetails[$booking->token_id]) ? $medicineDetails[$booking->token_id] : [],
                ];
            }),
            "message" => "medicine order details retrived successfully",
        ];

        return response()->json($responseData);
    }

    // medicine order submit

    public function MedicineOrderSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medical_shop_id' => 'required|exists:users,id',
            'user_id' => 'required|exists:users,id',
            'patient_id' => 'required|exists:patient,id',
            'token_id' => 'required|exists:new_tokens,token_id',
            'doctor_id' => 'required|exists:docter,id',
            'clinic_id' => 'required|exists:clinics,clinic_id',

        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $medicineOrder = new MedicineOrders([
            'medical_shop_id' => $request->medical_shop_id,
            'user_id' => $request->user_id,
            'patient_id' => $request->patient_id,
            'token_id' => $request->token_id,
            'doctor_id' => $request->doctor_id,
            'clinic_id' => $request->clinic_id,

        ]);
        $medicineOrder->save();
        return response()->json(['status'=>'true','message' => 'Medicine order created successfully'], 200);
    }

    ///get medicine completed orders

    public function getMedicineCompleteOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medical_shop_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 400);
        }

        $userId = $request->input('medical_shop_id');

        $medicalShop = Medicalshop::where('UserId', $userId)->first();

        if (!$medicalShop) {
            return $this->sendError('MedicalShop Not Found', [], 400);
        }

        $tokenBookings = DB::table('token_booking')
            ->join('new_tokens', 'token_booking.new_token_id', '=', 'new_tokens.token_id')
            ->join('patient', 'token_booking.patient_id', '=', 'patient.id')
            ->join('medicalprescription', 'new_tokens.token_id', '=', 'medicalprescription.token_id')
            ->join('medicalshop', 'medicalshop.id', '=', 'medicalprescription.medical_shop_id',)
            ->join('users', 'users.id', '=', 'medicalshop.UserId')
            ->join('medicine_orders', 'medicine_orders.token_id', '=', 'new_tokens.token_id')
            ->select( 'token_booking.*',
                'new_tokens.*',
                'patient.firstname as patient_firstname',
                'patient.gender as patient_gender',
                'patient.age as patient_age',
                'patient.mobileNo as patient_mobileNo',
                'medicalshop.UserId as medicalshop_userId',
                'patient.mediezy_patient_id')
            ->distinct('medicalprescription.token_id')
            ->where('medicalshop.UserId', $userId)
         //   ->where('medicine_orders.order_details_status', 0)
            ->get();

        $medicineDetails = [];
        foreach ($tokenBookings as $booking) {
            $medicines = Medicine::where('token_id', $booking->token_id)->get();
            $medicineDetails[$booking->token_id] = $medicines;
        }
        $responseData = [
            'status' => true,
            'Completed Orders' => $tokenBookings->map(function ($booking) use ($medicineDetails) {
                return [
                    'PatientName' => $booking->patient_firstname,
                    'mediezy_patient_id' => $booking->mediezy_patient_id,
                    'patient_id' => $booking->patient_id,
                    'User_id' => $booking->BookedPerson_id,
                    'gender' => $booking->patient_gender,
                    'age' => $booking->patient_age,
                    'MobileNo' => $booking->patient_mobileNo,
                    'Appoinmentfor_id' => $booking->Appoinmentfor_id,
                    'date' => $booking->date,
                    'TokenNumber' => $booking->TokenNumber,
                    'TokenTime' => $booking->TokenTime,
                    'medicalshop_userId' => $booking->medicalshop_userId,
                    'medicines' => isset($medicineDetails[$booking->token_id]) ? $medicineDetails[$booking->token_id] : [],
                ];
            }),
            'message' => 'medicine completed appointments data retrived successfully',
        ];
        return response()->json($responseData);
    }
    ///medicalhop update

    public function medicalshopUpdate(Request $request )
    {
        $rules = [
            'user_id'     => 'required',
        ];
        $messages = [
           // 'firstname.required' => 'firstname is required',
        ];
        $validation = Validator::make($request->all(), $rules, $messages);
        if ($validation->fails()) {
            return response()->json(['status' => false, 'response' => $validation->errors()->first()]);
        }
        $userId = $request->input('medical_shop_id');
        try {

            $medicalshop = Medicalshop::where('UserId', $userId)->first();
            if (is_null($medicalshop)) {
                return $this->sendError('Medical shop not found.');
            }

            $user = User::find($medicalshop->UserId);
            $user->firstname = $request->firstname;
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('mobileNo')) {
                $user->mobileNo = $request->mobileNo;
            }
            $user->save();
            $medicalshop->firstname = $request->firstname;
            if ($request->has('email')) {
                $medicalshop->email = $request->email;
            }
            if ($request->has('mobileNo')) {
                $medicalshop->mobileNo = $request->mobileNo;
            }
            if ($request->has('location')) {
                $medicalshop->location = $request->location;
            }
            if ($request->has('pincode')) {
                $medicalshop->pincode = $request->pincode;
            }
            if ($request->has('address')) {
                $medicalshop->address = $request->address;
            }
            if ($request->hasFile('medicalshop_image')) {
                $imageFile = $request->file('medicalshop_image');
                if ($imageFile->isValid()) {
                    $imageName = $imageFile->getClientOriginalName();
                    $imageFile->move(public_path('shopImages/images'), $imageName);
                    $medicalshop->medicalshop_image = $imageName;
                }
            }
            $medicalshop->save();
            return response()->json(['success' => true,'UserId' => $user->id,'MedicalShop' => $medicalshop,'code' => '1','message' => 'Medical shop updated successfully.' ]);
        } catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'response' => 'Internal Server Error']);
        }
    }


    public function medicalshopEdit($UserId)
    {
        $medicalshop = Medicalshop::Where('userId',$UserId)->first();

        if(is_null($medicalshop))
        {
            return $this->senderror('medicalshop not found');
        }
        return $this->sendResponse("medicalshop", $medicalshop, '1', 'medicines Updated successfully');
    }
}
