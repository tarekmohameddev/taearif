<?php

namespace App\Http\Requests\OwnerRental;

use App\Http\Requests\Api\BaseApiFormRequest;

class OwnerRentalLogoutRequest extends BaseApiFormRequest
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
