<?php

namespace App\Http\Requests\Api\blog;

use App\Models\Api\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMediaRequest extends FormRequest
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
        return [
            'file' => 'required|file|mimes:jpeg,png,gif,webp,mp4,mov,webm|max:51200',
            'mediable_type' => 'nullable|string|in:App\\Models\\Api\\Post',
            'mediable_id' => 'nullable|integer|required_with:mediable_type|exists:api_posts,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->filled('mediable_type') || !$this->filled('mediable_id')) {
                return;
            }
            $post = Post::find($this->mediable_id);
            if ($post && $post->user_id !== $this->user()?->id) {
                $validator->errors()->add('mediable_id', 'You do not own this post.');
            }
        });
    }
}
