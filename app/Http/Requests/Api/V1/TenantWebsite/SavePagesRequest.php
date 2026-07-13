<?php

namespace App\Http\Requests\Api\V1\TenantWebsite;

use Illuminate\Foundation\Http\FormRequest;

class SavePagesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tenantId' => 'required|string',
            'pages' => 'sometimes|array',
            'componentSettings' => 'sometimes|array',
            'globalComponentsData' => 'nullable|array',
            'WebsiteLayout' => 'nullable|array',
            'ThemesBackup' => 'nullable|array',
            'StaticPages' => 'sometimes|array',
            'branding' => 'sometimes|array',
            'branding.websiteBranding' => 'nullable|array',
            'loginSessionMeta' => 'sometimes|array',
            'loginSessionMeta.loginSource' => 'sometimes|nullable|string',
            'loginSessionMeta.loginIp' => 'sometimes|nullable|string',
            'loginSessionMeta.isDevelopment' => 'sometimes|nullable|boolean',
            'loginSessionMeta.isLocalhost' => 'sometimes|nullable|boolean',
            'loginSessionMeta.loginAt' => 'sometimes|nullable|string',
            'loginSessionMeta.loginAtMs' => 'sometimes|nullable|numeric',
        ];
    }
}
