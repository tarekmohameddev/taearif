<?php

namespace App\Http\Requests\Api\V1\TenantWebsite;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Api\V1\TenantWebsite\Concerns\ValidatesPageSeoMeta;
use App\Services\TenantWebsite\PageSeoService;
use Illuminate\Contracts\Validation\Validator;

class PageSeoUpdateRequest extends BaseApiFormRequest
{
    use ValidatesPageSeoMeta;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->metaFieldRules(partial: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function metaChanges(): array
    {
        $changes = [];

        foreach (PageSeoService::META_FIELD_KEYS as $key) {
            if ($this->exists($key)) {
                $changes[$key] = $this->input($key);
            }
        }

        return $changes;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->metaChanges() === []) {
                $validator->errors()->add('body', 'At least one SEO field must be provided.');
            }
        });
    }
}
