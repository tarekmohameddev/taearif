<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Domain\Reports\DTOs\ReportDateFilter;
use App\Domain\Reports\Services\PropertiesReportService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Traits\HandlesApiExceptions;

class PropertiesReportController extends BaseApiController
{
    use HandlesApiExceptions;

    public function __construct(protected PropertiesReportService $service) {}

    public function summary(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->summary($userId, $filter));
        }, 'retrieve properties report summary');
    }

    public function priceDistribution(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->priceDistribution($userId));
        }, 'retrieve properties price distribution');
    }

    public function byCity(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->byCity($userId));
        }, 'retrieve properties by city');
    }

    public function byType(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->byType($userId));
        }, 'retrieve properties by type');
    }

    public function viewsTrend(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->viewsTrend($userId, $filter));
        }, 'retrieve properties views trend');
    }

    public function featuredComparison(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->featuredComparison($userId, $filter));
        }, 'retrieve properties featured comparison');
    }

    public function importHistory(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->importHistory($userId, $filter));
        }, 'retrieve properties import history');
    }

    public function topListings(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->topListings($userId, $filter));
        }, 'retrieve top property listings');
    }

    public function agentPerformance(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId  = $request->attributes->get('report_user_id');
            $actorId = $request->attributes->get('report_scope') === 'self'
                ? $request->attributes->get('report_actor_id')
                : null;
            $filter  = ReportDateFilter::fromRequest($request);
            $page    = (int) $request->input('page', 1);
            $limit   = (int) $request->input('limit', 20);
            return $this->success($this->service->agentPerformance($userId, $filter, $page, $limit, $actorId));
        }, 'retrieve properties agent performance');
    }
}
