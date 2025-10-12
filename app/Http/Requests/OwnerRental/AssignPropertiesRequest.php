<?php

namespace App\Http\Requests\OwnerRental;

use Illuminate\Foundation\Http\FormRequest;

class AssignPropertiesRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'property_ids' => 'required|array|min:1',
            'property_ids.*' => 'required|integer|exists:user_properties,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'property_ids.required' => 'At least one property must be selected',
            'property_ids.array' => 'Property IDs must be an array',
            'property_ids.*.exists' => 'One or more selected properties do not exist',
        ];
    }
}

