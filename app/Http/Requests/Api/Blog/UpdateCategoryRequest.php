<?php

namespace App\Http\Requests\Api\blog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $slug = $this->route('slug');
        $category = \App\Models\Api\Category::where('slug', $slug)->first();
        $id = $category ? $category->id : null;

        return [
            'name' => 'required|string|max:255|unique:api_categories,name,' . $id,
        ];
    }
}
