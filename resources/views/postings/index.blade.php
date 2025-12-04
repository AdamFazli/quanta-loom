<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postings</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .posting-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .posting-header {
            margin-bottom: 15px;
        }
        .posting-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .posting-header p {
            color: #666;
            margin: 5px 0;
        }
        .posting-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .posting-image {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1;
            border: 2px solid #ddd;
        }
        .posting-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .posting-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .posting-meta {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
    </style>
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
            <h1>Postings</h1>
            <a href="{{ route('postings.create') }}" class="btn">Create New Posting</a>
        </div>

        @if ($postings->isEmpty())
            <p style="text-align: center; color: #666; padding: 40px;">
                No postings yet. <a href="{{ route('postings.create') }}" style="color: #667eea;">Create your first posting</a>
            </p>
        @else
            @foreach ($postings as $posting)
                <div class="posting-card">
                    <div class="posting-header">
                        <h2>{{ $posting->title }}</h2>
                        @if($posting->description)
                            <p>{{ $posting->description }}</p>
                        @endif
                    </div>

                    @if($posting->images->count() > 0)
                        <div class="posting-images">
                            @foreach ($posting->images as $image)
                                <div class="posting-image">
                                    <img src="{{ $image->url }}" alt="{{ $image->original_name }}">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="posting-meta">
                        {{ $posting->images->count() }} image(s) • Posted: {{ $posting->created_at->format('d M, Y') }}
                    </div>

                    <div class="posting-actions">
                        <a href="{{ route('postings.show', $posting->id) }}" class="btn btn-small">View</a>
                        <form action="{{ route('postings.destroy', $posting->id) }}" method="POST" 
                            style="display: inline;"
                            onsubmit="return confirm('Are you sure you want to delete this posting? All images will be deleted. This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>