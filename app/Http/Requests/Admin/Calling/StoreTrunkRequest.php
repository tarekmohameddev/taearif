<?php

namespace App\Http\Requests\Admin\Calling;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'         => ['required', 'integer', 'exists:users,id'],
            'name'              => ['required', 'string', 'max:100'],
            'type'              => ['required', 'in:yeastar_gsm,stc_sip'],
            'ownership'         => ['required', 'in:customer_owned,company_hosted'],
            'registration_mode' => ['sometimes', 'in:register,ip_auth'],
            'meta'              => ['nullable', 'array'],
        ];
    }
}
