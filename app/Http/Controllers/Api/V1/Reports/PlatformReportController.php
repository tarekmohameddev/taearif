<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Domain\Reports\DTOs\ReportDateFilter;
use App\Domain\Reports\Services\PlatformReportService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Traits\HandlesApiExceptions;

class PlatformReportController extends BaseApiController
{
    use HandlesApiExceptions;

    public function __construct(protected PlatformReportService $service) {}

    public function summary(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->summary($userId, $filter));
        }, 'retrieve platform summary');
    }

    public function overviewRevenue(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->overviewRevenue($userId));
        }, 'retrieve platform revenue overview');
    }

    public function overviewPortfolio(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->overviewPortfolio($userId));
        }, 'retrieve platform property portfolio');
    }

    public function employees(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId  = $request->attributes->get('report_user_id');
            $actorId = $request->attributes->get('report_scope') === 'self'
                ? $request->attributes->get('report_actor_id')
                : null;
            $page    = (int) $request->input('page', 1);
            $limit   = (int) $request->input('limit', 20);
            return $this->success($this->service->employees($userId, $page, $limit, $actorId));
        }, 'retrieve platform employees');
    }

    public function geographicCities(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->geographicCities($userId));
        }, 'retrieve platform geographic cities');
    }

    public function geographicAreas(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->geographicAreas($userId));
        }, 'retrieve platform geographic areas');
    }

    public function reservationStatus(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->reservationStatus($userId));
        }, 'retrieve platform reservation status');
    }

    public function propertyDetails(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            $page   = (int) $request->input('page', 1);
            $limit  = (int) $request->input('limit', 20);
            return $this->success($this->service->propertyDetails($userId, $filter, $page, $limit));
        }, 'retrieve platform property details');
    }

    public function financialMonthly(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->financialMonthly($userId));
        }, 'retrieve platform financial monthly');
    }

    public function financialSummary(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->financialSummary($userId));
        }, 'retrieve platform financial summary');
    }

    public function performanceAlerts(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->performanceAlerts($userId, $filter));
        }, 'retrieve platform performance alerts');
    }
}
