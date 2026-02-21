<?php

namespace App\Http\Requests\Api\Rbac;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class PermissionAdminUpdateRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $permission = request()->route('permission');
        $actor = auth()->user();
        $tenantId = $actor && $actor->isTenant() ? (int) $actor->id : (int) ($actor->tenant_id ?? 0);
        $id = $permission ? $permission->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('api_permissions', 'name')
                    ->where(function ($q) use ($tenantId) {
                        return $q->where('team_id', $tenantId);
                    })
                    ->ignore($id),
            ],
            'name_ar' => ['nullable', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
        ];
    }
}
