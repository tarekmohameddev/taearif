<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PipelineService
 *
 * Handles pipeline/kanban board operations for property requests (users_property_requests)
 * organized by request lifecycle stages (property_request_statuses).
 */
class PipelineService
{
    /** Default stage colors by display_order index (Option A: no schema change). */
    private const STAGE_COLORS = [
        '#3b82f6', // blue
        '#8b5cf6', // violet
        '#06b6d4', // cyan
        '#10b981', // emerald
        '#f59e0b', // amber
        '#ef4444', // red
    ];

    /**
     * Get pipeline board data (stages with property requests).
     * Uses property_request_statuses and users_property_requests.status_id.
     */
    public function getPipelineBoard(int $userId, array $filters = []): array
    {
        $stages = DB::table('property_request_statuses')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'name_ar', 'name_en', 'display_order', 'slug']);

        $stagesData = [];
        $totalCount = 0;

        foreach ($stages as $index => $stage) {
            $requestsQuery = DB::table('users_property_requests as upr')
                ->leftJoin('api_customer_property_request as acpr', 'acpr.property_request_id', '=', 'upr.id')
                ->leftJoin('api_customers as ac', function ($join) use ($userId) {
                    $join->on('ac.id', '=', 'acpr.customer_id')
                        ->on('ac.user_id', '=', DB::raw((int) $userId));
                })
                ->leftJoin('users as emp', 'ac.responsible_employee_id', '=', 'emp.id')
                ->where('upr.user_id', $userId)
                ->where('upr.status_id', $stage->id)
                ->where('upr.is_active', 1)
                ->where('upr.is_archived', 0);

            $this->applyFilters($requestsQuery, $filters);

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
                    'ac.responsible_employee_id as assigned_employee_id',
                    DB::raw("CONCAT(COALESCE(emp.first_name, ''), ' ', COALESCE(emp.last_name, '')) as assigned_employee_name"),
                ])
                ->limit(100)
                ->get();

            $count = $requests->count();
            $totalCount += $count;

            $stagesData[] = [
                'id' => (int) $stage->id,
                'stage_id' => (int) $stage->id,
                'name' => $stage->name_ar,
                'nameEn' => $stage->name_en ?? $stage->name_ar,
                'color' => self::STAGE_COLORS[$index % count(self::STAGE_COLORS)],
                'order' => (int) $stage->display_order,
                'count' => $count,
                'customers' => $requests->map(fn ($r) => $this->mapRequestToCard($r)),
            ];
        }

        return [
            'stages' => $stagesData,
            'totalCustomers' => $totalCount,
        ];
    }

    /**
     * Map a request row to a card shape (same keys as customer cards for frontend).
     */
    private function mapRequestToCard(object $r): array
    {
        $name = $r->full_name ?? '';
        $priority = $this->seriousnessToPriority($r->seriousness);

        return [
            'id' => $r->id,
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
     * Get stage analytics (request-based).
     */
    public function getStageAnalytics(int $userId, array $filters = []): array
    {
        $baseQuery = function () use ($userId, $filters) {
            $q = DB::table('users_property_requests as upr')
                ->leftJoin('api_customer_property_request as acpr', 'acpr.property_request_id', '=', 'upr.id')
                ->leftJoin('api_customers as ac', function ($join) use ($userId) {
                    $join->on('ac.id', '=', 'acpr.customer_id')
                        ->on('ac.user_id', '=', DB::raw((int) $userId));
                })
                ->where('upr.user_id', $userId)
                ->where('upr.is_active', 1)
                ->where('upr.is_archived', 0);
            $this->applyFilters($q, $filters);
            return $q;
        };

        $totalRequests = (clone $baseQuery())->count();

        $statuses = DB::table('property_request_statuses')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'slug', 'name_ar', 'display_order']);
        $numStages = $statuses->count();
        $avgPerStage = $numStages > 0 ? $totalRequests / $numStages : 0;
        $threshold = $avgPerStage * 1.5;

        // Conversion: e.g. follow_up -> property_found/contract_signed
        $qualifiedSlugs = ['follow_up', 'property_found'];
        $closedSlugs = ['contract_signed', 'cancelled'];
        $qualifiedIds = $statuses->whereIn('slug', $qualifiedSlugs)->pluck('id')->all();
        $closedIds = $statuses->whereIn('slug', $closedSlugs)->pluck('id')->all();
        $terminalIds = $statuses->whereIn('slug', ['cancelled', 'contract_signed'])->pluck('id')->all();

        $qualified = !empty($qualifiedIds)
            ? (clone $baseQuery())->whereIn('upr.status_id', $qualifiedIds)->count()
            : 0;
        $closed = !empty($closedIds)
            ? (clone $baseQuery())->whereIn('upr.status_id', $closedIds)->count()
            : 0;
        $conversionRate = $qualified > 0 ? round(($closed / $qualified) * 100, 2) : 0;

        $avgDays = null;
        if (empty($terminalIds)) {
            $avgDays = (clone $baseQuery())->selectRaw('AVG(DATEDIFF(NOW(), upr.created_at)) as avg_days')->value('avg_days');
        } else {
            $avgDays = (clone $baseQuery())->whereNotIn('upr.status_id', $terminalIds)
                ->selectRaw('AVG(DATEDIFF(NOW(), upr.created_at)) as avg_days')
                ->value('avg_days');
        }

        $countsByStatus = (clone $baseQuery())
            ->select('upr.status_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('upr.status_id')
            ->get()
            ->keyBy('status_id');

        $bottlenecks = [];
        foreach ($statuses as $index => $status) {
            $cnt = (int) ($countsByStatus->get($status->id)->cnt ?? 0);
            if ($cnt > $threshold) {
                $bottlenecks[] = [
                    'stageId' => $status->id,
                    'stageName' => $status->name_ar,
                    'color' => self::STAGE_COLORS[$index % count(self::STAGE_COLORS)],
                    'count' => $cnt,
                    'avgCustomersPerStage' => (int) round($avgPerStage),
                ];
            }
        }

        return [
            'conversionRate' => $conversionRate,
            'avgDaysInPipeline' => $avgDays !== null ? (int) round($avgDays) : 0,
            'bottlenecks' => $bottlenecks,
        ];
    }

    /**
     * Move property request to a new stage (status_id).
     */
    public function moveRequestToStage(int $userId, int $requestId, int $newStatusId): bool
    {
        return DB::table('users_property_requests')
            ->where('id', $requestId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->update([
                'status_id' => $newStatusId,
                'updated_at' => Carbon::now(),
            ]) > 0;
    }

    /**
     * Get request's current status for move response (previous stage).
     */
    public function getRequestCurrentStatus(int $userId, int $requestId): ?object
    {
        return DB::table('users_property_requests as upr')
            ->join('property_request_statuses as prs', 'upr.status_id', '=', 'prs.id')
            ->where('upr.id', $requestId)
            ->where('upr.user_id', $userId)
            ->select('prs.id', 'prs.name_ar', 'prs.name_en')
            ->first();
    }

    /**
     * Get status by id for move response (new stage).
     */
    public function getStatusById(int $statusId): ?object
    {
        return DB::table('property_request_statuses')
            ->where('id', $statusId)
            ->where('is_active', true)
            ->select('id', 'name_ar', 'name_en')
            ->first();
    }

    /**
     * Bulk move property requests to a new stage.
     */
    public function bulkMoveToStage(int $userId, array $requestIds, int $newStatusId): int
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
                'status_id' => $newStatusId,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Apply filters to pipeline requests query (users_property_requests as upr).
     */
    private function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        if (!empty($filters['status']) && is_array($filters['status'])) {
            $query->whereIn('upr.status_id', array_map('intval', $filters['status']));
        }
        if (!empty($filters['status_id']) && is_array($filters['status_id'])) {
            $query->whereIn('upr.status_id', array_map('intval', $filters['status_id']));
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
            $query->where('ac.responsible_employee_id', (int) $filters['assignedEmployeeId']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('upr.full_name', 'like', $search)
                    ->orWhere('upr.phone', 'like', $search);
            });
        }
    }
}
