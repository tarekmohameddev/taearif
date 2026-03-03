<?php

namespace App\Http\Requests\Api\Content;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateApiMenuSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'menuItems' => 'required|array',
            'menuItems.*.id' => 'required|integer',
            'menuItems.*.label' => 'required|string',
            'menuItems.*.url' => 'required|string',
            'menuItems.*.isExternal' => 'required|boolean',
            'menuItems.*.isActive' => 'required|boolean',
            'menuItems.*.order' => 'required|integer',
            'menuItems.*.parentId' => 'nullable|integer',
            'menuItems.*.showOnMobile' => 'required|boolean',
            'menuItems.*.showOnDesktop' => 'required|boolean',
            'settings' => 'required|array',
            'settings.menuPosition' => 'nullable|string|in:top,bottom,left,right',
            'settings.menuStyle' => 'nullable|string|in:buttons,underline,minimal,standard,default',
            'settings.mobileMenuType' => 'nullable|string|in:hamburger,sidebar,fullscreen',
            'settings.isSticky' => 'nullable|boolean',
            'settings.isTransparent' => 'nullable|boolean',
        ];
    }
}
