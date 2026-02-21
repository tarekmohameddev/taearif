<?php

namespace App\Http\Requests\Pms;

use App\Http\Requests\Api\BaseApiFormRequest;

class StageActionRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'notes' => 'nullable|string',
        ];
    }
}
