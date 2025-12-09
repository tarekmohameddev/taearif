<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:accept,reject'],
            'reservationIds' => ['required', 'array', 'min:1'],
            'reservationIds.*' => ['required'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}


