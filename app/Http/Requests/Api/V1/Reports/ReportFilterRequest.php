<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reports;

use App\Http\Requests\Api\BaseApiFormRequest;

class ReportFilterRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preset'     => 'nullable|string|in:today,week,month,quarter,year,custom',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'page'       => 'nullable|integer|min:1',
            'limit'      => 'nullable|integer|min:1|max:200',
        ];
    }
}
