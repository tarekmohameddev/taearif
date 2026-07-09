<?php

namespace App\Http\Requests\Api\Project;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\NormalizesDistrictLocation;
use Illuminate\Validation\Validator;

class StoreProjectRequest extends BaseApiFormRequest
{
    use NormalizesDistrictLocation;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDistrictForValidation();
    }

    public function rules(): array
    {
        return array_merge([
            'featured_image' => 'required|string',
            'video_url' => 'nullable|string',
            'brochure' => 'nullable|string|url',
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
        ], $this->districtLocationRules(false));
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateDistrictCityMatch($validator);
    }
}
