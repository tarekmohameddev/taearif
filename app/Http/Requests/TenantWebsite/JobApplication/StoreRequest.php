<?php

namespace App\Http\Requests\TenantWebsite\JobApplication;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'pdf_path' => ['required', 'string', 'max:500'],
        ];
    }
}
