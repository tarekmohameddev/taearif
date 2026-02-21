<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadPropertyDeedImageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'deed_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
