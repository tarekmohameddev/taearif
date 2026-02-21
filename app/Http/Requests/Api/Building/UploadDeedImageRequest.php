<?php

namespace App\Http\Requests\Api\Building;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadDeedImageRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deed_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
