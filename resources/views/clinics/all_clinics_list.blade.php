<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinics List</title>

    <!-- Include Tailwind CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">

    <style>
        body {
            background: #f0f4f8;
            /* Set a background color for the body */
            font-family: 'Arial', sans-serif;
            /* Specify a font for better readability */
            margin: 0;
            /* Remove default margin */
            padding: 1rem;
            /* Add some padding for content */
        }

        h1 {
            color: #1a202c;
            /* Set heading color */
            text-align: center;
            /* Center align the heading */
            margin-bottom: 1.5rem;
            /* Add margin below the heading */
        }

        p {
            color: #48bb78;
            /* Set success message color */
            margin-bottom: 1rem;
            /* Add margin below the success message */
        }

        table {
            width: 100%;
            /* Make the table take full width of the container */
            border-collapse: collapse;
            /* Remove the spacing between table cells */
            margin-top: 1.5rem;
            /* Add margin above the table */
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            /* Add border to table cells */
            padding: 0.75rem;
            /* Add padding to table cells for better spacing */
            text-align: left;
            /* Align text to the left in cells */
        }

        th {
            background-color: #2d3748;
            /* Set header background color */
            color: #ffffff;
            /* Set header text color */
        }

        img {
            max-width: 100%;
            /* Make images responsive within their containers */
            height: auto;
            /* Maintain image aspect ratio */
        }
    </style>
</head>

<body>

    <h1>Clinics List</h1>

    @if(session('success'))
    <p>{{ session('success') }}</p>
    @endif
    <a href="{{ route('add-clinic-form') }}" class="add-article-button" style="text-decoration: none; padding: 10px 15px; background-color: #4CAF50; 
     color: white; border-radius: 5px; margin-bottom: 15px; display: inline-block; float: right;">Add Clinic</a>
    <table>
        <thead>
            <tr>
                <th>Clinic Name</th>
                <th>Clinic Description</th>
                <th>Address</th>
                <th>Location</th>
                <th>Clinic Main Image</th>
                <th>First Banner</th>
                <th>Second Banner</th>
                <th>Third Banner</th>
            </tr>

        </thead>
        <tbody>
            @foreach($all_clinics as $clinic)
            <tr>
                <td>{{ $clinic->clinic_name }}</td>
                <td>{{ $clinic->clinic_description }}</td>
                <td>{{ $clinic->address }}</td>
                <td>{{ $clinic->location }}</td>
                <td><img src="{{ asset($clinic->clinic_main_image) }}" alt="Clinic Main Image" style="max-width: 100px; max-height: 100px;"></td>
                <td><img src="{{ asset($clinic->first_banner) }}" alt="First Banner" style="max-width: 100px; max-height: 100px;"></td>
                <td><img src="{{ asset($clinic->second_banner) }}" alt="Second Banner" style="max-width: 100px; max-height: 100px;"></td>
                <td><img src="{{ asset($clinic->third_banner) }}" alt="Third Banner" style="max-width: 100px; max-height: 100px;"></td>
            </tr>
            @endforeach
        </tbody>




    </table>


</body>

</html>