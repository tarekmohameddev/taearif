<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Services\ReportExportService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Reports\ReportExportRequest;
use App\Traits\HandlesApiExceptions;

class ReportExportController extends BaseApiController
{
    use HandlesApiExceptions;

    public function __construct(protected ReportExportService $service) {}

    public function export(ReportExportRequest $request, string $group)
    {
        $userId = $request->attributes->get('report_user_id');
        $format = $request->input('format', 'excel');
        $filter = ReportFilters::fromRequest($request);

        try {
            return $this->service->download($group, $format, $userId, $filter);
        } catch (\Throwable $e) {
            return $this->fail('Export failed: ' . $e->getMessage(), 500);
        }
    }
}
