<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_plan_id' => ['required', 'integer', 'min:1'],
            'change_type' => ['required', 'string', 'in:immediate,next_cycle'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_plan_id.required' => 'A target plan is required',
            'new_plan_id.exists' => 'The selected plan does not exist',
            'change_type.required' => 'Change type is required',
            'change_type.in' => 'Change type must be either immediate or next_cycle',
        ];
    }
}

