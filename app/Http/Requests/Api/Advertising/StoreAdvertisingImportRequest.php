<?php

namespace App\Http\Requests\Api\Advertising;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreAdvertisingImportRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'platform' => ['required', Rule::in(['aqar', 'deal', 'bayut', 'other'])],
        ];
    }
}
