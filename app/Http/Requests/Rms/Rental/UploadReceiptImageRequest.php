<?php

namespace App\Http\Requests\Rms\Rental;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadReceiptImageRequest extends BaseApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receipt_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receipt_image.required' => 'Please select a receipt image to upload.',
            'receipt_image.image' => 'The file must be an image.',
            'receipt_image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'receipt_image.max' => 'The image size cannot exceed 5MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'receipt_image' => 'receipt image',
        ];
    }
}

