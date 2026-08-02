<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class ResetBotSandboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'wa_number_id'   => 'required|integer|min:1',
            'customer_phone' => 'nullable|string|max:30',
            'tenant_id'      => 'nullable|integer|min:1',
        ];
    }

    public function tenantId(): int
    {
        return (int) ($this->input('tenant_id') ?? auth()->id());
    }

    public function customerPhone(): string
    {
        return (string) ($this->input('customer_phone') ?: '+966500000001');
    }
}
