<?php

namespace App\Http\Requests\Api\V1\TenantWebsite;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Api\V1\TenantWebsite\Concerns\ValidatesPageSeoMeta;

class PageSeoStoreRequest extends BaseApiFormRequest
{
    use ValidatesPageSeoMeta;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->metaFieldRules(partial: false);
    }
}
