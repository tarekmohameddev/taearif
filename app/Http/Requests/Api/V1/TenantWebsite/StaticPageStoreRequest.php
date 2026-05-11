<?php

namespace App\Http\Requests\Api\V1\TenantWebsite;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\TenantStaticPage;
use Illuminate\Validation\Rule;

class StaticPageStoreRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_id' => ['required', 'string', Rule::in(TenantStaticPage::DASHBOARD_PAGE_IDS)],
            'components' => ['required', 'array'],
            'url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
