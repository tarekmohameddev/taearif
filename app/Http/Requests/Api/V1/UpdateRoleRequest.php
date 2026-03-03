<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends BaseApiFormRequest
{
    public function rules()
    {
        $user = auth()->user();
        $tenantId = $user ? (int) $user->id : 0;
        $id = request()->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_roles', 'name')
                    ->where(function ($query) use ($tenantId) {
                        return $query->where('team_id', $tenantId);
                    })
                    ->ignore($id),
            ],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:api_permissions,name'],
        ];
    }

    public function authorize()
    {
        return true;
    }
}
