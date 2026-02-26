<?php

namespace App\Http\Requests\Api\V1\Email;

use App\Http\Requests\Api\BaseApiFormRequest;

class ResumeCampaignRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|in:continue,restart',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer',
            'manual_emails' => 'nullable|array',
            'manual_emails.*' => 'email',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (trim((string) $this->header('Idempotency-Key', '')) === '') {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key header is required.');
            }
        });
    }
}
