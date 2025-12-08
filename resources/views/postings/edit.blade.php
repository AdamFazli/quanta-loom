<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Posting</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ time() }}">
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
            <h1>Edit Posting</h1>
            <a href="{{ route('postings.show', $posting->id) }}" class="btn">Cancel</a>
        </div>

        <form action="{{ route('postings.update', $posting->id) }}" method="POST" enctype="multipart/form-data" id="editForm">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="{{ old('title', $posting->title) }}" required>
                @error('title')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4">{{ old('description', $posting->description) }}</textarea>
                @error('description')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            @if($posting->images->count() > 0)
                <div class="current-images">
                    <h3>Current Images ({{ $posting->images->count() }})</h3>
                    <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
                        Check the boxes of images you want to delete
                    </p>
                    <div class="current-images-grid">
                        @foreach ($posting->images as $image)
                            <div class="current-image-item" data-image-id="{{ $image->id }}">
                                <img src="{{ $image->url }}" alt="{{ $image->original_name }}">
                                <input 
                                    type="checkbox" 
                                    name="delete_images[]" 
                                    value="{{ $image->id }}" 
                                    class="delete-checkbox"
                                    onchange="toggleDeleteMarker(this)"
                                >
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="form-group">
                <label>Add New Images (JPG, PNG, GIF, WebP - Max 5MB each)</label>
                <div class="file-input-wrapper">
                    <label for="images" class="file-input-label" id="fileLabel">
                        <span>Click to select new images or drag and drop</span>
                        <br>
                        <small>You can select multiple images</small>
                    </label>
                    <input 
                        type="file" 
                        id="images" 
                        name="images[]" 
                        accept="image/jpeg,image/png,image/gif,image/webp" 
                        multiple
                    >
                </div>
                @error('images.*')
                    <span class="error">{{ $message }}</span>
                @enderror
                
                <div class="image-preview-container" id="imagePreview"></div>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit" class="btn">Update Posting</button>
            </div>
        </form>
    </div>

    <script>
        function toggleDeleteMarker(checkbox) {
            const imageItem = checkbox.closest('.current-image-item');
            if (checkbox.checked) {
                imageItem.classList.add('marked-for-deletion');
            } else {
                imageItem.classList.remove('marked-for-deletion');
            }
        }

        const fileInput = document.getElementById('images');
        const imagePreview = document.getElementById('imagePreview');
        const fileLabel = document.getElementById('fileLabel');
        const selectedFiles = [];

        fileInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }

                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert(`File ${file.name} is not a valid image type.`);
                    return;
                }

                if (!selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                    displayPreview(file);
                }
            });

            updateFileLabel();
        });

        function displayPreview(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'image-preview';
                previewDiv.dataset.fileName = file.name;
                previewDiv.dataset.fileSize = file.size;
                
                previewDiv.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="remove-btn" onclick="removeImage(this, '${file.name}', ${file.size})">×</button>
                `;
                
                imagePreview.appendChild(previewDiv);
            };
            
            reader.readAsDataURL(file);
        }

        function removeImage(button, fileName, fileSize) {
            button.closest('.image-preview').remove();
            
            const index = selectedFiles.findIndex(f => f.name === fileName && f.size === fileSize);
            if (index > -1) {
                selectedFiles.splice(index, 1);
            }
            
            updateFileInput();
            updateFileLabel();
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
        }

        function updateFileLabel() {
            const count = selectedFiles.length;
            if (count > 0) {
                fileLabel.classList.add('has-files');
                fileLabel.innerHTML = `
                    <span><strong>${count}</strong> new image${count !== 1 ? 's' : ''} selected</span>
                    <br>
                    <small>Click to add more images</small>
                `;
            } else {
                fileLabel.classList.remove('has-files');
                fileLabel.innerHTML = `
                    <span>Click to select new images or drag and drop</span>
                    <br>
                    <small>You can select multiple images</small>
                `;
            }
        }

        const fileInputWrapper = document.querySelector('.file-input-wrapper');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileInputWrapper.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            fileInputWrapper.addEventListener(eventName, () => {
                fileInputWrapper.style.borderColor = '#007bff';
                fileInputWrapper.style.backgroundColor = '#f8f9fa';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileInputWrapper.addEventListener(eventName, () => {
                fileInputWrapper.style.borderColor = '#ddd';
                fileInputWrapper.style.backgroundColor = 'transparent';
            }, false);
        });

        fileInputWrapper.addEventListener('drop', function(e) {
            const files = Array.from(e.dataTransfer.files).filter(file => 
                file.type.startsWith('image/')
            );
            
            files.forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }

                if (!selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                    displayPreview(file);
                }
            });
            
            updateFileInput();
            updateFileLabel();
        }, false);
    </script>
</body>
</html>