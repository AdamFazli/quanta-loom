<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $posting->title }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
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
            <div class="posting-header">
                <h1>{{ $posting->title }}</h1>
                @if($posting->description)
                    <p class="posting-description">{{ $posting->description }}</p>
                @endif
            </div>
            <div class="posting-actions">
                <a href="{{ route('postings.index') }}" class="btn">Back to Postings</a>
                <a href="{{ route('postings.edit', $posting->id) }}" class="btn" style="background: #28a745;">Edit Posting</a>
                <form action="{{ route('postings.destroy', $posting->id) }}" method="POST" 
                    style="display: inline;" 
                    onsubmit="return confirm('Are you sure you want to delete this posting? All {{ $posting->images->count() }} image(s) will be deleted. This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Posting</button>
                </form>
            </div>
        </div>

        @if($posting->images->count() > 0)
            <div class="images-gallery">
                @foreach ($posting->images as $image)
                    <div class="image-item">
                        <img src="{{ $image->url }}" 
                            alt="{{ $image->original_name }}"
                            loading="lazy">
                        <form action="{{ route('postings.images.destroy.web', ['posting' => $posting->id, 'image' => $image->id]) }}" 
                            method="POST" 
                            style="display: inline;"
                            onsubmit="return confirm('Are you sure you want to delete this image? This action cannot be undone.');"
                            class="image-delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="image-delete-btn" title="Delete this image">×</button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="posting-meta">
                <p><strong>{{ $posting->images->count() }}</strong> image(s) in this posting</p>
                <p>Posted on: {{ $posting->created_at->format('d M, Y H:i:s') }}</p>
                @if($posting->updated_at != $posting->created_at)
                    <p>Last updated: {{ $posting->updated_at->format('d M, Y H:i:s') }}</p>
                @endif
            </div>
        @else
            <div style="text-align: center; padding: 40px; color: #666;">
                <p>This posting has no images.</p>
            </div>
        @endif
    </div>
</body>
</html>