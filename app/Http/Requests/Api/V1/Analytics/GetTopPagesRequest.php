<?php

namespace App\Http\Requests\Api\V1\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class GetTopPagesRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => 'required|integer|in:7,30,90,365',
            'limit' => 'nullable|integer|min:1|max:100',
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
            'days.required' => 'The days parameter is required.',
            'days.integer' => 'The days parameter must be an integer.',
            'days.in' => 'The days parameter must be 7, 30, 90, or 365.',
            'limit.integer' => 'The limit parameter must be an integer.',
            'limit.min' => 'The limit parameter must be at least 1.',
            'limit.max' => 'The limit parameter must not exceed 100.',
        ];
    }
}
