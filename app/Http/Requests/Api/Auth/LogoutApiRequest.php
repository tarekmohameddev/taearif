<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;

class LogoutApiRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'device_id' => ['sometimes', 'string', 'max:191'],
            'push_token' => ['sometimes', 'string', 'max:4096'],
        ];
    }
}
