
{{-- @extends('backend.layouts.app')
@section('content')
<div class="container">
    <h2>Clinic-Wise Doctor Consultation Fee</h2>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('clinic-consultations.store') }}">
        @csrf
        <div class="mb-3">
            <label for="doctor_id" class="form-label">Doctor</label>
            <select class="form-control" id="doctor_id" name="doctor_id" >
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="clinic_id" class="form-label">Clinic</label>
            <select class="form-control" id="clinic_id" name="clinic_id">
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->clinic_id }}">{{ $clinic->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="consultation_fee" class="form-label">Consultation Fee</label>
            <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
@endsection --}}




{{-- @extends('layouts.app') --}}
<style>

</style>
@extends('backend.layouts.app')
@section('content')
<div class="container">
    <h2>Clinic-Wise Doctor Consultation Fee</h2>
    {{-- @dump($doctors->toArray())
    @dump($clinics->toArray()) --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('clinic-consultations.clinicwiseConsultationFees') }}">
        @csrf
        {{-- <div class="mb-3">
            <label for="doctor_id" class="form-label">Doctor</label>
            <select class="form-control" id="doctor_id" name="doctor_id">
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="clinic_id" class="form-label">Clinic</label>
            <select class="form-control" id="clinic_id" name="clinic_id">
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->clinic_id }}">{{ $clinic->name }}</option>
                @endforeach
            </select>
        </div> --}}
        <label for="doctor_id">Select Doctor:</label>
        <select class="form-control" name="doctor_id" id="doctor_id" >
            @foreach($doctors as $doctor)
            <option value="{{ $doctor->id }}">{{ $doctor->firstname }}</option>
            @endforeach
        </select>

        <label for="clinic_id">Select Clinics:</label>
        <select class="form-control" name="clinic_id" id="clinic_id" >
            @foreach($clinics as $clinic)
            <option value="{{ $clinic->clinic_id }}">{{ $clinic->clinic_name }}</option>
            @endforeach
        </select>

        <div class="mb-3">
            <label for="consultation_fee" class="form-label">Consultation Fee</label>
            <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>
@endsection
