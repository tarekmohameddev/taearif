<?php

namespace App\Http\Controllers\Api\V1\Logs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Logs\PropertyLog;
use App\Http\Controllers\Api\V1\Logs\Concerns\BuildsLogResponses;

class PropertyLogController extends Controller
{
    use BuildsLogResponses;

    public function index(Request $request, int $id)
    {
        $tenantId = $this->resolveTenantId($request);

        $paginator = PropertyLog::where('tenant_id', $tenantId)
            ->where('property_id', $id)
            ->orderByDesc('id')
            ->paginate(max(1, min(100, (int) $request->integer('per_page', 20))));

        return $this->respondWithLogs($paginator);
    }
}
