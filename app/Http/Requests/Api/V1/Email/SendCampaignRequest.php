<?php

namespace App\Http\Requests\Api\V1\Email;

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
            'manual_emails' => 'nullable|array',
            'manual_emails.*' => 'email',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (trim((string) $this->header('Idempotency-Key', '')) === '') {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key header is required.');
            }

            $customerIds = $this->input('customer_ids', []);
            $manualEmails = $this->input('manual_emails', []);
            $hasCustomerIds = is_array($customerIds) && count($customerIds) > 0;
            $hasManualEmails = is_array($manualEmails) && count($manualEmails) > 0;
            if (!$hasCustomerIds && !$hasManualEmails) {
                $validator->errors()->add('customer_ids', 'At least one of customer_ids or manual_emails must be provided with at least one value.');
            }
        });
    }
}
