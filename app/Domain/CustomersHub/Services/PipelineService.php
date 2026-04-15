<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PipelineService
 *
 * Handles pipeline/kanban board operations for property requests and inquiries
 * organized by customers_hub_stages (stage_id string).
 */
class PipelineService
{
    /** Default stage colors by order index. */
    private const STAGE_COLORS = [
        '#3b82f6', // blue
        '#8b5cf6', // violet
        '#06b6d4', // cyan
        '#10b981', // emerald
        '#f59e0b', // amber
        '#ef4444', // red
    ];

    /**
     * Resolve newStageId (string or integer) to stage_id string. Returns null if invalid.
     */
    public function resolveNewStageId(int|string $newStageId): ?string
    {
        // Backward compatible: without user context, only validate active system stages.
        $presenter = app(CustomersHubStagesPresenter::class);
        return $presenter->resolveStageIdString(0, $newStageId, true);
    }

    /**
     * Resolve newStageId for a given tenant (system + tenant custom stages).
     */
    public function resolveNewStageIdForTenant(int $tenantUserId, int|string $newStageId): ?string
    {
        $presenter = app(CustomersHubStagesPresenter::class);
        return $presenter->resolveStageIdString($tenantUserId, $newStageId, true);
    }

    /**
     * Get pipeline board data (stages from customers_hub_stages; requests + inquiries per stage).
     */
    public function getPipelineBoard(int $userId, array $filters = []): array
    {
        $presenter = app(CustomersHubStagesPresenter::class);
        $stages = $presenter->listStages($userId, true)
            ->map(function ($s) {
                return (object) [
                    'id' => $s->id,
                    'stage_id' => $s->stage_id,
                    'stage_name_ar' => $s->stage_name_ar,
                    'stage_name_en' => $s->stage_name_en,
                    'color' => $s->color,
                    'order' => (int) $s->order,
                ];
            });

        $stagesData = [];
        $totalCount = 0;

        foreach ($stages as $index => $stage) {
            $requestsQuery = DB::table('users_property_requests as upr')
                ->leftJoin('api_customers as ac', function ($join) use ($userId) {
                    $join->on('ac.id', '=', 'upr.customer_id')
                        ->on('ac.user_id', '=', DB::raw((int) $userId));
                })
                ->leftJoin('users as emp', DB::raw('emp.id'), '=', DB::raw('COALESCE(upr.responsible_employee_id, ac.responsible_employee_id)'))
                ->where('upr.user_id', $userId)
                ->where('upr.customers_hub_stage_id', $stage->stage_id)
                ->where('upr.is_active', 1)
                ->where('upr.is_archived', 0);

            $this->applyFilters($requestsQuery, $filters, $userId);

            $requests = $requestsQuery
                ->select([
                    'upr.id',
                    'upr.full_name',
                    'upr.phone',
                    'upr.property_type',
                    'upr.budget_from',
                    'upr.budget_to',
                    'upr.seriousness',
                    'upr.created_at',
                    'upr.updated_at',
                    DB::raw('COALESCE(upr.responsible_employee_id, ac.responsible_employee_id) as assigned_employee_id'),
                    DB::raw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) as assigned_employee_name"),
                ])
                ->limit(100)
                ->get();

            $inquiriesQuery = DB::table('api_customer_inquiry as aci')
                ->leftJoin('api_customers as ac', 'aci.customer_id', '=', 'ac.id')
                ->leftJoin('users as emp', 'aci.responsible_employee_id', '=', 'emp.id')
                ->where('aci.user_id', $userId)
                ->where('aci.stage_id', $stage->stage_id)
                ->where('aci.is_archived', 0);

            $this->applyInquiryFilters($inquiriesQuery, $filters);

            $inquiries = $inquiriesQuery
                ->select([
                    'aci.id',
                    'aci.phone_number',
                    'aci.message',
                    'aci.property_type',
                    'aci.budget',
                    'aci.created_at',
                    'aci.updated_at',
                    'ac.name as customer_name',
                    'aci.responsible_employee_id as assigned_employee_id',
                    DB::raw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) as assigned_employee_name"),
                ])
                ->limit(100)
                ->get();

            $requestCards = $requests->map(fn ($r) => $this->mapRequestToCard($r));
            $inquiryCards = $inquiries->map(fn ($i) => $this->mapInquiryToCard($i));
            $cards = $requestCards->concat($inquiryCards)->values()->all();
            $count = count($cards);
            $totalCount += $count;

            $stagesData[] = [
                'id' => (int) $stage->id,
                'stage_id' => $stage->stage_id,
                'name' => $stage->stage_name_ar,
                'nameEn' => $stage->stage_name_en ?? $stage->stage_name_ar,
                'color' => $stage->color ?? self::STAGE_COLORS[$index % count(self::STAGE_COLORS)],
                'order' => (int) $stage->order,
                'count' => $count,
                'customers' => $cards,
            ];
        }

        // Unassigned column: requests and inquiries with null stage
        $unassignedRequests = DB::table('users_property_requests as upr')
            ->leftJoin('api_customers as ac', function ($join) use ($userId) {
                $join->on('ac.id', '=', 'upr.customer_id')->on('ac.user_id', '=', DB::raw((int) $userId));
            })
            ->leftJoin('users as emp', DB::raw('emp.id'), '=', DB::raw('COALESCE(upr.responsible_employee_id, ac.responsible_employee_id)'))
            ->where('upr.user_id', $userId)
            ->whereNull('upr.customers_hub_stage_id')
            ->where('upr.is_active', 1)
            ->where('upr.is_archived', 0);

            $this->applyFilters($unassignedRequests, $filters, $userId);

        $unassignedInquiries = DB::table('api_customer_inquiry as aci')
            ->leftJoin('api_customers as ac', 'aci.customer_id', '=', 'ac.id')
            ->leftJoin('users as emp', 'aci.responsible_employee_id', '=', 'emp.id')
            ->where('aci.user_id', $userId)
            ->whereNull('aci.stage_id')
            ->where('aci.is_archived', 0);

        $this->applyInquiryFilters($unassignedInquiries, $filters);

        $ur = $unassignedRequests->select([
            'upr.id', 'upr.full_name', 'upr.phone', 'upr.property_type', 'upr.budget_from', 'upr.budget_to',
            'upr.seriousness', 'upr.created_at', 'upr.updated_at',
            DB::raw('COALESCE(upr.responsible_employee_id, ac.responsible_employee_id) as assigned_employee_id'),
            DB::raw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) as assigned_employee_name"),
        ])->limit(100)->get();

        $ui = $unassignedInquiries->select([
            'aci.id', 'aci.phone_number', 'aci.message', 'aci.property_type', 'aci.budget', 'aci.created_at', 'aci.updated_at',
            'ac.name as customer_name',
            'aci.responsible_employee_id as assigned_employee_id',
            DB::raw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) as assigned_employee_name"),
        ])->limit(100)->get();

        $unassignedCards = $ur->map(fn ($r) => $this->mapRequestToCard($r))->concat($ui->map(fn ($i) => $this->mapInquiryToCard($i)))->values()->all();
        $unassignedCount = count($unassignedCards);
        $totalCount += $unassignedCount;

        $stagesData[] = [
            'id' => null,
            'stage_id' => null,
            'name' => 'غير معين',
            'nameEn' => 'Unassigned',
            'color' => '#6b7280',
            'order' => 999,
            'count' => $unassignedCount,
            'customers' => $unassignedCards,
        ];

        return [
            'stages' => $stagesData,
            'totalCustomers' => $totalCount,
        ];
    }

    /**
     * Map a request row to a card shape with source and ids for move.
     */
    private function mapRequestToCard(object $r): array
    {
        $name = $r->full_name ?? '';
        $priority = $this->seriousnessToPriority($r->seriousness);

        return [
            'id' => $r->id,
            'requestId' => $r->id,
            'inquiryId' => null,
            'source' => 'request',
            'name' => $name,
            'phone' => $r->phone ?? '',
            'avatar' => $this->initialsFromName($name),
            'totalDealValue' => $r->budget_to !== null ? (float) $r->budget_to : (float) ($r->budget_from ?? 0),
            'propertyType' => $r->property_type ? (is_array($r->property_type) ? $r->property_type : [$r->property_type]) : [],
            'priority' => $priority,
            'assignedEmployee' => ($r->assigned_employee_id && trim($r->assigned_employee_name ?? ''))
                ? ['id' => $r->assigned_employee_id, 'name' => trim($r->assigned_employee_name)]
                : null,
            'lastContactAt' => $r->updated_at ? Carbon::parse($r->updated_at)->toIso8601String() : null,
            'createdAt' => $r->created_at ? Carbon::parse($r->created_at)->toIso8601String() : null,
        ];
    }

    /**
     * Map an inquiry row to a card shape with source and ids for move.
     */
    private function mapInquiryToCard(object $i): array
    {
        $name = $i->customer_name ?? $i->phone_number ?? 'استفسار';
        $budget = isset($i->budget) && $i->budget !== null ? (float) $i->budget : 0;

        return [
            'id' => $i->id,
            'requestId' => null,
            'inquiryId' => $i->id,
            'source' => 'inquiry',
            'name' => $name,
            'phone' => $i->phone_number ?? '',
            'avatar' => $this->initialsFromName($name),
            'totalDealValue' => $budget,
            'propertyType' => $i->property_type ? (is_array($i->property_type) ? $i->property_type : [$i->property_type]) : [],
            'priority' => ['id' => 'medium', 'name' => 'متوسط', 'color' => '#ffc107'],
            'assignedEmployee' => ($i->assigned_employee_id && trim($i->assigned_employee_name ?? ''))
                ? ['id' => $i->assigned_employee_id, 'name' => trim($i->assigned_employee_name)]
                : null,
            'lastContactAt' => $i->updated_at ? Carbon::parse($i->updated_at)->toIso8601String() : null,
            'createdAt' => $i->created_at ? Carbon::parse($i->created_at)->toIso8601String() : null,
        ];
    }

    private function applyInquiryFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        if (!empty($filters['assignedEmployeeId'])) {
            $query->where('aci.responsible_employee_id', (int) $filters['assignedEmployeeId']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('aci.message', 'like', $search)
                    ->orWhere('aci.phone_number', 'like', $search)
                    ->orWhere('ac.name', 'like', $search);
            });
        }
    }

    private function initialsFromName(string $name): string
    {
        $parts = array_filter(preg_split('/\s+/', trim($name), 2));
        if (empty($parts)) {
            return '?';
        }
        $first = mb_substr($parts[0], 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : $first;
        return mb_strtoupper($first . $second);
    }

    private function seriousnessToPriority($seriousness): array
    {
        $map = [
            'urgent' => ['id' => 'urgent', 'name' => 'عاجل', 'color' => '#dc3545'],
            'high' => ['id' => 'high', 'name' => 'عالي', 'color' => '#fd7e14'],
            'medium' => ['id' => 'medium', 'name' => 'متوسط', 'color' => '#ffc107'],
            'low' => ['id' => 'low', 'name' => 'منخفض', 'color' => '#28a745'],
        ];
        if ($seriousness !== null && isset($map[$seriousness])) {
            return $map[$seriousness];
        }
        return $map['medium'];
    }

    /**
     * Get stage analytics (request-based, customers_hub_stage_id).
     */
    public function getStageAnalytics(int $userId, array $filters = []): array
    {
        $baseQuery = function () use ($userId, $filters) {
            $q = DB::table('users_property_requests as upr')
                ->leftJoin('api_customers as ac', function ($join) use ($userId) {
                    $join->on('ac.id', '=', 'upr.customer_id')
                        ->on('ac.user_id', '=', DB::raw((int) $userId));
                })
                ->where('upr.user_id', $userId)
                ->where('upr.is_active', 1)
                ->where('upr.is_archived', 0);
            $this->applyFilters($q, $filters, $userId);
            return $q;
        };

        $totalRequests = (clone $baseQuery())->count();

        $presenter = app(CustomersHubStagesPresenter::class);
        $stages = $presenter->listStages($userId, true)
            ->map(function ($s) {
                return (object) [
                    'id' => $s->id,
                    'stage_id' => $s->stage_id,
                    'stage_name_ar' => $s->stage_name_ar,
                    'color' => $s->color,
                    'order' => (int) $s->order,
                ];
            });
        $numStages = $stages->count();
        $avgPerStage = $numStages > 0 ? $totalRequests / $numStages : 0;
        $threshold = $avgPerStage * 1.5;

        $countsByStage = (clone $baseQuery())
            ->whereNotNull('upr.customers_hub_stage_id')
            ->select('upr.customers_hub_stage_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('upr.customers_hub_stage_id')
            ->get()
            ->keyBy('customers_hub_stage_id');

        $bottlenecks = [];
        foreach ($stages as $index => $stage) {
            $cnt = (int) ($countsByStage->get($stage->stage_id)->cnt ?? 0);
            if ($cnt > $threshold) {
                $bottlenecks[] = [
                    'stageId' => $stage->stage_id,
                    'stageName' => $stage->stage_name_ar,
                    'color' => $stage->color ?? self::STAGE_COLORS[$index % count(self::STAGE_COLORS)],
                    'count' => $cnt,
                    'avgCustomersPerStage' => (int) round($avgPerStage),
                ];
            }
        }

        $avgDays = (clone $baseQuery())->selectRaw('AVG(DATEDIFF(NOW(), upr.created_at)) as avg_days')->value('avg_days');

        return [
            'conversionRate' => 0,
            'avgDaysInPipeline' => $avgDays !== null ? (int) round($avgDays) : 0,
            'bottlenecks' => $bottlenecks,
        ];
    }

    /**
     * Move property request to a new stage (customers_hub_stage_id).
     */
    public function moveRequestToStage(int $userId, int $requestId, string $stageIdString): bool
    {
        return DB::table('users_property_requests')
            ->where('id', $requestId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->update([
                'customers_hub_stage_id' => $stageIdString,
                'updated_at' => Carbon::now(),
            ]) > 0;
    }

    /**
     * Move inquiry to a new stage (api_customer_inquiry.stage_id).
     */
    public function moveInquiryToStage(int $userId, int $inquiryId, string $stageIdString): bool
    {
        return DB::table('api_customer_inquiry')
            ->where('id', $inquiryId)
            ->where('user_id', $userId)
            ->update([
                'stage_id' => $stageIdString,
                'updated_at' => Carbon::now(),
            ]) > 0;
    }

    /**
     * Get request's current stage for move response (previous stage).
     */
    public function getRequestCurrentStatus(int $userId, int $requestId): ?object
    {
        $row = DB::table('users_property_requests as upr')
            ->leftJoin('customers_hub_stages as chs', 'upr.customers_hub_stage_id', '=', 'chs.stage_id')
            ->where('upr.id', $requestId)
            ->where('upr.user_id', $userId)
            ->select('chs.id', 'chs.stage_id', 'chs.stage_name_ar as name_ar', 'chs.stage_name_en as name_en')
            ->first();

        if (!$row || $row->id === null) {
            return null;
        }

        return (object) [
            'id' => (int) $row->id,
            'stage_id' => $row->stage_id,
            'name_ar' => $row->name_ar,
            'name_en' => $row->name_en ?? $row->name_ar,
        ];
    }

    /**
     * Get inquiry's current stage for move response (previous stage).
     */
    public function getInquiryCurrentStage(int $userId, int $inquiryId): ?object
    {
        $row = DB::table('api_customer_inquiry as aci')
            ->leftJoin('customers_hub_stages as chs', 'aci.stage_id', '=', 'chs.stage_id')
            ->where('aci.id', $inquiryId)
            ->where('aci.user_id', $userId)
            ->select('chs.id', 'chs.stage_id', 'chs.stage_name_ar as name_ar', 'chs.stage_name_en as name_en')
            ->first();

        if (!$row || $row->id === null) {
            return null;
        }

        return (object) [
            'id' => (int) $row->id,
            'stage_id' => $row->stage_id,
            'name_ar' => $row->name_ar,
            'name_en' => $row->name_en ?? $row->name_ar,
        ];
    }

    /**
     * Get stage by stage_id (string) or id (integer) for move response (new stage).
     */
    public function getStageByStageIdOrId(int|string $stageIdOrId): ?object
    {
        // Backward compatible: without user context, return active system stage only.
        $presenter = app(CustomersHubStagesPresenter::class);
        return $presenter->getEffectiveStageForTenant(0, $stageIdOrId, true);
    }

    public function getStageByStageIdOrIdForTenant(int $tenantUserId, int|string $stageIdOrId): ?object
    {
        $presenter = app(CustomersHubStagesPresenter::class);
        return $presenter->getEffectiveStageForTenant($tenantUserId, $stageIdOrId, true);
    }

    /**
     * Bulk move property requests to a new stage (customers_hub_stage_id).
     */
    public function bulkMoveToStage(int $userId, array $requestIds, string $stageIdString): int
    {
        if (empty($requestIds)) {
            return 0;
        }
        return DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->whereIn('id', $requestIds)
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->update([
                'customers_hub_stage_id' => $stageIdString,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Apply filters to pipeline requests query (users_property_requests as upr).
     */
    private function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters, ?int $tenantUserId = null): void
    {
        $stageIds = $this->resolveStageFilterToStageIds($filters['stage_id'] ?? $filters['status_id'] ?? null, $tenantUserId);
        if (!empty($stageIds)) {
            $query->whereIn('upr.customers_hub_stage_id', $stageIds);
        }
        if (!empty($filters['status']) && is_array($filters['status'])) {
            $query->whereIn('upr.customers_hub_stage_id', $this->resolveStageFilterToStageIds($filters['status'], $tenantUserId));
        }
        if (!empty($filters['property_type']) && is_array($filters['property_type'])) {
            $query->whereIn('upr.property_type', $filters['property_type']);
        }
        if (!empty($filters['city_id'])) {
            $query->where('upr.city_id', (int) $filters['city_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('upr.districts_id', (int) $filters['district_id']);
        }
        if (isset($filters['districts_id']) && $filters['districts_id'] !== null && $filters['districts_id'] !== '') {
            $query->where('upr.districts_id', (int) $filters['districts_id']);
        }
        if (isset($filters['budget_from']) && $filters['budget_from'] !== null && $filters['budget_from'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->where('upr.budget_to', '>=', (float) $filters['budget_from'])
                    ->orWhere('upr.budget_from', '>=', (float) $filters['budget_from']);
            });
        }
        if (isset($filters['budget_to']) && $filters['budget_to'] !== null && $filters['budget_to'] !== '') {
            $query->where(function ($q) use ($filters) {
                $q->where('upr.budget_from', '<=', (float) $filters['budget_to'])
                    ->orWhere('upr.budget_to', '<=', (float) $filters['budget_to']);
            });
        }
        if (!empty($filters['assignedEmployeeId'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('upr.responsible_employee_id', (int) $filters['assignedEmployeeId'])
                    ->orWhere('ac.responsible_employee_id', (int) $filters['assignedEmployeeId']);
            });
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('upr.full_name', 'like', $search)
                    ->orWhere('upr.phone', 'like', $search);
            });
        }
    }

    /**
     * Resolve filter value (array of stage_id strings or numeric ids) to array of stage_id strings.
     *
     * @param  array|null  $values
     * @return array<string>
     */
    private function resolveStageFilterToStageIds($values, ?int $tenantUserId = null): array
    {
        if (!is_array($values) || empty($values)) {
            return [];
        }
        $stageIds = [];
        foreach ($values as $v) {
            if (is_int($v) || (is_string($v) && ctype_digit($v))) {
                $q = DB::table('customers_hub_stages')
                    ->where('id', (int) $v)
                    ->where('is_active', true);

                if ($tenantUserId !== null) {
                    $q->where(function ($w) use ($tenantUserId) {
                        $w->where('is_system', true)->orWhere('user_id', $tenantUserId);
                    });
                } else {
                    $q->where('is_system', true);
                }

                $sid = $q->value('stage_id');
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
