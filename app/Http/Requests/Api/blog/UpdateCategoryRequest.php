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

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($category): void {
                    // Editing and keeping the same name (body name = current category name) → allow
                    if ($category !== null && (string) $value === (string) $category->name) {
                        return;
                    }
                    $query = \App\Models\Api\Category::where('name', $value);
                    if ($category?->id !== null) {
                        $query->where('id', '!=', $category->id);
                    }
                    if ($query->exists()) {
                        $fail(__('validation.unique', ['attribute' => 'name']));
                    }
                },
            ],
        ];
    }
}
