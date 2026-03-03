<?php

namespace App\Http\Requests\Api\Blog;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreBlogPostRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'status' => 'nullable|in:published,draft',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'featured' => 'nullable|boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
        ];
    }
}
