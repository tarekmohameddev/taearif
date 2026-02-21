<?php

namespace App\Http\Requests\Pms;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateStageStatusRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => ['required', Rule::in(['الانتظار', 'قيد التنفيذ', 'مكتمل'])],
            'notes' => 'nullable|string',
        ];
    }
}
