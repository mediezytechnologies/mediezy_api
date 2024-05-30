<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Relations</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        h2 {
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 8px;
        }

        select {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        form:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>

    <h2>Manage Doctor-Clinic Relations</h2>

    <form action="{{ route('saveDoctorClinicRelations') }}" method="post">
        @csrf

        <!-- Assign doctors to clinics -->
        <label for="doctor_id">Select Doctor:</label>
        <select name="doctor_id" id="doctor_id">
            @foreach($doctor_data as $doctor)
            <option value="{{ $doctor->id }}">{{ $doctor->firstname }}</option>
            @endforeach
        </select>

        <label for="clinic_ids">Select Clinics:</label>
        <select name="clinic_ids[]" id="clinic_ids" multiple>
            @foreach($clinic_data as $clinic)
            <option value="{{ $clinic->clinic_id }}">{{ $clinic->clinic_name }}</option>
            @endforeach
        </select>

        <button type="submit">Assign Doctor to Clinic</button>
    </form>

    <!-- Assign clinics to doctors -->
    <form action="{{ route('saveClinicDoctorRelations') }}" method="post">
        @csrf

        <label for="clinic_id">Select Clinic:</label>
        <select name="clinic_id" id="clinic_id">
            @foreach($clinic_data as $clinic)
            <option value="{{ $clinic->clinic_id }}">{{ $clinic->clinic_name }}</option>
            @endforeach
        </select>

        <label for="doctor_ids">Select Doctors:</label>
        <select name="doctor_ids[]" id="doctor_ids" multiple>
            @foreach($doctor_data as $doctor)
            <option value="{{ $doctor->id }}">{{ $doctor->firstname }}</option>
            @endforeach
        </select>

        <button type="submit">Assign Clinic to Doctor</button>
    </form>

</body>

</html>
