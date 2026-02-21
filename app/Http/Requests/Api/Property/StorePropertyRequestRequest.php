<?php

namespace App\Http\Requests\Api\property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tenant_username' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'property_type' => 'nullable',
            'category' => 'nullable|string',
            'region' => ['required', 'integer', Rule::exists('user_cities', 'id')],
            'districts_id' => ['nullable', 'integer', Rule::exists('user_districts', 'id')],
            'area_from' => 'nullable|integer|min:0',
            'area_to' => 'nullable|integer|min:0',
            'purchase_method' => 'nullable',
            'budget_from' => 'nullable',
            'budget_to' => 'nullable',
            'seriousness' => ['nullable', 'string', Rule::in(['مستعد فورًا', 'خلال شهر', 'خلال 3 أشهر', 'لاحقًا / استكشاف فقط'])],
            'purchase_goal' => ['nullable', 'string', Rule::in(['سكن خاص', 'استثمار وتأجير', 'بناء وبيع', 'مشروع تجاري'])],
            'wants_similar_offers' => 'nullable|boolean',
            'contact_on_whatsapp' => 'nullable|boolean',
            'notes' => 'nullable|string|max:5000',
            'status_id' => 'nullable|integer|exists:property_request_statuses,id',
        ];
    }
}
