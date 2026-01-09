<?php

namespace App\Http\Requests\Admin\MarketplaceApp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketplaceAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'app_id' => 'required|exists:api_apps,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|numeric|min:0|max:999999.99',
            'type' => 'required|in:builtin,marketplace',
            'rating' => 'nullable|numeric|min:0|max:5',
            'path' => 'nullable|string|max:255|regex:/^\/dashboard\/[a-z0-9-]+$/',
            'img' => 'nullable|url|max:500',
            'image' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048',
            'billing_type' => 'required|in:free,paid,paid_trial',
            'trial_days' => 'nullable|integer|min:1|max:365|required_if:billing_type,paid_trial',
            'is_enabled' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'app_id.required' => 'معرف التطبيق مطلوب',
            'app_id.exists' => 'التطبيق المحدد غير موجود',
            'name.required' => 'اسم التطبيق مطلوب',
            'name.max' => 'اسم التطبيق لا يمكن أن يكون أكبر من 255 حرف',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'price.min' => 'السعر يجب أن يكون على الأقل 0',
            'type.required' => 'نوع التطبيق مطلوب',
            'type.in' => 'نوع التطبيق يجب أن يكون إما مدمج أو متجر',
            'rating.numeric' => 'التقييم يجب أن يكون رقماً',
            'rating.min' => 'التقييم يجب أن يكون على الأقل 0',
            'rating.max' => 'التقييم لا يجب أن يتجاوز 5',
            'image.file' => 'الصورة يجب أن تكون ملف',
            'image.image' => 'الصورة يجب أن تكون ملف صورة',
            'image.mimes' => 'الصورة يجب أن تكون ملف jpg أو jpeg أو png',
            'image.max' => 'حجم الصورة لا يجب أن يتجاوز 2 ميجابايت',
            'billing_type.required' => 'نوع الفوترة مطلوب',
            'billing_type.in' => 'نوع الفوترة يجب أن يكون مجاني أو مدفوع أو مدفوع مع تجربة',
            'trial_days.required_if' => 'أيام التجربة مطلوبة عندما يكون نوع الفوترة مدفوع مع تجربة',
            'trial_days.integer' => 'أيام التجربة يجب أن تكون رقماً صحيحاً',
            'trial_days.min' => 'أيام التجربة يجب أن تكون على الأقل 1',
            'trial_days.max' => 'أيام التجربة لا يجب أن تتجاوز 365',
            'img.url' => 'رابط الصورة يجب أن يكون رابطاً صحيحاً',
            'img.max' => 'رابط الصورة لا يجب أن يتجاوز 500 حرف',
            'description.max' => 'الوصف لا يجب أن يتجاوز 5000 حرف',
            'price.max' => 'السعر لا يجب أن يتجاوز 999999.99',
            'path.regex' => 'المسار يجب أن يبدأ بـ /dashboard/ متبوعاً بأحرف صغيرة وأرقام وشرطات فقط. مثال: /dashboard/whatsapp-center',
            'path.max' => 'المسار لا يجب أن يتجاوز 255 حرف',
        ];
    }
}

