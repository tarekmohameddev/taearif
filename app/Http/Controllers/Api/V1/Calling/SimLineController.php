<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Services\CallingLoopbackService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\Calling\SimLineResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimLineController extends BaseApiController
{
    protected CallingLoopbackService $loopback;

    public function __construct(CallingLoopbackService $loopback)
    {
        $this->loopback = $loopback;
    }

    /**
     * GET /api/v1/calling/sim-lines
     *
     * Read-only list of the tenant's assigned SIM lines / numbers.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenantOwnerId();

        if ($this->loopback->isEnabledForTenant($tenantId)) {
            $this->loopback->ensureDummyLine($tenantId);
        }

        $lines = CallSimLine::where('tenant_id', $tenantId)
            ->with('trunk:id,name,type,status')
            ->get();

        return $this->successResponse(SimLineResource::collection($lines));
    }
}
