<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $posting->title }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .posting-header {
            margin-bottom: 30px;
        }
        .posting-header h1 {
            margin-bottom: 10px;
        }
        .posting-description {
            color: #666;
            font-size: 16px;
            margin: 15px 0;
            line-height: 1.6;
        }
        .images-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .image-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #ddd;
            background: #f8f9fa;
        }
        .image-item img {
            width: 100%;
            height: auto;
            display: block;
        }
        .image-item:hover {
            transform: scale(1.02);
            transition: transform 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .image-delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 10;
        }
        .image-item:hover .image-delete-btn {
            opacity: 1;
        }
        .image-delete-btn:hover {
            background: rgba(255, 0, 0, 1);
            transform: scale(1.1);
        }
        .posting-meta {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        .posting-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .images-gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }
            .image-delete-btn {
                opacity: 1;
            }
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
                        <form action="{{ route('postings.images.destroy.web', ['postingId' => $posting->id, 'imageId' => $image->id]) }}" 
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