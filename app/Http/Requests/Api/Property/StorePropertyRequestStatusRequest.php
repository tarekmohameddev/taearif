<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequestStatusRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ownerId = $this->user()->tenantOwnerId();

        return [
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('property_request_statuses', 'slug')->where(function ($query) use ($ownerId) {
                    $query->where(function ($q) use ($ownerId) {
                        $q->whereNull('user_id')->orWhere('user_id', $ownerId);
                    });
                }),
            ],
            'display_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
