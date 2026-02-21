<?php

namespace App\Http\Requests\Api\Project;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreProjectRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'featured_image' => 'required|string',
            'video_url' => 'nullable|string',
            'address' => 'nullable',
            'description' => 'nullable|min:15',
            'complete_status' => 'nullable',
            'units' => 'nullable|integer',
            'completion_date' => 'nullable|date',
            'developer' => 'nullable|max:255',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable',
            'floorplan_images' => 'nullable|array',
            'floorplan_images.*' => 'nullable',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'featured' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'label' => 'nullable|array',
            'value' => 'nullable|array',
        ];
    }
}
