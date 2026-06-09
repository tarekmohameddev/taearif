<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Logs\Concerns\BuildsLogResponses;
use App\Http\Requests\Api\Property\BulkCreatePropertiesRequest;
use App\Http\Requests\Api\Property\ChangePropertyStatusRequest;
use App\Http\Requests\Api\Property\StoreArchiveItemRequest;
use App\Http\Requests\Api\Property\StoreInternalNoteRequest;
use App\Http\Requests\Api\Property\StorePropertyCrmRelationRequest;
use App\Http\Resources\Api\PropertyCrmRelationResource;
use App\Http\Resources\Api\PropertyDocumentResource;
use App\Models\Logs\PropertyLog;
use App\Models\Property\BulkImportBatch;
use App\Models\User\RealestateManagement\Property;
use App\Services\Property\BulkPropertyImportService;
use App\Services\Property\PropertyCrmRelationService;
use App\Services\Property\PropertyDocumentService;
use App\Services\Property\PropertyStatusChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PropertyManagementController extends Controller
{
    use BuildsLogResponses;

    public function __construct(
        private readonly PropertyStatusChangeService $statusChangeService,
        private readonly PropertyDocumentService $documentService,
        private readonly PropertyCrmRelationService $crmRelationService,
        private readonly BulkPropertyImportService $bulkImportService,
    ) {}

    public function changeStatus(ChangePropertyStatusRequest $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $property = $this->resolveProperty($id);

        $result = $this->statusChangeService->changeStatus(
            $property,
            $request->input('unit_status'),
            $request->input('reason'),
            $request->input('customer_id'),
            $user->id,
        );

        $property = $result['property'];

        $data = [
            'status' => (int) $property->status,
            'unit_status' => $property->unit_status,
            'property_status' => $property->property_status,
            'listing_purpose' => $property->listing_purpose,
            'publish_status' => $property->publish_status,
            'customer' => $result['customer'],
        ];

        if ($result['crm'] !== null) {
            $data['crm'] = [
                'success' => $result['crm']['success'],
                'closed_requests' => $result['crm']['closed_requests'],
                'closed_customers' => $result['crm']['closed_customers'],
                'warnings' => $result['crm']['warnings'],
            ];
            if (! empty($result['crm']['errors'])) {
                $data['crm']['errors'] = $result['crm']['errors'];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    public function auditLogs(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user->can('properties.view_audit_log') && $user->account_type !== 'tenant') {
            abort(403, 'Unauthorized to view audit logs.');
        }

        $this->resolveProperty($id);

        $paginator = PropertyLog::where('property_id', $id)
            ->orderByDesc('id')
            ->paginate(max(1, min(100, (int) $request->integer('per_page', 20))));

        return $this->respondWithLogs($paginator);
    }

    public function internalNotes(Request $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);
        $paginator = $this->documentService->listNotes($property, (int) $request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => PropertyDocumentResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeInternalNote(StoreInternalNoteRequest $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);
        $attachments = $request->file('attachments', []);

        $doc = $this->documentService->storeNote(
            $property,
            $request->input('note'),
            $attachments,
            Auth::id(),
        );

        return response()->json([
            'status' => 'success',
            'data' => new PropertyDocumentResource($doc->load('author')),
        ], 201);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);
        $paginator = $this->documentService->listArchive($property, (int) $request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => PropertyDocumentResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeArchiveItem(StoreArchiveItemRequest $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);

        $doc = $this->documentService->storeArchiveItem(
            $property,
            $request->input('type'),
            $request->input('title'),
            $request->input('content'),
            $request->file('attachments', []),
            $request->input('meta'),
            Auth::id(),
        );

        return response()->json([
            'status' => 'success',
            'data' => new PropertyDocumentResource($doc->load('author')),
        ], 201);
    }

    public function crmCounters(int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->crmRelationService->counters($property->id),
        ]);
    }

    public function crmRelations(Request $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);
        $paginator = $this->crmRelationService->listRelations($property->id, (int) $request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => PropertyCrmRelationResource::collection($paginator),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeCrmRelation(StorePropertyCrmRelationRequest $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);

        $relation = $this->crmRelationService->manuallyAdd(
            $property,
            (int) $request->input('request_id'),
            Auth::id(),
            $request->input('customer_id'),
        );

        return response()->json([
            'status' => 'success',
            'data' => new PropertyCrmRelationResource($relation->load(['request.customer', 'employee'])),
        ], 201);
    }

    public function bulkCreate(BulkCreatePropertiesRequest $request): JsonResponse
    {
        $user = Auth::user();
        $batch = $this->bulkImportService->createTableBatch(
            $user->id,
            $request->input('units'),
            $request->input('project_id'),
            $request->input('building_id'),
            $request->input('publish_status', 'draft'),
        );

        return response()->json([
            'status' => 'success',
            'data' => ['batch_id' => $batch->id],
        ], 201);
    }

    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'project_id' => 'nullable|integer',
            'building_id' => 'nullable|integer',
            'publish_status' => 'nullable|in:draft,published',
        ]);

        $user = Auth::user();
        $batch = $this->bulkImportService->createExcelPreviewBatch(
            $user->id,
            $request->file('file'),
            $request->input('project_id'),
            $request->input('building_id'),
            $request->input('publish_status', 'draft'),
        );

        return response()->json([
            'status' => 'success',
            'data' => ['batch_id' => $batch->id],
        ], 201);
    }

    public function importPreview(int $batchId): JsonResponse
    {
        $batch = $this->resolveBatch($batchId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'batch_id' => $batch->id,
                'status' => $batch->status,
                'total' => $batch->total,
                'rows' => $batch->preview_data,
            ],
        ]);
    }

    public function importApply(int $batchId): JsonResponse
    {
        $batch = $this->resolveBatch($batchId);
        $this->bulkImportService->applyBatch($batch);

        return response()->json([
            'status' => 'success',
            'data' => ['batch_id' => $batch->id, 'status' => 'processing'],
        ]);
    }

    public function importReport(int $batchId): JsonResponse
    {
        $batch = $this->resolveBatch($batchId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'batch_id' => $batch->id,
                'status' => $batch->status,
                'total' => $batch->total,
                'succeeded' => $batch->succeeded,
                'failed' => $batch->failed,
                'rows' => $batch->report['rows'] ?? [],
            ],
        ]);
    }

    private function resolveProperty(int $id): Property
    {
        $user = Auth::user();
        $allowedUserIds = $this->resolveAllowedUserIds($user);

        return Property::where('id', $id)->whereIn('user_id', $allowedUserIds)->firstOrFail();
    }

    /**
     * @return list<int>
     */
    private function resolveAllowedUserIds(\App\Models\User $user): array
    {
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $allowedUserIds = [$owner->id];

        try {
            $employeeIds = \App\Models\User::where('tenant_id', $owner->id)->pluck('id')->toArray();
            $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
        } catch (\Throwable $e) {
            // fall back to owner-only scoping
        }

        return array_values($allowedUserIds);
    }

    private function resolveBatch(int $batchId): BulkImportBatch
    {
        $user = Auth::user();

        return BulkImportBatch::where('id', $batchId)->where('user_id', $user->id)->firstOrFail();
    }
}
