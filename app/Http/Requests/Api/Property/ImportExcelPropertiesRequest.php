<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;
use App\Http\Requests\Concerns\ValidatesTenantProjectId;

class ImportExcelPropertiesRequest extends BaseApiFormRequest
{
    use ValidatesPropertyListingStatus;
    use ValidatesTenantProjectId;

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            [
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
                'building_id' => 'nullable|integer|exists:buildings,id',
                'auto_apply' => 'nullable|boolean',
            ],
            $this->tenantProjectIdRules(),
            $this->propertyListingStatusRules(),
        );
    }
}
