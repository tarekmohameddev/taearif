<?php

namespace App\Http\Requests\TenantWebsite\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'propertySlug' => ['required', 'string', 'max:200'],
            'customerName' => ['required', 'string', 'max:100'],
            'customerPhone' => ['required', 'string', 'max:40', 'regex:/^\\+?\\d{7,15}$/'],
            'desiredDate' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customerPhone.regex' => 'Invalid phone format. Use international format like +9665XXXXXXX',
        ];
    }
}


