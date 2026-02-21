<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class PipelineMoveRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'requestId' => 'nullable|integer',
            'customerId' => 'nullable|integer',
            'inquiryId' => 'nullable|integer',
            'newStageId' => 'required',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requestId = request()->input('requestId');
            $customerId = request()->input('customerId');
            $inquiryId = request()->input('inquiryId');
            $reqId = isset($requestId) ? (int) $requestId : (isset($customerId) ? (int) $customerId : null);
            $inqId = isset($inquiryId) ? (int) $inquiryId : null;
            if ($reqId === null && $inqId === null) {
                $validator->errors()->add('requestId', 'At least one of requestId or inquiryId is required.');
            }
            if ($reqId !== null && $inqId !== null) {
                $validator->errors()->add('requestId', 'Provide either requestId or inquiryId, not both.');
            }
        });
    }
}
