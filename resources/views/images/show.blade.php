<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $image->title }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <h1>{{ $image->title }}</h1>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('images.index') }}" class="btn">Back to Gallery</a>
                <form action="{{ route('images.destroy', $image->id) }}" method="POST" 
                    style="display: inline;" 
                    onsubmit="return confirm('Are you sure you want to delete \"{{ $image->title }}\"? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Image</button>
                </form>
            </div>
        </div>

        <div class="image-detail">
            <img src="{{ $image->url }}" 
                alt="{{ $image->title }}"
                style="max-width: 100%; height: auto; border-radius: 8px;">
            
            <div style="margin-top: 20px;">
                <h2>Description</h2>
                <p>{{ $image->description }}</p>
                
                <hr>
                
                <h3>Details</h3>
                <ul>
                    <li><strong>Original Name:</strong> {{ $image->original_name }}</li>
                    <li><strong>Size:</strong> {{ round($image->size / 1024, 2) }} KB</li>
                    <li><strong>Type:</strong> {{ $image->mime_type }}</li>
                    <li><strong>Uploaded:</strong> {{ $image->created_at->format('d M, Y H:i:s') }}</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

