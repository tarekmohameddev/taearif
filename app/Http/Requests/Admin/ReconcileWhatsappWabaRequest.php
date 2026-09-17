<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconcileWhatsappWabaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['required', Rule::in(['RECONCILE'])],
            'expected_phone_id' => ['required', 'string', 'max:191'],
            'expected_stored_waba_id' => ['nullable', 'string', 'max:191'],
            'expected_verified_waba_id' => ['required', 'string', 'max:191'],
        ];
    }
}
