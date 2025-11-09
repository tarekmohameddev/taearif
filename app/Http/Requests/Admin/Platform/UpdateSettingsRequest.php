<?php

namespace App\Http\Requests\Admin\Platform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Settings Request
 *
 * Validates platform settings updates
 * Validation rules adapt based on the section being updated
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $section = $this->route('section');

        return match($section) {
            'general' => $this->generalRules(),
            'email' => $this->emailRules(),
            'whatsapp' => $this->whatsappRules(),
            'seo' => $this->seoRules(),
            'maintenance' => $this->maintenanceRules(),
            'appearance' => $this->appearanceRules(),
            default => [],
        };
    }

    /**
     * General settings validation rules
     */
    protected function generalRules(): array
    {
        return [
            'website_title' => ['sometimes', 'string', 'max:255'],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'currency' => ['sometimes', 'array'],
            'currency.symbol' => ['sometimes', 'string', 'max:10'],
            'currency.symbol_position' => ['sometimes', 'string', 'in:left,right'],
            'currency.text' => ['sometimes', 'string', 'max:10'],
            'currency.text_position' => ['sometimes', 'string', 'in:left,right'],
            'currency.rate' => ['sometimes', 'numeric', 'min:0.00000001'],
            'email_verification_status' => ['sometimes', 'boolean'],
            'base_color' => ['sometimes', 'string', 'regex:/^#?[0-9A-F]{6}$/i'],
        ];
    }

    /**
     * Email settings validation rules
     */
    protected function emailRules(): array
    {
        return [
            'smtp_enabled' => ['sometimes', 'boolean'],
            'email_notifications_enabled' => ['sometimes', 'boolean'],
            'smtp' => ['sometimes', 'array'],
            'smtp.host' => ['required_if:smtp_enabled,true', 'string', 'max:255'],
            'smtp.port' => ['required_if:smtp_enabled,true', 'string', 'max:10'],
            'smtp.username' => ['required_if:smtp_enabled,true', 'string', 'max:255'],
            'smtp.password' => ['nullable', 'string', 'max:255'],
            'smtp.encryption' => ['required_if:smtp_enabled,true', 'string', 'in:TLS,SSL'],
            'from' => ['sometimes', 'array'],
            'from.email' => ['sometimes', 'email', 'max:255'],
            'from.name' => ['sometimes', 'string', 'max:255'],
            'to' => ['sometimes', 'array'],
            'to.email' => ['sometimes', 'email', 'max:255'],
        ];
    }

    /**
     * WhatsApp settings validation rules
     */
    protected function whatsappRules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'service' => ['sometimes', 'string', 'in:meta_cloud,evolution_api'],
            'whatsapp_number' => ['sometimes', 'string', 'max:20'],
            'whatsapp_status' => ['sometimes', 'boolean'],
            'meta_cloud' => ['sometimes', 'array'],
            'meta_cloud.access_token' => ['nullable', 'string', 'max:500'],
            'meta_cloud.phone_number_id' => ['sometimes', 'string', 'max:100'],
            'meta_cloud.business_account_id' => ['sometimes', 'string', 'max:100'],
            'meta_cloud.template_name' => ['sometimes', 'string', 'max:100'],
            'meta_cloud.template_language' => ['sometimes', 'string', 'max:10'],
            'evolution_api' => ['sometimes', 'array'],
            'evolution_api.url' => ['nullable', 'string', 'url', 'max:500'],
            'evolution_api.key' => ['nullable', 'string', 'max:500'],
            'evolution_api.instance_name' => ['sometimes', 'string', 'max:100'],
            'evolution_api.phone_number' => ['sometimes', 'string', 'max:20'],
        ];
    }

    /**
     * SEO settings validation rules
     */
    protected function seoRules(): array
    {
        return [
            'meta_keywords' => ['sometimes', 'string', 'max:1000'],
            'meta_description' => ['sometimes', 'string', 'max:1000'],
            'google_analytics' => ['sometimes', 'string', 'max:5000'],
            'facebook_pixel' => ['sometimes', 'string', 'max:5000'],
        ];
    }

    /**
     * Maintenance settings validation rules
     */
    protected function maintenanceRules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'message' => ['sometimes', 'string', 'max:5000'],
            'secret_path' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /**
     * Appearance settings validation rules
     */
    protected function appearanceRules(): array
    {
        return [
            'base_color' => ['sometimes', 'string', 'regex:/^#?[0-9A-F]{6}$/i'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website_title.required' => 'Website title is required',
            'timezone.required' => 'Timezone is required',
            'currency.rate.min' => 'Currency rate must be greater than 0',
            'smtp.host.required_if' => 'SMTP host is required when SMTP is enabled',
            'smtp.port.required_if' => 'SMTP port is required when SMTP is enabled',
            'smtp.username.required_if' => 'SMTP username is required when SMTP is enabled',
            'smtp.encryption.required_if' => 'SMTP encryption is required when SMTP is enabled',
            'from.email.email' => 'Please provide a valid from email address',
            'to.email.email' => 'Please provide a valid to email address',
        ];
    }
}

