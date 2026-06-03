<?php

namespace App\Http\Requests\Api\Building;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateBuildingRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : ($user?->id ?? 0);
        $buildingId = (int) $this->route('id');

        return [
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
            'water_meter_numbers' => 'nullable|array',
            'water_meter_numbers.*' => 'string|max:255',
            'electricity_meter_numbers' => 'nullable|array',
            'electricity_meter_numbers.*' => 'string|max:255',
            'image' => 'nullable|string|max:500',
            'deed_image' => 'nullable|string|max:500',
        ];
    }
}
