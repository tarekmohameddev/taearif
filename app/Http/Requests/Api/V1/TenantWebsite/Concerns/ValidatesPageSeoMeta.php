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
            'TitleAr' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'TitleEn' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'DescriptionAr' => $this->fieldRules($partial, ['nullable', 'string', 'max:1000']),
            'DescriptionEn' => $this->fieldRules($partial, ['nullable', 'string', 'max:1000']),
            'KeywordsAr' => $this->fieldRules($partial, ['nullable', 'string', 'max:1000']),
            'KeywordsEn' => $this->fieldRules($partial, ['nullable', 'string', 'max:1000']),
            'Author' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'AuthorEn' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'Robots' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'RobotsEn' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'og:title' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'og:description' => $this->fieldRules($partial, ['nullable', 'string', 'max:1000']),
            'og:keywords' => $this->fieldRules($partial, ['nullable', 'string', 'max:1000']),
            'og:author' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'og:robots' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'og:url' => $this->fieldRules($partial, ['nullable', 'string', 'max:2048']),
            'og:image' => $this->fieldRules($partial, ['nullable', 'string', 'max:2048']),
            'og:type' => $this->fieldRules($partial, ['nullable', 'string', Rule::in(PageSeoService::OG_TYPES)]),
            'og:locale' => $this->fieldRules($partial, ['nullable', 'string', 'max:32']),
            'og:locale:alternate' => $this->fieldRules($partial, ['nullable', 'string', 'max:32']),
            'og:site_name' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
            'og:image:width' => $this->fieldRules($partial, ['nullable']),
            'og:image:height' => $this->fieldRules($partial, ['nullable']),
            'og:image:type' => $this->fieldRules($partial, ['nullable', 'string', 'max:64']),
            'og:image:alt' => $this->fieldRules($partial, ['nullable', 'string', 'max:255']),
        ]);
    }

    /**
     * @param  list<mixed>  $rules
     * @return list<mixed>
     */
    protected function fieldRules(bool $partial, array $rules): array
    {
        return $partial ? array_merge(['sometimes'], $rules) : $rules;
    }
}
