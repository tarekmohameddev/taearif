<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerStageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage_id' => 'required|string|exists:customers_hub_stages,stage_id',
        ];
    }
}
