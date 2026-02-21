<?php

namespace App\Http\Requests\Api\Pixel;

use App\Http\Requests\Api\BaseApiFormRequest;

class StorePixelRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'platform' => 'required|in:facebook,tiktok,snapchat,gtm',
            'pixel_id' => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
