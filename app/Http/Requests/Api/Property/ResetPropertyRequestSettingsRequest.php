<?php

namespace App\Http\Requests\Api\property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Services\PropertyRequestFormSettings;
use Illuminate\Validation\Rule;

class ResetPropertyRequestSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $allowedKeys = array_keys(PropertyRequestFormSettings::defaultMap());

        return [
            'keys' => ['nullable', 'array'],
            'keys.*' => [Rule::in($allowedKeys)],
        ];
    }
}
