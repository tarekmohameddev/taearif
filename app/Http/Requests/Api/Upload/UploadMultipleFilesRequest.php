<?php

namespace App\Http\Requests\Api\Upload;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadMultipleFilesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'files' => 'required|array|min:1',
            'files.*' => 'nullable|file',
            'context' => 'required|string',
            'sub_folder' => 'nullable|string',
        ];
    }
}
