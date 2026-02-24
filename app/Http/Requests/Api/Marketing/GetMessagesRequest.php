<?php

namespace App\Http\Requests\Api\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class GetMessagesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'channel_id' => 'nullable|exists:marketing_channels,id',
            'customer_id' => 'nullable|exists:api_customers,id',
            'status' => 'nullable|in:pending,sent,delivered,read,failed',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
