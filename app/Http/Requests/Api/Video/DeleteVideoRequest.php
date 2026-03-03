<?php

namespace App\Http\Requests\Api\Video;

use App\Http\Requests\Api\BaseApiFormRequest;

class DeleteVideoRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'path' => 'required|string',
        ];
    }
}
