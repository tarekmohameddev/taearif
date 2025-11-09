<?php

namespace App\Http\Requests\Admin\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Plan Request
 *
 * Validates plan update data
 */
class UpdatePlanRequest extends FormRequest
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
        $planId = $this->route('plan'); // ID from route

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('packages', 'slug')->ignore($planId)
            ],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'term' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:255'],
            'featured' => ['sometimes', 'integer', 'in:0,1'],
            'is_trial' => ['sometimes', 'boolean'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'integer'],
            'features' => ['nullable', 'array'],
            'new_features' => ['nullable', 'array'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'number_of_vcards' => ['nullable', 'integer', 'min:0'],
            'project_limit_number' => ['nullable', 'integer', 'min:0'],
            'real_estate_limit_number' => ['nullable', 'integer', 'min:0'],
            'video_size_limit' => ['nullable', 'integer', 'min:0'],
            'file_size_limit' => ['nullable', 'integer', 'min:0'],
            'serial_number' => ['nullable', 'integer', 'min:0'],
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
            'slug.unique' => 'This slug is already taken',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price cannot be negative',
        ];
    }
}

