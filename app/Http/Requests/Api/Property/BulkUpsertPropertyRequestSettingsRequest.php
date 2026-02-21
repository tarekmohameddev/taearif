<?php

namespace App\Http\Requests\Api\property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Services\PropertyRequestFormSettings;
use Illuminate\Validation\Rule;

class BulkUpsertPropertyRequestSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $allowedKeys = array_keys(PropertyRequestFormSettings::defaultMap());

        return [
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.field_key'     => ['required', 'string', Rule::in($allowedKeys)],
            'items.*.is_visible'    => ['nullable', 'boolean'],
            'items.*.is_required'   => ['nullable', 'boolean'],
            'items.*.sort_order'    => ['nullable', 'integer'],
            'items.*.label_ar'      => ['nullable', 'string', 'max:255'],
            'items.*.label_en'      => ['nullable', 'string', 'max:255'],
            'items.*.meta'          => ['nullable', 'array'],
        ];
    }
}
