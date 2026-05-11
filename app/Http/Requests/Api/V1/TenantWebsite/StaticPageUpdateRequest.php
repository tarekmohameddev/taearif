<?php

namespace App\Http\Requests\Api\V1\TenantWebsite;

use App\Http\Requests\Api\BaseApiFormRequest;

class StaticPageUpdateRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'components' => ['sometimes', 'array'],
            'url' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }
}
