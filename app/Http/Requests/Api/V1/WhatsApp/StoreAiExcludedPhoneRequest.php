<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAiExcludedPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:20',
        ];
    }
}
