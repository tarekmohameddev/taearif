<?php

namespace App\Http\Requests\Api\Content;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateBannerSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'banner_type' => 'required|string|in:static,slider',
            'status' => 'required|boolean',
            'static' => 'required|array',
            'static.enabled' => 'boolean',
            'static.image' => 'nullable|string',
            'static.title' => 'nullable|string|max:200',
            'static.subtitle' => 'nullable|string|max:500',
            'static.caption' => 'nullable|string|max:500',
            'static.buttonText' => 'nullable|string|max:50',
            'static.buttonUrl' => 'nullable|string|max:255',
            'static.buttonStyle' => 'string|in:primary,secondary,outline,link',
            'static.textAlignment' => 'string|in:left,center,right',
            'static.overlayColor' => 'nullable|string|max:30',
            'static.textColor' => 'nullable|string|max:30',
            'slider' => 'required|array',
            'slider.enabled' => 'boolean',
            'slider.slides' => 'array',
            'slider.slides.*.id' => 'required|string',
            'slider.slides.*.image' => 'nullable|string',
            'slider.slides.*.title' => 'nullable|string|max:200',
            'slider.slides.*.subtitle' => 'nullable|string|max:500',
            'slider.slides.*.caption' => 'nullable|string|max:500',
            'slider.slides.*.buttonText' => 'nullable|string|max:50',
            'slider.slides.*.buttonUrl' => 'nullable|string|max:255',
            'slider.slides.*.buttonStyle' => 'string|in:primary,secondary,outline,link',
            'slider.slides.*.textAlignment' => 'string|in:left,center,right',
            'slider.slides.*.enabled' => 'boolean',
            'slider.autoplay' => 'boolean',
            'slider.autoplaySpeed' => 'integer|min:1000|max:10000',
            'slider.showArrows' => 'boolean',
            'slider.showDots' => 'boolean',
            'slider.animation' => 'string|in:fade,slide',
            'slider.overlayColor' => 'nullable|string|max:30',
            'slider.textColor' => 'nullable|string|max:30',
            'common' => 'required|array',
            'common.height' => 'string|in:small,medium,large,full',
            'common.showSearchBox' => 'boolean',
            'common.searchBoxPosition' => 'string|in:left,center,right',
            'common.responsive' => 'boolean',
            'common.fullWidth' => 'boolean',
        ];
    }
}
