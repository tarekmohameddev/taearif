<?php

namespace App\Http\Requests\Api\Content;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateFooterSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|boolean',
            'general' => 'required|array',
            'general.companyName' => 'required|string|max:100',
            'general.address' => 'nullable|string|max:255',
            'general.phone' => 'nullable|string|max:255',
            'general.email' => 'nullable|email|max:100',
            'general.workingHours' => 'nullable|string|max:100',
            'general.showContactInfo' => 'boolean',
            'general.showWorkingHours' => 'boolean',
            'general.copyrightText' => 'nullable|string|max:255',
            'general.showCopyright' => 'boolean',
            'social' => 'required|array',
            'social.*.id' => 'required|string',
            'social.*.platform' => 'required|string|in:facebook,twitter,instagram,linkedin,youtube,snapchat,tiktok',
            'social.*.url' => 'nullable|string|max:255',
            'social.*.enabled' => 'boolean',
            'columns' => 'required|array',
            'columns.*.id' => 'required|string',
            'columns.*.title' => 'required|string|max:100',
            'columns.*.enabled' => 'boolean',
            'columns.*.links' => 'required|array',
            'columns.*.links.*.id' => 'required|string',
            'columns.*.links.*.text' => 'required|string|max:100',
            'columns.*.links.*.url' => 'required|string|max:255',
            'newsletter' => 'required|array',
            'newsletter.enabled' => 'boolean',
            'newsletter.title' => 'required|string|max:100',
            'newsletter.description' => 'nullable|string|max:255',
            'newsletter.buttonText' => 'required|string|max:50',
            'newsletter.placeholderText' => 'required|string|max:100',
            'style' => 'required|array',
            'style.layout' => 'required|string|in:full-width,contained',
            'style.backgroundColor' => 'required|string|max:20',
            'style.textColor' => 'required|string|max:20',
            'style.accentColor' => 'required|string|max:20',
            'style.columns' => 'required|integer|min:1|max:4',
            'style.showSocialIcons' => 'boolean',
            'style.socialIconsPosition' => 'required|string|in:top,bottom',
        ];
    }
}
