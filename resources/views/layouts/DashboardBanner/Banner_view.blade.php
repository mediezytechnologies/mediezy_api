<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Flag</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .btn-submit {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .alert {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 3px;
            color: #155724;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
        }
        img {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Flag</h2>
        <!-- Your form code here -->
        <form action="{{ route('UserBanner.listview') }}" method="post" enctype="multipart/form-data">
            @csrf
            <!-- Form fields -->
        </form>
        
        <!-- Success message -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Table for displaying data -->
        <h2>Flag Table</h2>
        <table>
            <thead>
                <tr>
                    <th>Banner Title</th>
                    <th>Banner Type</th>
                    <th>Banner Image</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Loop through flags and display each row -->
                @foreach($Banners as $Banner)
                {{-- <tr>
                    <td>{{ $flag->banner_title }}</td>
                    <td>{{ $flag->banner_type }}</td>
                    <td><img src="{{ asset('img/' . $flag->banner_image) }}" alt="Banner Image"></td>
                </tr> --}}
                <tr>
                    <td>{{ $Banner->banner_title }}</td>
                    <td>{{ $Banner->banner_type }}</td>
                    <td><img src="{{ asset('img/' . $Banner->banner_image) }}" alt="Banner Image"></td>
                    <td>
                        <!-- Update button -->
                        <a href="" class="btn-update">Update</a>
                        
                        <!-- Delete button -->
                        {{-- <form action="" method="post" class="form-delete">
                            @csrf
                            @method('DELETE') --}}
                            <a href="" class="btn-update">Delete</a>                        </form>
                    </td>
                </tr>
                
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
