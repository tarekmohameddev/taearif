<?php

namespace App\Http\Requests\Api\Building;

use App\Http\Requests\Api\BaseApiFormRequest;

class AttachBuildingPropertyRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|integer|min:1',
        ];
    }
}
