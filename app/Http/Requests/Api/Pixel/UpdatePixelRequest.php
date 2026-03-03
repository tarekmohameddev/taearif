<?php

namespace App\Http\Requests\Api\Pixel;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdatePixelRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'platform' => 'sometimes|in:facebook,tiktok,snapchat,gtm',
            'pixel_id' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
