<?php

namespace App\Http\Requests\Pms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateStagesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stages' => 'required|array',
            'stages.*.stage_id' => 'required|exists:purchase_request_stages,id',
            'stages.*.status' => ['required', Rule::in(['الانتظار', 'قيد التنفيذ', 'مكتمل'])],
            'stages.*.notes' => 'nullable|string',
        ];
    }
}
