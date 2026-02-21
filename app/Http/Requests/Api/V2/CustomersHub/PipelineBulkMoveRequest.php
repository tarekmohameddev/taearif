<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class PipelineBulkMoveRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requestIds' => 'nullable|array',
            'requestIds.*' => 'integer',
            'customerIds' => 'nullable|array',
            'customerIds.*' => 'integer',
            'newStageId' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requestIds = request()->input('requestIds', []);
            $customerIds = request()->input('customerIds', []);
            if (empty($requestIds) && empty($customerIds)) {
                $validator->errors()->add('requestIds', 'At least one of request ids or customer ids is required.');
            }
        });
    }
}
