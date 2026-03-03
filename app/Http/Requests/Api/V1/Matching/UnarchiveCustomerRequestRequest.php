<?php

namespace App\Http\Requests\Api\V1\Matching;

use App\Http\Requests\Api\BaseApiFormRequest;

class UnarchiveCustomerRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}
