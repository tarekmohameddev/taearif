<?php

namespace App\Http\Requests\Api\Blog;

use App\Http\Requests\Api\BaseApiFormRequest;

class UploadBlogImageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }
}
