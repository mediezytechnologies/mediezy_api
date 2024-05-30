<?php

namespace App\Http\Controllers\API;

use App\Helpers\UserLocationHelper;
use App\Http\Controllers\API\BaseController;
use App\Models\Category;
use App\Models\Docter;
use App\Models\NewTokens;
use App\Models\Specialize;
use App\Models\SelectedDocters;
use App\Services\DistanceMatrixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoriesController extends BaseController
{
    public function index()   //doctor
    {
        $categories = Category::where('type', 'doctor')->get();
        $responseCategories = $categories->map(function ($category) {
            $category->image = $category->image ? url("/img/{$category->image}") : null;
            return $category;
        });
        Log::info("Doctor categories retrieved successfully");

        return $this->sendResponse('categories', $responseCategories, '1', 'Doctor categories retrieved successfully.');
    }
    public function indexs()  //symptoms
    {
        $categories = Category::where('type', 'symptoms')->get();


        $responseCategories = $categories->map(function ($category) {
            $category->image = $category->image ? url("/img/{$category->image}") : null;
            return $category;
        });
        Log::info("Symptoms categories retrieved successfully");

        return $this->sendResponse('categories', $responseCategories, '1', 'Symptoms categories retrieved successfully.');
    }
    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'type' => 'required|in:doctor,medicine,symptoms',
            'description' => 'nullable|string',
            'docter_id' => [
                'sometimes:type,doctor',
                'exists:docter,id',
            ],
        ]);
        //alreadyexist
        $existingCategory = DB::table('categories')
            ->where('category_name', $request['category_name'])
            ->where('type', $request['type'])
            ->first();
        if ($existingCategory) {
            return $this->sendResponse("category", 'Exists', '0', 'Category name already exists');
        }
        // image uploading
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('img'), $imageName);
        }
        if ($request->type == 'doctor') {
            $docter = Docter::all();
        }
        $categoryId = DB::table('categories')->insertGetId([
            'category_name' => $request['category_name'],
            'type' => $request['type'],
            'description' => $request['description'],
            'image' => $imageName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $DoctersList = json_decode($request['doctorsList'], true);
        $selectedDocters = new SelectedDocters();
        $selectedDocters->cat_id = $categoryId;
        $selectedDocters->dataList = $DoctersList;
        $selectedDocters->save();
        Log::info("Category created successfully $selectedDocters");

        return $this->sendResponse('category', $selectedDocters, '1', 'Category created successfully.');
    }
    //category doctor
    public function show($id)
    {

        $authenticatedUserId = auth()->user()->id;
        $category = Category::find($id);

        if (!$category) {
            return $this->sendResponse('category', null, 404, 'Category not found.');
        }

        $selectedDoctors = DB::table('selecteddocters')
            ->join('categories', 'selecteddocters.cat_id', '=', 'categories.id')
            ->where('selecteddocters.cat_id', $category->id)
            ->select(
                'selecteddocters.dataList as selected_doctor_details',
                'categories.id as category_id',
                'categories.category_name',
                'categories.type'
            )
            ->first();

        if (!$selectedDoctors) {
            return response()->json(['status' => true, 'health_concern' => null, 'message' => 'Selected doctors not found.']);
        }
        $selectedDoctors->selected_doctor_details = json_decode($selectedDoctors->selected_doctor_details);
        $data = [];
        if ($category->type === 'doctor') {
            foreach ($selectedDoctors->selected_doctor_details as $key) {
                $doctor = Docter::select('docter.id', 'docter.UserId', 'docter.firstname','docter.lastname AS secondname', 'docter.location', 'docter.Services_at AS MainHospital', 'specialize.specialization AS Specialization', DB::raw("CONCAT('" . asset('/DocterImages/images') . "/', docter_image) AS DocterImage"))
                    ->join('specialize', 'specialize.id', '=', 'docter.specialization_id')->where('docter.id', $key->id)->first();

                if ($doctor) {
                    $check = DB::table('specialize')->where('id', 3)->first();
                    $key->UserId = $doctor->UserId;
                    $key->firstname = $doctor->firstname;
                    $key->secondname = $doctor->secondname;
                 //   distance_from_user => $distance,
                    $key->location = $doctor->location;
                    $key->MainHospital = $doctor->Services_at;
                    $key->docter_image = $doctor->docter_image_path;
                    $key->Specialization = $check->specialization;
                    unset($key->userid);
                    $clinics = Docter::join('doctor_clinic_relations', 'docter.id', '=', 'doctor_clinic_relations.doctor_id')
                        ->join('clinics', 'doctor_clinic_relations.clinic_id', '=', 'clinics.clinic_id')->where('docter.id', $key->id)->distinct()
                        ->get();
                    $clinicData = [];
                     //user location
                $current_location_data = UserLocationHelper::getUserCurrentLocation($authenticatedUserId);
                $user_latitude = $current_location_data ? $current_location_data->latitude : null;
                $user_longitude = $current_location_data ? $current_location_data->longitude : null;
                    foreach ($clinics as $clinic) {
                        // $doctor_latitude = $clinic['latitude'];
                        // $doctor_longitude = $clinic['longitude'];
                        $doctor_latitude = $clinic->latitude;
                        $doctor_longitude = $clinic->longitude;
                        $apiKey = config('services.google.api_key');
                        $service = new DistanceMatrixService($apiKey);
                        // $distance = $service->getDistance($user_latitude, $user_longitude, $doctor_latitude, $doctor_longitude);
                        $distance = null;
                        $doctor->distance_from_user = $distance;
                        $current_date = Carbon::now()->toDateString();
                        $current_time = Carbon::now()->toDateTimeString();


                        $total_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                            ->where('token_scheduled_date', $current_date)
                            ->where('doctor_id', $doctor->id)
                            ->count();

                        $available_token_count = NewTokens::where('clinic_id', $clinic->clinic_id)
                            ->where('token_scheduled_date', $current_date)
                            ->where('token_booking_status', NULL)
                            ->where('token_start_time', '>', $current_time)
                            ->where('doctor_id', $doctor->id)
                            ->count();

                        //schedule details

                        // $doctor_id =   $doctor_id = Docter::where('UserId', $userId)->pluck('id')->first();

                        $schedule_start_data = NewTokens::select('token_start_time', 'token_end_time')
                            ->where('doctor_id', $doctor->id)
                            ->where('clinic_id', $clinic->clinic_id)
                            ->where('token_scheduled_date', $current_date)
                            ->orderBy('token_start_time', 'ASC')
                            ->first();


                        $schedule_end_data = NewTokens::select('token_start_time', 'token_end_time')
                            ->where('doctor_id',$doctor->id)
                            ->where('clinic_id', $clinic->clinic_id)
                            ->where('token_scheduled_date', $current_date)
                            ->orderBy('token_start_time', 'DESC')
                            ->first();

                        if ($schedule_start_data && $schedule_end_data) {
                            $start_time = Carbon::parse($schedule_start_data->token_start_time)->format('h:i A');
                            $end_time = Carbon::parse($schedule_end_data->token_end_time)->format('h:i A');
                        } else {

                            $start_time = null;
                            $end_time = null;
                        }

                        //

                        $clinicData[] = [
                            'clinic_id' => $clinic->clinic_id,
                            'clinic_name' => $clinic->clinic_name,
                            'clinic_start_time' => $start_time,
                            'clinic_end_time' => $end_time,
                            'clinic_address' => $clinic->address,
                            'clinic_location' => $clinic->location,
                            'clinic_main_image' => isset($clinic->clinic_main_image) ? asset("clinic_images/{$clinic->clinic_main_image}") : null,
                            'clinic_description' => $clinic->clinic_description,
                            'total_token_Count' => $total_token_count,
                            'available_token_count' => $available_token_count,
                        ];
                    }
                    $doctor->clinics = $clinicData;
                    $data[] = $doctor;
                }
            }
            usort($data, function ($a, $b) {
                return $a['distance_from_user'] <=> $b['distance_from_user'];
            });
            if (empty($data)) {
                return response()->json(['status' => true, 'health_concern' => null, 'message' => 'No doctors found.']);
            }
            return response()->json(['status' => true, 'health_concern' => $data, 'message' => 'Health Concern By Doctor retrieved successfully.']);
        } else {
            return response()->json(['status' => true, 'health_concern' => null, 'message' => 'Category is not of type doctor.']);
        }
    }

    //symptoms category

    public function shows($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->sendResponse('category', null, 404, 'Category not found.');
        }
        $selectedDoctors = DB::table('selecteddocters')
            ->join('categories', 'selecteddocters.cat_id', '=', 'categories.id')
            ->where('selecteddocters.cat_id', $category->id)
            ->select(
                'selecteddocters.dataList as selected_doctor_details',
                'categories.id as category_id',
                'categories.category_name',
                'categories.type',
            )
            ->first();
        if (!$selectedDoctors) {
            return response()->json(['status' => true, 'data' => null, 'message' => 'Selected doctors not found.']);
        }
        $selectedDoctors->selected_doctor_details = json_decode($selectedDoctors->selected_doctor_details);
        if ($category->type === 'symptoms') {
            foreach ($selectedDoctors->selected_doctor_details as $key) {
                $doctor = Docter::select('UserId', 'id', 'firstname', 'lastname', 'location', 'Services_at', 'specialization_id', DB::raw("CONCAT('" . asset('/DocterImages/images') . "/', docter_image) AS docter_image_path"))->where('id', $key->id)->first();
                if ($doctor) {
                    $check = DB::table('specialize')->where('id', 3)->first();
                    logger()->info('userid value:', ['userid' => $key->userid]);
                    $key->UserId = $doctor->UserId;
                    $key->firstname = $doctor->firstname;
                    $key->secondname = $doctor->lastname;
                    $key->location = $doctor->location;
                    $key->MainHospital = $doctor->Services_at;
                    $key->docter_image = $doctor->docter_image_path;
                    $key->Specialization = $check->specialization;
                    unset($key->userid);
                    $clinicInfo = DB::table('docteravaliblity')->where('docter_id', $key->id)->get();

                    // $key->clincs = $clinicInfo;
                    $clinics = [];
                    foreach ($clinicInfo as $clinic) {
                        $clinics[] = [
                            'id' => $clinic->id,
                            // 'docter_id' => $clinic->docter_id,
                            'hospital_Name' => $clinic->hospital_Name,
                            'startingTime' => $clinic->startingTime,
                            'endingTime' => $clinic->endingTime,
                            'address' => $clinic->address,
                            'location' => $clinic->location
                        ];
                    }
                    $firstTokenCount = NewTokens::where('clinic_id',  $key->id)
                        ->whereDate('token_start_time', Carbon::today())
                        ->orderBy('id', 'asc')
                        ->count();
                    $key->clincs = $clinics;
                    //  $key->firstTokenCount = $firstTokenCount;
                    $key->first_clinic_name = count($clinics) > 0 ? $clinics[0]['hospital_Name'] : null;
                    $key->firstTokenCount = $firstTokenCount !== null ? $firstTokenCount : 0;
                }
            }

            $data = $selectedDoctors->selected_doctor_details;
            if (!$data) {
                return response()->json(['status' => true, 'data' => null, 'message' => 'Doctors not found.']);
            }

            $data = $selectedDoctors->selected_doctor_details;
            if (!$data) {
                return response()->json(['status' => true, 'data' => null, 'message' => 'Doctors not found.']);
            }
            return response()->json(['status' => true, 'data' => $data, 'message' => 'Doctors retrieved successfully.']);
        } else {
            return response()->json(['status' => true, 'data' => null, 'message' => 'Category is not of type Symptoms.']);
        }
    }
    //delete
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            Log::info("Category not found");

            return $this->sendResponse('category', null, 404, 'Category not found.');
        }
        $selectedDoctors = SelectedDocters::where('cat_id', $category->id)->first();
        if ($selectedDoctors) {
            $selectedDoctors->delete();
        }
        $category->delete();
        Log::info("Category deleted successfully.");

        return $this->sendResponse('category', null, '1', 'Category deleted successfully.');
    }
    //update
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:doctor,medicine',
            'description' => 'nullable|string',
            'docter_id' => [
                'sometimes',
                'required_if:type,doctor',
                'exists:docter,id',
            ],
        ]);
        $category = Category::find($id);
        if (!$category) {
            Log::info("Category not found.");

            return $this->sendResponse('category', null, 404, 'Category not found.');
        }
        $category->category_name = $request->input('category_name', $category->category_name);
        $category->type = $request->input('type', $category->type);
        $category->description = $request->input('description', $category->description);
        //$category->image = $request->input('image', $category->image);
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('img'), $imageName);
            $category->image = $imageName;
        }
        $category->save();
        $selectedDocters = SelectedDocters::where('cat_id', $category->id)->first();
        if ($selectedDocters) {
            $DoctersList = json_decode($request->input('doctorsList', '[]'), true);
            $selectedDocters->dataList = $DoctersList;
            $selectedDocters->save();
        }
        Log::info("Category updated successfully.");

        return $this->sendResponse('category', $category, 200, 'Category updated successfully.');
    }
    //edit
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $selectedDocters = DB::table('selecteddocters')
            ->where('cat_id', $id)
            ->first();
        $doctorData = [];
        if ($selectedDocters && property_exists($selectedDocters, 'dataList') && $selectedDocters->dataList) {
            $doctorIds = json_decode($selectedDocters->dataList, true);
            // Flatten the array
            $doctorIds = array_flatten($doctorIds);
            if (is_array($doctorIds) && count($doctorIds) > 0) {
                $doctors = DB::table('docter')
                    ->whereIn('id', $doctorIds)
                    ->get();
                foreach ($doctors as $doctor) {
                    $doctorData[] = [
                        'id' => $doctor->id,
                        'firstname' => $doctor->firstname,
                    ];
                }
            }
        }
        $imagePath = $category->image;
        return response()->json([
            'success' => true,
            'category' => $category,
            'selectedDocters' => [
                'id' => optional($selectedDocters)->id,
                'cat_id' => optional($selectedDocters)->cat_id,
                'dataList' => $doctorData,
                'created_at' => optional($selectedDocters)->created_at,
                'updated_at' => optional($selectedDocters)->updated_at,
            ],
            'imagePath' => $imagePath,
        ]);
    }
}
