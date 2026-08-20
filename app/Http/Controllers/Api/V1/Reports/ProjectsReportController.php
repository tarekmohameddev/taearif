<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Domain\Reports\DTOs\ReportDateFilter;
use App\Domain\Reports\Services\ProjectsReportService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Traits\HandlesApiExceptions;

class ProjectsReportController extends BaseApiController
{
    use HandlesApiExceptions;

    public function __construct(protected ProjectsReportService $service) {}

    public function summary(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->summary($userId, $filter));
        }, 'retrieve projects report summary');
    }

    public function inquiriesTrend(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->inquiriesTrend($userId, $filter));
        }, 'retrieve projects inquiries trend');
    }

    public function statusDistribution(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->statusDistribution($userId));
        }, 'retrieve projects status distribution');
    }

    public function topByVisits(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->topByVisits($userId, $filter));
        }, 'retrieve top projects by visits');
    }

    public function projectsList(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            $page   = (int) $request->input('page', 1);
            $limit  = (int) $request->input('limit', 20);
            return $this->success($this->service->projectsList($userId, $filter, $page, $limit));
        }, 'retrieve projects list');
    }
}
