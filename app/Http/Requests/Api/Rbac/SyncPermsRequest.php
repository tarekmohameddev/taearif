<?php

namespace App\Http\Requests\Api\Rbac;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class SyncPermsRequest extends BaseApiFormRequest
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
            'permissions'   => ['array'],
            'permissions.*' => [
                'string',
                Rule::exists('api_permissions', 'name')->where(function ($q) use ($teamId) {
                    return $q->whereNull('team_id')->orWhere('team_id', $teamId);
                }),
            ],
        ];
    }
}
