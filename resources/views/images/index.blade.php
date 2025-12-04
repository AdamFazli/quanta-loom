<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="header-actions">
            <h1>Image Gallery</h1>
            <a href="{{ route('images.create') }}" class="btn">Upload New Image</a>
        </div>

        @if ($images->isEmpty())
            <p style="text-align: center; color: #666; padding: 40px;">
                No images uploaded yet. <a href="{{ route('images.create') }}" style="color: #667eea;">Upload your first
                    image</a>
            </p>
        @else
            <div class="image-grid">
                @foreach ($images as $image)
                    <div class="image-card">
                        <img src="{{ $image->url }}" alt="{{ $image->title }}">
                        <div class="image-card-body">
                            <h3>{{ $image->title }}</h3>
                            <p>{{ $image->description }}</p>
                            <p style="font-size: 12px; color: #999; margin-top: 10px;">
                                Uploaded: {{ $image->created_at->format('d M, Y') }}
                            </p>
                            <div class="image-card-actions" style="margin-top: 10px; display: flex; gap: 10px;">
                                <a href="{{ route('images.show', $image->id) }}" class="btn btn-small">View</a>
                                <form action="{{ route('images.destroy', $image->id) }}" method="POST" 
                                    style="display: inline;"
                                    onsubmit="return confirm('Are you sure you want to delete \"{{ $image->title }}\"? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>

</html>