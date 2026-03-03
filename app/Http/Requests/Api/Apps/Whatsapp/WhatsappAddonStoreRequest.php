<?php

namespace App\Http\Requests\Api\Apps\Whatsapp;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class WhatsappAddonStoreRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = $this->tenantId();

        return [
            'whatsapp_number_id' => [
                'required',
                'integer',
                Rule::exists('whatsapp_users', 'id')->where(fn ($q) => $q->where('user_id', $tenantId)),
            ],
            'qty' => ['required', 'integer', 'min:1'],
            'plan_id' => ['required', 'exists:whatsapp_addon_plans,id'],
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
