<?php

namespace App\Http\Requests\Api\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketingSettingsRequest extends FormRequest
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
            'crm_integration_enabled' => 'sometimes|boolean',
            'appointment_system_integration_enabled' => 'sometimes|boolean',
            'customers_page_integration_enabled' => 'sometimes|boolean',
            'rental_page_integration_enabled' => 'sometimes|boolean',
            'integration_settings' => 'nullable|array',
            'marketing_settings' => 'nullable|array',
        ];
    }
}
