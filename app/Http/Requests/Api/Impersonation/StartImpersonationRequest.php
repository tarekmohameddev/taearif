<?php

namespace App\Http\Requests\Api\Impersonation;

use App\Http\Requests\Api\BaseApiFormRequest;

class StartImpersonationRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
