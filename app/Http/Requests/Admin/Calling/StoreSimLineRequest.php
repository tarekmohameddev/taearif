<?php

namespace App\Http\Requests\Admin\Calling;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trunk_id'   => ['required', 'integer', 'exists:call_trunks,id'],
            'tenant_id'  => ['required', 'integer', 'exists:users,id'],
            'label'      => ['required', 'string', 'max:100'],
            'msisdn'     => ['required', 'string', 'max:20'],
            'port_index' => ['nullable', 'integer', 'min:1', 'max:8'],
            'user_id'    => ['nullable', 'integer', 'exists:users,id'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }
}
