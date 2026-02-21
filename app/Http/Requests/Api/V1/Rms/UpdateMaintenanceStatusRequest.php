<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Constants\RmsConstants;
use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateMaintenanceStatusRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['required', RmsConstants::validationRule(RmsConstants::MAINTENANCE_STATUSES)],
        ];
    }
}
