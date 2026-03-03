<?php

namespace App\Http\Requests\Api\blog;

use App\Models\Api\Post;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
        $user = $this->user();

        // Get the post ID from the slug and user_id
        $postId = null;
        if ($slug && $user) {
            $post = Post::where('slug', $slug)
                ->where('user_id', $user->id)
                ->first();
            $postId = $post?->id;
        }

        return [
            'title' => 'nullable|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($postId, $user) {
                    if (!$value || !$user) {
                        return;
                    }
                    $routeSlug = $this->route('slug');
                    // Editing and keeping the same slug (body slug = URL slug) → allow
                    if ($routeSlug !== null && (string) $value === (string) $routeSlug) {
                        return;
                    }
                    $exists = Post::where('slug', $value)
                        ->where('user_id', $user->id)
                        ->when($postId, fn($query) => $query->where('id', '!=', $postId))
                        ->exists();

                    if ($exists) {
                        $fail('The slug has already been taken.');
                    }
                },
            ],
            'content' => 'nullable|string|max:100000',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'nullable|in:draft,published',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:api_categories,id',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'exists:api_media,id',
            'thumbnail_id' => 'nullable|integer|exists:api_media,id',
        ];
    }
}
