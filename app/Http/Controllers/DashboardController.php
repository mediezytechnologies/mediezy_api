<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Docter;
use App\Models\NewTokens;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public
    function index()
    {

        $doctor_count = Docter::count();

        $clinic_count = Clinic::count();

        $patient_count = Patient::count();

        $todays_booking_count = NewTokens::where('token_booking_status', 1)
            ->whereDate('token_scheduled_date', Carbon::today())
            ->count();

        return view('backend.dashboard', compact('doctor_count', 'clinic_count', 'patient_count', 'todays_booking_count'));
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
