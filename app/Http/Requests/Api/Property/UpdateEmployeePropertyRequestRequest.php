<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeePropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = auth()->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) ($user->id ?? 0);

        return [
            'responsible_employee_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($ownerId) {
                    $query->where('tenant_id', $ownerId)
                        ->where('account_type', 'employee')
                        ->where('active', true);
                }),
            ],
        ];
    }
}
