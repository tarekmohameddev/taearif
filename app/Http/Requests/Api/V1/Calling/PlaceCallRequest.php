<?php

namespace App\Http\Requests\Api\V1\Calling;

use Illuminate\Foundation\Http\FormRequest;

class PlaceCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by middleware
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:api_customers,id'],
            'to'          => ['nullable', 'string', 'max:20'],
            'sim_line_id' => ['nullable', 'integer', 'exists:call_sim_lines,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (!$this->filled('customer_id') && !$this->filled('to')) {
                $v->errors()->add('to', 'Either customer_id or a destination phone number (to) is required.');
            }
        });
    }
}
