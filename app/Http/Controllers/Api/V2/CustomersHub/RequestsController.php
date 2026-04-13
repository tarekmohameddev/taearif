<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V2\CustomersHub\RequestsListRequest;
use App\Http\Requests\Api\V2\CustomersHub\RequestUpdateRequest;
use App\Http\Requests\Api\V2\CustomersHub\AddNoteRequest;
use App\Http\Requests\Api\V2\CustomersHub\CreateAppointmentRequest;
use App\Http\Requests\Api\V2\CustomersHub\CreateReminderRequest;
use App\Http\Requests\Api\V2\CustomersHub\BulkCompleteRequest;
use App\Http\Requests\Api\V2\CustomersHub\BulkDismissRequest;
use App\Http\Requests\Api\V2\CustomersHub\RequestsBulkRequest;
use App\Http\Requests\Api\V2\CustomersHub\DismissRequest;
use App\Http\Requests\Api\V2\CustomersHub\SnoozeRequest;
use App\Domain\CustomersHub\Services\ActionsAggregatorService;
use App\Domain\CustomersHub\Services\PropertyRequestDetailBuilder;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserPropertyRequest;
use App\Models\CustomersHub\CustomersHubStage;
use App\Models\User;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * RequestsController
 *
 * API endpoints for Customers Hub Requests Center.
 * Implements read-only aggregation from legacy tables.
 *
 * Routes:
 * - POST /api/v2/customers-hub/requests/list
 * - GET  /api/v2/customers-hub/requests/filter-options
 * - GET  /api/v2/customers-hub/requests/{requestId}
 * - PATCH /api/v2/customers-hub/requests/{requestId}
 * - POST /api/v2/customers-hub/requests/{requestId}/notes
 * - POST /api/v2/customers-hub/requests/{requestId}/complete
 * - POST /api/v2/customers-hub/requests/{requestId}/dismiss
 * - POST /api/v2/customers-hub/requests/bulk
 * - POST /api/v2/customers-hub/requests/bulk-complete
 * - POST /api/v2/customers-hub/requests/bulk-dismiss
 * - POST /api/v2/customers-hub/requests/mark-viewed
 */
class RequestsController extends ApiController
{
    private ActionsAggregatorService $aggregator;

    private PropertyRequestDetailBuilder $propertyRequestDetailBuilder;

    public function __construct(ActionsAggregatorService $aggregator, PropertyRequestDetailBuilder $propertyRequestDetailBuilder)
    {
        $this->aggregator = $aggregator;
        $this->propertyRequestDetailBuilder = $propertyRequestDetailBuilder;
    }

    /**
     * POST /api/v2/customers-hub/requests/list
     *
     * Get paginated list of customer actions with filtering.
     *
     * Status filtering: sending `tab` (e.g. `all`, `completed`) applies status rules in ActionsAggregatorService::applyFilters.
     * To include every Customers Hub status (pending through completed/dismissed, etc.), omit both `tab` and `statuses`.
     * To limit to property requests only while keeping all statuses, omit `tab` and `statuses` and pass `objectTypes: ["property_request"]`
     * (and/or `types: ["property_match"]` per your UI).
     */
    public function list(RequestsListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $filters = $validated;
        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        // Get list
        $result = $this->aggregator->getList($userId, $filters, $limit, $offset);

        $items = $result['items'];
        $propertyRequestSourceIds = $items->filter(function ($item) {
            return ($item->objectType ?? '') === 'property_request';
        })->pluck('sourceId')->filter()->unique()->values()->all();
        $inquirySourceIds = $items->filter(function ($item) {
            return ($item->objectType ?? '') === 'inquiry';
        })->pluck('sourceId')->filter()->unique()->values()->all();

        $appointmentsByRequest = [];
        $remindersByRequest = [];
        $appointmentsByInquiry = [];
        $remindersByInquiry = [];
        $now = Carbon::now();
        if (!empty($propertyRequestSourceIds)) {
            $appointmentRows = DB::table('property_request_appointments')
                ->where('user_id', $userId)
                ->whereIn('property_request_id', $propertyRequestSourceIds)
                ->orderBy('datetime', 'asc')
                ->get();
            foreach ($appointmentRows as $row) {
                $appointmentsByRequest[$row->property_request_id][] = $this->propertyRequestDetailBuilder->formatPropertyRequestAppointment($row);
            }
            $reminderRows = DB::table('property_request_reminders')
                ->where('user_id', $userId)
                ->whereIn('property_request_id', $propertyRequestSourceIds)
                ->orderBy('datetime', 'asc')
                ->get();
            foreach ($reminderRows as $row) {
                $remindersByRequest[$row->property_request_id][] = $this->propertyRequestDetailBuilder->formatPropertyRequestReminder($row, $now);
            }
        }
        if (!empty($inquirySourceIds)) {
            $appointmentRows = DB::table('inquiry_appointments')
                ->where('user_id', $userId)
                ->whereIn('inquiry_id', $inquirySourceIds)
                ->orderBy('datetime', 'asc')
                ->get();
            foreach ($appointmentRows as $row) {
                $appointmentsByInquiry[$row->inquiry_id][] = $this->propertyRequestDetailBuilder->formatPropertyRequestAppointment($row);
            }
            $reminderRows = DB::table('inquiry_reminders')
                ->where('user_id', $userId)
                ->whereIn('inquiry_id', $inquirySourceIds)
                ->orderBy('datetime', 'asc')
                ->get();
            foreach ($reminderRows as $row) {
                $remindersByInquiry[$row->inquiry_id][] = $this->propertyRequestDetailBuilder->formatPropertyRequestReminder($row, $now);
            }
        }

        // Load property_ids per property request and batch-load property summaries
        $propertiesByRequestId = [];
        if (!empty($propertyRequestSourceIds)) {
            $requestRows = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereIn('id', $propertyRequestSourceIds)
                ->get(['id', 'property_ids']);
            foreach ($requestRows as $row) {
                $ids = $row->property_ids;
                if (is_string($ids)) {
                    $decoded = json_decode($ids, true);
                    $ids = is_array($decoded) ? $decoded : [];
                }
                $ids = is_array($ids) ? $ids : [];
                $propertiesByRequestId[(int) $row->id] = array_values(array_filter(array_map(function ($id) {
                    return is_numeric($id) ? (int) $id : null;
                }, $ids)));
            }
            $allPropertyIds = array_values(array_unique(array_merge(...array_values($propertiesByRequestId))));
            $summariesById = $this->propertyRequestDetailBuilder->getPropertySummariesForIds($userId, $allPropertyIds);
            foreach ($propertiesByRequestId as $requestId => $ids) {
                $propertiesByRequestId[$requestId] = array_values(array_filter(array_map(function ($id) use ($summariesById) {
                    return $summariesById[$id] ?? null;
                }, $ids)));
            }
        }

        $items->each(function ($item) use ($appointmentsByRequest, $remindersByRequest, $appointmentsByInquiry, $remindersByInquiry, $propertiesByRequestId) {
            if (($item->objectType ?? '') === 'property_request' && isset($item->sourceId)) {
                $item->appointments = $appointmentsByRequest[$item->sourceId] ?? [];
                $item->reminders = $remindersByRequest[$item->sourceId] ?? [];
                $item->properties = $propertiesByRequestId[$item->sourceId] ?? [];
            } elseif (($item->objectType ?? '') === 'inquiry' && isset($item->sourceId)) {
                $item->appointments = $appointmentsByInquiry[$item->sourceId] ?? [];
                $item->reminders = $remindersByInquiry[$item->sourceId] ?? [];
                $item->properties = [];
            } else {
                $item->appointments = [];
                $item->reminders = [];
                $item->properties = [];
            }
        });

        // isUpdated flag: true only when request existed at last view and was modified since (per viewer)
        $viewerId = $request->user()->id;
        $viewedRow = DB::table('customers_hub_requests_list_viewed')
            ->where('user_id', $viewerId)
            ->first(['viewed_at']);
        $viewedAt = $viewedRow?->viewed_at ? Carbon::parse($viewedRow->viewed_at) : null;
        $items->each(function ($item) use ($viewedAt) {
            if ($viewedAt === null) {
                $item->isUpdated = false;
                return;
            }
            $createdAt = $item->createdAt ? Carbon::parse($item->createdAt) : null;
            $updatedAt = $item->updatedAt ? Carbon::parse($item->updatedAt) : null;
            $item->isUpdated = $createdAt !== null
                && $updatedAt !== null
                && $createdAt->lte($viewedAt)
                && $updatedAt->gt($viewedAt);
        });

        // Get stats
        $stats = $this->aggregator->getStats($userId, $filters);
        $comparison = $this->aggregator->getComparisonStats($userId, $filters);
        $stats = array_merge($stats, $comparison);

        try {
            $stages = $this->aggregator->getStageStats($userId, $filters);
        } catch (\Throwable $e) {
            $stages = [];
        }

        return $this->success([
            'actions' => $items,
            'stats' => $stats,
            'stages' => $stages,
            'pagination' => [
                'total' => $result['total'],
                'limit' => $result['limit'],
                'offset' => $result['offset'],
                'hasMore' => $result['hasMore'],
                'sortBy' => $result['sortBy'],
                'sortDir' => $result['sortDir'],
            ],
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/mark-viewed
     *
     * Mark the requests list as viewed by the current user (viewer). Used to compute isUpdated
     * per action on the next list load. Always uses server now(); client-provided timestamp is ignored.
     */
    public function markListViewed(Request $request): JsonResponse
    {
        $viewerId = $request->user()->id;
        $now = now();
        DB::table('customers_hub_requests_list_viewed')->upsert(
            [
                [
                    'user_id' => $viewerId,
                    'viewed_at' => $now,
                ],
            ],
            ['user_id'],
            ['viewed_at']
        );
        return $this->success(['viewedAt' => $now->toIso8601String()]);
    }

    /**
     * GET /api/v2/customers-hub/requests/filter-options
     *
     * Get available filter options for the requests center.
     * Cached for 30 minutes per user.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $cacheKey = "ch:reqs:filter-options:{$userId}";

        $data = Cache::remember($cacheKey, 1800, function () use ($userId) {
            // Action types
            $types = [
                ['id' => 'new_inquiry', 'label' => 'استفسار جديد', 'labelEn' => 'New Inquiry'],
                ['id' => 'callback_request', 'label' => 'طلب اتصال', 'labelEn' => 'Callback Request'],
                ['id' => 'whatsapp_incoming', 'label' => 'رسالة واتساب', 'labelEn' => 'WhatsApp Message'],
                ['id' => 'property_match', 'label' => 'عقار مطابق', 'labelEn' => 'Property Match'],
                ['id' => 'follow_up', 'label' => 'متابعة', 'labelEn' => 'Follow-up'],
                ['id' => 'site_visit', 'label' => 'معاينة', 'labelEn' => 'Site Visit'],
            ];

            // Statuses
            $statuses = [
                ['id' => 'pending', 'label' => 'قيد الانتظار', 'labelEn' => 'Pending'],
                ['id' => 'in_progress', 'label' => 'قيد التنفيذ', 'labelEn' => 'In Progress'],
                ['id' => 'snoozed', 'label' => 'مؤجل', 'labelEn' => 'Snoozed'],
                ['id' => 'completed', 'label' => 'مكتمل', 'labelEn' => 'Completed'],
                ['id' => 'dismissed', 'label' => 'مرفوض', 'labelEn' => 'Dismissed'],
            ];

            // Priorities
            $priorities = [
                ['id' => 'urgent', 'label' => 'عاجل', 'labelEn' => 'Urgent', 'color' => '#dc3545'],
                ['id' => 'high', 'label' => 'عالي', 'labelEn' => 'High', 'color' => '#fd7e14'],
                ['id' => 'medium', 'label' => 'متوسط', 'labelEn' => 'Medium', 'color' => '#ffc107'],
                ['id' => 'low', 'label' => 'منخفض', 'labelEn' => 'Low', 'color' => '#28a745'],
            ];

            // Sources: distinct values from users_property_requests.source for this tenant
            $sourceValues = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereNotNull('source')
                ->distinct()
                ->orderBy('source')
                ->pluck('source');
            $sources = $sourceValues->map(fn (string $value) => [
                'id' => $value,
                'label' => $this->sourceLabel($value, 'ar'),
                'labelEn' => $this->sourceLabel($value, 'en'),
            ])->values()->all();

            // Due date buckets
            $dueDateBuckets = [
                ['id' => 'overdue', 'label' => 'متأخر', 'labelEn' => 'Overdue'],
                ['id' => 'today', 'label' => 'اليوم', 'labelEn' => 'Today'],
                ['id' => 'week', 'label' => 'هذا الأسبوع', 'labelEn' => 'This Week'],
                ['id' => 'no_date', 'label' => 'بدون موعد', 'labelEn' => 'No Date'],
            ];

            // Object types (for filtering by kind of record)
            // request_appointment / request_reminder are nested on property_request rows, not separate list actions
            $objectTypes = [
                ['id' => 'inquiry', 'label' => 'استفسار', 'labelEn' => 'Inquiry'],
                ['id' => 'property_request', 'label' => 'طلب عقار', 'labelEn' => 'Property Request'],
                ['id' => 'reminder', 'label' => 'تذكير', 'labelEn' => 'Reminder'],
            ];

            // Appointment types (for filtering property requests by appointment type)
            $appointmentTypes = [
                ['id' => 'site_visit', 'label' => 'معاينة', 'labelEn' => 'Site visit'],
                ['id' => 'office_meeting', 'label' => 'اجتماع مكتب', 'labelEn' => 'Office meeting'],
                ['id' => 'phone_call', 'label' => 'اتصال هاتفي', 'labelEn' => 'Phone call'],
                ['id' => 'video_call', 'label' => 'مكالمة فيديو', 'labelEn' => 'Video call'],
                ['id' => 'contract_signing', 'label' => 'توقيع عقد', 'labelEn' => 'Contract signing'],
                ['id' => 'other', 'label' => 'أخرى', 'labelEn' => 'Other'],
            ];

            // Pipeline stages (customers_hub_stages) for request list filtering
            $stages = DB::table('customers_hub_stages')
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'stage_id', 'stage_name_ar', 'stage_name_en'])
                ->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'stage_id' => $s->stage_id,
                    'label' => $s->stage_name_ar,
                    'labelEn' => $s->stage_name_en ?? $s->stage_name_ar,
                ])
                ->values()
                ->all();

            // Customer types (user-defined)
            $customerTypes = UserApiCustomerType::where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'value', 'icon', 'color']);

            // Customer priorities (user-defined)
            $customerPriorities = UserApiCustomerPriority::where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'value', 'icon', 'color']);

            // Employees (assignees)
            $employees = User::where('tenant_id', $userId)
                ->where('account_type', 'employee')
                ->where('active', true)
                ->get(['id', 'first_name', 'last_name', 'email'])
                ->map(fn($e) => [
                    'id' => $e->id,
                    'label' => trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? '')),
                    'email' => $e->email,
                ]);

            // Cities: distinct cities via districts_id → user_districts
            $cities = DB::table('users_property_requests as upr')
                ->join('user_districts as d', 'upr.districts_id', '=', 'd.id')
                ->where('upr.user_id', $userId)
                ->whereNotNull('d.city_id')
                ->distinct()
                ->orderBy('d.city_name_ar')
                ->get(['d.city_id as value', 'd.city_name_ar as label', 'd.city_name_en as labelEn'])
                ->map(fn ($city) => [
                    'value' => (int) $city->value,
                    'label' => $city->label ?? '',
                    'labelEn' => $city->labelEn ?? $city->label ?? '',
                ])
                ->values()
                ->all();

            // Districts: distinct string values from users_property_requests.district
            $districtValues = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct()
                ->orderBy('district')
                ->pluck('district');

            $districts = $districtValues->map(fn (string $value) => [
                'value' => $value,
                'label' => $value,
            ])->values()->all();

            return [
                'types' => $types,
                'statuses' => $statuses,
                'priorities' => $priorities,
                'sources' => $sources,
                'objectTypes' => $objectTypes,
                'appointmentTypes' => $appointmentTypes,
                'dueDateBuckets' => $dueDateBuckets,
                'stages' => $stages,
                'customerTypes' => $customerTypes,
                'customerPriorities' => $customerPriorities,
                'employees' => $employees,
                'cities' => $cities,
                'districts' => $districts,
            ];
        });

        return $this->success($data);
    }

    /**
     * GET /api/v2/customers-hub/requests/{requestId}
     *
     * Get single action detail with related actions.
     */
    public function show(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);

        if (!$action) {
            return $this->error('Action not found', 404);
        }

        $isPropertyRequestAction = (($action->objectType ?? '') === 'property_request')
            && (($action->sourceTable ?? '') === 'users_property_requests')
            && !empty($action->sourceId);
        $isInquiryAction = (($action->objectType ?? '') === 'inquiry')
            && (($action->sourceTable ?? '') === 'api_customer_inquiry')
            && !empty($action->sourceId);

        if ($isPropertyRequestAction) {
            $fullAction = $this->propertyRequestDetailBuilder->buildFullPropertyRequestAction($userId, $action);
            if ($fullAction === null) {
                return $this->error('Action not found', 404);
            }
            $action = $fullAction;
        } elseif ($isInquiryAction) {
            $fullAction = $this->propertyRequestDetailBuilder->buildFullInquiryAction($userId, $action);
            if ($fullAction === null) {
                return $this->error('Action not found', 404);
            }
            $action = $fullAction;
        } else {
            $action->appointments = [];
            $action->reminders = [];
        }

        // Get related actions for the same customer
        $related = $this->aggregator->getRelated($userId, $requestId, [], 5);

        return $this->success([
            'action' => $action,
            'related' => $related['items'],
        ]);
    }

    /**
     * GET /api/v2/customers-hub/requests/{requestId}/stats
     *
     * Get stats for a specific action's customer.
     */
    public function actionStats(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);

        if (!$action) {
            return $this->error('Action not found', 404);
        }

        if (!$action->customerId) {
            return $this->success([
                'customerStats' => null,
            ]);
        }

        // Get stats for this customer
        $customerStats = $this->aggregator->getStats($userId, [
            'customer_id' => $action->customerId,
        ]);

        return $this->success([
            'customerStats' => $customerStats,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/complete
     *
     * Mark an action as completed.
     */
    public function complete(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $success = $this->aggregator->completeAction($userId, $requestId);

        if (!$success) {
            return $this->error('Failed to complete action', 422);
        }

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action completed successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/dismiss
     *
     * Dismiss an action.
     */
    public function dismiss(DismissRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        // Save dismiss reason as a note on the action
        $this->aggregator->addNoteToAction($userId, $requestId, $validated['reason'], 'current_user');

        $success = $this->aggregator->dismissAction($userId, $requestId);

        if (!$success) {
            return $this->error('Failed to dismiss action', 422);
        }

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action dismissed successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/snooze
     *
     * Snooze an action (where supported).
     */
    public function snooze(SnoozeRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $snoozedBy = $request->user()->id;

        if (!empty($validated['reason'])) {
            $this->aggregator->addNoteToAction($userId, $requestId, $validated['reason'], 'current_user');
        }

        $success = $this->aggregator->snoozeAction($userId, $requestId, $validated['snoozedUntil'], $snoozedBy);

        if (!$success) {
            return $this->error('Failed to snooze action or snooze is not supported for this request type', 422);
        }

        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action snoozed successfully',
            'actionId' => $requestId,
            'snoozedUntil' => $validated['snoozedUntil'],
            'snoozedBy' => $snoozedBy,
        ]);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}
     *
     * Update an action (partial update).
     */
    public function update(RequestUpdateRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);
        if (!$action) {
            return $this->error('Action not found', 404);
        }

        // Resolve pipeline stage: stage_id (string) or status_id (integer -> lookup customers_hub_stages.id)
        $stageIdString = null;
        if (array_key_exists('stage_id', $validated) && $validated['stage_id'] !== null && $validated['stage_id'] !== '') {
            $stageIdString = DB::table('customers_hub_stages')->where('stage_id', $validated['stage_id'])->where('is_active', true)->value('stage_id');
        } elseif (array_key_exists('status_id', $validated) && $validated['status_id'] !== null) {
            $stageIdString = DB::table('customers_hub_stages')->where('id', (int) $validated['status_id'])->where('is_active', true)->value('stage_id');
        }

        if ($stageIdString !== null) {
            if (($action->sourceTable ?? '') === 'users_property_requests' && !empty($action->sourceId)) {
                DB::table('users_property_requests')
                    ->where('id', $action->sourceId)
                    ->where('user_id', $userId)
                    ->update(['customers_hub_stage_id' => $stageIdString, 'updated_at' => now()]);
            } elseif (($action->sourceTable ?? '') === 'api_customer_inquiry' && !empty($action->sourceId)) {
                DB::table('api_customer_inquiry')
                    ->where('id', $action->sourceId)
                    ->where('user_id', $userId)
                    ->update(['stage_id' => $stageIdString, 'updated_at' => now()]);
            }
        }

        unset($validated['status_id'], $validated['stage_id']);

        $success = $this->aggregator->updateAction($userId, $requestId, $validated);
        if (!$success && !empty($validated)) {
            return $this->error('Failed to update action', 422);
        }

        $this->invalidateFilterOptionsCache($userId);

        $updated = $this->aggregator->getById($userId, $requestId);

        return $this->success([
            'message' => 'Action updated successfully',
            'actionId' => $requestId,
            'action' => $updated,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/notes
     *
     * Append a note to an action. For property_request_{id} and inquiry_{id}, saves to crm_hub_notes.
     * For other types (reminder, appointment, etc.) uses legacy note column where supported.
     */
    public function addNote(AddNoteRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $employeeId = auth()->user()->id;
        $parsed = $this->aggregator->parseActionId($requestId);

        // Request-level leads: save to crm_hub_notes (polymorphic)
        if ($parsed !== null) {
            if ($parsed['table'] === 'users_property_requests') {
                $noteable = UserPropertyRequest::where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first();
                if (!$noteable) {
                    return $this->error('Action not found', 404);
                }
                $note = $noteable->hubNotes()->create([
                    'employee_id' => $employeeId,
                    'note' => $validated['note'],
                ]);
                $note->load('employee.basic_setting');
                return $this->success([
                    'message' => 'Note added successfully',
                    'actionId' => $requestId,
                    'note' => $this->formatHubNote($note),
                ]);
            }
            if ($parsed['table'] === 'api_customer_inquiry') {
                $noteable = ApiCustomerInquiry::where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first();
                if (!$noteable) {
                    return $this->error('Action not found', 404);
                }
                $note = $noteable->hubNotes()->create([
                    'employee_id' => $employeeId,
                    'note' => $validated['note'],
                ]);
                $note->load('employee.basic_setting');
                return $this->success([
                    'message' => 'Note added successfully',
                    'actionId' => $requestId,
                    'note' => $this->formatHubNote($note),
                ]);
            }
        }

        // Legacy: reminder, appointment, etc. (append to notes/note column where supported)
        $action = $this->aggregator->getById($userId, $requestId);
        if (!$action) {
            return $this->error('Action not found', 404);
        }
        $addedBy = $validated['addedBy'] ?? 'current_user';
        $success = $this->aggregator->addNoteToAction($userId, $requestId, $validated['note'], $addedBy);
        if (!$success) {
            return $this->error('Action not found or notes not supported for this request type', 422);
        }
        return $this->success([
            'message' => 'Note added successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/appointments
     *
     * Create an appointment linked to a property request.
     */
    public function createAppointmentForPropertyRequest(CreateAppointmentRequest $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $validated = $request->validated();

        $resolved = $this->resolvePropertyRequestAndCustomer($requestId, $userId);
        $isInquiry = false;
        if ($resolved === null) {
            $resolved = $this->resolveInquiryAndCustomer($requestId, $userId);
            $isInquiry = $resolved !== null;
        }
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST_ID',
                    'message' => 'Request not found',
                    'message_ar' => 'الطلب غير موجود',
                ],
            ], 404);
        }

        $title = !empty($validated['title']) ? $validated['title'] : (
            $validated['type'] === 'site_visit' ? 'معاينة عقار' : 'موعد طلب عقار'
        );
        $duration = (int) ($validated['duration'] ?? 30);
        $priorityDb = $this->mapPriorityAppointmentToDb($validated['priority'] ?? 'medium');
        $datetime = !empty($validated['datetime'])
            ? Carbon::parse($validated['datetime'])->toDateTimeString()
            : now()->toDateTimeString();
        $now = now();

        if ($isInquiry) {
            $id = DB::table('inquiry_appointments')->insertGetId([
                'user_id' => $userId,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        } else {
            $id = DB::table('property_request_appointments')->insertGetId([
                'user_id' => $userId,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $base = $this->propertyRequestDetailBuilder->formatPropertyRequestAppointment($row);
        $base['requestId'] = $requestId;
        $base['customerId'] = $row->customer_id !== null ? (int) $row->customer_id : null;
        $base['updatedAt'] = Carbon::parse($row->updated_at)->toIso8601String();
        $appointment = $base;

        return response()->json([
            'success' => true,
            'data' => ['appointment' => $appointment],
        ], 201);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/reminders
     *
     * Create a reminder linked to a property request.
     */
    public function createReminderForPropertyRequest(CreateReminderRequest $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $validated = $request->validated();

        $resolved = $this->resolvePropertyRequestAndCustomer($requestId, $userId);
        $isInquiry = false;
        if ($resolved === null) {
            $resolved = $this->resolveInquiryAndCustomer($requestId, $userId);
            $isInquiry = $resolved !== null;
        }
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST_ID',
                    'message' => 'Request not found',
                    'message_ar' => 'الطلب غير موجود',
                ],
            ], 404);
        }

        $priorityDb = $this->mapPriorityReminderToDb($validated['priority']);
        $datetime = Carbon::parse($validated['datetime'])->toDateTimeString();
        $now = now();

        if ($isInquiry) {
            $id = DB::table('inquiry_reminders')->insertGetId([
                'user_id' => $userId,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        } else {
            $id = DB::table('property_request_reminders')->insertGetId([
                'user_id' => $userId,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $now = Carbon::now();
        $base = $this->propertyRequestDetailBuilder->formatPropertyRequestReminder($row, $now);
        $base['requestId'] = $requestId;
        $base['customerId'] = $row->customer_id !== null ? (int) $row->customer_id : null;
        $base['updatedAt'] = Carbon::parse($row->updated_at)->toIso8601String();
        $reminder = $base;

        return response()->json([
            'success' => true,
            'data' => ['reminder' => $reminder],
        ], 201);
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk
     *
     * Unified bulk actions: complete, dismiss, snooze, assign, change_priority.
     */
    public function bulk(RequestsBulkRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $action = $validated['action'];
        $data = $validated['data'];

        $result = $this->aggregator->bulkAction($userId, $action, $validated['actionIds'], $data);
        $this->invalidateFilterOptionsCache($userId);

        $successIds = $result['success'];
        $failedIds = $result['failed'];
        $failures = $result['failures'] ?? [];
        $meta = $result['meta'] ?? [];
        $successCount = count($successIds);
        $failedCount = count($failedIds);

        $httpStatus = $failedCount > 0 && $successCount > 0 ? 207 : ($successCount > 0 ? 200 : ($failedCount > 0 ? 404 : 422));
        $responseStatus = $failedCount > 0 && $successCount > 0 ? 'partial_success' : ($successCount > 0 ? 'success' : 'error');
        $message = $this->bulkResponseMessage($action, $successCount, $failedCount, $responseStatus);

        $responseData = [
            'action' => $action,
            'updatedCount' => $successCount,
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'actionIds' => $successIds,
            'failedActionIds' => $failedIds,
            'failures' => $failures,
        ];

        $responseData = array_merge($responseData, $this->bulkResponseMeta($action, $data, $meta));
        $timestamp = now()->toIso8601String();

        return response()->json([
            'status' => $responseStatus,
            'code' => $httpStatus,
            'message' => $message,
            'data' => $responseData,
            'timestamp' => $timestamp,
        ], $httpStatus);
    }

    /**
     * Build human-readable message for bulk response.
     */
    private function bulkResponseMessage(string $action, int $successCount, int $failedCount, string $responseStatus): string
    {
        if ($responseStatus === 'error') {
            return $failedCount > 0 ? __('Bulk operation failed for all actions.') : __('Validation failed.');
        }
        $actionMessages = [
            'complete' => ['تم إكمال %d إجراء بنجاح', 'تم إكمال %d إجراءات بنجاح'],
            'dismiss' => ['تم رفض إجراء واحد بنجاح', 'تم رفض %d إجراءات بنجاح'],
            'snooze' => ['تم تأجيل إجراء واحد بنجاح', 'تم تأجيل %d إجراءات بنجاح'],
            'assign' => ['تم تعيين إجراء واحد بنجاح', 'تم تعيين %d إجراءات بنجاح'],
            'change_priority' => ['تم تغيير أولوية إجراء واحد بنجاح', 'تم تغيير أولوية %d إجراءات بنجاح'],
        ];
        $tpl = $actionMessages[$action] ?? ['%d action(s) processed', '%d actions processed'];
        $msg = $successCount === 1 ? $tpl[0] : sprintf($tpl[1], $successCount);
        if ($failedCount > 0) {
            $msg .= '، ' . sprintf(__('%d failed'), $failedCount);
        }
        return $msg;
    }

    /**
     * Add operation-specific meta (completedBy, dismissedBy, etc.) to response data.
     */
    private function bulkResponseMeta(string $action, array $data, array $meta): array
    {
        $out = [];
        $userId = null;
        $fieldMap = [
            'complete' => ['completedBy', 'completedAt'],
            'dismiss' => ['dismissedBy', 'dismissedAt'],
            'snooze' => ['snoozedBy', 'snoozedAt', 'snoozedUntil', 'reason'],
            'assign' => ['assignedTo', 'assignedBy', 'assignedAt'],
            'change_priority' => ['changedBy', 'changedAt', 'priority'],
        ];
        $userFields = ['complete' => 'completedBy', 'dismiss' => 'dismissedBy', 'snooze' => 'snoozedBy', 'assign' => ['assignedTo', 'assignedBy'], 'change_priority' => 'changedBy'];
        $now = now()->toIso8601String();
        if ($action === 'complete') {
            $id = $data['completedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['completedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['completedAt'] = $meta['completedAt'] ?? $now;
        } elseif ($action === 'dismiss') {
            $id = $data['dismissedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['dismissedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['dismissedAt'] = $meta['dismissedAt'] ?? $now;
            if (isset($data['reason'])) $out['reason'] = $data['reason'];
        } elseif ($action === 'snooze') {
            $id = $data['snoozedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['snoozedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['snoozedAt'] = $meta['snoozedAt'] ?? $now;
            $out['snoozedUntil'] = $data['snoozedUntil'] ?? null;
            if (isset($data['reason'])) $out['reason'] = $data['reason'];
        } elseif ($action === 'assign') {
            foreach (['assignedTo', 'assignedBy'] as $f) {
                $id = $data[$f] ?? null;
                if ($id) {
                    $u = User::find($id);
                    $out[$f] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')), 'email' => $u->email ?? null] : null;
                }
            }
            $out['assignedAt'] = $meta['assignedAt'] ?? $now;
        } elseif ($action === 'change_priority') {
            $id = $data['changedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['changedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['changedAt'] = $meta['changedAt'] ?? $now;
            $out['priority'] = $data['priority'] ?? null;
        }
        return $out;
    }

    /**
     * Check that the given user ID is the tenant owner or an active employee of the tenant.
     */
    private function isValidTenantUserOrEmployee(int $tenantUserId, int $employeeId): bool
    {
        if ($employeeId === $tenantUserId) {
            return true;
        }
        return User::where('id', $employeeId)
            ->where('tenant_id', $tenantUserId)
            ->where('account_type', 'employee')
            ->where('active', true)
            ->exists();
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk-complete
     *
     * Bulk complete multiple actions.
     */
    public function bulkComplete(BulkCompleteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkComplete($userId, $validated['actionIds']);

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => sprintf(
                '%d actions completed, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk-dismiss
     *
     * Bulk dismiss multiple actions.
     */
    public function bulkDismiss(BulkDismissRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkDismiss($userId, $validated['actionIds'], $validated['reason']);

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => sprintf(
                '%d actions dismissed, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
        ]);
    }

    // =========================================================================
    // MATCHING APIs (V2)
    // =========================================================================

    /**
     * GET /api/v2/customers-hub/requests/{requestId}/matches
     *
     * Returns matched properties for a property request along with completeness status.
     * Only works for property_request_* composite IDs.
     */
    public function matches(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found or not matchable', 404);
        }

        $completeness = app(\App\Services\Matching\RequestCompletenessService::class)
            ->validateMinimal('web', $propertyRequest->id);

        $matches = DB::table('property_matches as pm')
            ->where('pm.user_id', $userId)
            ->where('pm.request_type', 'web')
            ->where('pm.request_id', $propertyRequest->id)
            ->orderByDesc('pm.match_score')
            ->get(['pm.id', 'pm.property_id', 'pm.match_score', 'pm.database_score', 'pm.ai_score',
                   'pm.match_explanation', 'pm.matched_criteria', 'pm.is_reviewed']);

        $matchItems = $matches->map(function ($m) {
            $prop = \App\Models\User\RealestateManagement\Property::find($m->property_id);
            return [
                'match_id'        => $m->id,
                'property_id'     => $m->property_id,
                'match_score'     => (int) $m->match_score,
                'database_score'  => (int) $m->database_score,
                'ai_score'        => (int) $m->ai_score,
                'match_explanation' => $m->match_explanation,
                'matched_criteria'  => is_string($m->matched_criteria)
                    ? json_decode($m->matched_criteria, true)
                    : $m->matched_criteria,
                'is_reviewed'     => (bool) $m->is_reviewed,
                'property'        => $prop ? [
                    'id'             => $prop->id,
                    'title'          => optional($prop->first_content)->title ?? null,
                    'featured_image' => $prop->featured_image_url ?? null,
                    'address'        => optional($prop->first_content)->address ?? $prop->address ?? null,
                    'price'          => $prop->price ?? null,
                    'purpose'        => $prop->purpose ?? null,
                    'property_type'  => $prop->property_type ?? null,
                    'beds'           => $prop->beds ?? null,
                    'baths'          => $prop->bath ?? null,
                    'area'           => $prop->area ?? null,
                ] : null,
            ];
        })->values()->all();

        return $this->success([
            'request_id'             => $requestId,
            'source'                 => $propertyRequest->source ?? 'website',
            'has_minimal_data'       => $completeness['has_minimal_data'],
            'minimal_missing_fields' => $completeness['minimal_missing_fields'],
            'is_complete'            => $completeness['is_complete'],
            'missing_fields'         => $completeness['missing_fields'],
            'is_ignored'             => (bool) ($propertyRequest->is_ignored ?? false),
            'matches'                => $matchItems,
            'total_matches'          => count($matchItems),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/complete-data
     *
     * Fill in missing fields on a property request to enable matching.
     * The observer auto-triggers matching after the update.
     */
    public function completeData(\App\Http\Requests\Api\V2\CustomersHub\CompleteDataRequest $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found or not matchable', 404);
        }

        $fields = $request->onlyProvided();

        if (empty($fields)) {
            return $this->error('No fields provided to update', 422);
        }

        // Map purpose aliases to canonical values for users_property_requests
        if (isset($fields['purpose'])) {
            $purposeMap = ['buy' => 'sale', 'invest' => 'sale'];
            $fields['purpose'] = $purposeMap[$fields['purpose']] ?? $fields['purpose'];
        }

        $propertyRequest->fill($fields);
        $propertyRequest->save();

        $completeness = app(\App\Services\Matching\RequestCompletenessService::class)
            ->validateMinimal('web', $propertyRequest->id);

        return $this->success([
            'request_id'             => $requestId,
            'has_minimal_data'       => $completeness['has_minimal_data'],
            'minimal_missing_fields' => $completeness['minimal_missing_fields'],
            'is_complete'            => $completeness['is_complete'],
            'missing_fields'         => $completeness['missing_fields'],
            'message'                => 'Request updated. Matching will run automatically.',
        ]);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}/read
     *
     * Mark a property request as read.
     */
    public function markRead(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        $propertyRequest->update(['is_read' => true]);

        return $this->success(['message' => 'Request marked as read']);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}/unread
     *
     * Mark a property request as unread.
     */
    public function markUnread(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        $propertyRequest->update(['is_read' => false]);

        return $this->success(['message' => 'Request marked as unread']);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}/ignore
     *
     * Toggle the ignored flag for matching.
     * Body: { "is_ignored": true|false }  (defaults to true when not provided)
     */
    public function ignore(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        $isIgnored = $request->boolean('is_ignored', true);
        $propertyRequest->update(['is_ignored' => $isIgnored]);

        $msg = $isIgnored
            ? 'Request ignored. Matching will be skipped.'
            : 'Request un-ignored. Matching will resume on next update.';

        return $this->success(['is_ignored' => $isIgnored, 'message' => $msg]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/rematch
     *
     * Manually trigger property matching for a request.
     */
    public function rematch(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        if ($propertyRequest->is_ignored) {
            return $this->error('Cannot rematch an ignored request. Un-ignore it first.', 422);
        }

        $completeness = app(\App\Services\Matching\RequestCompletenessService::class)
            ->validateMinimal('web', $propertyRequest->id);

        if (!$completeness['has_minimal_data']) {
            return $this->error('Request has insufficient data for matching.', 422, [
                'minimal_missing_fields' => $completeness['minimal_missing_fields'],
            ]);
        }

        $forceAi = $completeness['is_complete'];
        $limit = $forceAi ? 25 : 10;

        $results = app(\App\Services\Matching\MatchingService::class)
            ->generateMatchesForRequest('web', $propertyRequest->id, $limit, $forceAi, $userId);

        return $this->success([
            'request_id'    => $requestId,
            'matched_count' => count($results),
            'is_complete'   => $completeness['is_complete'],
            'message'       => count($results) > 0
                ? 'Matching completed successfully.'
                : 'No matching properties found.',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Get the tenant user ID from request.
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }

    /**
     * Known source value labels for filter-options. Unknown values use raw value or "Other".
     */
    private function sourceLabel(string $value, string $lang): string
    {
        $map = [
            'inquiry' => ['ar' => 'استفسار', 'en' => 'Inquiry'],
            'manual' => ['ar' => 'يدوي', 'en' => 'Manual'],
            'whatsapp' => ['ar' => 'واتساب', 'en' => 'WhatsApp'],
            'import' => ['ar' => 'استيراد', 'en' => 'Import'],
            'referral' => ['ar' => 'إحالة', 'en' => 'Referral'],
            'property_request' => ['ar' => 'طلب عقار', 'en' => 'Property Request'],
            'website' => ['ar' => 'الموقع', 'en' => 'Website'],
            'property_interest' => ['ar' => 'اهتمام بعقار', 'en' => 'Property Interest'],
            'public_form' => ['ar' => 'نموذج عام', 'en' => 'Public Form'],
            'employee_dashboard' => ['ar' => 'لوحة الموظف', 'en' => 'Employee Dashboard'],
            'whatsapp_bot' => ['ar' => 'واتساب بوت', 'en' => 'WhatsApp Bot'],
        ];
        if (isset($map[$value][$lang])) {
            return $map[$value][$lang];
        }
        return $value;
    }

    /**
     * Invalidate filter options cache for a user.
     */
    private function invalidateFilterOptionsCache(int $userId): void
    {
        Cache::forget("ch:reqs:filter-options:{$userId}");
    }

    /**
     * Resolve property request and optional customer from requestId.
     * Returns ['propertyRequestId' => int, 'customerId' => int|null] or null if not found / not a property_request.
     */
    private function resolvePropertyRequestAndCustomer(string $requestId, int $userId): ?array
    {
        $parsed = $this->aggregator->parseActionId($requestId);
        if ($parsed === null || ($parsed['table'] ?? '') !== 'users_property_requests') {
            return null;
        }
        $sourceId = (int) $parsed['sourceId'];

        $exists = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->where('id', $sourceId)
            ->where('is_active', 1)
            ->exists();
        if (!$exists) {
            return null;
        }

        $row = DB::table('users_property_requests as upr')
            ->leftJoin('api_customers as ac', function ($join) {
                $join->on('upr.user_id', '=', 'ac.user_id')
                    ->on('upr.phone', '=', 'ac.phone_number');
            })
            ->where('upr.user_id', $userId)
            ->where('upr.id', $sourceId)
            ->select('upr.id as property_request_id', 'ac.id as customer_id')
            ->first();

        return [
            'propertyRequestId' => (int) $row->property_request_id,
            'customerId' => $row->customer_id !== null ? (int) $row->customer_id : null,
        ];
    }

    /**
     * Resolve inquiry and customer from requestId.
     * Returns ['inquiryId' => int, 'customerId' => int|null] or null if not found / not an inquiry.
     */
    private function resolveInquiryAndCustomer(string $requestId, int $userId): ?array
    {
        $parsed = $this->aggregator->parseActionId($requestId);
        if ($parsed === null || ($parsed['table'] ?? '') !== 'api_customer_inquiry') {
            return null;
        }
        $sourceId = (int) $parsed['sourceId'];

        $row = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->where('id', $sourceId)
            ->first(['id', 'customer_id']);
        if (!$row) {
            return null;
        }

        return [
            'inquiryId' => (int) $row->id,
            'customerId' => $row->customer_id !== null ? (int) $row->customer_id : null,
        ];
    }

    /**
     * Map API priority string to appointments table (1=low, 2=medium, 3=high, 4=urgent).
     */
    private function mapPriorityAppointmentToDb(?string $priority): int
    {
        return match ($priority) {
            'urgent' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 2,
        };
    }

    /**
     * Map API priority string to reminders table (0=low, 1=medium, 2=high, 3=urgent).
     */
    private function mapPriorityReminderToDb(?string $priority): int
    {
        return match ($priority) {
            'urgent' => 3,
            'high' => 2,
            'medium' => 1,
            'low' => 0,
            default => 1,
        };
    }

    /**
     * Resolve a composite requestId to a UserPropertyRequest model for matching endpoints.
     * Returns null if not found, not owned by $userId, or not a property_request_ composite ID.
     */
    private function resolvePropertyRequestForMatching(string $requestId, int $userId): ?UserPropertyRequest
    {
        $parsed = $this->aggregator->parseActionId($requestId);
        if ($parsed === null || ($parsed['table'] ?? '') !== 'users_property_requests') {
            return null;
        }
        $sourceId = (int) $parsed['sourceId'];

        return UserPropertyRequest::where('id', $sourceId)
            ->where('user_id', $userId)
            ->first();
    }
}
