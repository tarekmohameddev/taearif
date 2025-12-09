<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Be tolerant with various client payload shapes
        $payload = $this->all();

        $reason = $payload['reason'] ?? null;

        // Some clients send it as 'notes' or as nested 'data.reason'
        if (!$reason) {
            $reason = $payload['notes'] ?? null;
        }
        if (!$reason && isset($payload['data']) && is_array($payload['data'])) {
            $reason = $payload['data']['reason'] ?? null;
        }

        // As a last resort, try to decode raw content if content-type is unusual
        if (!$reason) {
            $raw = $this->getContent();
            if (is_string($raw) && $raw !== '') {
                $json = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $reason = $json['reason'] ?? ($json['notes'] ?? $reason);
                }
            }
        }

        if ($reason) {
            $this->merge(['reason' => $reason]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'confirmPayment' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];

        // If reject route, reason is required (be robust to name/prefixing)
        if ($this->routeIs('reservations.reject') || $this->is('api/v1/reservations/*/reject')) {
            $rules['reason'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}


