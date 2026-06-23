<?php

namespace App\Http\Requests\TenantWebsite\ContactMessage;

use App\Models\ContactMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', 'string', 'in:' . implode(',', ContactMessage::SOURCES)],
            'message' => ['required', 'string', 'min:3', 'max:5000'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasContact = filled($this->input('customer_name'))
                || filled($this->input('customer_email'))
                || filled($this->input('customer_phone'));

            if (! $hasContact) {
                $validator->errors()->add(
                    'customer_name',
                    'At least one of customer_name, customer_email, or customer_phone is required.'
                );
            }
        });
    }
}
