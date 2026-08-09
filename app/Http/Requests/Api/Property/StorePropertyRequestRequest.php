<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use App\Rules\PropertyTypeRule;
use Illuminate\Validation\Rule;

class StorePropertyRequestRequest extends BaseApiFormRequest
{
    use ValidatesTenantProjectId;

    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();

        if ($this->has('property_type')) {
            $normalized = PropertyTypeRule::normalize(is_string($this->input('property_type')) ? $this->input('property_type') : null);
            if ($normalized !== null) {
                $this->merge(['property_type' => $normalized]);
            }
        }
    }

    public function rules()
    {
        return [
            'tenant_username' => 'nullable|string|max:255',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'property_ids' => 'nullable|array',
            'property_ids.*' => 'integer|exists:user_properties,id',
            // Ownership checked in controller after tenant resolve (public may have no auth).
            'project_id' => 'nullable|integer|exists:user_projects,id',
            'source' => ['nullable', 'string', Rule::in([
                'property_interest',
                'public_form',
                'employee_dashboard',
                'whatsapp_bot',
                'import',
            ])],
            'referral_source' => ['nullable', 'string'],
            'property_type' => PropertyTypeRule::nullableRule(),
            'category_id' => ['nullable', 'integer', Rule::exists('api_user_categories', 'id')],
            'category' => 'nullable|string',
            'region' => ['nullable', 'integer', Rule::exists('user_cities', 'id')],
            'districts_id' => ['nullable', 'integer', Rule::exists('user_districts', 'id')],
            'area_from' => 'nullable|integer|min:0',
            'area_to' => 'nullable|integer|min:0',
            'purchase_method' => ['nullable', Rule::in(['كاش', 'تمويل بنكي'])],
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
