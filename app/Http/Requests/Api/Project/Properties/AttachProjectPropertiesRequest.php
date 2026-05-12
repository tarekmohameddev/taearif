<?php

namespace App\Http\Requests\Api\Project\Properties;

use App\Http\Requests\Api\BaseApiFormRequest;

class AttachProjectPropertiesRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_ids' => 'required|array|min:1',
            'property_ids.*' => 'required|integer|distinct|min:1',
        ];
    }
}
