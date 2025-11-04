<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Api\UserApiCustomerStage;

class UpdatePropertyRequestSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Already handled by auth:sanctum middleware
    }

    public function rules(): array
    {
        return [
            'auto_create_customer' => 'required|boolean',
            'default_stage_id' => [
                'nullable',
                'integer',
                'exists:users_api_customers_stages,id',
                function ($attribute, $value, $fail) {
                    // Validate stage belongs to authenticated user and is active
                    if ($value) {
                        $exists = UserApiCustomerStage::where('id', $value)
                            ->where('user_id', $this->user()->id)
                            ->where('is_active', true)
                            ->exists();

                        if (!$exists) {
                            $fail('المرحلة المحددة غير موجودة أو غير نشطة');
                        }
                    }
                },
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // If auto_create_customer is true, default_stage_id is required
            if ($this->auto_create_customer && !$this->default_stage_id) {
                $validator->errors()->add(
                    'default_stage_id',
                    'يجب اختيار مرحلة افتراضية عند تفعيل إنشاء العملاء تلقائياً'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'auto_create_customer.required' => 'حقل الإنشاء التلقائي مطلوب',
            'auto_create_customer.boolean' => 'قيمة غير صحيحة للإنشاء التلقائي',
            'default_stage_id.integer' => 'معرف المرحلة يجب أن يكون رقماً',
            'default_stage_id.exists' => 'المرحلة المحددة غير موجودة',
        ];
    }
}

