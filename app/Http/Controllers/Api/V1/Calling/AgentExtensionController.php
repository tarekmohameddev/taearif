<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Services\SipProvisioningService;
use App\Domain\Calling\Exceptions\CallingModuleDisabledException;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\Calling\AgentExtensionResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AgentExtensionController extends BaseApiController
{
    protected SipProvisioningService $provisioning;

    public function __construct(SipProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    /**
     * GET /api/v1/calling/extensions
     *
     * List active extensions for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenantOwnerId();

        $extensions = CallAgentExtension::where('tenant_id', $tenantId)
            ->with('user:id,first_name,last_name,username,email,company_name')
            ->get();

        return $this->successResponse(AgentExtensionResource::collection($extensions));
    }

    /**
     * POST /api/v1/calling/extensions/{user}
     *
     * Enable calling for an employee (or the tenant owner themselves).
     * Requires calling.manage_agents permission (or owner bypass via OwnerOrCan middleware).
     */
    public function provision(Request $request, int $userId): JsonResponse
    {
        $requester = $request->user();
        $tenantId  = $requester->tenantOwnerId();

        $targetUser = User::where('id', $userId)
            ->where(function ($q) use ($tenantId) {
                $q->where('id', $tenantId) // the owner themselves
                  ->orWhere('tenant_id', $tenantId); // their employees
            })
            ->firstOrFail();

        try {
            $ext = $this->provisioning->provisionAgent($targetUser);
            return $this->successResponse(new AgentExtensionResource($ext), 'Extension provisioned.', 201);
        } catch (CallingModuleDisabledException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to provision extension: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/v1/calling/extensions/{user}
     *
     * Disable calling for an employee.
     */
    public function deprovision(Request $request, int $userId): JsonResponse
    {
        $requester = $request->user();
        $tenantId  = $requester->tenantOwnerId();

        $targetUser = User::where('id', $userId)
            ->where(function ($q) use ($tenantId) {
                $q->where('id', $tenantId)
                  ->orWhere('tenant_id', $tenantId);
            })
            ->firstOrFail();

        try {
            $this->provisioning->deprovisionAgent($targetUser);
            return $this->successResponse(null, 'Extension deprovisioned.');
        } catch (Throwable $e) {
            return $this->errorResponse('Failed to deprovision extension: ' . $e->getMessage(), 500);
        }
    }
}
