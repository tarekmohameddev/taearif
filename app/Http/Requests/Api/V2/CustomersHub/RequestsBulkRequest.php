<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\User;

class RequestsBulkRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'action' => 'required|string|in:complete,dismiss,snooze,assign,change_priority',
            'actionIds' => 'required|array|min:1|max:1000',
            'actionIds.*' => 'string',
            'data' => 'required|array',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $userId = $this->tenantUserId();
            $action = request()->input('action');
            $data = request()->input('data', []);

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

            if ($action === 'complete') {
                $by = $data['completedBy'] ?? null;
                if ($by === null) {
                    $validator->errors()->add('data.completedBy', __('validation.required', ['attribute' => 'data.completedBy']));
                } elseif (!$validEmployee($by)) {
                    $validator->errors()->add('data.completedBy', __('validation.exists', ['attribute' => 'data.completedBy']));
                }
                if (!empty($data['notes']) && !is_string($data['notes'])) {
                    $validator->errors()->add('data.notes', __('validation.string', ['attribute' => 'data.notes']));
                }
            } elseif ($action === 'dismiss') {
                $by = $data['dismissedBy'] ?? null;
                if ($by === null) {
                    $validator->errors()->add('data.dismissedBy', __('validation.required', ['attribute' => 'data.dismissedBy']));
                } elseif (!$validEmployee($by)) {
                    $validator->errors()->add('data.dismissedBy', __('validation.exists', ['attribute' => 'data.dismissedBy']));
                }
                $reason = $data['reason'] ?? null;
                if ($reason === null || $reason === '') {
                    $validator->errors()->add('data.reason', __('validation.required', ['attribute' => 'data.reason']));
                } elseif (!is_string($reason)) {
                    $validator->errors()->add('data.reason', __('validation.string', ['attribute' => 'data.reason']));
                }
            } elseif ($action === 'snooze') {
                $until = $data['snoozedUntil'] ?? null;
                if (!$until) {
                    $validator->errors()->add('data.snoozedUntil', __('validation.required', ['attribute' => 'data.snoozedUntil']));
                } else {
                    $ts = strtotime($until);
                    if ($ts === false) {
                        $validator->errors()->add('data.snoozedUntil', __('validation.date', ['attribute' => 'data.snoozedUntil']));
                    } elseif ($ts <= time()) {
                        $validator->errors()->add('data.snoozedUntil', __('validation.after', ['attribute' => 'data.snoozedUntil', 'date' => 'now']));
                    }
                }
                $by = $data['snoozedBy'] ?? null;
                if ($by === null) {
                    $validator->errors()->add('data.snoozedBy', __('validation.required', ['attribute' => 'data.snoozedBy']));
                } elseif (!$validEmployee($by)) {
                    $validator->errors()->add('data.snoozedBy', __('validation.exists', ['attribute' => 'data.snoozedBy']));
                }
            } elseif ($action === 'assign') {
                $to = $data['assignedTo'] ?? null;
                $by = $data['assignedBy'] ?? null;
                if ($to === null) {
                    $validator->errors()->add('data.assignedTo', __('validation.required', ['attribute' => 'data.assignedTo']));
                } elseif (!$validEmployee($to)) {
                    $validator->errors()->add('data.assignedTo', __('validation.exists', ['attribute' => 'data.assignedTo']));
                }
                if ($by === null) {
                    $validator->errors()->add('data.assignedBy', __('validation.required', ['attribute' => 'data.assignedBy']));
                } elseif (!$validEmployee($by)) {
                    $validator->errors()->add('data.assignedBy', __('validation.exists', ['attribute' => 'data.assignedBy']));
                }
            } elseif ($action === 'change_priority') {
                $priority = $data['priority'] ?? null;
                if (!in_array($priority, ['urgent', 'high', 'medium', 'low'], true)) {
                    $validator->errors()->add('data.priority', __('validation.in', ['attribute' => 'data.priority']));
                }
                $by = $data['changedBy'] ?? null;
                if ($by === null) {
                    $validator->errors()->add('data.changedBy', __('validation.required', ['attribute' => 'data.changedBy']));
                } elseif (!$validEmployee($by)) {
                    $validator->errors()->add('data.changedBy', __('validation.exists', ['attribute' => 'data.changedBy']));
                }
            }
        });
    }

    protected function tenantUserId(): int
    {
        $user = request()->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
