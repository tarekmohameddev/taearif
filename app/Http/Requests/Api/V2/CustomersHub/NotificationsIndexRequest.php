<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use Illuminate\Foundation\Http\FormRequest;

class NotificationsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sourceType' => ['sometimes', 'nullable', 'string', 'max:50'],
            'source_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
