<?php

namespace App\Http\Requests\Api\Video;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadChunkRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'chunk_data' => 'required|string',
            'upload_id' => 'required|string',
            'part_number' => 'required|integer|min:1',
            'filename' => 'required|string',
        ];
    }
}
