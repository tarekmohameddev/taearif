<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pending,accepted,rejected,all'],
            'type' => ['nullable', 'in:rent,buy,all'],
            'search' => ['nullable', 'string', 'max:200'],
            'sort_by' => ['nullable', 'in:date,price,name'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}


