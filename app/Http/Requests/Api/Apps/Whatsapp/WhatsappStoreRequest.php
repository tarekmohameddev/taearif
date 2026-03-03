<?php

namespace App\Http\Requests\Api\Apps\Whatsapp;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class WhatsappStoreRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tid = $this->tenantId();
        return [
            'phoneNumber' => ['required', 'regex:/^[0-9]{9,20}$/'],
            'linkingMethod' => ['required', 'in:support,automatic'],
            'apiMethod' => ['required', 'in:official,unofficial'],
            'customerName' => ['nullable', 'string'],
            'supportMessage' => ['nullable', 'string'],
            'notLinked' => ['nullable', 'boolean'],
            'employeeId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $tid)->where('account_type', 'employee')->where('active', true)),
                Rule::unique('whatsapp_users', 'employee_id')->where(fn ($q) => $q->where('user_id', $tid)),
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
