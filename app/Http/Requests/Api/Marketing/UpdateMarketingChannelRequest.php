<?php

namespace App\Http\Requests\Api\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketingChannelRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'sometimes|string|max:50|alpha_dash',
            'number' => 'sometimes|string|max:50',
            'business_id' => 'nullable|string|max:100',
            'phone_id' => 'nullable|string|max:100',
            'access_token' => 'nullable|string|max:500',
            'additional_settings' => 'nullable|array',
        ];
    }
}
