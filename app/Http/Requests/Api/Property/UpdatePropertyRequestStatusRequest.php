<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\PropertyRequestStatus;
use Illuminate\Validation\Rule;

class UpdatePropertyRequestStatusRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ownerId = $this->user()->tenantOwnerId();
        $id = (int) $this->route('id');

        $status = PropertyRequestStatus::query()->find($id);
        $isSystem = $status && $status->is_system;

        return [
            'name_ar' => ['sometimes', 'required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'slug' => $isSystem
                ? ['prohibited']
                : [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('property_request_statuses', 'slug')
                        ->ignore($id)
                        ->where(function ($query) use ($ownerId) {
                            $query->where(function ($q) use ($ownerId) {
                                $q->whereNull('user_id')->orWhere('user_id', $ownerId);
                            });
                        }),
                ],
            'display_order' => $isSystem ? ['prohibited'] : ['nullable', 'integer', 'min:1'],
            'is_active' => $isSystem ? ['prohibited'] : ['nullable', 'boolean'],
        ];
    }
}
