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
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
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
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Flag</h2>
        <form action="{{ route('UserBanner.add') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="banner_title">Banner Title:</label>
                <input type="text" id="banner_title" name="banner_title" required>
            </div>
            <div class="form-group">
                <label for="banner_type">Banner Type:</label>
                <select id="banner_type" name="banner_type" required>
                    <option value="1">Type 1</option>
                    <option value="2">Type 2</option>
                    <option value="3">Type 3</option>
                </select>
            </div>
            <div class="form-group">
                <label for="banner_image">Banner Image:</label>
                <input type="file" id="banner_image" name="banner_image" accept="image/*" required>
            </div>
            <button type="submit" class="btn-submit">Submit</button>
        </form>
        @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

    </div>
</body>
</html>
