<?php

namespace App\Http\Requests\Api\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemIntegrationSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'crm_integration_enabled' => 'required|boolean',
            'appointment_system_integration_enabled' => 'required|boolean',
            'customers_page_integration_enabled' => 'required|boolean',
            'rental_page_integration_enabled' => 'required|boolean',
            'integration_settings' => 'nullable|array',
        ];
    }
}
