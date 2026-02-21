<?php

namespace App\Http\Requests\Api\Upload;

use App\Http\Requests\Api\BaseApiFormRequest;

class DeleteUploadFileRequest extends BaseApiFormRequest
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
