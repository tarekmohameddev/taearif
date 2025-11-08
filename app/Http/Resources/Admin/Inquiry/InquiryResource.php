<?php

namespace App\Http\Resources\Admin\Inquiry;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inquiry Resource
 *
 * Transforms Inquiry model into JSON response
 */
class InquiryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant' => [
                'id' => $this->user_id,
                'name' => $this->user ? trim($this->user->first_name . ' ' . $this->user->last_name) : null,
                'email' => $this->user?->email,
            ],
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
            ],
            'contact' => [
                'phone_number' => $this->phone_number,
            ],
            'inquiry' => [
                'message' => $this->message,
                'type' => $this->inquiry_type,
                'property_type' => $this->property_type,
                'urgency' => $this->urgency,
            ],
            'property_details' => [
                'budget' => $this->budget ? (float) $this->budget : null,
                'currency' => $this->currency,
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'min_area_sqm' => $this->min_area_sqm ? (float) $this->min_area_sqm : null,
                'max_area_sqm' => $this->max_area_sqm ? (float) $this->max_area_sqm : null,
                'furnished' => (bool) $this->furnished,
            ],
            'location' => [
                'location_text' => $this->location,
                'country_code' => $this->country_code,
                'region_code' => $this->region_code,
                'region_name' => $this->region_name,
                'city' => $this->city,
                'district' => $this->district,
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
                'confidence' => $this->location_confidence ? (float) $this->location_confidence : null,
            ],
            'metadata' => [
                'source_channel' => $this->source_channel,
                'language' => $this->lang,
                'detected_entities' => $this->detected_entities_json,
            ],
            'dates' => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }
}

