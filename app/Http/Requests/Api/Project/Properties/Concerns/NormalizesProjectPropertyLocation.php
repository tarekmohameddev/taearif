<?php

namespace App\Http\Requests\Api\Project\Properties\Concerns;

use App\Http\Requests\Concerns\NormalizesDistrictLocation;
use Illuminate\Validation\Validator;

trait NormalizesProjectPropertyLocation
{
    use NormalizesDistrictLocation;

    protected function prepareForValidation(): void
    {
        $this->prepareDistrictForValidation();

        if ($this->has('property_type')) {
            $this->request->remove('property_type');
        }

        if ($this->has('project_id')) {
            $this->request->remove('project_id');
        }
    }

    protected function locationRules(bool $districtRequired = true): array
    {
        return $this->districtLocationRules($districtRequired);
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateDistrictCityMatch($validator);
    }
}
