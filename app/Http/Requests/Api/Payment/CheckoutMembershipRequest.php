<?php

namespace App\Http\Requests\Api\Payment;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class CheckoutMembershipRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where(function ($query) {
                    $query->where('is_active', true)->where('status', '1');
                }),
            ],
            'period' => 'nullable|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'package_id.exists' => 'The selected package is not available.',
        ];
    }
}
