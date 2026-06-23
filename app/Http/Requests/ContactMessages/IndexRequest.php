<?php

namespace App\Http\Requests\ContactMessages;

use App\Models\ContactMessage;
use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_read' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:active,archived,all'],
            'source' => ['nullable', 'string', 'in:' . implode(',', ContactMessage::SOURCES)],
            'customer_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:200'],
            'sort_by' => ['nullable', 'string', 'in:created_at,customer_name,source'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ];
    }
}
