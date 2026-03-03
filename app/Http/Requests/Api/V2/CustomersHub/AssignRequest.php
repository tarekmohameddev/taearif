<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class AssignRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requestIds' => 'nullable|array|min:1',
            'requestIds.*' => 'string',
            'customerIds' => 'nullable|array|min:1',
            'customerIds.*' => 'string',
            'employeeId' => 'required|string|exists:users,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requestIds = request()->input('requestIds', []);
            $customerIds = request()->input('customerIds', []);
            if (empty($requestIds) && empty($customerIds)) {
                $validator->errors()->add('requestIds', 'At least one of requestIds or customerIds is required.');
            }
        });
    }
}
