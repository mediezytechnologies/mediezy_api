<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f5f5f5;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        button {
            background-color: #4caf50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <h1>Clinics</h1>

    @if ($errors->any())
    <div class="error-message">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ url('/add-clinic') }}" method="post" enctype="multipart/form-data">
    @csrf

    <label for="clinic_name">Clinic Name:</label>
    <input type="text" id="clinic_name" name="clinic_name" value="{{ old('clinic_name') }}" required>

    <label for="clinic_description">Clinic Description:</label>
    <textarea id="clinic_description" name="clinic_description" rows="4" required>{{ old('clinic_description') }}</textarea>

    <label for="address">Address:</label>
    <input type="text" id="address" name="address" value="{{ old('address') }}" required>

    <label for="location">Location:</label>
    <input type="text" id="location" name="location" value="{{ old('location') }}" required>

    <label for="clinic_main_image">Clinic Main Image:</label>
    <input type="file" id="clinic_main_image" name="clinic_main_image" accept="image/*" required>

    <label for="clinic_start_time">Clinic Start Time:</label>
    <input type="datetime-local" id="clinic_start_time" name="clinic_start_time" value="{{ old('clinic_start_time') }}">

    <label for="clinic_end_time">Clinic End Time:</label>
    <input type="datetime-local" id="clinic_end_time" name="clinic_end_time" value="{{ old('clinic_end_time') }}">

    <label for="first_banner">First Banner:</label>
    <input type="file" id="first_banner" name="first_banner" accept="image/*" required>

    <label for="second_banner">Second Banner:</label>
    <input type="file" id="second_banner" name="second_banner" accept="image/*" required>

    <label for="third_banner">Third Banner:</label>
    <input type="file" id="third_banner" name="third_banner" accept="image/*" required>

    <button type="submit">Submit</button>
</form>



</body>

</html>