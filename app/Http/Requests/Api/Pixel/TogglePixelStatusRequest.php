<?php

namespace App\Http\Requests\Api\Pixel;

use App\Http\Requests\Api\BaseApiFormRequest;

class TogglePixelStatusRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}
