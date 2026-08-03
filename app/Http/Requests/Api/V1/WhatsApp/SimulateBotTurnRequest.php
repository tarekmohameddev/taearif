<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\WaNumber;

final class SimulateBotTurnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        if ($user?->is_admin) {
            return true;
        }

        $waNumberId = (int) $this->input('wa_number_id');
        if ($waNumberId <= 0) {
            return false;
        }

        $tenantId = $user?->tenantOwnerId() ?? (int) auth()->id();
        return WaNumber::where('id', $waNumberId)->where('user_id', $tenantId)->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'wa_number_id'        => 'required|integer|min:1',
            'message'             => 'required|string|max:1000',
            'customer_phone'      => 'nullable|string|max:30',
            'tenant_id'           => 'nullable|integer|min:1',
            'include_transcript'  => 'nullable|boolean',
        ];
    }

    public function tenantId(): int
    {
        $user = auth()->user();
        if ($user?->is_admin && $this->filled('tenant_id')) {
            return (int) $this->input('tenant_id');
        }

        return $user?->tenantOwnerId() ?? (int) auth()->id();
    }

    public function customerPhone(): string
    {
        return (string) ($this->input('customer_phone') ?: '+966500000001');
    }

    public function includeTranscript(): bool
    {
        return (bool) $this->input('include_transcript', false);
    }
}
