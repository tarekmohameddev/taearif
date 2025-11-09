<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Crm\Services\LeadService;
use App\Domain\Crm\Services\LeadActivityService;
use App\Http\Requests\Admin\Crm\StoreLeadRequest;
use App\Http\Requests\Admin\Crm\UpdateLeadRequest;
use App\Http\Requests\Admin\Crm\MoveLeadRequest;
use App\Http\Requests\Admin\Crm\ConvertLeadRequest;
use App\Http\Requests\Admin\Crm\StoreLeadActivityRequest;
use App\Http\Requests\Admin\Crm\UpdateLeadActivityRequest;
use App\Http\Resources\Admin\LeadResource;
use App\Http\Resources\Admin\LeadCollection;
use App\Http\Resources\Admin\LeadActivityResource;
use App\Http\Resources\Admin\LeadActivityCollection;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Lead Controller
 * 
 * Handles lead management endpoints
 */
class LeadController extends BaseController
{
    /**
     * @var LeadService
     */
    protected $leadService;

    /**
     * @var LeadActivityService
     */
    protected $activityService;

    /**
     * LeadController constructor.
     *
     * @param LeadService $leadService
     * @param LeadActivityService $activityService
     */
    public function __construct(
        LeadService $leadService,
        LeadActivityService $activityService
    ) {
        $this->leadService = $leadService;
        $this->activityService = $activityService;
    }

    /**
     * Get paginated list of leads
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'source',
                'stage_id',
                'assigned_admin_id',
                'start_date',
                'end_date',
                'order_by',
                'order_dir',
            ]);

            $perPage = min($request->input('per_page', 20), 100);
            $leads = $this->leadService->getLeads($filters, $perPage);

            return $this->successResponse(
                new LeadCollection($leads),
                'Leads retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve leads.');
        }
    }

    /**
     * Create a new lead
     * 
     * @param StoreLeadRequest $request
     * @return JsonResponse
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Set the admin who created the lead
            if (!isset($data['assigned_admin_id'])) {
                $data['assigned_admin_id'] = auth('admin-sanctum')->id();
            }

            $lead = $this->leadService->createLead($data);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead created successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create lead.');
        }
    }

    /**
     * Get lead by UUID
     * 
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $lead = $this->leadService->getLeadByUuid($uuid);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve lead.');
        }
    }

    /**
     * Update existing lead
     * 
     * @param UpdateLeadRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(UpdateLeadRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $request->validated();
            $lead = $this->leadService->updateLead($uuid, $data);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update lead.');
        }
    }

    /**
     * Delete lead
     * 
     * @param string $uuid
     * @return JsonResponse
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->leadService->deleteLead($uuid);

            return $this->successResponse(
                null,
                'Lead deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete lead.');
        }
    }

    /**
     * Move lead to different stage
     * 
     * @param MoveLeadRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function moveStage(MoveLeadRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $request->validated();
            $lead = $this->leadService->moveToStage(
                $uuid,
                $data['stage_id'],
                $data['status'] ?? null
            );

            return $this->successResponse(
                new LeadResource($lead),
                'Lead moved to new stage successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to move lead to new stage.');
        }
    }

    /**
     * Convert lead to user/tenant
     * 
     * @param ConvertLeadRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function convert(ConvertLeadRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $request->validated();
            $lead = $this->leadService->convertLead(
                $uuid,
                $data['user_uuid'],
                $data['notes'] ?? null
            );

            return $this->successResponse(
                new LeadResource($lead),
                'Lead converted to user successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to convert lead.');
        }
    }

    /**
     * Get activities for a lead
     * 
     * @param string $uuid
     * @return JsonResponse
     */
    public function activities(string $uuid): JsonResponse
    {
        try {
            $activities = $this->activityService->getLeadActivities($uuid);

            return $this->successResponse(
                new LeadActivityCollection($activities),
                'Lead activities retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve lead activities.');
        }
    }

    /**
     * Create activity for a lead
     * 
     * @param StoreLeadActivityRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function storeActivity(StoreLeadActivityRequest $request, string $uuid): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['admin_id'] = auth('admin-sanctum')->id();

            $activity = $this->activityService->createActivity($uuid, $data);

            return $this->successResponse(
                new LeadActivityResource($activity),
                'Activity created successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create lead activity.');
        }
    }

    /**
     * Update lead activity
     * 
     * @param UpdateLeadActivityRequest $request
     * @param string $uuid
     * @param int $activityId
     * @return JsonResponse
     */
    public function updateActivity(UpdateLeadActivityRequest $request, string $uuid, int $activityId): JsonResponse
    {
        try {
            $data = $request->validated();
            $activity = $this->activityService->updateActivity($activityId, $data);

            return $this->successResponse(
                new LeadActivityResource($activity),
                'Activity updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update lead activity.');
        }
    }

    /**
     * Delete lead activity
     * 
     * @param string $uuid
     * @param int $activityId
     * @return JsonResponse
     */
    public function destroyActivity(string $uuid, int $activityId): JsonResponse
    {
        try {
            $this->activityService->deleteActivity($activityId);

            return $this->successResponse(
                null,
                'Activity deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete lead activity.');
        }
    }

    /**
     * Centralized error handling for CRM endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY,
                ['error_code' => $e->getErrorCode()]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'CRM_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

