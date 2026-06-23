<?php

namespace App\Http\Requests\ContactMessages;

use Illuminate\Foundation\Http\FormRequest;

class LinkCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer'],
            'force' => ['sometimes', 'boolean'],
        ];
    }
}
