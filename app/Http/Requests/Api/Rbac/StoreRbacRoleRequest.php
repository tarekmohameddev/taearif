<?php

namespace App\Http\Requests\Api\Rbac;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreRbacRoleRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = $this->tenantId();

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('api_roles', 'name')->where(fn ($q) => $q->where('team_id', $tenantId)),
            ],
            'permissions' => [
                'sometimes',
                'nullable',
                function ($attribute, $value, $fail) {
                    if (!is_string($value) && !is_array($value)) {
                        $fail('The permissions field must be a string or an array.');
                    }
                    if (is_array($value) && !empty($value)) {
                        foreach ($value as $perm) {
                            if (!is_string($perm)) {
                                $fail('All permissions must be strings.');
                            }
                        }
                    }
                },
            ],
        ];
    }

    protected function tenantId(): int
    {
        $request = request();
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
