<?php

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;

class UploadBuildingImageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
