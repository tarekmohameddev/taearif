<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Crm\Services\LeadService;
use App\Http\Resources\Admin\CrmOverviewResource;
use Illuminate\Http\JsonResponse;

/**
 * CRM Controller
 * 
 * Handles CRM overview and dashboard endpoints
 */
class CrmController extends BaseController
{
    /**
     * @var LeadService
     */
    protected $leadService;

    /**
     * CrmController constructor.
     *
     * @param LeadService $leadService
     */
    public function __construct(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    /**
     * Get CRM overview/dashboard
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $overview = $this->leadService->getCrmOverview();

            return $this->successResponse(
                new CrmOverviewResource($overview),
                'CRM overview retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

