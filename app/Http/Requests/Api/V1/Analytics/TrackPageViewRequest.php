<?php

namespace App\Http\Requests\Api\V1\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class TrackPageViewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public endpoint - anyone can track pageviews
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
            'tenant_id' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'dynamic_slug' => 'nullable|string|max:255',
            'path' => 'required|string|max:500|regex:/^\/.*$/',
            'page_type' => 'required|string|in:page,post,project,property',
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
            'tenant_id.required' => 'The tenant ID is required.',
            'slug.required' => 'The page slug is required.',
            'path.required' => 'The page path is required.',
            'path.regex' => 'The path must start with a forward slash (/).',
            'page_type.required' => 'The page type is required.',
            'page_type.in' => 'The page type must be one of: page, post, project, property.',
        ];
    }
}
