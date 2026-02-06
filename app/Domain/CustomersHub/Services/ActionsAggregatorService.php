<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * ActionsAggregatorService
 * 
 * Aggregates customer actions from multiple legacy tables using UNION ALL.
 * This is a READ-ONLY layer that does NOT modify legacy tables.
 * 
 * Source tables:
 * - api_customer_inquiry (inquiries, callbacks, whatsapp)
 * - users_property_requests (property matches)
 * - reminders (follow-ups)
 * - users_api_customers_reminders (customer reminders)
 * - users_api_customers_appointments (site visits)
 */
class ActionsAggregatorService
{
    /**
     * Action type constants
     */
    public const TYPE_NEW_INQUIRY = 'new_inquiry';
    public const TYPE_CALLBACK_REQUEST = 'callback_request';
    public const TYPE_WHATSAPP_INCOMING = 'whatsapp_incoming';
    public const TYPE_PROPERTY_MATCH = 'property_match';
    public const TYPE_FOLLOW_UP = 'follow_up';
    public const TYPE_SITE_VISIT = 'site_visit';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Priority constants
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * ID prefixes for routing updates to correct tables
     */
    public const PREFIX_INQUIRY = 'inquiry_';
    public const PREFIX_PROPERTY_REQUEST = 'property_request_';
    public const PREFIX_REMINDER = 'reminder_';
    public const PREFIX_CUSTOMER_REMINDER = 'customer_reminder_';
    public const PREFIX_APPOINTMENT = 'appointment_';

    /**
     * Get the unified UNION ALL query for customer actions.
     */
    public function getUnifiedQuery(int $userId, array $filters = []): \Illuminate\Database\Query\Builder
    {
        $inquiriesQuery = $this->getInquiriesSubquery($userId);
        $propertyRequestsQuery = $this->getPropertyRequestsSubquery($userId);
        $remindersQuery = $this->getRemindersSubquery($userId);
        $appointmentsQuery = $this->getAppointmentsSubquery($userId);
        $customerRemindersQuery = $this->getCustomerRemindersSubquery($userId);

        // Build UNION ALL
        $unionQuery = $inquiriesQuery
            ->unionAll($propertyRequestsQuery)
            ->unionAll($remindersQuery)
            ->unionAll($appointmentsQuery)
            ->unionAll($customerRemindersQuery);

        // Wrap in subquery for filtering and ordering
        $query = DB::query()->fromSub($unionQuery, 'actions');

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Get actions list with pagination.
     */
    public function getList(int $userId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = $this->getUnifiedQuery($userId, $filters);

        // Get total count before pagination
        $totalQuery = clone $query;
        $total = $totalQuery->count();

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'createdAt';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        $items = $query->limit($limit)->offset($offset)->get();

        // Transform items
        $items = $items->map(function ($item) {
            return $this->transformAction($item);
        });

        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total,
        ];
    }

    /**
     * Get statistics for the actions.
     */
    public function getStats(int $userId, array $filters = []): array
    {
        $query = $this->getUnifiedQuery($userId, $filters);

        $stats = $query->selectRaw("
            SUM(CASE WHEN type IN ('new_inquiry', 'callback_request', 'whatsapp_incoming') AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as inbox,
            SUM(CASE WHEN type IN ('follow_up', 'site_visit') AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as followups,
            SUM(CASE WHEN status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN dueDate < NOW() AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN DATE(dueDate) = CURRENT_DATE AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        ")->first();

        return [
            'inbox' => (int) ($stats->inbox ?? 0),
            'followups' => (int) ($stats->followups ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'overdue' => (int) ($stats->overdue ?? 0),
            'today' => (int) ($stats->today ?? 0),
            'completed' => (int) ($stats->completed ?? 0),
        ];
    }

    /**
     * Get a single action by ID.
     */
    public function getById(int $userId, string $actionId): ?object
    {
        $parsed = $this->parseActionId($actionId);
        if (!$parsed) {
            return null;
        }

        $query = $this->getUnifiedQuery($userId, []);
        $query->where('id', $actionId);

        $item = $query->first();
        if (!$item) {
            return null;
        }

        return $this->transformAction($item);
    }

    /**
     * Get related actions for the same customer.
     */
    public function getRelated(int $userId, string $actionId, array $filters = [], int $limit = 10): array
    {
        // First get the action to find customer
        $action = $this->getById($userId, $actionId);
        if (!$action || !$action->customerId) {
            return ['items' => [], 'total' => 0];
        }

        $filters['customer_id'] = $action->customerId;
        $filters['exclude_id'] = $actionId;

        return $this->getList($userId, $filters, $limit, 0);
    }

    /**
     * Parse action ID to determine source table and record ID.
     */
    public function parseActionId(string $actionId): ?array
    {
        $prefixMap = [
            'inquiry_' => 'api_customer_inquiry',
            'property_request_' => 'users_property_requests',
            'reminder_' => 'reminders',
            'customer_reminder_' => 'users_api_customers_reminders',
            'appointment_' => 'users_api_customers_appointments',
        ];

        foreach ($prefixMap as $prefix => $table) {
            if (str_starts_with($actionId, $prefix)) {
                $sourceId = (int) substr($actionId, strlen($prefix));
                return [
                    'prefix' => $prefix,
                    'table' => $table,
                    'sourceId' => $sourceId,
                ];
            }
        }

        return null;
    }

    /**
     * Complete an action (mark as done).
     */
    public function completeAction(int $userId, string $actionId): bool
    {
        $parsed = $this->parseActionId($actionId);
        if (!$parsed) {
            return false;
        }

        $now = Carbon::now();

        switch ($parsed['table']) {
            case 'api_customer_inquiry':
                return DB::table('api_customer_inquiry')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'is_read' => 1,
                        'is_archived' => 0,
                        'updated_at' => $now,
                    ]) > 0;

            case 'users_property_requests':
                return DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'is_read' => 1,
                        'is_archived' => 0,
                        'updated_at' => $now,
                    ]) > 0;

            case 'reminders':
                return DB::table('reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->update([
                        'status' => 'completed',
                        'updated_at' => $now,
                    ]) > 0;

            case 'users_api_customers_reminders':
                // No status column - treat as no-op or delete
                return true;

            case 'users_api_customers_appointments':
                // No status column - appointments complete when datetime < NOW()
                return true;
        }

        return false;
    }

    /**
     * Dismiss an action.
     */
    public function dismissAction(int $userId, string $actionId): bool
    {
        $parsed = $this->parseActionId($actionId);
        if (!$parsed) {
            return false;
        }

        $now = Carbon::now();

        switch ($parsed['table']) {
            case 'api_customer_inquiry':
                return DB::table('api_customer_inquiry')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'is_archived' => 1,
                        'updated_at' => $now,
                    ]) > 0;

            case 'users_property_requests':
                return DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'is_archived' => 1,
                        'updated_at' => $now,
                    ]) > 0;

            case 'reminders':
                return DB::table('reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->update([
                        'status' => 'cancelled',
                        'updated_at' => $now,
                    ]) > 0;

            case 'users_api_customers_reminders':
                // Optional: soft delete or no-op
                return true;

            case 'users_api_customers_appointments':
                // No dismiss action for appointments
                return true;
        }

        return false;
    }

    /**
     * Bulk complete actions.
     */
    public function bulkComplete(int $userId, array $actionIds): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($actionIds as $actionId) {
            if ($this->completeAction($userId, $actionId)) {
                $results['success'][] = $actionId;
            } else {
                $results['failed'][] = $actionId;
            }
        }

        return $results;
    }

    /**
     * Bulk dismiss actions.
     */
    public function bulkDismiss(int $userId, array $actionIds): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($actionIds as $actionId) {
            if ($this->dismissAction($userId, $actionId)) {
                $results['success'][] = $actionId;
            } else {
                $results['failed'][] = $actionId;
            }
        }

        return $results;
    }

    // =========================================================================
    // PRIVATE SUBQUERY BUILDERS
    // =========================================================================

    /**
     * Build inquiries subquery.
     */
    private function getInquiriesSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('api_customer_inquiry as aci')
            ->join('api_customers as ac', 'aci.customer_id', '=', 'ac.id')
            ->leftJoin('users as u', 'ac.responsible_employee_id', '=', 'u.id')
            ->where('aci.user_id', $userId)
            ->select([
                DB::raw("CONCAT('inquiry_', aci.id) as id"),
                'aci.customer_id as customerId',
                'ac.name as customerName',
                'ac.phone_number as customerPhone',
                DB::raw("CASE
                    WHEN aci.inquiry_type = 'callback' THEN 'callback_request'
                    WHEN aci.inquiry_type = 'whatsapp' THEN 'whatsapp_incoming'
                    ELSE 'new_inquiry'
                END as type"),
                DB::raw("COALESCE(CONCAT('استفسار جديد من ', ac.name), 'استفسار جديد') as title"),
                'aci.message as description',
                DB::raw("CASE aci.urgency
                    WHEN 'urgent' THEN 'urgent'
                    WHEN 'high' THEN 'high'
                    WHEN 'medium' THEN 'medium'
                    ELSE 'low'
                END as priority"),
                DB::raw("CASE
                    WHEN aci.is_archived = 1 THEN 'dismissed'
                    WHEN aci.is_read = 1 THEN 'in_progress'
                    ELSE 'pending'
                END as status"),
                DB::raw("COALESCE(ac.source, 'inquiry') as source"),
                DB::raw("NULL as dueDate"),
                DB::raw("NULL as snoozedUntil"),
                'aci.created_at as createdAt',
                DB::raw("NULL as completedAt"),
                DB::raw("NULL as completedBy"),
                'ac.responsible_employee_id as assignedTo',
                DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as assignedToName"),
                DB::raw("JSON_OBJECT(
                    'inquiryId', aci.id,
                    'propertyType', aci.property_type,
                    'propertyCategory', aci.property_type,
                    'budget', aci.budget,
                    'bedrooms', aci.bedrooms,
                    'bathrooms', aci.bathrooms,
                    'city', aci.city,
                    'district', aci.district
                ) as metadata"),
                DB::raw("'api_customer_inquiry' as sourceTable"),
                'aci.id as sourceId',
                'aci.user_id as userId',
                'aci.property_type as propertyCategory',
                DB::raw("NULL as propertyType"),
            ]);
    }

    /**
     * Build property requests subquery.
     */
    private function getPropertyRequestsSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('users_property_requests as upr')
            ->leftJoin('api_customers as ac', function ($join) {
                $join->on('upr.user_id', '=', 'ac.user_id')
                    ->on('upr.phone', '=', 'ac.phone_number');
            })
            ->leftJoin('users as u2', 'ac.responsible_employee_id', '=', 'u2.id')
            ->where('upr.user_id', $userId)
            ->where('upr.is_active', 1)
            ->select([
                DB::raw("CONCAT('property_request_', upr.id) as id"),
                'ac.id as customerId',
                DB::raw("COALESCE(ac.name, upr.full_name) as customerName"),
                'upr.phone as customerPhone',
                DB::raw("'property_match' as type"),
                DB::raw("CONCAT('عقار مطابق: ', COALESCE(ac.name, upr.full_name)) as title"),
                'upr.notes as description',
                DB::raw("'low' as priority"),
                DB::raw("CASE
                    WHEN upr.is_archived = 1 THEN 'dismissed'
                    WHEN upr.is_read = 1 THEN 'in_progress'
                    ELSE 'pending'
                END as status"),
                DB::raw("'property_request' as source"),
                DB::raw("NULL as dueDate"),
                DB::raw("NULL as snoozedUntil"),
                'upr.created_at as createdAt',
                DB::raw("NULL as completedAt"),
                DB::raw("NULL as completedBy"),
                'ac.responsible_employee_id as assignedTo',
                DB::raw("CONCAT(COALESCE(u2.first_name, ''), ' ', COALESCE(u2.last_name, '')) as assignedToName"),
                DB::raw("JSON_OBJECT(
                    'propertyRequestId', upr.id,
                    'propertyType', upr.property_type,
                    'propertyCategory', upr.category_id,
                    'budgetFrom', upr.budget_from,
                    'budgetTo', upr.budget_to,
                    'purpose', upr.purpose,
                    'seriousness', upr.seriousness
                ) as metadata"),
                DB::raw("'users_property_requests' as sourceTable"),
                'upr.id as sourceId',
                'upr.user_id as userId',
                'upr.category_id as propertyCategory',
                'upr.property_type as propertyType',
            ]);
    }

    /**
     * Build reminders subquery.
     */
    private function getRemindersSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('reminders as r')
            ->join('api_customers as ac', 'r.customer_id', '=', 'ac.id')
            ->where('r.user_id', $userId)
            ->whereNull('r.deleted_at')
            ->select([
                DB::raw("CONCAT('reminder_', r.id) as id"),
                'r.customer_id as customerId',
                'ac.name as customerName',
                'ac.phone_number as customerPhone',
                DB::raw("'follow_up' as type"),
                'r.title as title',
                'r.description as description',
                DB::raw("CASE r.priority
                    WHEN 2 THEN 'high'
                    WHEN 1 THEN 'medium'
                    ELSE 'low'
                END as priority"),
                DB::raw("CASE
                    WHEN r.status = 'completed' THEN 'completed'
                    WHEN r.status = 'cancelled' THEN 'dismissed'
                    ELSE 'pending'
                END as status"),
                DB::raw("'manual' as source"),
                'r.datetime as dueDate',
                DB::raw("NULL as snoozedUntil"),
                'r.created_at as createdAt',
                DB::raw("CASE WHEN r.status = 'completed' THEN r.updated_at ELSE NULL END as completedAt"),
                DB::raw("NULL as completedBy"),
                DB::raw("NULL as assignedTo"),
                DB::raw("NULL as assignedToName"),
                DB::raw("JSON_OBJECT(
                    'reminderId', r.id,
                    'reminderTypeId', r.reminder_type_id,
                    'notes', r.notes
                ) as metadata"),
                DB::raw("'reminders' as sourceTable"),
                'r.id as sourceId',
                'r.user_id as userId',
                DB::raw("NULL as propertyCategory"),
                DB::raw("NULL as propertyType"),
            ]);
    }

    /**
     * Build appointments subquery.
     */
    private function getAppointmentsSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('users_api_customers_appointments as a')
            ->join('api_customers as ac', 'a.customer_id', '=', 'ac.id')
            ->where('a.user_id', $userId)
            ->select([
                DB::raw("CONCAT('appointment_', a.id) as id"),
                'a.customer_id as customerId',
                'ac.name as customerName',
                'ac.phone_number as customerPhone',
                DB::raw("'site_visit' as type"),
                'a.title as title',
                'a.note as description',
                DB::raw("CASE a.priority
                    WHEN 3 THEN 'high'
                    WHEN 2 THEN 'medium'
                    ELSE 'low'
                END as priority"),
                DB::raw("CASE WHEN a.datetime < NOW() THEN 'completed' ELSE 'pending' END as status"),
                DB::raw("'manual' as source"),
                'a.datetime as dueDate',
                DB::raw("NULL as snoozedUntil"),
                'a.created_at as createdAt',
                DB::raw("CASE WHEN a.datetime < NOW() THEN a.updated_at ELSE NULL END as completedAt"),
                DB::raw("NULL as completedBy"),
                DB::raw("NULL as assignedTo"),
                DB::raw("NULL as assignedToName"),
                DB::raw("JSON_OBJECT(
                    'appointmentId', a.id,
                    'type', a.type,
                    'duration', a.duration
                ) as metadata"),
                DB::raw("'users_api_customers_appointments' as sourceTable"),
                'a.id as sourceId',
                'a.user_id as userId',
                DB::raw("NULL as propertyCategory"),
                DB::raw("NULL as propertyType"),
            ]);
    }

    /**
     * Build customer reminders subquery.
     */
    private function getCustomerRemindersSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('users_api_customers_reminders as cr')
            ->join('api_customers as ac', 'cr.customer_id', '=', 'ac.id')
            ->where('cr.user_id', $userId)
            ->select([
                DB::raw("CONCAT('customer_reminder_', cr.id) as id"),
                'cr.customer_id as customerId',
                'ac.name as customerName',
                'ac.phone_number as customerPhone',
                DB::raw("'follow_up' as type"),
                'cr.title as title',
                DB::raw("NULL as description"),
                DB::raw("CASE cr.priority
                    WHEN 3 THEN 'high'
                    WHEN 2 THEN 'medium'
                    ELSE 'low'
                END as priority"),
                DB::raw("'pending' as status"),
                DB::raw("'manual' as source"),
                'cr.datetime as dueDate',
                DB::raw("NULL as snoozedUntil"),
                'cr.created_at as createdAt',
                DB::raw("NULL as completedAt"),
                DB::raw("NULL as completedBy"),
                DB::raw("NULL as assignedTo"),
                DB::raw("NULL as assignedToName"),
                DB::raw("JSON_OBJECT('reminderId', cr.id) as metadata"),
                DB::raw("'users_api_customers_reminders' as sourceTable"),
                'cr.id as sourceId',
                'cr.user_id as userId',
                DB::raw("NULL as propertyCategory"),
                DB::raw("NULL as propertyType"),
            ]);
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        // Tab filter (predefined filter sets)
        if (!empty($filters['tab'])) {
            switch ($filters['tab']) {
                case 'inbox':
                    $query->whereIn('type', ['new_inquiry', 'callback_request', 'whatsapp_incoming'])
                        ->whereIn('status', ['pending', 'in_progress']);
                    break;
                case 'followups':
                    $query->whereIn('type', ['follow_up', 'site_visit'])
                        ->whereIn('status', ['pending', 'in_progress']);
                    break;
                case 'all':
                    $query->whereIn('status', ['pending', 'in_progress']);
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
            }
        }

        // Types filter
        if (!empty($filters['types']) && is_array($filters['types'])) {
            $query->whereIn('type', $filters['types']);
        }

        // Status filter
        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        // Sources filter
        if (!empty($filters['sources']) && is_array($filters['sources'])) {
            $query->whereIn('source', $filters['sources']);
        }

        // Priorities filter
        if (!empty($filters['priorities']) && is_array($filters['priorities'])) {
            $query->whereIn('priority', $filters['priorities']);
        }

        // Assignees filter
        if (!empty($filters['assignees']) && is_array($filters['assignees'])) {
            $query->whereIn('assignedTo', $filters['assignees']);
        }

        // Customer ID filter
        if (!empty($filters['customer_id'])) {
            $query->where('customerId', $filters['customer_id']);
        }

        // Exclude specific action ID
        if (!empty($filters['exclude_id'])) {
            $query->where('id', '!=', $filters['exclude_id']);
        }

        // Due date bucket filter
        if (!empty($filters['due_date_bucket'])) {
            switch ($filters['due_date_bucket']) {
                case 'overdue':
                    $query->whereNotNull('dueDate')
                        ->where('dueDate', '<', Carbon::now());
                    break;
                case 'today':
                    $query->whereNotNull('dueDate')
                        ->whereDate('dueDate', Carbon::today());
                    break;
                case 'week':
                    $query->whereNotNull('dueDate')
                        ->whereBetween('dueDate', [Carbon::now(), Carbon::now()->addDays(7)]);
                    break;
                case 'no_date':
                    $query->whereNull('dueDate');
                    break;
            }
        }

        // Property categories filter (villa, apartment, etc.)
        if (!empty($filters['property_categories']) && is_array($filters['property_categories'])) {
            $query->whereIn('propertyCategory', $filters['property_categories']);
        }

        // Property types filter (Residential, Commercial, etc.)
        if (!empty($filters['property_types']) && is_array($filters['property_types'])) {
            $query->whereIn('propertyType', $filters['property_types']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('createdAt', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('createdAt', '<=', $filters['date_to']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('customerName', 'like', $search)
                    ->orWhere('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('customerPhone', 'like', $search);
            });
        }
    }

    /**
     * Transform a raw action record to API format.
     */
    private function transformAction(object $item): object
    {
        // Parse metadata JSON if string
        $metadata = $item->metadata;
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        return (object) [
            'id' => $item->id,
            'customerId' => $item->customerId,
            'customerName' => $item->customerName,
            'customerPhone' => $item->customerPhone ?? null,
            'type' => $item->type,
            'title' => $item->title,
            'description' => $item->description,
            'priority' => $item->priority,
            'status' => $item->status,
            'source' => $item->source,
            'dueDate' => $item->dueDate ? Carbon::parse($item->dueDate)->toIso8601String() : null,
            'snoozedUntil' => $item->snoozedUntil ? Carbon::parse($item->snoozedUntil)->toIso8601String() : null,
            'createdAt' => $item->createdAt ? Carbon::parse($item->createdAt)->toIso8601String() : null,
            'completedAt' => $item->completedAt ? Carbon::parse($item->completedAt)->toIso8601String() : null,
            'completedBy' => $item->completedBy,
            'assignedTo' => $item->assignedTo,
            'assignedToName' => trim($item->assignedToName ?? ''),
            'propertyCategory' => $item->propertyCategory ?? null,
            'propertyType' => $item->propertyType ?? null,
            'metadata' => $metadata,
            'sourceTable' => $item->sourceTable,
            'sourceId' => $item->sourceId,
        ];
    }
}
