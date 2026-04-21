<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2\CustomersHub;

use Illuminate\Foundation\Http\FormRequest;

final class IgnoredCustomersStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'       => ['nullable', 'string', 'max:30'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'reason'      => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->input('phone')) && empty($this->input('customer_id'))) {
                $v->errors()->add('phone', 'يجب توفير رقم الهاتف أو معرف العميل على الأقل.');
            }
        });
    }
}
