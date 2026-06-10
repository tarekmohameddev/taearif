<?php

namespace App\Http\Requests\Api\Building;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends BaseApiFormRequest
{
    use ValidatesTenantProjectId;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();
    }

    public function rules(): array
    {
        $user = $this->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : ($user?->id ?? 0);
        $buildingId = (int) $this->route('id');

        return array_merge([
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:191',
                'filled',
                Rule::unique('buildings', 'slug')
                    ->where(fn ($q) => $q->where('user_id', $ownerId))
                    ->ignore($buildingId),
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
            'image' => 'nullable|string|max:500',
            'deed_image' => 'nullable|string|max:500',
        ], $this->tenantProjectIdRules(sometimes: true));
    }
}
