<?php

namespace App\Http\Requests\Api\Upload;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadFileRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'file' => 'required|file',
            'context' => 'required|string',
            'sub_folder' => 'nullable|string',
        ];
    }
}
