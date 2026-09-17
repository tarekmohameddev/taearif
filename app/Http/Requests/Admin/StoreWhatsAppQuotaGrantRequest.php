<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhatsAppQuotaGrantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'tenant_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereNull('tenant_id')->where('account_type', 'tenant')),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'reason' => ['required', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
