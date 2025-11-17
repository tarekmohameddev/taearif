<?php

namespace App\Http\Requests\Admin\Employee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload Image Request
 *
 * Validates employee profile image upload
 */
class UploadImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png',
                'max:2048', // 2MB in kilobytes
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Image file is required',
            'image.image' => 'The file must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png.',
            'image.max' => 'The image must not be larger than 2MB.',
        ];
    }
}
