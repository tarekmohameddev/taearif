<?php

namespace App\Http\Requests\Api\V1\Analytics;

use App\Services\Analytics\PageviewService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

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
            'path' => 'required|string|max:500|regex:/^\/(?!\/).*$/',
            'page_type' => 'required|string|in:page,post,project,property',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('path'))) {
            try {
                $this->merge(['path' => PageviewService::normalizePath($this->input('path'))]);
            } catch (\InvalidArgumentException) {
                // Let the normal validator return a safe 422 response.
            }
        }
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $pageType = $this->input('page_type');
            if ($validator->errors()->isNotEmpty() || ! in_array($pageType, ['property', 'project'], true)) {
                return;
            }

            $tenant = DB::table('users')->where('username', $this->input('tenant_id'))->first(['id']);
            $slug = $this->input('dynamic_slug') ?: $this->input('slug');
            $table = $pageType === 'property' ? 'user_property_contents' : 'user_project_contents';
            $path = $this->input('path');
            $segments = is_string($path) ? array_map('rawurldecode', explode('/', trim($path, '/'))) : [];

            if (! $tenant || ! DB::table($table)->where('user_id', $tenant->id)->where('slug', $slug)->exists()) {
                $validator->errors()->add('slug', "The {$pageType} slug does not belong to the tenant.");
            } elseif (! in_array($slug, $segments, true)) {
                $validator->errors()->add('path', "The path must contain the {$pageType} slug.");
            }
        });
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
