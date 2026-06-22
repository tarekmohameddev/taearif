<?php

namespace App\Http\Requests\Building;

use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuildingRequest extends FormRequest
{
    use ValidatesTenantProjectId;

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();
    }

    public function rules()
    {
        $isJsonRequest = $this->isJson() || $this->header('Content-Type') === 'application/json';
        $user = $this->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : ($user?->id ?? 0);

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('buildings', 'slug')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'deed_number' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:32',
            'address' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'featured_image' => 'nullable|string|max:500',
            'city_id' => 'nullable|integer',
            'state_id' => 'nullable|integer',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
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

        return array_merge($rules, $this->tenantProjectIdRules());
    }
}
