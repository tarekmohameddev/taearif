<?php

namespace App\Http\Requests\Api\blog;

use App\Models\Api\Post;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
        $user = $this->user();

        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value && $user) {
                        $exists = Post::where('slug', $value)
                            ->where('user_id', $user->id)
                            ->exists();

                        if ($exists) {
                            $fail('The slug has already been taken.');
                        }
                    }
                },
            ],
            'content' => 'required|string|max:100000',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'in:draft,published',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:api_categories,id',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'exists:api_media,id',
            'thumbnail_id' => 'nullable|integer|exists:api_media,id',
        ];
    }
}
