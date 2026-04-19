<?php

namespace App\Domain\CustomersHub\Services;

use App\Models\ApiCustomer;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ActionsAggregatorService
 *
 * Aggregates customer actions from multiple legacy tables using UNION ALL.
 * This is a READ-ONLY layer that does NOT modify legacy tables.
 *
 * Source tables (UNION):
 * - api_customer_inquiry (one row per customer: latest inquiry only; hidden if an active property request exists for same customer/phone)
 * - users_property_requests (property matches)
 * - reminders (follow-ups)
 *
 * Note: property_request_appointments and property_request_reminders are NOT unioned as separate
 * actions — they are attached to property_request rows in RequestsController::list to avoid duplicate cards.
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
    public const PREFIX_REQUEST_APPOINTMENT = 'request_appointment_';
    public const PREFIX_REQUEST_REMINDER = 'request_reminder_';

    /**
     * Get the unified UNION ALL query for customer actions.
     */
    public function getUnifiedQuery(int $userId, array $filters = []): \Illuminate\Database\Query\Builder
    {
        $inquiriesQuery = $this->getInquiriesSubquery($userId);
        $propertyRequestsQuery = $this->getPropertyRequestsSubquery($userId);
        $remindersQuery = $this->getRemindersSubquery($userId);

        // Build UNION ALL.
        // property_request_appointments and property_request_reminders are intentionally excluded here:
        // they are already nested inside each property_request card by RequestsController::list,
        // so including them in the UNION would show them twice (once nested, once as a top-level card).
        $unionQuery = $inquiriesQuery
            ->unionAll($propertyRequestsQuery)
            ->unionAll($remindersQuery);

        // Wrap in subquery for filtering and ordering
        $query = DB::query()->fromSub($unionQuery, 'actions');

        // Apply filters
        $this->applyFilters($query, $filters, $userId);

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
        $sortBy = $filters['sort_by'] ?? 'updatedAt';
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
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
        ];
    }

    /**
     * Get statistics for the actions.
     */
    public function getStats(int $userId, array $filters = []): array
    {
        $cacheKey = 'ch_stats_' . $userId . '_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 30, function () use ($userId, $filters) {
            $query = $this->getUnifiedQuery($userId, $filters);

            $stats = $query->selectRaw("
                SUM(CASE WHEN type IN ('new_inquiry', 'callback_request', 'whatsapp_incoming') AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as inbox,
                SUM(CASE WHEN type IN ('follow_up', 'site_visit') AND status IN ('pending', 'in_progress') THEN 1 ELSE 0 END) as followups,
                SUM(CASE 
                    WHEN customers_hub_stage_id IS NULL THEN 1
                    WHEN customers_hub_stage_id NOT IN ('deal_completed', 'deal_rejected') THEN 1
                    ELSE 0 
                END) as pending,
                SUM(CASE WHEN dueDate < NOW() AND status IN ('pending', 'in_progress', 'in_waiting') THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN DATE(dueDate) = CURRENT_DATE AND status IN ('pending', 'in_progress', 'in_waiting') THEN 1 ELSE 0 END) as today,
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
        });
    }

    /**
     * Raw counts for the previous calendar month vs reference month labels (Customers Hub list stats).
     */
    public function getComparisonStats(int $userId, array $filters = []): array
    {
        $cacheKey = 'ch_comparison_stats_' . $userId . '_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 60, function () use ($userId, $filters) {
            $now = Carbon::now();

            if (!empty($filters['date_from'])) {
                $refDate = Carbon::parse($filters['date_from'])->startOfMonth();
            } else {
                $refDate = $now->copy()->subMonthNoOverflow()->startOfMonth();
            }

            $currentStart = $refDate->copy()->startOfMonth();

            $compareStart = $refDate->copy()->subMonthNoOverflow()->startOfMonth();
            $compareEnd = $compareStart->copy()->endOfMonth();

            $compareFilters = $filters;
            $compareFilters['date_from'] = $compareStart->toDateString();
            $compareFilters['date_to'] = $compareEnd->toDateString();

            $compareStats = $this->getStats($userId, $compareFilters);

            return [
                'inboxComparing' => $compareStats['inbox'],
                'followupsComparing' => $compareStats['followups'],
                'pendingComparing' => $compareStats['pending'],
                'overdueComparing' => $compareStats['overdue'],
                'completedComparing' => $compareStats['completed'],
                'month starts comparing' => $currentStart->format('F Y'),
                'month ends comparing' => $compareStart->format('F Y'),
            ];
        });
    }

    /**
     * Get stage statistics for the filtered actions (pipeline: customers_hub_stages).
     * Returns request count and percentage per pipeline stage (requests + inquiries).
     */
    public function getStageStats(int $userId, array $filters = []): array
    {
        $cacheKey = 'ch_stage_stats_' . $userId . '_' . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, 30, function () use ($userId, $filters) {
            try {
                [$countsRequests, $countsInquiries, $total] = $this->getHubStageCounts($userId, $filters);
                return $this->buildHubStagesArray($userId, $countsRequests, $countsInquiries, $total);
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Count by customers_hub_stage_id (requests) and stage_id (inquiries).
     *
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: int}
     */
    private function getHubStageCounts(int $userId, array $filters): array
    {
        $requestQuery = DB::table('users_property_requests as upr')
            ->where('upr.user_id', $userId)
            ->where('upr.is_active', 1);

        $this->applyPropertyRequestFilters($requestQuery, $filters, $userId);

        $countsRequests = (clone $requestQuery)
            ->whereNotNull('upr.customers_hub_stage_id')
            ->groupBy('upr.customers_hub_stage_id')
            ->selectRaw('upr.customers_hub_stage_id as stage_id, COUNT(*) as request_count')
            ->get()
            ->keyBy('stage_id');

        $inquiryQuery = DB::table('api_customer_inquiry as aci')
            ->where('aci.user_id', $userId);
        $countsInquiries = (clone $inquiryQuery)
            ->whereNotNull('aci.stage_id')
            ->groupBy('aci.stage_id')
            ->selectRaw('aci.stage_id as stage_id, COUNT(*) as inquiry_count')
            ->get()
            ->keyBy('stage_id');

        $totalRequests = (int) $countsRequests->sum('request_count');
        $totalInquiries = (int) $countsInquiries->sum('inquiry_count');
        $total = $totalRequests + $totalInquiries;

        return [$countsRequests, $countsInquiries, $total];
    }

    /**
     * Build stages array from customers_hub_stages with requestCount + inquiry count and percentage.
     */
    private function buildHubStagesArray(int $userId, \Illuminate\Support\Collection $countsRequests, \Illuminate\Support\Collection $countsInquiries, int $total): array
    {
        $presenter = app(CustomersHubStagesPresenter::class);
        $stages = $presenter->listStages($userId, true)
            ->map(fn ($s) => (object) [
                'stage_id' => $s->stage_id,
                'stage_name_ar' => $s->stage_name_ar,
                'stage_name_en' => $s->stage_name_en,
                'color' => $s->color,
                'order' => (int) $s->order,
            ]);

        if ($stages->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($stages as $stage) {
            $reqCount = (int) ($countsRequests->get($stage->stage_id)?->request_count ?? 0);
            $inqCount = (int) ($countsInquiries->get($stage->stage_id)?->inquiry_count ?? 0);
            $requestCount = $reqCount + $inqCount;
            $percentage = $total > 0 ? round(($requestCount / $total) * 100, 1) : 0.0;

            $result[] = [
                'stage_id' => $stage->stage_id,
                'stage_name_ar' => $stage->stage_name_ar,
                'stage_name_en' => $stage->stage_name_en ?? $stage->stage_name_ar,
                'color' => $stage->color ?? '#6b7280',
                'order' => (int) $stage->order,
                'requestCount' => $requestCount,
                'percentage' => $percentage,
            ];
        }

        return $result;
    }

    /**
     * Apply list filters to a query on users_property_requests (upr).
     * Adds joins only when required by filters (cities -> user_cities; assignees/customer_id -> api_customers).
     *
     * @param  int|null  $userId  Required when appointment_types filter is used (for stage counts).
     */
    private function applyPropertyRequestFilters(\Illuminate\Database\Query\Builder $query, array $filters, ?int $userId = null): void
    {
        $hasCities = !empty($filters['cities']) && is_array($filters['cities']);
        $hasAssignees = !empty($filters['assignees']) && is_array($filters['assignees']);
        $hasCustomerId = !empty($filters['customer_id']);
        $needsAc = $hasAssignees || $hasCustomerId;

        if ($hasCities && !$this->queryHasJoin($query, 'user_cities')) {
            $query->leftJoin('user_cities as uc', 'upr.city_id', '=', 'uc.id');
        }
        if ($needsAc && !$this->queryHasJoin($query, 'api_customers')) {
            $query->leftJoin('api_customers as ac', function ($join) {
                $join->on('upr.user_id', '=', 'ac.user_id')
                    ->on('upr.phone', '=', 'ac.phone_number');
            });
        }

        // Tab filter
        if (!empty($filters['tab'])) {
            switch ($filters['tab']) {
                case 'all':
                    $query->where('upr.is_archived', 0);
                    break;
                case 'completed':
                    $query->where('upr.is_archived', 1);
                    break;
                case 'inbox':
                case 'followups':
                    // Property requests only; treat same as all
                    $query->where('upr.is_archived', 0);
                    break;
            }
        }

        // Types: property_request type is always property_match; if types exclude it, no rows
        if (!empty($filters['types']) && is_array($filters['types']) && !in_array('property_match', $filters['types'])) {
            $query->whereRaw('1 = 0');
        }

        // Statuses: map API status to upr is_archived / is_read
        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->where(function ($q) use ($filters) {
                $statuses = $filters['statuses'];
                $ors = [];
                if (in_array('dismissed', $statuses) || in_array('completed', $statuses)) {
                    $ors[] = function ($q2) {
                        $q2->where('upr.is_archived', 1);
                    };
                }
                if (in_array('in_progress', $statuses)) {
                    $ors[] = function ($q2) {
                        $q2->where('upr.is_archived', 0)->where('upr.is_read', 1);
                    };
                }
                if (in_array('pending', $statuses)) {
                    $ors[] = function ($q2) {
                        $q2->where('upr.is_archived', 0)->where('upr.is_read', 0);
                    };
                }
                if (!empty($ors)) {
                    foreach ($ors as $or) {
                        $q->orWhere($or);
                    }
                }
            });
        }

        // Priorities: map API priority to upr.seriousness
        if (!empty($filters['priorities']) && is_array($filters['priorities'])) {
            $seriousnessMap = [
                'urgent' => 'مستعد فورًا',
                'high' => 'خلال شهر',
                'medium' => 'خلال 3 أشهر',
                'low' => 'لاحقًا / استكشاف فقط',
            ];
            $seriousness = [];
            foreach ($filters['priorities'] as $p) {
                if (isset($seriousnessMap[$p])) {
                    $seriousness[] = $seriousnessMap[$p];
                }
            }
            if (!empty($seriousness)) {
                $query->whereIn('upr.seriousness', $seriousness);
            }
        }

        // Sources
        if (!empty($filters['sources']) && is_array($filters['sources'])) {
            $query->whereIn(DB::raw('COALESCE(upr.source, \'website\')'), $filters['sources']);
        }

        // Assignees (requires ac join)
        if ($hasAssignees) {
            $query->where(function ($q) use ($filters) {
                $q->whereIn('upr.responsible_employee_id', $filters['assignees'])
                    ->orWhereIn('ac.responsible_employee_id', $filters['assignees'])
                    ->orWhereIn('ac_phone.responsible_employee_id', $filters['assignees']);
            });
        }

        // Customer ID (requires ac join)
        if ($hasCustomerId) {
            $query->where('ac.id', $filters['customer_id']);
        }

        // Property categories
        if (!empty($filters['property_categories']) && is_array($filters['property_categories'])) {
            $query->whereIn('upr.category_id', $filters['property_categories']);
        }

        // Property types
        if (!empty($filters['property_types']) && is_array($filters['property_types'])) {
            $query->whereIn('upr.property_type', $filters['property_types']);
        }

        // Cities (requires user_cities join)
        if ($hasCities) {
            $query->whereIn('uc.name_ar', $filters['cities']);
        }

        // Districts
        if (!empty($filters['districts']) && is_array($filters['districts'])) {
            $query->whereIn('upr.districts_id', $filters['districts']);
        }

        // Budget range
        $budgetMin = isset($filters['budget_min']) && $filters['budget_min'] !== '' ? (float) $filters['budget_min'] : null;
        $budgetMax = isset($filters['budget_max']) && $filters['budget_max'] !== '' ? (float) $filters['budget_max'] : null;
        if ($budgetMin !== null || $budgetMax !== null) {
            $query->where(function ($q) use ($budgetMin, $budgetMax) {
                $q->whereNotNull('upr.budget_from');
                if ($budgetMin !== null && $budgetMax !== null) {
                    $q->where('upr.budget_from', '<=', $budgetMax)
                        ->where(function ($q2) use ($budgetMin) {
                            $q2->where('upr.budget_to', '>=', $budgetMin)->orWhereNull('upr.budget_to');
                        });
                } elseif ($budgetMin !== null) {
                    $q->where(function ($q2) use ($budgetMin) {
                        $q2->where('upr.budget_to', '>=', $budgetMin)->orWhereNull('upr.budget_to');
                    });
                } else {
                    $q->where('upr.budget_from', '<=', $budgetMax);
                }
            });
        }

        // Date range
        if (!empty($filters['date_from'])) {
            $query->where('upr.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('upr.created_at', '<=', $filters['date_to']);
        }

        // Search (upr columns only)
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('upr.full_name', 'like', $search)
                    ->orWhere('upr.notes', 'like', $search)
                    ->orWhere('upr.phone', 'like', $search);
            });
        }

        // Appointment type filter: only requests that have at least one appointment of the given type(s)
        if (!empty($filters['appointment_types']) && is_array($filters['appointment_types']) && $userId !== null) {
            $query->whereExists(function ($sub) use ($userId, $filters) {
                $sub->select(DB::raw(1))
                    ->from('property_request_appointments as pra')
                    ->whereColumn('pra.property_request_id', 'upr.id')
                    ->where('pra.user_id', $userId)
                    ->whereIn('pra.type', $filters['appointment_types']);
            });
        }
    }

    /**
     * Check if the query already has a join to the given table (by alias or name).
     */
    private function queryHasJoin(\Illuminate\Database\Query\Builder $query, string $table): bool
    {
        $table = strtolower($table);
        foreach ($query->joins ?? [] as $join) {
            $joinTable = $join->table;
            if (is_string($joinTable) && strtolower($joinTable) === $table) {
                return true;
            }
            if (is_string($joinTable) && preg_match('/^\s*(\w+)\s+as\s+(\w+)/i', $joinTable, $m)) {
                if (strtolower($m[1]) === $table || strtolower($m[2]) === $table) {
                    return true;
                }
            }
        }
        return false;
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
            'request_appointment_' => 'property_request_appointments',
            'request_reminder_' => 'property_request_reminders',
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
                        'stage_id' => 'deal_completed',
                        'updated_at' => $now,
                    ]) > 0;

            case 'users_property_requests':
                return DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'is_read' => 1,
                        'is_archived' => 0,
                        'customers_hub_stage_id' => 'deal_completed',
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

            case 'property_request_appointments':
                return DB::table('property_request_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['status' => 'completed', 'updated_at' => $now]) > 0;

            case 'property_request_reminders':
                return DB::table('property_request_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['status' => 'completed', 'updated_at' => $now]) > 0;
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
                        'stage_id' => 'deal_rejected',
                        'updated_at' => $now,
                    ]) > 0;

            case 'users_property_requests':
                return DB::table('users_property_requests')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'is_archived' => 1,
                        'customers_hub_stage_id' => 'deal_rejected',
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

            case 'property_request_appointments':
                return DB::table('property_request_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['status' => 'cancelled', 'updated_at' => $now]) > 0;

            case 'property_request_reminders':
                return DB::table('property_request_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['status' => 'cancelled', 'updated_at' => $now]) > 0;
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
                if (array_key_exists('priority', $data)) {
                    $seriousness = $this->mapPriorityPropertyRequestsToSeriousness($data['priority']);
                    if ($seriousness !== null) {
                        $payload['seriousness'] = $seriousness;
                    }
                }
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

            case 'property_request_appointments':
                if (array_key_exists('title', $data)) {
                    $payload['title'] = $data['title'];
                }
                if (array_key_exists('notes', $data)) {
                    $payload['notes'] = $data['notes'];
                }
                if (array_key_exists('due_date', $data)) {
                    $payload['datetime'] = $data['due_date'];
                }
                if (array_key_exists('priority', $data)) {
                    $payload['priority'] = $this->mapPriorityRequestAppointments($data['priority']);
                }
                if (array_key_exists('duration', $data)) {
                    $payload['duration'] = (int) $data['duration'];
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('property_request_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update($payload) > 0;

            case 'property_request_reminders':
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
                    $payload['priority'] = $this->mapPriorityRequestReminders($data['priority']);
                }
                if (array_key_exists('notes', $data)) {
                    $payload['notes'] = $data['notes'];
                }
                if (count($payload) <= 1) {
                    return true;
                }
                return DB::table('property_request_reminders')
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

            case 'property_request_appointments':
                $row = DB::table('property_request_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first(['notes']);
                if (!$row) {
                    return false;
                }
                $notes = ($row->notes ?? '') . $line;
                return DB::table('property_request_appointments')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['notes' => $notes, 'updated_at' => $now]) > 0;

            case 'property_request_reminders':
                $row = DB::table('property_request_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first(['notes']);
                if (!$row) {
                    return false;
                }
                $notes = ($row->notes ?? '') . $line;
                return DB::table('property_request_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update(['notes' => $notes, 'updated_at' => $now]) > 0;

            case 'api_customer_inquiry':
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
     * Map API priority to appointments (1=low, 2=medium, 3=high). Urgent maps to high.
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
     * Map API priority to property_request_appointments (1=low, 2=medium, 3=high, 4=urgent).
     */
    private function mapPriorityRequestAppointments(?string $priority): int
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
     * Map API priority to property_request_reminders (0=low, 1=medium, 2=high, 3=urgent).
     */
    private function mapPriorityRequestReminders(?string $priority): int
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
     * Map API priority to users_property_requests.seriousness (Arabic labels).
     * Default null when unsupported, so callers can decide on a fallback.
     */
    private function mapPriorityPropertyRequestsToSeriousness(?string $priority): ?string
    {
        return match ($priority) {
            self::PRIORITY_URGENT => 'مستعد فورًا',
            self::PRIORITY_HIGH => 'خلال شهر',
            self::PRIORITY_MEDIUM => 'خلال 3 أشهر',
            self::PRIORITY_LOW => 'لاحقًا / استكشاف فقط',
            default => null,
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
                    $parsed = $this->parseActionId($actionId);
                    if ($parsed && $parsed['table'] === 'api_customer_inquiry') {
                        $updated = DB::table('api_customer_inquiry')
                            ->where('id', $parsed['sourceId'])
                            ->where('user_id', $userId)
                            ->update(['responsible_employee_id' => $assignedTo, 'updated_at' => $now]);
                        if ($updated > 0) {
                            $result['success'][] = $actionId;
                        } else {
                            $result['failed'][] = $actionId;
                            $result['failures'][] = ['actionId' => $actionId, 'reason' => 'UPDATE_FAILED'];
                        }
                        continue;
                    }

                    $customerId = $customerMap[$actionId] ?? null;

                    // Property requests: always keep both users_property_requests and api_customers in sync.
                    if ($parsed && $parsed['table'] === 'users_property_requests') {
                        if ($customerId !== null) {
                            $ok = $this->syncAssignWithCustomer($userId, (int) $parsed['sourceId'], (int) $customerId, $assignedTo, $now);
                            if ($ok) {
                                $result['success'][] = $actionId;
                            } else {
                                $result['failed'][] = $actionId;
                                $result['failures'][] = ['actionId' => $actionId, 'reason' => 'UPDATE_FAILED'];
                            }
                            continue;
                        }

                        $outcome = $this->resolveOrCreateCustomerForPropertyRequest($userId, (int) $parsed['sourceId'], $assignedTo, $now);
                        if ($outcome['success']) {
                            $result['success'][] = $actionId;
                        } else {
                            $result['failed'][] = $actionId;
                            $result['failures'][] = ['actionId' => $actionId, 'reason' => $outcome['reason']];
                        }
                        continue;
                    }

                    // Other action types: keep existing behavior.
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
                    ->leftJoin('api_customers as ac', 'upr.customer_id', '=', 'ac.id')
                    ->leftJoin('api_customers as ac_phone', function ($j) {
                        $j->on('upr.user_id', '=', 'ac_phone.user_id')
                            ->on('upr.phone', '=', 'ac_phone.phone_number');
                    })
                    ->where('upr.user_id', $userId)
                    ->whereIn('upr.id', $ids)
                    ->select(['upr.id', DB::raw('COALESCE(ac.id, ac_phone.id) as customer_id')])
                    ->get();
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
            } elseif ($table === 'property_request_appointments') {
                $rows = DB::table('property_request_appointments')
                    ->where('user_id', $userId)
                    ->whereIn('id', $ids)
                    ->get(['id', 'customer_id']);
                foreach ($rows as $r) {
                    $actionId = $idMap[$r->id] ?? null;
                    if ($actionId !== null) {
                        $out[$actionId] = $r->customer_id;
                    }
                }
            } elseif ($table === 'property_request_reminders') {
                $rows = DB::table('property_request_reminders')
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
     * Snooze a single action (reminders, property_request_appointments, property_request_reminders only).
     */
    public function snoozeAction(int $userId, string $actionId, string $snoozedUntil, int $snoozedBy): bool
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
            case 'property_request_reminders':
                return DB::table('property_request_reminders')
                    ->where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->update([
                        'snoozed_until' => $until,
                        'snoozed_at' => $now,
                        'snoozed_by' => $snoozedBy,
                        'updated_at' => $now,
                    ]) > 0;
            case 'property_request_appointments':
                return DB::table('property_request_appointments')
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
    public function bulkDismiss(int $userId, array $actionIds, string $reason): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($actionIds as $actionId) {
            if ($this->dismissAction($userId, $actionId)) {
                $results['success'][] = $actionId;
                $this->addNoteToAction($userId, $actionId, $reason, 'current_user');
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
     *
     * Two deduplication rules applied here:
     *  1. Only the LATEST inquiry per customer (MAX id grouped by customer_id) is returned,
     *     so a customer who sent 10 inquiries shows as one card, not ten.
     *  2. If an active property_request already exists for the same tenant and the same
     *     customer (matched by customer_id OR by phone number), the inquiry is suppressed —
     *     the property_request card already represents that customer.
     */
    private function getInquiriesSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        // Subquery: MAX inquiry id per customer for this tenant
        $latestIds = DB::table('api_customer_inquiry')
            ->selectRaw('MAX(id) as id')
            ->where('user_id', $userId)
            ->groupBy('customer_id');

        return DB::table('api_customer_inquiry as aci')
            ->joinSub($latestIds, 'li', 'aci.id', '=', 'li.id')
            ->join('api_customers as ac', 'aci.customer_id', '=', 'ac.id')
            ->leftJoin('users as u', 'aci.responsible_employee_id', '=', 'u.id')
            ->leftJoin('users_property_requests as upr_dedup', function ($join) use ($userId) {
                $join->where('upr_dedup.user_id', $userId)
                    ->where('upr_dedup.is_active', 1)
                    ->where(function ($j2) {
                        $j2->where(function ($j3) {
                            // matched by customer_id
                            $j3->whereNotNull('upr_dedup.customer_id')
                                ->on('upr_dedup.customer_id', '=', 'aci.customer_id');
                        })->orWhere(function ($j3) {
                            // matched by phone number
                            $j3->whereNotNull('upr_dedup.phone')
                                ->whereNotNull(DB::raw("'{$userId}'"))
                                ->on('upr_dedup.phone', '=', 'ac.phone_number');
                        });
                    });
            })
            ->where('aci.user_id', $userId)
            ->whereNull('upr_dedup.id')
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
                'aci.updated_at as updatedAt',
                DB::raw("NULL as completedAt"),
                DB::raw("NULL as completedBy"),
                'aci.responsible_employee_id as assignedTo',
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
                DB::raw("NULL as propertyRequestStatusId"),
                DB::raw("NULL as propertyRequestStatusSlug"),
                DB::raw("NULL as propertyRequestStatusNameAr"),
                DB::raw("NULL as propertyRequestStatusNameEn"),
                DB::raw("NULL as districts_id"),
                DB::raw("NULL as districtAR"),
                'aci.stage_id as customers_hub_stage_id',
            ]);
    }

    /**
     * Build property requests subquery.
     */
    private function getPropertyRequestsSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('users_property_requests as upr')
            ->leftJoin('api_customers as ac', function ($join) {
                $join->on('ac.id', '=', 'upr.customer_id')
                    ->on('ac.user_id', '=', 'upr.user_id');
            })
            ->leftJoin('api_customers as ac_phone', function ($join) {
                $join->on('ac_phone.user_id', '=', 'upr.user_id')
                    ->on('ac_phone.phone_number', '=', 'upr.phone');
            })
            ->leftJoin('user_cities as uc', 'upr.city_id', '=', 'uc.id')
            ->leftJoin('user_districts as ud_req', 'upr.districts_id', '=', 'ud_req.id')
            ->leftJoin('property_request_statuses as prs', 'upr.status_id', '=', 'prs.id')
            ->leftJoin('customers_hub_status_mapping as chsm', 'prs.slug', '=', 'chsm.property_request_status_slug')
            ->leftJoin('users as u2', DB::raw('u2.id'), '=', DB::raw('COALESCE(upr.responsible_employee_id, ac.responsible_employee_id, ac_phone.responsible_employee_id)'))
            ->where('upr.user_id', $userId)
            ->where('upr.is_active', 1)
            ->select([
                DB::raw("CONCAT('property_request_', upr.id) as id"),
                DB::raw('COALESCE(ac.id, ac_phone.id) as customerId'),
                DB::raw("COALESCE(ac.name, ac_phone.name, upr.full_name) as customerName"),
                DB::raw('COALESCE(ac.phone_number, ac_phone.phone_number, upr.phone) as customerPhone'),
                DB::raw("'property_match' as type"),
                DB::raw("CONCAT('عقار مطابق: ', COALESCE(ac.name, ac_phone.name, upr.full_name)) as title"),
                'upr.notes as description',
                DB::raw("CASE upr.seriousness
                    WHEN 'مستعد فورًا' THEN 'urgent'
                    WHEN 'خلال شهر' THEN 'high'
                    WHEN 'خلال 3 أشهر' THEN 'medium'
                    WHEN 'لاحقًا / استكشاف فقط' THEN 'low'
                    ELSE 'medium'
                END as priority"),
                DB::raw("COALESCE(chsm.customers_hub_status,
                    CASE
                        WHEN upr.is_archived = 1 THEN 'dismissed'
                        WHEN upr.is_read = 1 THEN 'in_progress'
                        ELSE 'pending'
                    END
                ) as status"),
                DB::raw("COALESCE(upr.source, 'website') as source"),
                DB::raw("'property_request' as objectType"),
                DB::raw("NULL as dueDate"),
                DB::raw("NULL as snoozedUntil"),
                'upr.created_at as createdAt',
                'upr.updated_at as updatedAt',
                DB::raw("NULL as completedAt"),
                DB::raw("NULL as completedBy"),
                DB::raw('COALESCE(upr.responsible_employee_id, ac.responsible_employee_id, ac_phone.responsible_employee_id) as assignedTo'),
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
                'upr.status_id as propertyRequestStatusId',
                'prs.slug as propertyRequestStatusSlug',
                'prs.name_ar as propertyRequestStatusNameAr',
                'prs.name_en as propertyRequestStatusNameEn',
                'upr.districts_id as districts_id',
                'ud_req.name_ar as districtAR',
                'upr.customers_hub_stage_id as customers_hub_stage_id',
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
                'r.updated_at as updatedAt',
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
                DB::raw("NULL as propertyRequestStatusId"),
                DB::raw("NULL as propertyRequestStatusSlug"),
                DB::raw("NULL as propertyRequestStatusNameAr"),
                DB::raw("NULL as propertyRequestStatusNameEn"),
                DB::raw("NULL as districts_id"),
                DB::raw("NULL as districtAR"),
                DB::raw("NULL as customers_hub_stage_id"),
            ]);
    }

    /**
     * Build request-level appointments subquery (property_request_appointments).
     */
    private function getRequestAppointmentsSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('property_request_appointments as a')
            ->leftJoin('api_customers as ac', 'a.customer_id', '=', 'ac.id')
            ->leftJoin('users_property_requests as upr', 'a.property_request_id', '=', 'upr.id')
            ->where('a.user_id', $userId)
            ->select([
                DB::raw("CONCAT('request_appointment_', a.id) as id"),
                'a.customer_id as customerId',
                DB::raw('COALESCE(ac.name, upr.full_name) as customerName'),
                DB::raw('COALESCE(ac.phone_number, upr.phone) as customerPhone'),
                DB::raw("'site_visit' as type"),
                'a.title as title',
                'a.notes as description',
                DB::raw("CASE a.priority
                    WHEN 4 THEN 'urgent'
                    WHEN 3 THEN 'high'
                    WHEN 2 THEN 'medium'
                    ELSE 'low'
                END as priority"),
                DB::raw("CASE
                    WHEN a.snoozed_until IS NOT NULL AND a.snoozed_until > NOW() THEN 'snoozed'
                    WHEN a.status = 'completed' THEN 'completed'
                    WHEN a.status IN ('cancelled', 'no_show') THEN 'dismissed'
                    ELSE 'pending'
                END as status"),
                DB::raw("'manual' as source"),
                DB::raw("'request_appointment' as objectType"),
                'a.datetime as dueDate',
                'a.snoozed_until as snoozedUntil',
                'a.created_at as createdAt',
                'a.updated_at as updatedAt',
                DB::raw("CASE WHEN a.status = 'completed' THEN a.updated_at ELSE NULL END as completedAt"),
                DB::raw("NULL as completedBy"),
                DB::raw("NULL as assignedTo"),
                DB::raw("NULL as assignedToName"),
                DB::raw("JSON_OBJECT(
                    'appointmentId', a.id,
                    'propertyRequestId', a.property_request_id,
                    'type', a.type,
                    'duration', a.duration
                ) as metadata"),
                DB::raw("'property_request_appointments' as sourceTable"),
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
     * Build request-level reminders subquery (property_request_reminders).
     */
    private function getRequestRemindersSubquery(int $userId): \Illuminate\Database\Query\Builder
    {
        return DB::table('property_request_reminders as r')
            ->leftJoin('api_customers as ac', 'r.customer_id', '=', 'ac.id')
            ->leftJoin('users_property_requests as upr', 'r.property_request_id', '=', 'upr.id')
            ->where('r.user_id', $userId)
            ->select([
                DB::raw("CONCAT('request_reminder_', r.id) as id"),
                'r.customer_id as customerId',
                DB::raw('COALESCE(ac.name, upr.full_name) as customerName'),
                DB::raw('COALESCE(ac.phone_number, upr.phone) as customerPhone'),
                DB::raw("'follow_up' as type"),
                'r.title as title',
                'r.description as description',
                DB::raw("CASE r.priority
                    WHEN 3 THEN 'urgent'
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
                DB::raw("'manual' as source"),
                DB::raw("'request_reminder' as objectType"),
                'r.datetime as dueDate',
                'r.snoozed_until as snoozedUntil',
                'r.created_at as createdAt',
                'r.updated_at as updatedAt',
                DB::raw("CASE WHEN r.status = 'completed' THEN r.updated_at ELSE NULL END as completedAt"),
                DB::raw("NULL as completedBy"),
                DB::raw("NULL as assignedTo"),
                DB::raw("NULL as assignedToName"),
                DB::raw("JSON_OBJECT(
                    'reminderId', r.id,
                    'propertyRequestId', r.property_request_id,
                    'type', r.type
                ) as metadata"),
                DB::raw("'property_request_reminders' as sourceTable"),
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
     * Apply filters to the query.
     */
    private function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters, int $userId): void
    {
        // Tab filter (predefined filter sets)
        if (!empty($filters['tab'])) {
            switch ($filters['tab']) {
                case 'inbox':
                    $query->whereIn('type', ['new_inquiry', 'callback_request', 'whatsapp_incoming'])
                        ->whereIn('status', ['pending', 'in_progress']);
                    break;
                case 'followups':
                    // Standalone follow_up / site_visit rows (reminders table) stay as before.
                    // Also include property_request rows that have at least one scheduled appointment
                    // or pending reminder — those were previously surfaced as separate request_appointment /
                    // request_reminder cards but are no longer in the UNION.
                    $query->where(function ($q) use ($userId) {
                        $q->where(function ($q2) {
                            $q2->whereIn('type', ['follow_up', 'site_visit'])
                                ->whereIn('status', ['pending', 'in_progress']);
                        })->orWhere(function ($q2) use ($userId) {
                            $q2->where('objectType', 'property_request')
                                ->whereIn('status', ['pending', 'in_progress'])
                                ->where(function ($q3) use ($userId) {
                                    $q3->whereIn('sourceId', function ($sub) use ($userId) {
                                        $sub->select('property_request_id')
                                            ->from('property_request_appointments')
                                            ->where('user_id', $userId)
                                            ->where('status', 'scheduled');
                                    })->orWhereIn('sourceId', function ($sub) use ($userId) {
                                        $sub->select('property_request_id')
                                            ->from('property_request_reminders')
                                            ->where('user_id', $userId)
                                            ->where('status', 'pending');
                                    });
                                });
                        });
                    });
                    break;
                case 'all':
                    break;
                case 'completed':
                    $query->where('status', 'completed');
                    break;
            }
        }

        // Statuses include/exclude filters (Customers Hub status values)
        // - statuses: include only these statuses (IN)
        // - excludeStatuses: exclude these statuses (NOT IN)
        // Empty arrays are ignored.
        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }
        if (!empty($filters['excludeStatuses']) && is_array($filters['excludeStatuses'])) {
            $query->whereNotIn('status', $filters['excludeStatuses']);
        }

        // Types filter
        if (!empty($filters['types']) && is_array($filters['types'])) {
            $query->whereIn('type', $filters['types']);
        }

        // Sources filter
        if (!empty($filters['sources']) && is_array($filters['sources'])) {
            $query->whereIn('source', $filters['sources']);
        }

        // Object types filter
        if (!empty($filters['objectTypes']) && is_array($filters['objectTypes'])) {
            $query->whereIn('objectType', $filters['objectTypes']);
        }

        // Appointment type filter: only property_request rows that have at least one appointment of the given type(s)
        if (!empty($filters['appointment_types']) && is_array($filters['appointment_types'])) {
            $query->where(function ($q) use ($userId, $filters) {
                $q->where(function ($q2) {
                    $q2->where('objectType', '!=', 'property_request')
                        ->orWhere('sourceTable', '!=', 'users_property_requests');
                })->orWhere(function ($q2) use ($userId, $filters) {
                    $q2->where('sourceTable', 'users_property_requests')
                        ->whereIn('sourceId', function ($sub) use ($userId, $filters) {
                            $sub->select('property_request_id')
                                ->from('property_request_appointments')
                                ->where('user_id', $userId)
                                ->whereIn('type', $filters['appointment_types']);
                        });
                });
            });
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

        // Stages filter (pipeline: requests and inquiries in given customers_hub stage_id or id)
        if (!empty($filters['stages']) && is_array($filters['stages'])) {
            $stageIdStrings = $this->resolveStagesFilterToStageIds($filters['stages']);
            if (!empty($stageIdStrings)) {
                $query->where(function ($q) use ($userId, $stageIdStrings) {
                    $q->where(function ($q2) use ($userId, $stageIdStrings) {
                        $q2->where('sourceTable', 'users_property_requests')
                            ->whereIn('sourceId', function ($sub) use ($userId, $stageIdStrings) {
                                $sub->select('id')->from('users_property_requests')
                                    ->where('user_id', $userId)
                                    ->whereIn('customers_hub_stage_id', $stageIdStrings);
                            });
                    })->orWhere(function ($q2) use ($userId, $stageIdStrings) {
                        $q2->where('sourceTable', 'api_customer_inquiry')
                            ->whereIn('sourceId', function ($sub) use ($userId, $stageIdStrings) {
                                $sub->select('id')->from('api_customer_inquiry')
                                    ->where('user_id', $userId)
                                    ->whereIn('stage_id', $stageIdStrings);
                            });
                    });
                });
            }
        }

        // Exclude stages filter (pipeline: exclude requests and inquiries in given customers_hub stage ids)
        if (!empty($filters['excludeStages']) && is_array($filters['excludeStages'])) {
            $excludeStageIdStrings = $this->resolveStagesFilterToStageIds($filters['excludeStages']);
            if (!empty($excludeStageIdStrings)) {
                $query->where(function ($q) use ($userId, $excludeStageIdStrings) {
                    // Property requests: keep rows not in excluded stages
                    $q->where(function ($q2) use ($userId, $excludeStageIdStrings) {
                        $q2->where('sourceTable', 'users_property_requests')
                            ->whereNotIn('sourceId', function ($sub) use ($userId, $excludeStageIdStrings) {
                                $sub->select('id')->from('users_property_requests')
                                    ->where('user_id', $userId)
                                    ->whereIn('customers_hub_stage_id', $excludeStageIdStrings);
                            });
                    })
                    // Inquiries: keep rows not in excluded stages
                    ->orWhere(function ($q2) use ($userId, $excludeStageIdStrings) {
                        $q2->where('sourceTable', 'api_customer_inquiry')
                            ->whereNotIn('sourceId', function ($sub) use ($userId, $excludeStageIdStrings) {
                                $sub->select('id')->from('api_customer_inquiry')
                                    ->where('user_id', $userId)
                                    ->whereIn('stage_id', $excludeStageIdStrings);
                            });
                    })
                    // Pass-through: non request/inquiry rows are unaffected
                    ->orWhere(function ($q2) {
                        $q2->whereNotIn('sourceTable', ['users_property_requests', 'api_customer_inquiry']);
                    });
                });
            }
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
                    $today = Carbon::today();
                    $query->where(function ($q) use ($userId, $today) {
                        // Inquiry with at least one appointment today
                        $q->orWhere(function ($q2) use ($userId, $today) {
                            $q2->where('objectType', 'inquiry')
                                ->whereIn('sourceId', function ($sub) use ($userId, $today) {
                                    $sub->select('inquiry_id')
                                        ->from('inquiry_appointments')
                                        ->where('user_id', $userId)
                                        ->whereDate('datetime', $today);
                                });
                        })
                        // Property request with at least one appointment today
                        ->orWhere(function ($q2) use ($userId, $today) {
                            $q2->where('objectType', 'property_request')
                                ->whereIn('sourceId', function ($sub) use ($userId, $today) {
                                    $sub->select('property_request_id')
                                        ->from('property_request_appointments')
                                        ->where('user_id', $userId)
                                        ->whereDate('datetime', $today);
                                });
                        })
                        // Other types: row has dueDate = today (reminder, request_appointment, request_reminder)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNotNull('dueDate')
                                ->whereDate('dueDate', $today);
                        });
                    });
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

        // Districts filter (request-level)
        if (!empty($filters['districts']) && is_array($filters['districts'])) {
            $query->whereIn('districts_id', $filters['districts']);
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
     * Enrich action items with pipeline stage (customers_hub_stages).
     * For property_request items uses users_property_requests.customers_hub_stage_id.
     * For inquiry items uses api_customer_inquiry.stage_id.
     */
    private function enrichItemsWithHubStage(Collection $items, int $userId): Collection
    {
        $items = $items->values();
        
        // Collect stage IDs from items that have customers_hub_stage_id already populated
        $stageIds = $items->filter(function ($item) {
            return !empty($item->customers_hub_stage_id);
        })->pluck('customers_hub_stage_id')->unique()->values()->all();

        $stageByStageId = [];
        if (!empty($stageIds)) {
            // Single query to load all stage objects (both system and user-specific)
            $stages = DB::table('customers_hub_stages as s')
                ->leftJoin('customers_hub_stage_overrides as o', function ($join) use ($userId) {
                    $join->on('o.stage_id', '=', 's.stage_id')
                        ->where('o.user_id', '=', DB::raw((int) $userId));
                })
                ->whereIn('s.stage_id', $stageIds)
                ->where('s.is_active', true)
                ->where(function ($w) use ($userId) {
                    $w->where('s.is_system', true)->orWhere('s.user_id', $userId);
                })
                ->get([
                    's.id',
                    's.stage_id',
                    DB::raw('COALESCE(o.stage_name_ar, s.stage_name_ar) as stage_name_ar'),
                    DB::raw('COALESCE(o.stage_name_en, s.stage_name_en) as stage_name_en'),
                ]);
            $stageByStageId = $stages->keyBy('stage_id')->toArray();
        }

        return $items->map(function ($item) use ($stageByStageId) {
            $item->stage_id = null;
            $item->stage = null;

            if (!empty($item->customers_hub_stage_id) && isset($stageByStageId[$item->customers_hub_stage_id])) {
                $s = $stageByStageId[$item->customers_hub_stage_id];
                $item->stage_id = $item->customers_hub_stage_id;
                $item->stage = (object) [
                    'id' => (int) $s['id'],
                    'stage_id' => $s['stage_id'],
                    'nameAr' => $s['stage_name_ar'],
                    'nameEn' => $s['stage_name_en'] ?? $s['stage_name_ar'],
                ];
            }

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

        // Stable numeric ids for Customers Hub status values (v2).
        // This is intentionally separate from users_property_requests.status_id.
        $hubStatusIdMap = [
            'pending' => 1,
            'in_progress' => 2,
            'in_waiting' => 3,
            'completed' => 4,
            'dismissed' => 5,
            'snoozed' => 6,
        ];
        $hubStatusId = $hubStatusIdMap[$item->status] ?? null;

        return (object) [
            // Alias to make it explicit what to pass to:
            // GET /api/v2/customers-hub/requests/{requestId}
            'requestId' => $item->id,
            'id' => $item->id,
            'customerId' => $item->customerId,
            'customerName' => $item->customerName,
            'customerPhone' => $item->customerPhone ?? null,
            'type' => $item->type,
            'title' => $item->title,
            'description' => $item->description,
            'priority' => $item->priority,
            'status' => $item->status,
            'Status_hub_Id' => $hubStatusId,
            'source' => $item->source ?? '',
            'objectType' => $item->objectType ?? '',
            'dueDate' => $item->dueDate ? Carbon::parse($item->dueDate)->toIso8601String() : null,
            'snoozedUntil' => $item->snoozedUntil ? Carbon::parse($item->snoozedUntil)->toIso8601String() : null,
            'createdAt' => $item->createdAt ? Carbon::parse($item->createdAt)->toIso8601String() : null,
            'updatedAt' => $item->updatedAt ? Carbon::parse($item->updatedAt)->toIso8601String() : null,
            'completedAt' => $item->completedAt ? Carbon::parse($item->completedAt)->toIso8601String() : null,
            'completedBy' => $item->completedBy,
            'assignedTo' => $item->assignedTo,
            'assignedToName' => trim($item->assignedToName ?? ''),
            'propertyCategory' => $item->propertyCategory ?? null,
            'propertyType' => $item->propertyType ?? null,
            'city' => $item->city ?? null,
            'state' => $item->state ?? null,
            'districts_id' => isset($item->districts_id) && $item->districts_id !== null ? (int) $item->districts_id : null,
            'districtAR' => $item->districtAR ?? null,
            'budgetMin' => isset($item->budgetMin) && $item->budgetMin !== null ? (float) $item->budgetMin : null,
            'budgetMax' => isset($item->budgetMax) && $item->budgetMax !== null ? (float) $item->budgetMax : null,
            // For property_request objectType only (users_property_requests.status_id).
            // Null for inquiry/reminder/appointments.
            'propertyRequestStatusId' => isset($item->propertyRequestStatusId) && $item->propertyRequestStatusId !== null
                ? (int) $item->propertyRequestStatusId
                : null,
            'propertyRequestStatus' => isset($item->propertyRequestStatusId) && $item->propertyRequestStatusId !== null
                ? [
                    'id' => (int) $item->propertyRequestStatusId,
                    'slug' => $item->propertyRequestStatusSlug ?? null,
                    'name_ar' => $item->propertyRequestStatusNameAr ?? null,
                    'name_en' => $item->propertyRequestStatusNameEn ?? null,
                ]
                : null,
            'metadata' => $metadata,
            'sourceTable' => $item->sourceTable,
            'sourceId' => $item->sourceId,
        ];
    }

    private function syncAssignWithCustomer(int $userId, int $uprId, int $customerId, int $assignedTo, Carbon $now): bool
    {
        try {
            return (bool) DB::transaction(function () use ($userId, $uprId, $customerId, $assignedTo, $now) {
                $customerQuery = DB::table('api_customers')
                    ->where('id', $customerId)
                    ->where('user_id', $userId);

                $uprQuery = DB::table('users_property_requests')
                    ->where('id', $uprId)
                    ->where('user_id', $userId);

                $customerUpdated = (int) $customerQuery->update(['responsible_employee_id' => $assignedTo, 'updated_at' => $now]);
                $uprUpdated = (int) $uprQuery->update(['responsible_employee_id' => $assignedTo, 'updated_at' => $now]);

                // MySQL returns 0 if the row exists but values are unchanged, so treat as success if row exists.
                $customerOk = $customerUpdated > 0 || $customerQuery->exists();
                $uprOk = $uprUpdated > 0 || $uprQuery->exists();

                return $customerOk && $uprOk;
            });
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Resolve (or create) a customer for a property request, then assign and sync both tables.
     *
     * @return array{success: bool, reason?: string}
     */
    private function resolveOrCreateCustomerForPropertyRequest(int $userId, int $sourceId, int $assignedTo, Carbon $now): array
    {
        $upr = DB::table('users_property_requests')
            ->where('id', $sourceId)
            ->where('user_id', $userId)
            ->first(['id', 'full_name', 'phone', 'city_id', 'districts_id', 'customers_hub_stage_id']);

        if (!$upr) {
            return ['success' => false, 'reason' => 'NOT_FOUND'];
        }

        $normalizedPhone = PhoneNormalizer::normalize($upr->phone ?? null);
        if (!$normalizedPhone) {
            return ['success' => false, 'reason' => 'NO_PHONE'];
        }

        try {
            return DB::transaction(function () use ($userId, $upr, $normalizedPhone, $assignedTo, $now) {
                $existing = DB::table('api_customers')
                    ->where('user_id', $userId)
                    ->where('phone_number', $normalizedPhone)
                    ->whereNull('deleted_at')
                    ->first(['id']);

                if ($existing) {
                    DB::table('users_property_requests')
                        ->where('id', $upr->id)
                        ->where('user_id', $userId)
                        ->update(['customer_id' => $existing->id, 'updated_at' => $now]);

                    $ok = $this->syncAssignWithCustomer($userId, (int) $upr->id, (int) $existing->id, $assignedTo, $now);
                    return $ok ? ['success' => true] : ['success' => false, 'reason' => 'UPDATE_FAILED'];
                }

                $defaults = $this->getDefaultCustomerDefaults($userId);
                $customersHubStageId = $upr->customers_hub_stage_id ?? $defaults['customers_hub_stage_id'];

                $customer = ApiCustomer::create([
                    'user_id' => $userId,
                    'name' => $upr->full_name,
                    'phone_number' => $normalizedPhone,
                    'email' => null,
                    'note' => null,
                    'password' => bcrypt('12345678'),

                    // Legacy pipeline fields
                    'stage_id' => $defaults['stage_id'],
                    'type_id' => $defaults['type_id'],
                    'priority_id' => $defaults['priority_id'],
                    'procedure_id' => $defaults['procedure_id'],

                    // CustomersHub pipeline fields
                    'customers_hub_stage_id' => $customersHubStageId,
                    'customers_hub_stage_changed_at' => $now,

                    'responsible_employee_id' => $assignedTo,
                    'city_id' => $upr->city_id,
                    'district_id' => $upr->districts_id,
                    'created_by_type' => 'system',
                    'created_by_id' => null,
                    'source' => 'property_request',
                    'source_id' => $upr->id,
                ]);

                DB::table('users_property_requests')
                    ->where('id', $upr->id)
                    ->where('user_id', $userId)
                    ->update([
                        'customer_id' => $customer->id,
                        'responsible_employee_id' => $assignedTo,
                        'updated_at' => $now,
                    ]);

                return ['success' => true];
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'reason' => 'CREATE_CUSTOMER_FAILED'];
        }
    }

    /**
     * Load default customer attributes (first active record per tenant).
     *
     * @return array{type_id: int|null, priority_id: int|null, procedure_id: int|null, stage_id: int|null, customers_hub_stage_id: string|null}
     */
    private function getDefaultCustomerDefaults(int $userId): array
    {
        $customersHubStageId = DB::table('customers_hub_stages')
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->value('stage_id');

        return [
            'type_id' => DB::table('users_api_customers_types')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('order')
                ->value('id'),
            'priority_id' => DB::table('users_api_customers_priorities')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('order')
                ->value('id'),
            'procedure_id' => DB::table('users_api_customers_procedures')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('order')
                ->value('id'),
            'stage_id' => DB::table('users_api_customers_stages')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('order')
                ->value('id'),
            'customers_hub_stage_id' => $customersHubStageId,
        ];
    }

    /**
     * Resolve stages filter (array of stage_id strings or numeric ids) to array of stage_id strings.
     *
     * @param  array  $values
     * @return array<string>
     */
    private function resolveStagesFilterToStageIds(array $values): array
    {
        if (empty($values)) {
            return [];
        }
        $stageIds = [];
        foreach ($values as $v) {
            if (is_int($v) || (is_string($v) && ctype_digit($v))) {
                $sid = DB::table('customers_hub_stages')->where('id', (int) $v)->where('is_active', true)->value('stage_id');
                if ($sid !== null) {
                    $stageIds[] = $sid;
                }
            } else {
                $stageIds[] = (string) $v;
            }
        }

        return array_values(array_unique($stageIds));
    }
}
