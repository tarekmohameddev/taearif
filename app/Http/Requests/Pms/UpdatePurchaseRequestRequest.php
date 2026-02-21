<?php

namespace App\Http\Requests\Pms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequestRequest extends FormRequest
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
        $user = Auth::user();
        $tenantId = $user ? ($user->isTenant() ? $user->id : $user->tenant_id) : 0;

        return [
            'client_name' => 'sometimes|required|string|max:255',
            'client_email' => 'sometimes|required|email|max:255',
            'client_phone' => 'sometimes|required|string|max:20',
            'client_national_id' => 'nullable|string|max:50',
            'property_id' => [
                'nullable',
                Rule::exists('user_properties', 'id')->where('user_id', $tenantId)
            ],
            'project_id' => [
                'nullable',
                Rule::exists('user_projects', 'id')->where('user_id', $tenantId)
            ],
            'priority' => ['sometimes', 'required', Rule::in(['منخفضة', 'متوسطة', 'عالية', 'عاجل'])],
            'budget_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function($query) use ($tenantId) {
                    $query->where('id', $tenantId)->orWhere('tenant_id', $tenantId);
                })
            ],
            'overall_status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed', 'cancelled'])],
            'expected_completion_date' => 'nullable|date',
        ];
    }
}
