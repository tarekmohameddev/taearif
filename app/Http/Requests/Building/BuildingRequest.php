<?php

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;

class BuildingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isJsonRequest = $this->isJson() || $this->header('Content-Type') === 'application/json';
        
        $rules = [
            'name' => 'required|string|max:255',
            'deed_number' => 'nullable|string|max:255',
            'water_meter_numbers' => 'nullable|array',
            'water_meter_numbers.*' => 'string|max:255',
            'electricity_meter_numbers' => 'nullable|array',
            'electricity_meter_numbers.*' => 'string|max:255',
        ];

        if ($isJsonRequest) {
            $rules['image'] = 'nullable|string|max:500';
            $rules['deed_image'] = 'nullable|string|max:500';
        } else {
            $rules['image'] = 'nullable|file|mimes:jpg,jpeg,png|max:5120';
            $rules['deed_image'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
        }

        return $rules;
    }
}
