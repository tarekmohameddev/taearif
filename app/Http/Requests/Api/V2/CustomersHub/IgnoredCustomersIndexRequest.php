<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V2\CustomersHub;

use Illuminate\Foundation\Http\FormRequest;

final class IgnoredCustomersIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'        => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
