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

    <h1>Articles</h1>

    @if ($errors->any())
    <div class="error-message">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ url('/add-article') }}" method="post" enctype="multipart/form-data">
        @csrf

        <label for="article_title">Article Title:</label>
        <input type="text" name="article_title" required>

        <label for="author_name">Author Name:</label>
        <input type="text" name="author_name" required>

        <label for="banner_image">Banner Image:</label>
        <input type="file" name="banner_image" accept="image/*" required>

        <label for="author_image">Author Image:</label>
        <input type="file" name="author_image" accept="image/*" required>

        <label for="category_id">Category:</label>
        <select name="category_id" required>
            @foreach ($article_categories as $category)
            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
            @endforeach
        </select>

        <label for="article_description">Article Description:</label>
        <textarea name="article_description" rows="4" required></textarea>

        <label for="main_banner_image">Main Banner Image:</label>
        <input type="file" name="main_banner_image" accept="image/*" required>

        <button type="submit">Submit</button>
    </form>


</body>

</html>