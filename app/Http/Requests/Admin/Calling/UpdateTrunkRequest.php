<?php

namespace App\Http\Requests\Admin\Calling;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'string', 'max:100'],
            'registration_mode' => ['sometimes', 'in:register,ip_auth'],
            'meta'              => ['nullable', 'array'],
        ];
    }
}
