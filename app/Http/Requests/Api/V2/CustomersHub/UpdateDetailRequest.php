<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateDetailRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $map = [
            'cityId' => 'city_id',
            'districtId' => 'district_id',
            'stageId' => 'stage_id',
            'priorityId' => 'priority_id',
            'typeId' => 'type_id',
            'phoneNumber' => 'phone_number',
            'responsibleEmployeeId' => 'responsible_employee_id',
            'customersHubStageId' => 'customers_hub_stage_id',
        ];

        $merge = [];
        foreach ($map as $camel => $snake) {
            if ($this->has($camel) && !$this->has($snake)) {
                $merge[$snake] = $this->input($camel);
            }
        }
        if ($merge) {
            $this->merge($merge);
        }
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'stage_id' => 'nullable|integer',
            'customers_hub_stage_id' => 'nullable|string|max:50|exists:customers_hub_stages,stage_id',
            'priority_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'source' => 'nullable|string|max:50',
            'responsible_employee_id' => 'nullable|integer',
            'note' => 'nullable|string',
        ];
    }
}
