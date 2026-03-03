<?php

namespace App\Http\Requests\Api\Project;

use App\Http\Requests\Api\BaseApiFormRequest;

class ToggleProjectFeaturedRequest extends BaseApiFormRequest
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
