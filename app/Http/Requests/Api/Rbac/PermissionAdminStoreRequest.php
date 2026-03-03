<?php

namespace App\Http\Requests\Api\Rbac;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class PermissionAdminStoreRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $input = request()->input('name');
        if (is_array($input)) {
            return [
                'name'   => ['array', 'min:1'],
                'name.*' => ['string', 'max:191', 'distinct:strict'],
            ];
        }

        $actor = auth()->user();
        $tenantId = $actor && $actor->isTenant() ? (int) $actor->id : (int) ($actor->tenant_id ?? 0);

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('api_permissions', 'name')->where(function ($q) use ($tenantId) {
                    return $q->where('team_id', $tenantId);
                }),
            ],
            'name_ar' => ['nullable', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
        ];
    }
}
