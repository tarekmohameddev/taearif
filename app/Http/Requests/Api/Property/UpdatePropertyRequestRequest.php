<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\PropertyTypeRule;
use Illuminate\Validation\Rule;

class UpdatePropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('property_type')) {
            $normalized = PropertyTypeRule::normalize(is_string($this->input('property_type')) ? $this->input('property_type') : null);
            if ($normalized !== null) {
                $this->merge(['property_type' => $normalized]);
            }
        }
    }

    public function rules()
    {
        $user = $this->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId')
            ? (int) $user->tenantOwnerId()
            : (int) ($user->id ?? 0);

        return [
            'status_id' => ['nullable', 'integer', 'exists:property_request_statuses,id'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'region' => ['nullable', 'integer', Rule::exists('user_cities', 'id')],
            'city_id' => ['nullable', 'integer', Rule::exists('user_cities', 'id')],
            'districts_id' => ['nullable', 'integer', Rule::exists('user_districts', 'id')],
            'area_from' => ['nullable', 'integer', 'min:0'],
            'area_to' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:128'],
            'district' => ['nullable', 'string', 'max:128'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'region_code' => ['nullable', 'string', 'max:4'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_confidence' => ['nullable', 'numeric', 'between:0,1'],

            'purpose' => ['nullable', 'string', Rule::in(['rent', 'sale'])],
            'property_type' => PropertyTypeRule::nullableRule(),
            'category_id' => ['nullable', 'integer', Rule::exists('api_user_categories', 'id')],
            'purchase_method' => ['nullable', Rule::in(['كاش', 'تمويل بنكي'])],
            'budget_from' => ['nullable', 'numeric', 'min:0'],
            'budget_to' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'seriousness' => ['nullable', 'string', Rule::in(['مستعد فورًا', 'خلال شهر', 'خلال 3 أشهر', 'لاحقًا / استكشاف فقط'])],
            'purchase_goal' => ['nullable', 'string', Rule::in(['سكن خاص', 'استثمار وتأجير', 'بناء وبيع', 'مشروع تجاري'])],
            'wants_similar_offers' => ['nullable', 'boolean'],
            'contact_on_whatsapp' => ['nullable', 'boolean'],
            'is_read' => ['nullable', 'boolean'],
            'is_archived' => ['nullable', 'boolean'],
            'is_ignored' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],

            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'furnished' => ['nullable', 'boolean'],

            'customers_hub_stage_id' => ['nullable', 'string', 'max:255'],
            'responsible_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($ownerId) {
                    $query->where('tenant_id', $ownerId)
                        ->where('account_type', 'employee')
                        ->where('active', true);
                }),
            ],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('api_customers', 'id')->where(function ($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                }),
            ],

            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer'],

            'inquiry_type' => ['nullable', 'string', 'max:100'],
            'lang' => ['nullable', 'string', 'max:8'],
            'referral_source' => ['nullable', 'string', 'max:255'],
            'detected_entities_json' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
