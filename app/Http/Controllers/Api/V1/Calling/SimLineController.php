<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallSimLine;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\Calling\SimLineResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimLineController extends BaseApiController
{
    /**
     * GET /api/v1/calling/sim-lines
     *
     * Read-only list of the tenant's assigned SIM lines / numbers.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenantOwnerId();

        $lines = CallSimLine::where('tenant_id', $tenantId)
            ->with('trunk:id,name,type,status')
            ->get();

        return $this->successResponse(SimLineResource::collection($lines));
    }
}
