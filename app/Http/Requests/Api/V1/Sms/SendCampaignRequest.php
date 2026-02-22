<?php

namespace App\Http\Requests\Api\V1\Sms;

use App\Http\Requests\Api\BaseApiFormRequest;

class SendCampaignRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer',
            'manual_phones' => 'nullable|array',
            'manual_phones.*' => 'string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (trim((string) $this->header('Idempotency-Key', '')) === '') {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key header is required.');
            }

            $customerIds = $this->input('customer_ids', []);
            $manualPhones = $this->input('manual_phones', []);
            $hasCustomerIds = is_array($customerIds) && count($customerIds) > 0;
            $hasManualPhones = is_array($manualPhones) && count($manualPhones) > 0;
            if (!$hasCustomerIds && !$hasManualPhones) {
                $validator->errors()->add('customer_ids', 'At least one of customer_ids or manual_phones must be provided with at least one value.');
            }
        });
    }
}
