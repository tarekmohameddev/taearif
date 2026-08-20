<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Domain\Reports\DTOs\ReportDateFilter;
use App\Domain\Reports\Services\CustomersReportService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Traits\HandlesApiExceptions;

class CustomersReportController extends BaseApiController
{
    use HandlesApiExceptions;

    public function __construct(protected CustomersReportService $service) {}

    public function summary(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->summary($userId, $filter));
        }, 'retrieve customers report summary');
    }

    public function requestsBySource(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->requestsBySource($userId, $filter));
        }, 'retrieve customers requests by source');
    }

    public function pipelineFunnel(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->pipelineFunnel($userId, $filter));
        }, 'retrieve customers pipeline funnel');
    }

    public function dailyNewCustomers(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->dailyNewCustomers($userId, $filter));
        }, 'retrieve daily new customers');
    }

    public function lifecycleDistribution(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->lifecycleDistribution($userId));
        }, 'retrieve customers lifecycle distribution');
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
        }, 'retrieve customers agent performance');
    }

    public function topDeals(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->topDeals($userId, $filter));
        }, 'retrieve top deals');
    }
}
