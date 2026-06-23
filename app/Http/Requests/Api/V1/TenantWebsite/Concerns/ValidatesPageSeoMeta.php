<?php

namespace App\Http\Requests\Api\V1\TenantWebsite\Concerns;

use App\Services\TenantWebsite\PageSeoService;
use Illuminate\Validation\Rule;

trait ValidatesPageSeoMeta
{
    /**
     * @return array<string, mixed>
     */
    protected function metaFieldRules(bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes|' : '';

        $identityRules = $partial
            ? [
                'path' => ['sometimes', 'nullable', 'string', 'max:255'],
                'page_key' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z0-9_-]+$/'],
            ]
            : [
                'path' => ['nullable', 'string', 'max:255', 'required_without:page_key'],
                'page_key' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]+$/', 'required_without:path'],
            ];

        return array_merge($identityRules, [
            'TitleAr' => [$prefix.'nullable', 'string', 'max:255'],
            'TitleEn' => [$prefix.'nullable', 'string', 'max:255'],
            'DescriptionAr' => [$prefix.'nullable', 'string', 'max:1000'],
            'DescriptionEn' => [$prefix.'nullable', 'string', 'max:1000'],
            'KeywordsAr' => [$prefix.'nullable', 'string', 'max:1000'],
            'KeywordsEn' => [$prefix.'nullable', 'string', 'max:1000'],
            'Author' => [$prefix.'nullable', 'string', 'max:255'],
            'AuthorEn' => [$prefix.'nullable', 'string', 'max:255'],
            'Robots' => [$prefix.'nullable', 'string', 'max:255'],
            'RobotsEn' => [$prefix.'nullable', 'string', 'max:255'],
            'og:title' => [$prefix.'nullable', 'string', 'max:255'],
            'og:description' => [$prefix.'nullable', 'string', 'max:1000'],
            'og:keywords' => [$prefix.'nullable', 'string', 'max:1000'],
            'og:author' => [$prefix.'nullable', 'string', 'max:255'],
            'og:robots' => [$prefix.'nullable', 'string', 'max:255'],
            'og:url' => [$prefix.'nullable', 'string', 'max:2048'],
            'og:image' => [$prefix.'nullable', 'string', 'max:2048'],
            'og:type' => [$prefix.'nullable', 'string', Rule::in(PageSeoService::OG_TYPES)],
            'og:locale' => [$prefix.'nullable', 'string', 'max:32'],
            'og:locale:alternate' => [$prefix.'nullable', 'string', 'max:32'],
            'og:site_name' => [$prefix.'nullable', 'string', 'max:255'],
            'og:image:width' => [$prefix.'nullable'],
            'og:image:height' => [$prefix.'nullable'],
            'og:image:type' => [$prefix.'nullable', 'string', 'max:64'],
            'og:image:alt' => [$prefix.'nullable', 'string', 'max:255'],
        ]);
    }
}
