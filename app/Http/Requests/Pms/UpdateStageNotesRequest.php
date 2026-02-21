<?php

namespace App\Http\Requests\Pms;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateStageNotesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'notes' => 'required|string',
        ];
    }
}
