<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class BulkPipedriveSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids'   => 'required|array|max:50',
            'user_ids.*' => 'integer|exists:users,id',
            'force'      => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.max' => 'A maximum of 50 users can be synced at once.',
        ];
    }
}
