<?php

namespace App\Http\Requests\Api\V1\Analytics;

use App\Http\Requests\Api\BaseApiFormRequest;

class PageviewDashboardRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'days' => 'nullable|integer|in:7,30,90',
        ];
    }
}
