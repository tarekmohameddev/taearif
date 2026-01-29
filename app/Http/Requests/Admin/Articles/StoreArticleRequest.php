<?php

namespace App\Http\Requests\Admin\Articles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:admin_articles_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:admin_articles,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'main_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'status' => ['required', 'string', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'cta_enabled' => ['nullable', 'boolean'],
            'cta_text' => ['nullable', 'string', 'max:255', 'required_if:cta_enabled,true'],
            'cta_url' => ['nullable', 'url', 'max:500', 'required_if:cta_enabled,true'],
            'cta_target_blank' => ['nullable', 'boolean'],
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
            'category_id.required' => 'The category is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'title.required' => 'The article title is required.',
            'body.required' => 'The article body is required.',
            'status.required' => 'The article status is required.',
            'status.in' => 'The selected status is invalid.',
            'published_at.required_if' => 'The published date is required when status is scheduled.',
            'cta_text.required_if' => 'The CTA text is required when CTA is enabled.',
            'cta_url.required_if' => 'The CTA URL is required when CTA is enabled.',
            'main_image.image' => 'The main image must be an image file.',
            'main_image.mimes' => 'The main image must be a JPEG, JPG, PNG, or WEBP file.',
            'main_image.max' => 'The main image may not be greater than 5MB.',
            'og_image.image' => 'The OG image must be an image file.',
            'og_image.mimes' => 'The OG image must be a JPEG, JPG, PNG, or WEBP file.',
            'og_image.max' => 'The OG image may not be greater than 5MB.',
        ];
    }
}
