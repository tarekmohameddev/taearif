<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkListRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string|in:update_stage,update_priority,update_type,assign_employee,archive,delete',
            'customerIds' => 'required|array|min:1|max:500',
            'customerIds.*' => 'integer|min:1',
            'data' => 'required|array',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->tenantUserId();
            $action = $this->input('action');
            $data = $this->input('data', []);

            $validEmployee = function ($value) use ($userId) {
                if ($value === null) {
                    return true;
                }
                $id = (int) $value;
                if ($id === $userId) {
                    return true;
                }

                return User::where('id', $id)
                    ->where('tenant_id', $userId)
                    ->where('account_type', 'employee')
                    ->where('active', true)
                    ->exists();
            };

            if ($action === 'update_stage') {
                $stageId = $data['stageId'] ?? null;
                if ($stageId === null || $stageId === '') {
                    $validator->errors()->add('data.stageId', __('validation.required', ['attribute' => 'data.stageId']));
                } elseif (!is_string($stageId)) {
                    $validator->errors()->add('data.stageId', __('validation.string', ['attribute' => 'data.stageId']));
                } elseif (!DB::table('customers_hub_stages')
                    ->where('stage_id', $stageId)
                    ->where('is_active', true)
                    ->where(function ($w) use ($userId) {
                        $w->where('is_system', true)->orWhere('user_id', $userId);
                    })
                    ->exists()) {
                    $validator->errors()->add('data.stageId', __('validation.exists', ['attribute' => 'data.stageId']));
                }
            }

            if ($action === 'assign_employee') {
                $employeeId = $data['employeeId'] ?? null;
                if ($employeeId !== null && !$validEmployee($employeeId)) {
                    $validator->errors()->add('data.employeeId', __('validation.exists', ['attribute' => 'data.employeeId']));
                }
            }
        });
    }

    protected function tenantUserId(): int
    {
        $user = $this->user();

        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
