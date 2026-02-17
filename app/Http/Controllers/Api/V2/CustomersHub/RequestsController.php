<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\ActionsAggregatorService;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\PropertyRequestStatus;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserPropertyRequest;
use App\Models\CustomersHub\CrmHubNote;
use App\Models\User;
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
 */
class RequestsController extends ApiController
{
    private ActionsAggregatorService $aggregator;

    public function __construct(ActionsAggregatorService $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * POST /api/v2/customers-hub/requests/list
     * 
     * Get paginated list of customer actions with filtering.
     */
    public function list(Request $request): JsonResponse
    {
        // Normalize frontend parameter names so both naming conventions work
        $request->merge([
            'tab' => $request->input('activeTab') ?? $request->input('tab'),
            'types' => $request->input('selectedTypes') ?? $request->input('types'),
            'sources' => $request->input('selectedSources') ?? $request->input('sources'),
            'priorities' => $request->input('selectedPriorities') ?? $request->input('priorities'),
            'assignees' => $request->input('selectedAssignees') ?? $request->input('assignees'),
            'due_date_bucket' => $request->input('dueDateFilter') ?? $request->input('due_date_bucket'),
            'property_categories' => $request->input('selectedPropertyTypes') ?? $request->input('property_categories'),
            'cities' => $request->input('selectedCities') ?? $request->input('cities'),
            'states' => $request->input('selectedStates') ?? $request->input('states'),
            'budget_min' => $request->input('budgetMin') ?? $request->input('budget_min'),
            'budget_max' => $request->input('budgetMax') ?? $request->input('budget_max'),
            'objectTypes' => $request->input('selectedObjectTypes') ?? $request->input('objectTypes'),
            'stages' => $request->input('selectedStages') ?? $request->input('stages'),
        ]);

        $validated = $request->validate([
            'tab' => 'nullable|in:inbox,followups,all,completed',
            'types' => 'nullable|array',
            'types.*' => 'string|in:new_inquiry,callback_request,whatsapp_incoming,property_match,follow_up,site_visit',
            'statuses' => 'nullable|array',
            'statuses.*' => 'string|in:pending,in_progress,completed,dismissed,snoozed',
            'sources' => 'nullable|array',
            'sources.*' => 'string|in:inquiry,manual,whatsapp,import,referral,property_request',
            'priorities' => 'nullable|array',
            'priorities.*' => 'string|in:low,medium,high,urgent',
            'assignees' => 'nullable|array',
            'assignees.*' => 'integer',
            'customer_id' => 'nullable|integer',
            'due_date_bucket' => 'nullable|in:overdue,today,week,no_date',
            'property_categories' => 'nullable|array',
            'property_categories.*' => 'string',
            'property_types' => 'nullable|array',
            'property_types.*' => 'string',
            'cities' => 'nullable|array',
            'cities.*' => 'string',
            'states' => 'nullable|array',
            'states.*' => 'string',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:createdAt,dueDate,priority,customerName',
            'sort_dir' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'objectTypes' => 'nullable|array',
            'objectTypes.*' => 'string|in:inquiry,property_request,reminder,request_appointment,request_reminder',
            'stages' => 'nullable|array',
            'stages.*' => 'integer',
        ]);

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

        $appointmentsByRequest = [];
        $remindersByRequest = [];
        if (!empty($propertyRequestSourceIds)) {
            $appointmentRows = DB::table('property_request_appointments')
                ->where('user_id', $userId)
                ->whereIn('property_request_id', $propertyRequestSourceIds)
                ->orderBy('datetime', 'asc')
                ->get();
            $now = Carbon::now();
            foreach ($appointmentRows as $row) {
                $appointmentsByRequest[$row->property_request_id][] = $this->formatPropertyRequestAppointment($row);
            }
            $reminderRows = DB::table('property_request_reminders')
                ->where('user_id', $userId)
                ->whereIn('property_request_id', $propertyRequestSourceIds)
                ->orderBy('datetime', 'asc')
                ->get();
            foreach ($reminderRows as $row) {
                $remindersByRequest[$row->property_request_id][] = $this->formatPropertyRequestReminder($row, $now);
            }
        }

        $items->each(function ($item) use ($appointmentsByRequest, $remindersByRequest) {
            if (($item->objectType ?? '') === 'property_request' && isset($item->sourceId)) {
                $item->appointments = $appointmentsByRequest[$item->sourceId] ?? [];
                $item->reminders = $remindersByRequest[$item->sourceId] ?? [];
            } else {
                $item->appointments = [];
                $item->reminders = [];
            }
        });

        // Get stats
        $stats = $this->aggregator->getStats($userId, $filters);

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
            ],
        ]);
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

            // Sources
            $sources = [
                ['id' => 'inquiry', 'label' => 'استفسار', 'labelEn' => 'Inquiry'],
                ['id' => 'manual', 'label' => 'يدوي', 'labelEn' => 'Manual'],
                ['id' => 'whatsapp', 'label' => 'واتساب', 'labelEn' => 'WhatsApp'],
                ['id' => 'import', 'label' => 'استيراد', 'labelEn' => 'Import'],
                ['id' => 'referral', 'label' => 'إحالة', 'labelEn' => 'Referral'],
                ['id' => 'property_request', 'label' => 'طلب عقار', 'labelEn' => 'Property Request'],
            ];

            // Due date buckets
            $dueDateBuckets = [
                ['id' => 'overdue', 'label' => 'متأخر', 'labelEn' => 'Overdue'],
                ['id' => 'today', 'label' => 'اليوم', 'labelEn' => 'Today'],
                ['id' => 'week', 'label' => 'هذا الأسبوع', 'labelEn' => 'This Week'],
                ['id' => 'no_date', 'label' => 'بدون موعد', 'labelEn' => 'No Date'],
            ];

            // Object types (for filtering by kind of record)
            $objectTypes = [
                ['id' => 'inquiry', 'label' => 'استفسار', 'labelEn' => 'Inquiry'],
                ['id' => 'property_request', 'label' => 'طلب عقار', 'labelEn' => 'Property Request'],
                ['id' => 'reminder', 'label' => 'تذكير', 'labelEn' => 'Reminder'],
                ['id' => 'request_appointment', 'label' => 'موعد طلب', 'labelEn' => 'Request Appointment'],
                ['id' => 'request_reminder', 'label' => 'تذكير طلب', 'labelEn' => 'Request Reminder'],
            ];

            // Pipeline stages (property_request_statuses) for request list filtering
            $stages = PropertyRequestStatus::active()->ordered()->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'label' => $s->name_ar,
                    'labelEn' => $s->name_en ?? $s->name_ar,
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

            return [
                'types' => $types,
                'statuses' => $statuses,
                'priorities' => $priorities,
                'sources' => $sources,
                'objectTypes' => $objectTypes,
                'dueDateBuckets' => $dueDateBuckets,
                'stages' => $stages,
                'customerTypes' => $customerTypes,
                'customerPriorities' => $customerPriorities,
                'employees' => $employees,
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

        if ($isPropertyRequestAction) {
            $fullAction = $this->buildFullPropertyRequestAction($userId, $action);
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
    public function dismiss(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

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
     * PATCH /api/v2/customers-hub/requests/{requestId}
     *
     * Update an action (partial update).
     */
    public function update(Request $request, string $requestId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'notes' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
            'status_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('property_request_statuses', 'id')->where('is_active', true)],
        ]);

        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);
        if (!$action) {
            return $this->error('Action not found', 404);
        }

        // Update pipeline stage (property request only) when status_id is provided
        if (array_key_exists('status_id', $validated) && $validated['status_id'] !== null && ($action->sourceTable ?? '') === 'users_property_requests' && !empty($action->sourceId)) {
            DB::table('users_property_requests')
                ->where('id', $action->sourceId)
                ->where('user_id', $userId)
                ->update(['status_id' => $validated['status_id'], 'updated_at' => now()]);
        }

        unset($validated['status_id']);

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
    public function addNote(Request $request, string $requestId): JsonResponse
    {
        $validated = $request->validate([
            'note' => 'required|string',
            'addedBy' => 'nullable|string|max:255',
        ]);

        $userId = $this->getTenantUserId($request);
        $employeeId = $request->user()->id;
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
                $noteable->hubNotes()->create([
                    'employee_id' => $employeeId,
                    'note' => $validated['note'],
                ]);
                return $this->success([
                    'message' => 'Note added successfully',
                    'actionId' => $requestId,
                ]);
            }
            if ($parsed['table'] === 'api_customer_inquiry') {
                $noteable = ApiCustomerInquiry::where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first();
                if (!$noteable) {
                    return $this->error('Action not found', 404);
                }
                $noteable->hubNotes()->create([
                    'employee_id' => $employeeId,
                    'note' => $validated['note'],
                ]);
                return $this->success([
                    'message' => 'Note added successfully',
                    'actionId' => $requestId,
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
    public function createAppointmentForPropertyRequest(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $validated = $request->validate([
            'type' => 'required|string|in:site_visit,office_meeting,phone_call,video_call,contract_signing,other',
            'datetime' => 'required|date',
            'duration' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ]);

        if (Carbon::parse($validated['datetime'])->isPast()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_DATETIME',
                    'message' => 'Datetime must be in the future',
                    'message_ar' => 'التاريخ والوقت يجب أن يكون في المستقبل',
                ],
            ], 422);
        }

        $resolved = $this->resolvePropertyRequestAndCustomer($requestId, $userId);
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST_ID',
                    'message' => 'Property request not found',
                    'message_ar' => 'طلب العقار غير موجود',
                ],
            ], 404);
        }

        $title = !empty($validated['title']) ? $validated['title'] : (
            $validated['type'] === 'site_visit' ? 'معاينة عقار' : 'موعد طلب عقار'
        );
        $duration = (int) ($validated['duration'] ?? 30);
        $priorityDb = $this->mapPriorityAppointmentToDb($validated['priority'] ?? 'medium');
        $datetime = Carbon::parse($validated['datetime'])->toDateTimeString();

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
            'created_at' => $now = now(),
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

        $appointment = $this->formatPropertyRequestAppointmentForResponse($row, $requestId);

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
    public function createReminderForPropertyRequest(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'datetime' => 'required|date',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'type' => 'required|string|in:follow_up,payment_due,document_required,other',
            'notes' => 'nullable|string',
        ]);

        if (Carbon::parse($validated['datetime'])->isPast()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_DATETIME',
                    'message' => 'Datetime must be in the future',
                    'message_ar' => 'التاريخ والوقت يجب أن يكون في المستقبل',
                ],
            ], 422);
        }

        $resolved = $this->resolvePropertyRequestAndCustomer($requestId, $userId);
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST_ID',
                    'message' => 'Property request not found',
                    'message_ar' => 'طلب العقار غير موجود',
                ],
            ], 404);
        }

        $priorityDb = $this->mapPriorityReminderToDb($validated['priority']);
        $datetime = Carbon::parse($validated['datetime'])->toDateTimeString();

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
            'created_at' => $now = now(),
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

        $reminder = $this->formatPropertyRequestReminderForResponse($row, $requestId);

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
    public function bulk(Request $request): JsonResponse
    {
        $baseRules = [
            'action' => 'required|string|in:complete,dismiss,snooze,assign,change_priority',
            'actionIds' => 'required|array|min:1|max:1000',
            'actionIds.*' => 'string',
            'data' => 'required|array',
        ];
        $validated = $request->validate($baseRules);
        $userId = $this->getTenantUserId($request);
        $action = $validated['action'];
        $data = $validated['data'];

        // Action-specific validation
        $employeeIdRules = ['nullable', 'integer', function ($attr, $value, $fail) use ($userId) {
            if ($value === null) return;
            if (!$this->isValidTenantUserOrEmployee($userId, (int) $value)) {
                $fail(__('validation.exists', ['attribute' => $attr]));
            }
        }];
        $requiredEmployeeRule = ['required', 'integer', function ($attr, $value, $fail) use ($userId) {
            if (!$this->isValidTenantUserOrEmployee($userId, (int) $value)) {
                $fail(__('validation.exists', ['attribute' => $attr]));
            }
        }];

        if ($action === 'complete') {
            $request->validate(['data.completedBy' => $requiredEmployeeRule, 'data.notes' => 'nullable|string']);
        } elseif ($action === 'dismiss') {
            $request->validate(['data.dismissedBy' => $requiredEmployeeRule, 'data.reason' => 'nullable|string']);
        } elseif ($action === 'snooze') {
            $request->validate([
                'data.snoozedUntil' => 'required|date|after:now',
                'data.snoozedBy' => $requiredEmployeeRule,
                'data.reason' => 'nullable|string',
            ]);
        } elseif ($action === 'assign') {
            $request->validate([
                'data.assignedTo' => $requiredEmployeeRule,
                'data.assignedBy' => $requiredEmployeeRule,
            ]);
        } elseif ($action === 'change_priority') {
            $request->validate([
                'data.priority' => 'required|in:urgent,high,medium,low',
                'data.changedBy' => $requiredEmployeeRule,
            ]);
        }

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
    public function bulkComplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actionIds' => 'required|array|min:1|max:100',
            'actionIds.*' => 'string',
        ]);

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
    public function bulkDismiss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actionIds' => 'required|array|min:1|max:100',
            'actionIds.*' => 'string',
        ]);

        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkDismiss($userId, $validated['actionIds']);

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
     * Build full property request payload for show endpoint.
     */
    private function buildFullPropertyRequestAction(int $userId, object $action): ?array
    {
        $propertyRequestId = (int) ($action->sourceId ?? 0);
        if ($propertyRequestId <= 0) {
            return null;
        }

        $propertyRequest = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->where('id', $propertyRequestId)
            ->where('is_active', 1)
            ->first();

        if (!$propertyRequest) {
            return null;
        }

        $now = Carbon::now();
        $appointmentRows = DB::table('property_request_appointments')
            ->where('user_id', $userId)
            ->where('property_request_id', $propertyRequestId)
            ->orderBy('datetime', 'asc')
            ->get();
        $reminderRows = DB::table('property_request_reminders')
            ->where('user_id', $userId)
            ->where('property_request_id', $propertyRequestId)
            ->orderBy('datetime', 'asc')
            ->get();

        $appointments = $appointmentRows
            ->map(fn ($row) => $this->formatPropertyRequestAppointment($row))
            ->values()
            ->all();
        $reminders = $reminderRows
            ->map(fn ($row) => $this->formatPropertyRequestReminder($row, $now))
            ->values()
            ->all();

        $stageId = null;
        $stage = null;
        if (!empty($propertyRequest->status_id)) {
            $stageRow = DB::table('property_request_statuses')
                ->where('id', $propertyRequest->status_id)
                ->where('is_active', true)
                ->first(['id', 'name_ar', 'name_en']);
            if ($stageRow) {
                $stageId = (int) $stageRow->id;
                $stage = [
                    'id' => (int) $stageRow->id,
                    'nameAr' => $stageRow->name_ar,
                    'nameEn' => $stageRow->name_en ?? $stageRow->name_ar,
                ];
            }
        }

        $city = null;
        if (!empty($propertyRequest->city_id)) {
            $city = DB::table('user_cities')
                ->where('id', $propertyRequest->city_id)
                ->value('name_ar');
        }

        $propertyCategory = null;
        if (!empty($propertyRequest->category_id)) {
            $propertyCategory = DB::table('api_user_categories')
                ->where('id', $propertyRequest->category_id)
                ->value('name');
        }

        $assignedTo = isset($action->assignedTo) && $action->assignedTo !== null && $action->assignedTo !== ''
            ? (int) $action->assignedTo
            : null;
        $assignedToName = trim((string) ($action->assignedToName ?? ''));

        if ($assignedTo === null || $assignedToName === '') {
            $assignee = DB::table('api_customers as ac')
                ->leftJoin('users as u', 'ac.responsible_employee_id', '=', 'u.id')
                ->where('ac.user_id', $userId)
                ->where('ac.phone_number', $propertyRequest->phone)
                ->select([
                    'ac.responsible_employee_id',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assigned_to_name"),
                ])
                ->first();

            if ($assignedTo === null && $assignee && $assignee->responsible_employee_id !== null) {
                $assignedTo = (int) $assignee->responsible_employee_id;
            }
            if ($assignedToName === '' && $assignee) {
                $assignedToName = trim((string) ($assignee->assigned_to_name ?? ''));
            }
        }

        $fullAction = array_merge((array) $action, (array) $propertyRequest);

        // Keep existing action identifier contract and expose DB id explicitly too.
        $fullAction['id'] = $action->id ?? ('property_request_' . $propertyRequestId);
        $fullAction['property_request_id'] = $propertyRequestId;
        $fullAction['sourceId'] = $propertyRequestId;
        $fullAction['sourceTable'] = 'users_property_requests';
        $fullAction['objectType'] = 'property_request';
        $fullAction['source'] = $action->source ?? ($propertyRequest->source ?? 'website');

        $fullAction['notes'] = $propertyRequest->notes;
        $fullAction['stage_id'] = $stageId;
        $fullAction['stage'] = $stage;
        $fullAction['priority'] = $this->mapPropertyRequestPriorityToString($propertyRequest->seriousness ?? null);
        $fullAction['status'] = $this->mapPropertyRequestStatusToString(
            (bool) ($propertyRequest->is_archived ?? false),
            (bool) ($propertyRequest->is_read ?? false)
        );
        $fullAction['propertyCategory'] = $propertyCategory;
        $fullAction['propertyType'] = $propertyRequest->property_type;
        $fullAction['city'] = $city;
        $fullAction['state'] = $propertyRequest->region;
        $fullAction['budgetMin'] = $propertyRequest->budget_from !== null ? (float) $propertyRequest->budget_from : null;
        $fullAction['budgetMax'] = $propertyRequest->budget_to !== null ? (float) $propertyRequest->budget_to : null;
        $fullAction['assignedTo'] = $assignedTo;
        $fullAction['assignedToName'] = $assignedToName;
        $fullAction['completedAt'] = null;
        $fullAction['completedBy'] = null;
        $fullAction['snoozedUntil'] = null;
        $fullAction['dueDate'] = null;
        $fullAction['appointments'] = $appointments;
        $fullAction['reminders'] = $reminders;
        $fullAction['metadata'] = $this->buildPropertyRequestMetadata($propertyRequest, $action->metadata ?? []);

        return $fullAction;
    }

    /**
     * Map users_property_requests.seriousness value to API priority.
     */
    private function mapPropertyRequestPriorityToString(?string $seriousness): string
    {
        return match ($seriousness) {
            'مستعد فورًا' => 'urgent',
            'خلال شهر' => 'high',
            'خلال 3 أشهر' => 'medium',
            'لاحقًا / استكشاف فقط' => 'low',
            default => 'medium',
        };
    }

    /**
     * Map property request read/archive flags to API status.
     */
    private function mapPropertyRequestStatusToString(bool $isArchived, bool $isRead): string
    {
        if ($isArchived) {
            return 'dismissed';
        }
        if ($isRead) {
            return 'in_progress';
        }
        return 'pending';
    }

    /**
     * Build metadata object for property request response.
     */
    private function buildPropertyRequestMetadata(object $propertyRequest, mixed $existingMetadata): array
    {
        $metadata = [];
        if (is_array($existingMetadata)) {
            $metadata = $existingMetadata;
        } elseif (is_object($existingMetadata)) {
            $metadata = (array) $existingMetadata;
        } elseif (is_string($existingMetadata)) {
            $decoded = json_decode($existingMetadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $defaults = [
            'propertyRequestId' => (int) $propertyRequest->id,
            'propertyType' => $propertyRequest->property_type,
            'propertyCategory' => $propertyRequest->category_id,
            'budgetFrom' => $propertyRequest->budget_from !== null ? (float) $propertyRequest->budget_from : null,
            'budgetTo' => $propertyRequest->budget_to !== null ? (float) $propertyRequest->budget_to : null,
            'purpose' => $propertyRequest->purpose,
            'seriousness' => $propertyRequest->seriousness,
        ];

        return array_replace($defaults, $metadata);
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
     * Map DB priority (appointments 1-4) to API string.
     */
    private function mapPriorityAppointmentToString(int $priority): string
    {
        return match ($priority) {
            4 => 'urgent',
            3 => 'high',
            2 => 'medium',
            1 => 'low',
            default => 'medium',
        };
    }

    /**
     * Map DB priority (reminders 0-3) to API string.
     */
    private function mapPriorityReminderToString(int $priority): string
    {
        return match ($priority) {
            3 => 'urgent',
            2 => 'high',
            1 => 'medium',
            0 => 'low',
            default => 'medium',
        };
    }

    /**
     * Format appointment row for list/single (no requestId/customerId).
     */
    private function formatPropertyRequestAppointment(object $row): array
    {
        return [
            'id' => $row->id,
            'title' => $row->title,
            'type' => $row->type,
            'datetime' => Carbon::parse($row->datetime)->toIso8601String(),
            'duration' => (int) $row->duration,
            'status' => $row->status ?? 'scheduled',
            'priority' => $this->mapPriorityAppointmentToString((int) ($row->priority ?? 2)),
            'notes' => $row->notes,
            'createdAt' => Carbon::parse($row->created_at)->toIso8601String(),
        ];
    }

    /**
     * Format appointment for create response (with requestId, customerId).
     */
    private function formatPropertyRequestAppointmentForResponse(object $row, string $requestId): array
    {
        $base = $this->formatPropertyRequestAppointment($row);
        $base['requestId'] = $requestId;
        $base['customerId'] = $row->customer_id !== null ? (int) $row->customer_id : null;
        $base['updatedAt'] = Carbon::parse($row->updated_at)->toIso8601String();
        return $base;
    }

    /**
     * Format reminder row for list/single (with isOverdue, daysUntilDue).
     */
    private function formatPropertyRequestReminder(object $row, Carbon $now): array
    {
        $dt = Carbon::parse($row->datetime);
        $isOverdue = $dt->lt($now);
        $daysUntilDue = $isOverdue ? 0 : (int) $now->diffInDays($dt, false);

        return [
            'id' => $row->id,
            'title' => $row->title,
            'description' => $row->description,
            'datetime' => $dt->toIso8601String(),
            'priority' => $this->mapPriorityReminderToString((int) ($row->priority ?? 1)),
            'type' => $row->type ?? 'follow_up',
            'status' => $row->status ?? 'pending',
            'notes' => $row->notes,
            'isOverdue' => $isOverdue,
            'daysUntilDue' => $daysUntilDue,
            'createdAt' => Carbon::parse($row->created_at)->toIso8601String(),
        ];
    }

    /**
     * Format reminder for create response (with requestId, customerId, updatedAt).
     */
    private function formatPropertyRequestReminderForResponse(object $row, string $requestId): array
    {
        $now = Carbon::now();
        $base = $this->formatPropertyRequestReminder($row, $now);
        $base['requestId'] = $requestId;
        $base['customerId'] = $row->customer_id !== null ? (int) $row->customer_id : null;
        $base['updatedAt'] = Carbon::parse($row->updated_at)->toIso8601String();
        return $base;
    }
}
