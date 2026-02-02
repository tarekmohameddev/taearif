<?php

namespace App\Http\Requests\Admin\SupportCenter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportCenterCategoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('support_center_categories', 'slug')->ignore($categoryId)],
            'short_description' => ['nullable', 'string'],
            'icon_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
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
            'name.required' => 'The category name is required.',
            'name.max' => 'The category name may not be greater than 255 characters.',
            'slug.unique' => 'This slug is already taken.',
            'slug.max' => 'The slug may not be greater than 255 characters.',
            'icon_image.image' => 'The icon must be an image file.',
            'icon_image.mimes' => 'The icon must be a JPEG, JPG, PNG, or WEBP file.',
            'icon_image.max' => 'The icon may not be greater than 5MB.',
        ];
    }
}
