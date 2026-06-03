<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreArchiveItemRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['deed', 'meter', 'document'])],
            'title' => 'nullable|string|max:191',
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240',
            'meta' => 'nullable|array',
            'meta.meter_number' => 'nullable|string|max:64',
            'meta.reading' => 'nullable|string|max:64',
            'meta.reading_date' => 'nullable|date',
        ];
    }
}
