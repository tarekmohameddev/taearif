<?php

namespace App\Http\Requests\Admin\Calling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSimLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'     => ['sometimes', 'string', 'max:100'],
            'msisdn'    => ['sometimes', 'string', 'max:20'],
            'user_id'   => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
