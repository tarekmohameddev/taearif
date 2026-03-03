<?php

namespace App\Http\Requests\Api\Building;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateBuildingRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'deed_number' => 'nullable|string|max:255',
            'water_meter_numbers' => 'nullable|array',
            'water_meter_numbers.*' => 'string|max:255',
            'electricity_meter_numbers' => 'nullable|array',
            'electricity_meter_numbers.*' => 'string|max:255',
            'image' => 'nullable|string|max:500',
            'deed_image' => 'nullable|string|max:500',
        ];
    }
}
