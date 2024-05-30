{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Flag</title>
    <style>
        /* Your CSS styles here */
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Flag</h2>
        <form action="{{ route('flag.update', $flag->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <!-- Add a hidden input field to send the flag ID -->
            <input type="hidden" name="_method" value="PUT">
            <div class="form-group">
                <label for="banner_title">Banner Title:</label>
                <input type="text" id="banner_title" name="banner_title" value="{{ $flag->banner_title }}" required>
            </div>
            <div class="form-group">
                <label for="banner_type">Banner Type:</label>
                <select id="banner_type" name="banner_type" required>
                    <option value="1" {{ $flag->banner_type == 1 ? 'selected' : '' }}>Type 1</option>
                    <option value="2" {{ $flag->banner_type == 2 ? 'selected' : '' }}>Type 2</option>
                    <option value="3" {{ $flag->banner_type == 3 ? 'selected' : '' }}>Type 3</option>
                </select>
            </div>
            <div class="form-group">
                <label for="banner_image">Banner Image:</label>
                <!-- Display the current banner image -->
                <img src="{{ asset('img/' . $flag->banner_image) }}" alt="Current Banner Image"><br>
                <label for="new_banner_image">Upload New Image:</label>
                <input type="file" id="new_banner_image" name="banner_image" accept="image/*">
            </div>
            <button type="submit" class="btn-submit">Update</button>
        </form>
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
    </div>
</body>
</html> --}}
{{-- </html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners Update Form</title>
    <style>
        /* CSS Styles */
        /* Basic form styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .banners-form {
            max-width: 400px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="file"] {
            padding: 0.5rem;
        }

        .error-message {
            color: red;
        }

        button {
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<body>
    <form method="POST" action="{{ route('flag.update', ['banner_id' => $flag->banner_id]) }}" enctype="multipart/form-data" class="banners-form">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="flag_title">Banner Title</label>
            <input type="text" name="banner_title" id="flag_title" class="form-control" value="{{ old('banner_title', $userbanner->banner_titlee) }}" required>
            @error('flag_title')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="banner_type">Banner Type</label>
            <select name="banner_type" id="banner_type" class="form-control" required>
                <!-- Your options here -->
            </select>
            @error('banner_type')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        {{-- <div class="form-group">
            <label for="flag_type">Banner Type</label>
            <select name="flag_type" id="flag_type" class="form-control" required>
                <!-- Loop through the available types and create an option for each -->
                @foreach($types as $types)
                    <option value="{{ $types }}">{{ $types }}</option>
                @endforeach
            </select>
            @error('flag_type')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div> --}}

{{-- 
        <div class="form-group">
            <label for="flag_image">Image Upload</label>
            <input type="file" name="banner_image" id="banner_image" class="form-control-file">
            <img src="{{ asset('img/' . $userbanner->banner_image) }}" alt="{{ $userbanner->banner_title }}" class="flag-image" style="max-width: 150px; max-height: 100px;">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</body>
</html> --}}



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>banners Update Form</title>
    <style>
        /* CSS Styles */
        /* Basic form styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .banners-form {
            max-width: 400px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 0.5rem;
        }

        input[type="text"],
        select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="file"] {
            padding: 0.5rem;
        }

        button {
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>




    @foreach($banners as $banner)
    <form method="POST" action=" {{ route('UserBanner.update', ['id' => $banner->id]) }}" enctype="multipart/form-data">

        @csrf
        @method('get')

        <div class="form-group">
            <label for="banner_title">Banner Title</label>
            <input type="text" name="flag_title" id="flag_title" class="form-control" value="{{ $banner->banner_title }}" >
        </div>

        <div class="form-group">
            <label for="banner_type">Banner Type</label>
            <select name="banner_type" id="banner_type" class="form-control">
                <!-- Your options here -->
            </select>
        </div>

        <div class="form-group">
            <label for="flag_image">Image Upload</label>
            <input type="file" name="banner_image" id="banner_image" class="form-control-file">
            <img src="{{ asset('img/' . $banner->banner_image) }}" alt="{{ $banner->banner_title }}" class="banner-image">
        </div>
        <div class="form-group">

                <a href="{{ route('Banner_update', ['id' => $item->id]) }}"
                    class="edit-btn">Update</a>

                <button type="submit" class="delete-btn">Update</button>

        </div>
        {{-- <button type="submit" class="btn btn-primary">Update</button> --}}
    </form>
@endforeach





</body>
</html>
