<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Property\BulkCreatePropertiesRequest;
use App\Http\Requests\Api\Property\ImportExcelPropertiesRequest;
use App\Http\Requests\Api\Property\ChangePropertyStatusRequest;
use App\Http\Requests\Api\Property\StoreArchiveItemRequest;
use App\Http\Requests\Api\Property\StoreInternalNoteRequest;
use App\Http\Requests\Api\Property\StorePropertyCrmRelationRequest;
use App\Http\Resources\Api\PropertyCrmRelationResource;
use App\Http\Resources\Api\PropertyDocumentResource;
use App\Models\Property\BulkImportBatch;
use App\Models\Property\PropertyCrmRelation;
use App\Models\User\RealestateManagement\Property;
use App\Services\Property\BulkPropertyImportService;
use App\Services\Property\PropertyCrmRelationService;
use App\Services\Property\PropertyDocumentService;
use App\Services\Property\PropertyStatusChangeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PropertyManagementController extends Controller
{
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

    public function crmRelationsSummary(int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->crmRelationService->counters($property->id),
        ]);
    }

    /** @deprecated Use crmRelationsSummary — route /crm-relations/summary */
    public function crmCounters(int $id): JsonResponse
    {
        return $this->crmRelationsSummary($id);
    }

    public function crmRelations(Request $request, int $id): JsonResponse
    {
        $property = $this->resolveProperty($id);

        $validated = $request->validate([
            'relation_type' => [
                'nullable',
                Rule::in([
                    PropertyCrmRelation::TYPE_AI_MATCHED,
                    PropertyCrmRelation::TYPE_MANUALLY_ADDED,
                    PropertyCrmRelation::TYPE_SENT_TO_CUSTOMER,
                ]),
            ],
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $paginator = $this->crmRelationService->listRelations(
            $property->id,
            (int) ($validated['per_page'] ?? 20),
            $validated['relation_type'] ?? null,
        );

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
        try {
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
        } catch (ConflictHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function bulkCreate(BulkCreatePropertiesRequest $request): JsonResponse
    {
        $user = Auth::user();
        $ownerId = $user->tenantOwnerId();
        $publishStatus = $request->input('publish_status', 'draft');
        $units = $request->input('units');
        $autoApply = $request->boolean('auto_apply', true);

        $preview = $this->bulkImportService->buildTablePreview(
            $ownerId,
            $units,
            $request->input('project_id'),
            $request->input('building_id'),
            $publishStatus,
        );

        $report = $this->bulkImportService->buildInitialReportFromPreview(
            $preview,
            $autoApply ? 'pending' : 'pending',
        );

        if ($report['invalid'] === $report['total']) {
            return response()->json([
                'status' => 'error',
                'data' => $report,
            ], 422);
        }

        if ($autoApply && $report['valid'] > 0) {
            $limitError = $this->bulkImportService->membershipLimitError($ownerId, $report['valid']);
            if ($limitError !== null) {
                return response()->json($limitError, 403);
            }
        }

        $batch = $this->bulkImportService->createTableBatch(
            $ownerId,
            $units,
            $request->input('project_id'),
            $request->input('building_id'),
            $publishStatus,
            $user->id,
        );

        $report = $this->bulkImportService->buildInitialReport($batch);

        if ($autoApply && $report['valid'] > 0) {
            $this->bulkImportService->applyBatch($batch->fresh());
            $report['status'] = 'processing';
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge(['batch_id' => $batch->id], $report),
        ], 201);
    }

    public function importExcel(ImportExcelPropertiesRequest $request): JsonResponse
    {
        $user = Auth::user();
        $ownerId = $user->tenantOwnerId();
        $publishStatus = $request->input('publish_status', 'draft');
        $autoApply = $request->boolean('auto_apply', true);

        $preview = $this->bulkImportService->buildExcelPreview(
            $ownerId,
            $request->file('file'),
            $request->input('project_id'),
            $request->input('building_id'),
            $publishStatus,
        );

        $report = $this->bulkImportService->buildInitialReportFromPreview($preview);

        if ($report['total'] === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No data rows found in file.',
                'data' => $report,
            ], 422);
        }

        if ($report['invalid'] === $report['total']) {
            return response()->json([
                'status' => 'error',
                'data' => $report,
            ], 422);
        }

        if ($autoApply && $report['valid'] > 0) {
            $limitError = $this->bulkImportService->membershipLimitError($ownerId, $report['valid']);
            if ($limitError !== null) {
                return response()->json($limitError, 403);
            }
        }

        $batch = $this->bulkImportService->createExcelBatch(
            $ownerId,
            $preview,
            $request->input('project_id'),
            $request->input('building_id'),
            $publishStatus,
            $user->id,
        );

        $report = $this->bulkImportService->buildInitialReport($batch);

        if ($autoApply && $report['valid'] > 0) {
            $this->bulkImportService->applyBatch($batch->fresh());
            $report['status'] = 'processing';
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge(['batch_id' => $batch->id], $report),
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
            'data' => $this->bulkImportService->buildFinalReport($batch),
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
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        return BulkImportBatch::where('id', $batchId)->where('user_id', $owner->id)->firstOrFail();
    }
}
