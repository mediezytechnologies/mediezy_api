<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Article List</title>

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

    <h1>Article List</h1>

    @if(session('success'))
    <p>{{ session('success') }}</p>
    @endif
    <a href="{{ route('add-article-form') }}" class="add-article-button" style="text-decoration: none; padding: 10px 15px; background-color: #4CAF50; 
     color: white; border-radius: 5px; margin-bottom: 15px; display: inline-block; float: right;">Add Article</a>
    <table>
        <thead>
            <tr>
                <th>Article ID</th>
                <th>Main Banner</th>
                <th>Banner Image</th>
                <th>Article Title</th>
                <th>Author Name</th>
                <th>Author Image</th>
                <!-- <th>Category Name</th> -->
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($all_articles as $article)
            <tr>
                <td>{{ $article->article_id }}</td>
                <td><img src="{{ asset($article->main_banner) }}" alt="Main Banner" style="max-width: 100px; max-height: 100px;"></td>
                <td><img src="{{ asset($article->banner_image) }}" alt="Banner Image" style="max-width: 100px; max-height: 100px;"></td>
                <td>{{ $article->article_title }}</td>
                <td>{{ $article->author_name }}</td>
                <td><img src="{{ asset($article->author_image) }}" alt="Author Image" style="max-width: 50px; max-height: 50px;"></td>
                <!-- <td>{{ $article->category_name }}</td> -->
                <td>{{ $article->created_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>


</body>

</html>