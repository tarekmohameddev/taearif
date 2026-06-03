<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class BulkCreatePropertiesRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'units' => 'required|array|min:1',
            'units.*.title' => 'required|string|max:255',
            'project_id' => 'nullable|integer',
            'building_id' => 'nullable|integer',
            'publish_status' => ['nullable', Rule::in(['draft', 'published'])],
        ];
    }
}
