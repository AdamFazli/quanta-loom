<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdatePostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,gif,webp|max:5120',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'exists:images,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 5000 characters.',
            'images.max' => 'Maximum 10 images allowed per posting.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, GIF, or WebP format.',
            'images.*.max' => 'Each image may not be greater than 5MB.',
            'delete_images.*.exists' => 'One or more selected images do not exist.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $posting = $this->route('posting');
        $postingId = $posting ? $posting->id : $this->route('id');

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($postingId ? route('postings.edit', $postingId) : url()->previous());
    }
}

