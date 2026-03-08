<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePriorityPropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'priority' => ['required', 'string', Rule::in(['urgent', 'high', 'medium', 'low'])],
        ];
    }
}
