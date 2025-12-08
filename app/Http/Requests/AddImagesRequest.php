<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'At least one image is required.',
            'images.min' => 'At least one image is required.',
            'images.max' => 'Maximum 10 images allowed per request.',
            'images.*.required' => 'Each image file is required.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, GIF, or WebP format.',
            'images.*.max' => 'Each image may not be greater than 5MB.',
        ];
    }
}

