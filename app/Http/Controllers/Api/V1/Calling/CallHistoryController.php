<?php

namespace App\Http\Controllers\Api\V1\Calling;

use App\Domain\Calling\Models\CallLog;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\V1\Calling\CallLogResource;
use App\Models\ApiCustomer;
use App\Services\AlibabaOssService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallHistoryController extends BaseApiController
{
    /**
     * GET /api/v1/calling/calls
     *
     * With calling.view_history: own calls only.
     * With calling.view_all_history: all tenant calls.
     */
    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $tenantId = $user->tenantOwnerId();

        $query = CallLog::where('tenant_id', $tenantId)
            ->with(['customer:id,name,phone_number', 'agent:id,first_name,last_name,username,company_name', 'recording'])
            ->latest();

        // Scope to own calls unless user has view_all_history
        if (!$user->can('calling.view_all_history')) {
            $query->where('user_id', $user->id);
        }

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $logs    = $query->paginate($perPage);

        return $this->successResponse(
            CallLogResource::collection($logs)->response()->getData(true)
        );
    }

    /**
     * GET /api/v1/calling/customers/{customer}/calls
     *
     * Call history for a specific customer, tenant-scoped.
     * Works even for soft-deleted customers (uses withTrashed).
     */
    public function forCustomer(Request $request, int $customerId): JsonResponse
    {
        $tenantId = $request->user()->tenantOwnerId();

        // Verify the customer belongs to this tenant
        $customer = ApiCustomer::withTrashed()
            ->where('id', $customerId)
            ->where('user_id', $tenantId)
            ->first();

        if (!$customer) {
            return $this->errorResponse('Customer not found.', 404);
        }

        $logs = CallLog::where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->with(['agent:id,first_name,last_name,username,company_name', 'recording'])
            ->latest()
            ->paginate(min((int) $request->input('per_page', 20), 100));

        return $this->successResponse(
            CallLogResource::collection($logs)->response()->getData(true)
        );
    }

    /**
     * GET /api/v1/calling/calls/{id}/recording-url
     *
     * Signed temporary URL for playback (OSS disk).
     */
    public function recordingUrl(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenantOwnerId();

        $log = CallLog::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('recording')
            ->first();

        if (!$log || !$log->recording) {
            return $this->errorResponse('Recording not found.', 404);
        }

        if (!$log->recording->isReady()) {
            return $this->errorResponse('Recording is not yet available.', 422);
        }

        $ttl = (int) config('calling.recordings.url_ttl_minutes', 30);
        $url = app(AlibabaOssService::class)->signedUrl(
            $log->recording->path,
            $ttl * 60
        );

        return $this->successResponse([
            'url'        => $url,
            'expires_in' => $ttl * 60, // seconds
        ]);
    }
}
