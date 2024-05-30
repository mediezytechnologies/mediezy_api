@extends('backend.layouts.app')

@section('content')
<div class="content-wrapper" style="padding-left: 100px; padding-right: 20px;">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Manage Doctor-Clinic Specializations</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Form to assign clinics to specializations -->
        <div class="box">
            <div class="box-header with-border">
                <h6 class="box-title">Assign Clinics to Specializations</h6>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <form action="{{ route('saveDoctorClinicSpecializations') }}" method="post">
                    @csrf

                    <div class="form-group">
                        <label for="special_id">Select Specializations:</label>
                        <select name="special_id" id="special_id" class="form-control" required>
                            @foreach($specilization_data as $specialization)
                            <option value="{{ $specialization->id }}">{{ $specialization->specialization }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="clinic_ids">Select Clinics:</label>
                        <select name="clinic_ids[]" id="clinic_ids" class="form-control" multiple required>
                            @foreach($clinic_data as $clinic)
                            <option value="{{ $clinic->clinic_id }}">{{ $clinic->clinic_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mt-3 mb-3">Assign Clinic to Specializations</button>
                </form>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->

        <!-- Form to assign doctors to specializations -->
        <div class="box">
            <div class="box-header with-border">
                <h6 class="box-title">Assign Doctors to Specializations</h6>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <form action="{{ route('saveClinicDoctorSpecializations') }}" method="post">
                    @csrf

                    <div class="form-group">
                        <label for="specialization_id_doctor">Select Specializations:</label>
                        <select name="specialization_id_doctor" id="specialization_id_doctor" class="form-control" required>
                            @foreach($specilization_data as $specialization)
                            <option value="{{ $specialization->id }}">{{ $specialization->specialization }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="doctor_ids">Select Doctors:</label>
                        <select name="doctor_ids[]" id="doctor_ids" class="form-control" multiple>
                            @foreach($doctor_data as $doctor)
                            <option value="{{ $doctor->id }}">{{ $doctor->firstname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm mt-3 mb-3">Assign Doctor to Specializations</button>
                </form>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
@endsection
