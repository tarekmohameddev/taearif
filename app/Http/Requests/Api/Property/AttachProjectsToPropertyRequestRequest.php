<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class AttachProjectsToPropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId')
            ? (int) $user->tenantOwnerId()
            : (int) ($user->id ?? 0);

        return [
            'projectIds' => ['required', 'array', 'min:1'],
            'projectIds.*' => [
                'integer',
                'min:1',
                Rule::exists('user_projects', 'id')->where('user_id', $ownerId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'projectIds.required' => 'At least one project ID is required.',
            'projectIds.*.exists' => 'The selected project ID is invalid.',
        ];
    }
}
