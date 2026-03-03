<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = auth()->user();
        $tenantId = $user ? (int) $user->id : 0;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_roles', 'name')->where(function ($query) use ($tenantId) {
                    return $query->where('team_id', $tenantId);
                }),
            ],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:api_permissions,name'],
        ];
    }
}
