<?php

namespace App\Http\Requests\Api\Project\Properties\Concerns;

use Illuminate\Validation\Validator;

trait NormalizesProjectPropertyLocation
{
    protected function prepareForValidation(): void
    {
        if ($this->has('district_id') && !$this->filled('state_id')) {
            $this->merge(['state_id' => $this->input('district_id')]);
        }

        if ($this->has('property_type')) {
            $this->request->remove('property_type');
        }

        if ($this->has('project_id')) {
            $this->request->remove('project_id');
        }
    }

    protected function locationRules(bool $districtRequired = true): array
    {
        $districtRule = $districtRequired ? 'required' : 'sometimes';

        return [
            'district_id' => [$districtRule, 'required_without:state_id', 'integer', 'exists:user_districts,id'],
            'state_id' => [$districtRule, 'required_without:district_id', 'integer', 'exists:user_districts,id'],
            'city_id' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $districtId = $this->input('state_id') ?? $this->input('district_id');
            if ($districtId === null || $districtId === '') {
                return;
            }

            $district = \App\Models\User\UserDistrict::query()->find((int) $districtId);
            if (!$district) {
                return;
            }

            if ($this->filled('city_id') && (int) $this->input('city_id') !== (int) $district->city_id) {
                $validator->errors()->add('city_id', 'The city_id must match the selected district city.');
            }
        });
    }
}
