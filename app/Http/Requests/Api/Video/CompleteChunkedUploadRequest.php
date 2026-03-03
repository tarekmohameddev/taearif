<?php

namespace App\Http\Requests\Api\Video;

use App\Http\Requests\Api\BaseApiFormRequest;

class CompleteChunkedUploadRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'upload_id' => 'required|string',
            'filename' => 'required|string',
            'parts' => 'required|array',
            'parts.*.etag' => 'required|string',
            'parts.*.part_number' => 'required|integer',
        ];
    }
}
