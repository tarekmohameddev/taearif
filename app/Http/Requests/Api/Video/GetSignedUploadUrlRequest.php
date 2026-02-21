<?php

namespace App\Http\Requests\Api\Video;

use App\Http\Requests\Api\BaseApiFormRequest;

class GetSignedUploadUrlRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'filename' => 'required|string',
            'expires' => 'nullable|integer|min:300|max:3600',
        ];
    }
}
