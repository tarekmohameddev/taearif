<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;
use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use App\Rules\PropertyTypeRule;
use Illuminate\Validation\Rule;

class BulkCreatePropertiesRequest extends BaseApiFormRequest
{
    use ValidatesPropertyListingStatus;
    use ValidatesTenantProjectId;

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();

        $units = $this->input('units', []);
        if (is_array($units)) {
            foreach ($units as $index => $unit) {
                if (is_array($unit) && array_key_exists('project_id', $unit) && $unit['project_id'] === '') {
                    $units[$index]['project_id'] = null;
                }
            }
            $this->merge(['units' => $units]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantOwnerId = $this->user() && method_exists($this->user(), 'tenantOwnerId')
            ? (int) $this->user()->tenantOwnerId()
            : (int) ($this->user()?->id ?? 0);

        return array_merge(
            [
                'units' => 'required|array|min:1|max:500',
                'units.*.title' => 'required|string|max:255',
                'units.*.address' => 'nullable|string|max:500',
                'units.*.description' => 'nullable|string',
                'units.*.price' => 'nullable|numeric|min:0',
                'units.*.area' => 'nullable|numeric|min:0',
                'units.*.beds' => 'nullable|integer|min:0',
                'units.*.bath' => 'nullable|integer|min:0',
                'units.*.listing_purpose' => ['nullable', Rule::in(['sale', 'rent'])],
                'units.*.unit_status' => ['nullable', Rule::in(['available', 'reserved', 'sold', 'rented'])],
                'units.*.publish_status' => ['nullable', Rule::in(['draft', 'published'])],
                'units.*.property_type' => PropertyTypeRule::nullableRule(),
                'units.*.category_id' => 'nullable|integer|min:1',
                'units.*.project_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('user_projects', 'id')->where('user_id', $tenantOwnerId),
                ],
                'units.*.building_id' => 'nullable|integer|exists:buildings,id',
                'building_id' => 'nullable|integer|exists:buildings,id',
                'auto_apply' => 'nullable|boolean',
            ],
            $this->tenantProjectIdRules(),
            $this->propertyListingStatusRules(),
        );
    }
}
