@extends('backend.layouts.app')

@section('content')

<div class="content-wrapper" style="padding-left: 100px; padding-right: 20px;">
    <h2 class="text-bold mt-4">Manage Doctor-Clinic Relations</h2>

    <form action="{{ route('saveDoctorClinicRelations') }}" method="post" class="mb-4">
        @csrf

        <!-- Assign doctors to clinics -->
        <label for="doctor_id">Select Doctor:</label>
        <select class="form-control" name="doctor_id" id="doctor_id">
            @foreach($doctor_data as $doctor)
            <option value="{{ $doctor->id }}">{{ $doctor->firstname }}</option>
            @endforeach
        </select>

        <label for="clinic_ids">Select Clinics:</label>
        <select class="form-control" name="clinic_ids[]" id="clinic_ids" multiple>
            @foreach($clinic_data as $clinic)
            <option value="{{ $clinic->clinic_id }}">{{ $clinic->clinic_name }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary mt-3">Assign Doctor to Clinic</button>
    </form>

    <!-- Assign clinics to doctors -->
    <form action="{{ route('saveClinicDoctorRelations') }}" method="post" class="mb-4">
        @csrf

        <label for="clinic_id">Select Clinic:</label>
        <select class="form-control" name="clinic_id" id="clinic_id">
            @foreach($clinic_data as $clinic)
            <option value="{{ $clinic->clinic_id }}">{{ $clinic->clinic_name }}</option>
            @endforeach
        </select>

        <label for="doctor_ids">Select Doctors:</label>
        <select class="form-control" name="doctor_ids[]" id="doctor_ids" multiple>
            @foreach($doctor_data as $doctor)
            <option value="{{ $doctor->id }}">{{ $doctor->firstname }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary mt-3">Assign Clinic to Doctor</button>
    </form>
</div>

@endsection
