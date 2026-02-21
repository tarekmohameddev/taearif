<?php

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;

class UploadDeedImageRequest extends FormRequest
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
