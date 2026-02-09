<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\CustomersHub\CustomersHubStage;

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

        // Enrich with customer hub stage (stage_id + stage object)
        $items = $this->enrichItemsWithHubStage($items, $userId);

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
     * Get stage statistics for the filtered actions (request count and percentage per stage).
     * Returns all active Customer Hub stages; stages with 0 requests are included.
     */
    public function getStageStats(int $userId, array $filters = []): array
    {
        try {
            $stages = CustomersHubStage::where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'stage_id', 'stage_name_ar', 'stage_name_en', 'color', 'order']);

            if ($stages->isEmpty()) {
                return [];
            }

            $query = $this->getUnifiedQuery($userId, $filters);
            $query->join('api_customers as ac', 'ac.id', '=', 'actions.customerId')
                ->where('ac.user_id', $userId)
                ->whereNotNull('ac.customers_hub_stage_id')
                ->groupBy('ac.customers_hub_stage_id')
                ->selectRaw('ac.customers_hub_stage_id as stage_id, COUNT(*) as request_count');

            $counts = $query->get()->keyBy('stage_id');
            $total = $counts->sum('request_count');

            $result = [];
            foreach ($stages as $stage) {
                $row = $counts->get($stage->stage_id);
                $requestCount = $row ? (int) $row->request_count : 0;
                $percentage = $total > 0 ? round(($requestCount / $total) * 100, 1) : 0.0;

                $result[] = [
                    'stage_id' => $stage->stage_id,
                    'stage_name_ar' => $stage->stage_name_ar,
                    'stage_name_en' => $stage->stage_name_en,
                    'color' => $stage->color,
                    'order' => (int) $stage->order,
                    'requestCount' => $requestCount,
                    'percentage' => $percentage,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
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

        $transformed = $this->transformAction($item);
        $enriched = $this->enrichItemsWithHubStage(collect([$transformed]), $userId);

        return $enriched->first();
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
     * Update an action (partial update by source table).
     */
    public function updateAction(int $userId, string $actionId, array $data): bool
    {
        $parsed = $this->parseActionId($actionId);
        if (!$parsed) {
            return false;
        }

        $now = Carbon::now();
        $payload = ['updated_at' => $now];

        switch ($parsed['table']) {
            case 'reminders':
                if (array_key_exists('title', $data)) {
                    $payload['title'] = $data['title'];
                }
                if (array_key_exists('description', $data)) {
                    $payload['description'] = $data['description'];
                }
                if (array_key_exists('due_date', $data)) {
                    $payload['datetime'] = $data['due_date'];
                }
                if (array_key_exists('priority', $data)) {
                    $payload['priority'] = $this->mapPriorityReminders($data['priority']);
                }
                if (array_key_exists('notes', $data)) {
                    $payload['notes'] = $data['notes'];
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->update($payload) > 0;

            case 'users_property_requests':
                if (array_key_exists('notes', $data)) {
                    $payload['notes'] = $data['notes'];
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update($payload) > 0;

            case 'users_api_customers_appointments':
                if (array_key_exists('title', $data)) {
                    $payload['title'] = $data['title'];
                }
                if (array_key_exists('notes', $data)) {
                    $payload['note'] = $data['notes'];
                }
                if (array_key_exists('due_date', $data)) {
                    $payload['datetime'] = $data['due_date'];
                }
                if (array_key_exists('priority', $data)) {
                    $payload['priority'] = $this->mapPriorityAppointments($data['priority']);
                }
                if (array_key_exists('duration', $data)) {
                    $payload['duration'] = (int) $data['duration'];
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('users_api_customers_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update($payload) > 0;

            case 'api_customer_inquiry':
                // Only message if needed; no notes column
                if (array_key_exists('message', $data)) {
                    $payload['message'] = $data['message'];
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('api_customer_inquiry')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update($payload) > 0;

            case 'users_api_customers_reminders':
                if (array_key_exists('title', $data)) {
                    $payload['title'] = $data['title'];
                }
                if (array_key_exists('due_date', $data)) {
                    $payload['datetime'] = $data['due_date'];
                }
                if (array_key_exists('priority', $data)) {
                    $payload['priority'] = $this->mapPriorityAppointments($data['priority']);
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('users_api_customers_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update($payload) > 0;
        }

        return false;
    }

    /**
     * Append a note to an action (only for tables with notes/note column).
     */
    public function addNoteToAction(int $userId, string $actionId, string $note, string $addedBy): bool
    {
        $parsed = $this->parseActionId($actionId);
        if (!$parsed) {
            return false;
        }

        $now = Carbon::now();
        $line = '[' . $now->format('Y-m-d H:i') . '] ' . $addedBy . ': ' . $note . "\n";

        switch ($parsed['table']) {
            case 'reminders':
                $row = DB::table('reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->first(['notes']);
                if (!$row) {
                    return false;
                }
                $notes = ($row->notes ?? '') . $line;
                return DB::table('reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->update(['notes' => $notes, 'updated_at' => $now]) > 0;

            case 'users_property_requests':
                $row = DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first(['notes']);
                if (!$row) {
                    return false;
                }
                $notes = ($row->notes ?? '') . $line;
                return DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['notes' => $notes, 'updated_at' => $now]) > 0;

            case 'users_api_customers_appointments':
                $row = DB::table('users_api_customers_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first(['note']);
                if (!$row) {
                    return false;
                }
                $noteContent = ($row->note ?? '') . $line;
                return DB::table('users_api_customers_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['note' => $noteContent, 'updated_at' => $now]) > 0;

            case 'api_customer_inquiry':
            case 'users_api_customers_reminders':
                return false;
        }

        return false;
    }

    /**
     * Map API priority to reminders table (0=low, 1=medium, 2=high). Urgent maps to high.
     */
    private function mapPriorityReminders(?string $priority): int
    {
        return match ($priority) {
            'urgent', 'high' => 2,
            'medium' => 1,
            'low' => 0,
            default => 1,
        };
    }

    /**
     * Map API priority to appointments/customer_reminders (1=low, 2=medium, 3=high). Urgent maps to high.
     */
    private function mapPriorityAppointments(?string $priority): int
    {
        return match ($priority) {
            'urgent', 'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 2,
        };
    }

    /**
     * Unified bulk action dispatcher. Returns success/failed IDs and failures with reasons.
     *
     * @return array{success: string[], failed: string[], failures: array<int, array{actionId: string, reason: string}>, meta: array}
     */
    public function bulkAction(int $userId, string $action, array $actionIds, array $data): array
    {
        $actionIds = array_values(array_unique(array_slice($actionIds, 0, 1000)));
        $result = ['success' => [], 'failed' => [], 'failures' => [], 'meta' => []];

        $now = Carbon::now();
        $result['meta'][$action === 'complete' ? 'completedAt' : ($action === 'dismiss' ? 'dismissedAt' : ($action === 'snooze' ? 'snoozedAt' : ($action === 'assign' ? 'assignedAt' : 'changedAt')))] = $now->toIso8601String();

        switch ($action) {
            case 'complete':
                foreach ($actionIds as $actionId) {
                    if (!empty($data['notes'])) {
                        $this->addNoteToAction($userId, $actionId, $data['notes'], (string) ($data['completedBy'] ?? 'current_user'));
                    }
                    if ($this->completeAction($userId, $actionId)) {
                        $result['success'][] = $actionId;
                    } else {
                        $result['failed'][] = $actionId;
                        $result['failures'][] = ['actionId' => $actionId, 'reason' => 'ACTION_NOT_FOUND_OR_INVALID_STATE'];
                    }
                }
                break;
            case 'dismiss':
                foreach ($actionIds as $actionId) {
                    if (!empty($data['reason'])) {
                        $this->addNoteToAction($userId, $actionId, $data['reason'], (string) ($data['dismissedBy'] ?? 'current_user'));
                    }
                    if ($this->dismissAction($userId, $actionId)) {
                        $result['success'][] = $actionId;
                    } else {
                        $result['failed'][] = $actionId;
                        $result['failures'][] = ['actionId' => $actionId, 'reason' => 'ACTION_NOT_FOUND_OR_INVALID_STATE'];
                    }
                }
                break;
            case 'snooze':
                foreach ($actionIds as $actionId) {
                    if ($this->snoozeAction($userId, $actionId, $data['snoozedUntil'], (int) $data['snoozedBy'])) {
                        $result['success'][] = $actionId;
                    } else {
                        $result['failed'][] = $actionId;
                        $result['failures'][] = ['actionId' => $actionId, 'reason' => 'SNOOZE_NOT_SUPPORTED_OR_NOT_FOUND'];
                    }
                }
                break;
            case 'assign':
                $customerMap = $this->getCustomerIdsForActionIds($userId, $actionIds);
                $assignedTo = (int) $data['assignedTo'];
                foreach ($actionIds as $actionId) {
                    $customerId = $customerMap[$actionId] ?? null;
                    if ($customerId === null) {
                        $result['failed'][] = $actionId;
                        $result['failures'][] = ['actionId' => $actionId, 'reason' => 'NO_CUSTOMER'];
                        continue;
                    }
                    $updated = DB::table('api_customers')
                        ->where('id', $customerId)
                        ->where('user_id', $userId)
                        ->update(['responsible_employee_id' => $assignedTo, 'updated_at' => $now]);
                    if ($updated > 0) {
                        $result['success'][] = $actionId;
                    } else {
                        $result['failed'][] = $actionId;
                        $result['failures'][] = ['actionId' => $actionId, 'reason' => 'UPDATE_FAILED'];
                    }
                }
                break;
            case 'change_priority':
                foreach ($actionIds as $actionId) {
                    if ($this->updateAction($userId, $actionId, ['priority' => $data['priority']])) {
                        $result['success'][] = $actionId;
                    } else {
                        $result['failed'][] = $actionId;
                        $result['failures'][] = ['actionId' => $actionId, 'reason' => 'ACTION_NOT_FOUND_OR_PRIORITY_NOT_SUPPORTED'];
                    }
                }
                break;
        }

        return $result;
    }

    /**
     * Resolve action IDs to customer IDs (for bulk assign). Returns actionId => customerId|null.
     *
     * @return array<string, int|null>
     */
    public function getCustomerIdsForActionIds(int $userId, array $actionIds): array
    {
        $out = [];
        $byTable = [];
        foreach ($actionIds as $actionId) {
            $parsed = $this->parseActionId($actionId);
            if (!$parsed) {
                $out[$actionId] = null;
                continue;
            }
            $byTable[$parsed['table']][$parsed['sourceId']] = $actionId;
        }

        foreach ($byTable as $table => $idMap) {
            $ids = array_keys($idMap);
            if ($table === 'api_customer_inquiry') {
                $rows = DB::table('api_customer_inquiry')
                    ->where('user_id', $userId)
                    ->whereIn('id', $ids)
                    ->get(['id', 'customer_id']);
                foreach ($rows as $r) {
                    $actionId = $idMap[$r->id] ?? null;
                    if ($actionId !== null) {
                        $out[$actionId] = $r->customer_id;
                    }
                }
            } elseif ($table === 'users_property_requests') {
                $rows = DB::table('users_property_requests as upr')
                    ->leftJoin('api_customers as ac', function ($j) {
                        $j->on('upr.user_id', '=', 'ac.user_id')->on('upr.phone', '=', 'ac.phone_number');
                    })
                    ->where('upr.user_id', $userId)
                    ->whereIn('upr.id', $ids)
                    ->get(['upr.id', 'ac.id as customer_id']);
                foreach ($rows as $r) {
                    $actionId = $idMap[$r->id] ?? null;
                    if ($actionId !== null) {
                        $out[$actionId] = $r->customer_id;
                    }
                }
            } elseif ($table === 'reminders') {
                $rows = DB::table('reminders')
                    ->where('user_id', $userId)
                    ->whereIn('id', $ids)
                    ->whereNull('deleted_at')
                    ->get(['id', 'customer_id']);
                foreach ($rows as $r) {
                    $actionId = $idMap[$r->id] ?? null;
                    if ($actionId !== null) {
                        $out[$actionId] = $r->customer_id;
                    }
                }
            } elseif ($table === 'users_api_customers_appointments') {
                $rows = DB::table('users_api_customers_appointments')
                    ->where('user_id', $userId)
                    ->whereIn('id', $ids)
                    ->get(['id', 'customer_id']);
                foreach ($rows as $r) {
                    $actionId = $idMap[$r->id] ?? null;
                    if ($actionId !== null) {
                        $out[$actionId] = $r->customer_id;
                    }
                }
            } elseif ($table === 'users_api_customers_reminders') {
                $rows = DB::table('users_api_customers_reminders')
                    ->where('user_id', $userId)
                    ->whereIn('id', $ids)
                    ->get(['id', 'customer_id']);
                foreach ($rows as $r) {
                    $actionId = $idMap[$r->id] ?? null;
                    if ($actionId !== null) {
                        $out[$actionId] = $r->customer_id;
                    }
                }
            }
        }

        foreach ($actionIds as $actionId) {
            if (!array_key_exists($actionId, $out)) {
                $out[$actionId] = null;
            }
        }
        return $out;
    }

    /**
     * Snooze a single action (reminders, users_api_customers_reminders, users_api_customers_appointments only).
     */
    private function snoozeAction(int $userId, string $actionId, string $snoozedUntil, int $snoozedBy): bool
    {
        $parsed = $this->parseActionId($actionId);
        if (!$parsed) {
            return false;
        }
        $until = Carbon::parse($snoozedUntil);
        $now = Carbon::now();

        switch ($parsed['table']) {
            case 'reminders':
                return DB::table('reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->update([
                        'snoozed_until' => $until,
                        'snoozed_at' => $now,
                        'snoozed_by' => $snoozedBy,
                        'updated_at' => $now,
                    ]) > 0;
            case 'users_api_customers_reminders':
                return DB::table('users_api_customers_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'snoozed_until' => $until,
                        'snoozed_at' => $now,
                        'snoozed_by' => $snoozedBy,
                        'updated_at' => $now,
                    ]) > 0;
            case 'users_api_customers_appointments':
                return DB::table('users_api_customers_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'snoozed_until' => $until,
                        'snoozed_at' => $now,
                        'snoozed_by' => $snoozedBy,
                        'updated_at' => $now,
                    ]) > 0;
            case 'api_customer_inquiry':
            case 'users_property_requests':
                return false;
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
                DB::raw("'inquiry' as objectType"),
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
                'aci.city as city',
                'aci.region_name as state',
                'aci.budget as budgetMin',
                'aci.budget as budgetMax',
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
            ->leftJoin('user_cities as uc', 'upr.city_id', '=', 'uc.id')
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
                DB::raw("COALESCE(upr.source, 'website') as source"),
                DB::raw("'property_request' as objectType"),
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
                DB::raw('uc.name_ar as city'),
                'upr.region as state',
                'upr.budget_from as budgetMin',
                'upr.budget_to as budgetMax',
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
                    WHEN r.snoozed_until IS NOT NULL AND r.snoozed_until > NOW() THEN 'snoozed'
                    WHEN r.status = 'completed' THEN 'completed'
                    WHEN r.status = 'cancelled' THEN 'dismissed'
                    ELSE 'pending'
                END as status"),
                DB::raw("COALESCE(r.source, 'manual') as source"),
                DB::raw("'reminder' as objectType"),
                'r.datetime as dueDate',
                'r.snoozed_until as snoozedUntil',
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
                DB::raw("NULL as city"),
                DB::raw("NULL as state"),
                DB::raw("NULL as budgetMin"),
                DB::raw("NULL as budgetMax"),
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
                DB::raw("CASE
                    WHEN a.snoozed_until IS NOT NULL AND a.snoozed_until > NOW() THEN 'snoozed'
                    WHEN a.datetime < NOW() THEN 'completed'
                    ELSE 'pending'
                END as status"),
                DB::raw("COALESCE(a.source, 'manual') as source"),
                DB::raw("'appointment' as objectType"),
                'a.datetime as dueDate',
                'a.snoozed_until as snoozedUntil',
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
                DB::raw("NULL as city"),
                DB::raw("NULL as state"),
                DB::raw("NULL as budgetMin"),
                DB::raw("NULL as budgetMax"),
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
                DB::raw("CASE
                    WHEN cr.snoozed_until IS NOT NULL AND cr.snoozed_until > NOW() THEN 'snoozed'
                    ELSE 'pending'
                END as status"),
                DB::raw("COALESCE(cr.source, 'manual') as source"),
                DB::raw("'customer_reminder' as objectType"),
                'cr.datetime as dueDate',
                'cr.snoozed_until as snoozedUntil',
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
                DB::raw("NULL as city"),
                DB::raw("NULL as state"),
                DB::raw("NULL as budgetMin"),
                DB::raw("NULL as budgetMax"),
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

        // Object types filter
        if (!empty($filters['objectTypes']) && is_array($filters['objectTypes'])) {
            $query->whereIn('objectType', $filters['objectTypes']);
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

        // Cities filter (request-level)
        if (!empty($filters['cities']) && is_array($filters['cities'])) {
            $query->whereIn('city', $filters['cities']);
        }

        // States filter (request-level)
        if (!empty($filters['states']) && is_array($filters['states'])) {
            $query->whereIn('state', $filters['states']);
        }

        // Budget range filter: request's budget range overlaps [budget_min, budget_max]
        $budgetMin = isset($filters['budget_min']) && $filters['budget_min'] !== '' ? (float) $filters['budget_min'] : null;
        $budgetMax = isset($filters['budget_max']) && $filters['budget_max'] !== '' ? (float) $filters['budget_max'] : null;
        if ($budgetMin !== null || $budgetMax !== null) {
            $query->where(function ($q) use ($budgetMin, $budgetMax) {
                $q->whereNotNull('budgetMin');
                if ($budgetMin !== null && $budgetMax !== null) {
                    $q->where('budgetMin', '<=', $budgetMax)
                        ->where(function ($q2) use ($budgetMin) {
                            $q2->where('budgetMax', '>=', $budgetMin)->orWhereNull('budgetMax');
                        });
                } elseif ($budgetMin !== null) {
                    $q->where(function ($q2) use ($budgetMin) {
                        $q2->where('budgetMax', '>=', $budgetMin)->orWhereNull('budgetMax');
                    });
                } else {
                    $q->where('budgetMin', '<=', $budgetMax);
                }
            });
        }
    }

    /**
     * Enrich action items with stage (stage_id and stage object).
     * For property_request items uses pipeline stage (users_property_requests.status_id → property_request_statuses).
     * For other items uses customer hub stage (api_customers.customers_hub_stage_id → customers_hub_stages).
     */
    private function enrichItemsWithHubStage(Collection $items, int $userId): Collection
    {
        $items = $items->values();
        $propertyRequestIds = $items->filter(function ($item) {
            return ($item->sourceTable ?? '') === 'users_property_requests';
        })->pluck('sourceId')->filter()->unique()->values()->all();

        // Pipeline stage for property_request items (property_request_statuses)
        $requestStageMap = [];
        if (!empty($propertyRequestIds)) {
            $requestStatuses = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereIn('id', $propertyRequestIds)
                ->get(['id', 'status_id']);
            $statusIds = $requestStatuses->pluck('status_id')->filter()->unique()->values()->all();
            if (!empty($statusIds)) {
                $stages = DB::table('property_request_statuses')
                    ->whereIn('id', $statusIds)
                    ->where('is_active', true)
                    ->get(['id', 'name_ar', 'name_en']);
                $stageById = $stages->keyBy('id');
                foreach ($requestStatuses as $row) {
                    if ($row->status_id === null) {
                        $requestStageMap[$row->id] = null;
                        continue;
                    }
                    $s = $stageById->get($row->status_id);
                    $requestStageMap[$row->id] = $s ? (object) [
                        'id' => (int) $s->id,
                        'nameAr' => $s->name_ar,
                        'nameEn' => $s->name_en ?? $s->name_ar,
                    ] : null;
                }
            }
        }

        // Customer hub stage for non–property_request items
        $customerIds = $items->reject(function ($item) {
            return ($item->sourceTable ?? '') === 'users_property_requests';
        })->pluck('customerId')->filter()->unique()->values()->all();

        $customerStageIds = [];
        $stageRows = [];
        if (!empty($customerIds)) {
            $customerStageIds = DB::table('api_customers')
                ->whereIn('id', $customerIds)
                ->where('user_id', $userId)
                ->get(['id', 'customers_hub_stage_id']);
            $stageIds = $customerStageIds->pluck('customers_hub_stage_id')->filter()->unique()->values()->all();
            if (!empty($stageIds)) {
                $stages = DB::table('customers_hub_stages')->whereIn('stage_id', $stageIds)->get();
                foreach ($stages as $s) {
                    $stageRows[$s->stage_id] = (object) [
                        'stage_id' => $s->stage_id,
                        'stage_name_ar' => $s->stage_name_ar,
                        'stage_name_en' => $s->stage_name_en,
                        'color' => $s->color,
                        'order' => (int) $s->order,
                    ];
                }
            }
        }

        $customerStages = [];
        foreach ($customerStageIds as $row) {
            $customerStages[$row->id] = $row->customers_hub_stage_id !== null
                ? ($stageRows[$row->customers_hub_stage_id] ?? null)
                : null;
        }

        return $items->map(function ($item) use ($requestStageMap, $customerStages) {
            if (($item->sourceTable ?? '') === 'users_property_requests') {
                $stageData = $requestStageMap[$item->sourceId] ?? null;
                $item->stage_id = $stageData ? $stageData->id : null;
                $item->stage = $stageData;
                return $item;
            }
            $stageData = $customerStages[$item->customerId] ?? null;
            $item->stage_id = $stageData ? $stageData->stage_id : null;
            $item->stage = $stageData;
            return $item;
        });
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
            'source' => $item->source ?? '',
            'objectType' => $item->objectType ?? '',
            'dueDate' => $item->dueDate ? Carbon::parse($item->dueDate)->toIso8601String() : null,
            'snoozedUntil' => $item->snoozedUntil ? Carbon::parse($item->snoozedUntil)->toIso8601String() : null,
            'createdAt' => $item->createdAt ? Carbon::parse($item->createdAt)->toIso8601String() : null,
            'completedAt' => $item->completedAt ? Carbon::parse($item->completedAt)->toIso8601String() : null,
            'completedBy' => $item->completedBy,
            'assignedTo' => $item->assignedTo,
            'assignedToName' => trim($item->assignedToName ?? ''),
            'propertyCategory' => $item->propertyCategory ?? null,
            'propertyType' => $item->propertyType ?? null,
            'city' => $item->city ?? null,
            'state' => $item->state ?? null,
            'budgetMin' => isset($item->budgetMin) && $item->budgetMin !== null ? (float) $item->budgetMin : null,
            'budgetMax' => isset($item->budgetMax) && $item->budgetMax !== null ? (float) $item->budgetMax : null,
            'metadata' => $metadata,
            'sourceTable' => $item->sourceTable,
            'sourceId' => $item->sourceId,
        ];
    }
}
