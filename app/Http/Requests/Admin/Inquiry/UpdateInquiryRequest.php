<?php

namespace App\Http\Requests\Admin\Inquiry;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Inquiry Request
 *
 * Validates inquiry update data
 */
class UpdateInquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // Optional fields for update
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:api_customers,id'],
            
            // Contact & Message
            'phone_number' => ['sometimes', 'string', 'max:20'],
            'message' => ['sometimes', 'string', 'max:5000'],
            
            // Inquiry details
            'inquiry_type' => ['sometimes', 'string', 'max:100'],
            'property_type' => ['sometimes', 'string', 'max:100'],
            'urgency' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            
            // Budget & Property specs
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'bedrooms' => ['sometimes', 'integer', 'min:0'],
            'bathrooms' => ['sometimes', 'integer', 'min:0'],
            'min_area_sqm' => ['sometimes', 'numeric', 'min:0'],
            'max_area_sqm' => ['sometimes', 'numeric', 'min:0', 'gte:min_area_sqm'],
            'furnished' => ['sometimes', 'boolean'],
            
            // Location
            'location' => ['sometimes', 'string', 'max:500'],
            'country_code' => ['sometimes', 'string', 'max:10'],
            'region_code' => ['sometimes', 'string', 'max:10'],
            'region_name' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'district' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'location_confidence' => ['sometimes', 'numeric', 'between:0,100'],
            
            // Metadata
            'source_channel' => ['sometimes', 'string', 'max:50'],
            'lang' => ['sometimes', 'string', 'max:10'],
            'detected_entities_json' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => 'Selected tenant/user does not exist',
            'customer_id.exists' => 'Selected customer does not exist',
            'message.max' => 'Message cannot exceed 5000 characters',
            'max_area_sqm.gte' => 'Maximum area must be greater than or equal to minimum area',
        ];
    }
}

