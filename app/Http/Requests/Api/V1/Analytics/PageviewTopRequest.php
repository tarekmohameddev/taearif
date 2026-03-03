<?php

namespace App\Http\Requests\Api\V1\Analytics;

use App\Http\Requests\Api\BaseApiFormRequest;

class PageviewTopRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'days' => 'nullable|integer|min:1|max:365',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}
