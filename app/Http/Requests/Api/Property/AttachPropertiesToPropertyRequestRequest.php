<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class AttachPropertiesToPropertyRequestRequest extends BaseApiFormRequest
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
            'propertyIds' => ['required', 'array', 'min:1'],
            'propertyIds.*' => [
                'integer',
                Rule::exists('user_properties', 'id')->where('user_id', $ownerId),
            ],
        ];
    }

    public function messages()
    {
        return [
            'propertyIds.required' => 'At least one property ID is required.',
            'propertyIds.*.exists' => 'The selected property Id is invalid.',
        ];
    }
}
