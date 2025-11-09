<?php

namespace App\Http\Requests\Admin\Crm;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['required', 'in:website,referral,ads,manual,other'],
            'status' => ['nullable', 'in:new,contacted,qualified,lost,converted'],
            'stage_id' => ['nullable', 'exists:admin_crm_cards,id'],
            'assigned_admin_id' => ['nullable', 'exists:admins,id'],
            'notes' => ['nullable', 'string'],
            'custom_fields' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'lead name',
            'email' => 'email address',
            'phone' => 'phone number',
            'source' => 'lead source',
            'stage_id' => 'CRM stage',
            'assigned_admin_id' => 'assigned admin',
        ];
    }
}

