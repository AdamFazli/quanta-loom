<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
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
            <h1>Create New Posting</h1>
            <a href="{{ route('postings.index') }}" class="btn">Back to Postings</a>
        </div>

        <form action="{{ route('postings.store') }}" method="POST" enctype="multipart/form-data" id="imageForm">
            @csrf
            
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required>
                @error('title')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
                @error('description')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Image File(s) * (JPG, PNG, GIF, WebP - Max 5MB each)</label>
                <div class="file-input-wrapper">
                    <label for="images" class="file-input-label" id="fileLabel">
                        <span>Click to select images or drag and drop</span>
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
                @error('images')
                    <span class="error">{{ $message }}</span>
                @enderror
                
                <div class="image-preview-container" id="imagePreview"></div>
            </div>

            <button type="submit" class="btn">Upload Image(s)</button>
        </form>
    </div>

    <script>
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
                    <span><strong>${count}</strong> image${count !== 1 ? 's' : ''} selected</span>
                    <br>
                    <small>Click to add more images</small>
                `;
            } else {
                fileLabel.classList.remove('has-files');
                fileLabel.innerHTML = `
                    <span>Click to select images or drag and drop</span>
                    <br>
                    <small>You can select multiple images</small>
                `;
            }
        }

        const fileInputWrapper = document.querySelector('.file-input-wrapper');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileInputWrapper.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

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

        document.getElementById('imageForm').addEventListener('submit', function(e) {
            if (selectedFiles.length === 0) {
                e.preventDefault();
                alert('Please select at least one image to upload.');
                return false;
            }
        });
    </script>
</body>
</html>

