<?php

namespace App\Http\Requests\Rms\Rental;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class ListRentalsRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort_by' => ['nullable', 'string', RmsConstants::validationRule(RmsConstants::SORT_FIELDS)],
            'sort_order' => ['nullable', 'string', RmsConstants::validationRule(RmsConstants::SORT_ORDERS)],
            'q' => 'nullable|string|max:255',
            'status' => ['nullable', 'string', RmsConstants::validationRule(RmsConstants::RENTAL_STATUSES)],
            'building_id' => 'nullable',
            'unit_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'paying_plan' => ['nullable', 'string', RmsConstants::validationRule(RmsConstants::PAYING_PLANS)],
            'filter_by_month' => 'nullable|integer|min:1|max:12',
            'filter_by_year' => 'nullable|integer|min:2000|max:2100',
            'filter_by_day' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'contract_status' => ['nullable', 'string', RmsConstants::validationRule(RmsConstants::CONTRACT_STATUSES)],
            'payment_status' => 'nullable|string|in:paid,partial,overdue,pending,unpaid',
            'contract_created_from_date' => 'nullable|date',
            'contract_created_to_date' => 'nullable|date|after_or_equal:contract_created_from_date',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Items per page must be a number.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
            'page.integer' => 'Page number must be a number.',
            'page.min' => 'Page number must be at least 1.',
            'sort_by.in' => 'Invalid sort field.',
            'sort_order.in' => 'Sort order must be either "asc" or "desc".',
            'q.max' => 'Search query cannot exceed 255 characters.',
            'status.in' => 'Invalid rental status.',
            'paying_plan.in' => 'Invalid payment plan.',
            'filter_by_month.min' => 'Month must be between 1 and 12.',
            'filter_by_month.max' => 'Month must be between 1 and 12.',
            'filter_by_year.min' => 'Year must be between 2000 and 2100.',
            'filter_by_year.max' => 'Year must be between 2000 and 2100.',
            'filter_by_day.date' => 'Filter date must be a valid date.',
            'from_date.date' => 'From date must be a valid date.',
            'to_date.date' => 'To date must be a valid date.',
            'to_date.after_or_equal' => 'To date must be equal to or after from date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'per_page' => 'items per page',
            'sort_by' => 'sort field',
            'sort_order' => 'sort order',
            'q' => 'search query',
            'building_id' => 'building',
            'unit_id' => 'unit',
            'project_id' => 'project',
            'paying_plan' => 'payment plan',
            'filter_by_month' => 'month',
            'filter_by_year' => 'year',
            'filter_by_day' => 'day',
            'from_date' => 'from date',
            'to_date' => 'to date',
        ];
    }
}

