<?php

namespace App\Http\Requests\Api\property;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateStatusPropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status_id' => 'required|integer|exists:property_request_statuses,id',
        ];
    }
}
