<?php

namespace App\Http\Requests\Api\Rbac;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class SyncRolesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $actor = auth()->user();
        $teamId = $actor && $actor->isTenant() ? (int) $actor->id : (int) ($actor->tenant_id ?? 0);

        return [
            'roles'   => ['array'],
            'roles.*' => [
                'string',
                Rule::exists('api_roles', 'name')->where(fn ($q) => $q->where('team_id', $teamId)),
            ],
        ];
    }
}
