<?php

namespace App\Http\Requests\Admin\Inquiry;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Inquiry Request
 *
 * Validates inquiry creation data
 */
class StoreInquiryRequest extends FormRequest
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
            // Required fields
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:api_customers,id'],
            
            // Contact & Message
            'phone_number' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
            
            // Inquiry details
            'inquiry_type' => ['nullable', 'string', 'max:100'],
            'property_type' => ['nullable', 'string', 'max:100'],
            'urgency' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            
            // Budget & Property specs
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'min_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'max_area_sqm' => ['nullable', 'numeric', 'min:0', 'gte:min_area_sqm'],
            'furnished' => ['nullable', 'boolean'],
            
            // Location
            'location' => ['nullable', 'string', 'max:500'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'region_code' => ['nullable', 'string', 'max:10'],
            'region_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_confidence' => ['nullable', 'numeric', 'between:0,100'],
            
            // Metadata
            'source_channel' => ['nullable', 'string', 'max:50'],
            'lang' => ['nullable', 'string', 'max:10'],
            'detected_entities_json' => ['nullable', 'array'],
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
            'user_id.required' => 'Tenant/User is required',
            'user_id.exists' => 'Selected tenant/user does not exist',
            'customer_id.exists' => 'Selected customer does not exist',
            'message.required' => 'Message is required',
            'message.max' => 'Message cannot exceed 5000 characters',
            'max_area_sqm.gte' => 'Maximum area must be greater than or equal to minimum area',
        ];
    }
}

