<?php

namespace App\Http\Requests\Api\Apps\Whatsapp;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class WhatsappUpdateEmployeeRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = $this->tenantId();
        $whatsappUserId = request()->route('id');

        return [
            'employeeId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->where('account_type', 'employee')
                        ->where('active', true);
                }),
                Rule::unique('whatsapp_users', 'employee_id')
                    ->where(fn ($q) => $q->where('user_id', $tenantId))
                    ->ignore($whatsappUserId),
            ],
        ];
    }

    protected function tenantId(): int
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user) {
            abort(response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401));
        }
        return $user instanceof \App\Models\User ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
