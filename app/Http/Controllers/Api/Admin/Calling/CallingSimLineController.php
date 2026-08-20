<?php

namespace App\Http\Controllers\Api\Admin\Calling;

use App\Domain\Calling\Models\CallSimLine;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Calling\StoreSimLineRequest;
use App\Http\Requests\Admin\Calling\UpdateSimLineRequest;
use App\Http\Resources\Admin\Calling\SimLineAdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallingSimLineController extends BaseController
{
    /**
     * GET /api/v1/admin/calling/sim-lines
     */
    public function index(Request $request): JsonResponse
    {
        $query = CallSimLine::with(['tenant:id,first_name,last_name,email,username,company_name', 'trunk:id,name,type,status', 'dedicatedAgent:id,first_name,last_name,username,company_name']);

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }
        if ($request->filled('trunk_id')) {
            $query->where('trunk_id', $request->input('trunk_id'));
        }

        $lines = $query->paginate(min((int) $request->input('per_page', 20), 100));

        return $this->successResponse(SimLineAdminResource::collection($lines)->response()->getData(true));
    }

    /**
     * GET /api/v1/admin/calling/sim-lines/{id}
     */
    public function show(int $id): JsonResponse
    {
        $line = CallSimLine::with(['tenant:id,first_name,last_name,email,username,company_name', 'trunk:id,name,type,status', 'dedicatedAgent:id,first_name,last_name,username,company_name'])->findOrFail($id);

        return $this->successResponse(new SimLineAdminResource($line));
    }

    /**
     * PUT /api/v1/admin/calling/sim-lines/{id}
     *
     * Update label, msisdn, dedicated agent, or active flag.
     */
    public function update(UpdateSimLineRequest $request, int $id): JsonResponse
    {
        $line = CallSimLine::findOrFail($id);
        $line->update($request->validated());

        return $this->successResponse(new SimLineAdminResource($line), 'SIM line updated.');
    }

    /**
     * POST /api/v1/admin/calling/sim-lines/{id}/toggle
     *
     * Toggle is_active for this line.
     */
    public function toggle(int $id): JsonResponse
    {
        $line = CallSimLine::findOrFail($id);
        $line->update(['is_active' => !$line->is_active]);

        return $this->successResponse(new SimLineAdminResource($line), 'SIM line toggled.');
    }
}
