<?php

namespace App\Http\Requests\Pms;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class TransitionStageRequest extends BaseApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_stage_name' => ['required', Rule::in(['الحجز', 'العقد', 'الإنجاز', 'الاستلام'])],
            'requirements_met' => 'required|array',
            'requirements_met.*' => 'required|boolean',
            'inspection_date' => 'nullable|date',
            'payment_amount' => 'nullable|numeric|min:0',
            'expected_completion_date' => 'nullable|date',
            'additional_notes' => 'nullable|string',
        ];
    }
}
