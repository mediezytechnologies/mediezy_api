<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\DocterAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClinicManagementController extends Controller
{
    public function index()
    {


        return view('clinics.create_clinic_form');
    }

    public function getAllClinics()
    {
        $all_clinics = Clinic::select(
            'clinic_name',
            'clinic_description',
            'address',
            'location',
            'clinic_main_image',
            'first_banner',
            'second_banner',
            'third_banner',


        )
            ->get();

        return view('clinics.all_clinics_list', compact('all_clinics'));
    }


    public function addClinic(Request $request)
    {
        try {
            $request->validate([
                'clinic_name' => 'required',
                'clinic_description' => 'required',
                'address' => 'required',
                'location' => 'required',
                'clinic_main_image' => 'required|image|mimes:jpeg,png,jpg,gif',
                'clinic_start_time' => 'sometimes',
                'clinic_end_time' => 'sometimes',
                'first_banner' => 'required|image|mimes:jpeg,png,jpg,gif',
                'second_banner' => 'required|image|mimes:jpeg,png,jpg,gif',
                'third_banner' => 'required|image|mimes:jpeg,png,jpg,gif',
            ]);


            $data = $request->all();
            $clinic = new Clinic($data);
            $timestamp = now()->timestamp;

            $clinic_main_image = $request->file('clinic_main_image');
            $clinic_main_image_path = 'clinic_images/' . $timestamp . '_' . $clinic_main_image->getClientOriginalName();
            $clinic_main_image->move(public_path('clinic_images'), $clinic_main_image_path);
            $clinic->clinic_main_image = $clinic_main_image_path;

            $first_banner = $request->file('first_banner');
            $first_banner_path = 'clinic_images/' . $timestamp . '_' . $first_banner->getClientOriginalName();
            $first_banner->move(public_path('clinic_images'), $first_banner_path);
            $clinic->first_banner = $first_banner_path;

            $second_banner = $request->file('second_banner');
            $second_banner_path = 'clinic_images/' . $timestamp . '_' . $second_banner->getClientOriginalName();
            $second_banner->move(public_path('clinic_images'), $second_banner_path);
            $clinic->second_banner = $second_banner_path;

            $third_banner = $request->file('third_banner');
            $third_banner_path = 'clinic_images/' . $timestamp . '_' . $third_banner->getClientOriginalName();
            $third_banner->move(public_path('clinic_images'), $third_banner_path);
            $clinic->third_banner = $third_banner_path;

            $clinic->save();

            // $new_clinic_id =   $clinic->clinic_id;



            //// doctor avalilabity data
            // $doctor_availability = new DocterAvailability(); 
            // $doctor_availability->doctor_id = $request->doctor_id ?? null; 
            // $doctor_availability->hospital_name = $request->clinic_name ?? null; 
            // $doctor_availability->starting_time = $request->clinic_start_time ?? null;
            // $doctor_availability->ending_time = $request->clinic_end_time ?? null;
            // $doctor_availability->address = $request->address ?? null;
            // $doctor_availability->location = $request->location ?? null;
            // $doctor_availability->clinic_description = $request->clinic_description ?? null;
            // $doctor_availability->new_clinic_id = $new_clinic_id ?? null;
            // $doctor_availability->save();
            

            return redirect()->route('clinics')->with('success', 'Clinic added successfully!');
        } catch (\Exception $e) {

            Log::error('Error occurred: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error occurred while adding clinic.');
        }
    }
}
