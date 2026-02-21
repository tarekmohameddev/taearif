<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class TerminateContractRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'termination_reason' => 'required|string|max:255',
            'terminate_on' => 'required|date',
        ];
    }
}
