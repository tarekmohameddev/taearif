<?php

namespace App\Http\Requests\Admin\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreWhatsAppTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:whatsapp_templates,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'language' => ['required', 'string', 'in:ar,en'],
            'variables' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'template name',
            'description' => 'description',
            'content' => 'template content',
            'type' => 'template type',
            'language' => 'language',
            'variables' => 'variables',
            'status' => 'status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.unique' => 'A template with this name already exists.',
            'language.in' => 'Language must be either Arabic (ar) or English (en).',
        ];
    }
}

