<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Domain\Reports\DTOs\ReportDateFilter;
use App\Domain\Reports\Services\WhatsAppReportService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Traits\HandlesApiExceptions;

class WhatsAppReportController extends BaseApiController
{
    use HandlesApiExceptions;

    public function __construct(protected WhatsAppReportService $service) {}

    public function summary(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->summary($userId, $filter));
        }, 'retrieve WhatsApp report summary');
    }

    public function conversationVolume(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->conversationVolume($userId, $filter));
        }, 'retrieve WhatsApp conversation volume');
    }

    public function hourlyDistribution(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->hourlyDistribution($userId, $filter));
        }, 'retrieve WhatsApp hourly distribution');
    }

    public function campaignDelivery(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->campaignDelivery($userId, $filter));
        }, 'retrieve WhatsApp campaign delivery');
    }

    public function automationTriggers(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            return $this->success($this->service->automationTriggers($userId, $filter));
        }, 'retrieve WhatsApp automation triggers');
    }

    public function conversationStatus(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            return $this->success($this->service->conversationStatus($userId));
        }, 'retrieve WhatsApp conversation status distribution');
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
        }, 'retrieve WhatsApp agent performance');
    }

    public function numberPerformance(ReportFilterRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $userId = $request->attributes->get('report_user_id');
            $filter = ReportDateFilter::fromRequest($request);
            $page   = (int) $request->input('page', 1);
            $limit  = (int) $request->input('limit', 20);
            return $this->success($this->service->numberPerformance($userId, $filter, $page, $limit));
        }, 'retrieve WhatsApp number performance');
    }
}
