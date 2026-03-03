<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class CompletePropertyDraftRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'address' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|nullable|numeric',
            'pricePerMeter' => 'sometimes|nullable|numeric',
            'purpose' => 'sometimes|nullable|string',
            'type' => 'sometimes|nullable|string',
            'beds' => 'sometimes|nullable|integer|min:0',
            'bath' => 'sometimes|nullable|integer|min:0',
            'area' => 'sometimes|nullable|numeric|min:0',
            'size' => 'sometimes|nullable|numeric|min:0',
            'video_url' => 'sometimes|nullable|string',
            'virtual_tour' => 'sometimes|nullable|string',
            'features' => 'sometimes|nullable|array',
            'payment_method' => 'sometimes|nullable|string',
            'water_meter_number' => 'sometimes|nullable|string|max:255',
            'electricity_meter_number' => 'sometimes|nullable|string|max:255',
            'deed_number' => 'sometimes|nullable|string|max:255',
            'advertising_license' => 'sometimes|nullable|string|max:255',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'category_id' => 'sometimes|nullable|integer',
            'project_id' => 'sometimes|nullable|integer',
            'building_id' => 'sometimes|nullable|integer',
            'gallery_images' => 'sometimes|array',
            'gallery_images.*' => 'string',
            'amenity_ids' => 'sometimes|array',
            'amenity_ids.*' => 'integer',
        ];
    }
}
