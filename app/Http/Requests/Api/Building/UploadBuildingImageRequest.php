<?php

namespace App\Http\Requests\Api\Building;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadBuildingImageRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
