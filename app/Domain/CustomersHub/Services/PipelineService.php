<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PipelineService
 * 
 * Handles pipeline/kanban board operations for customer lifecycle stages.
 */
class PipelineService
{
    /**
     * Get pipeline board data (stages with customers).
     */
    public function getPipelineBoard(int $userId, array $filters = []): array
    {
        $stages = DB::table('users_api_customers_stages')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $stagesData = [];

        foreach ($stages as $stage) {
            $customersQuery = DB::table('api_customers')
                ->where('api_customers.user_id', $userId)
                ->where('api_customers.stage_id', $stage->id);

            // Apply filters
            $this->applyFilters($customersQuery, $filters);

            $customers = $customersQuery
                ->leftJoin('users_api_customers_priorities as p', 'api_customers.priority_id', '=', 'p.id')
                ->select([
                    'api_customers.id',
                    'api_customers.name',
                    'api_customers.phone_number',
                    'api_customers.priority_id',
                    'p.name as priority_name',
                    'p.color as priority_color',
                    'api_customers.created_at',
                ])
                ->limit(100) // Limit per stage
                ->get();

            $stagesData[] = [
                'id' => $stage->id,
                'name' => $stage->stage_name,
                'color' => $stage->color,
                'icon' => $stage->icon,
                'order' => $stage->order,
                'count' => count($customers),
                'customers' => $customers->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone_number,
                    'priority' => [
                        'id' => $c->priority_id,
                        'name' => $c->priority_name,
                        'color' => $c->priority_color,
                    ],
                    'createdAt' => $c->created_at ? Carbon::parse($c->created_at)->toIso8601String() : null,
                ]),
            ];
        }

        return [
            'stages' => $stagesData,
            'totalCustomers' => array_sum(array_column($stagesData, 'count')),
        ];
    }

    /**
     * Get stage analytics.
     */
    public function getStageAnalytics(int $userId, array $filters = []): array
    {
        // Total customers
        $totalCustomers = DB::table('api_customers')
            ->where('user_id', $userId)
            ->count();

        // Conversion rate (qualified to closing)
        $qualifiedStage = DB::table('users_api_customers_stages')
            ->where('user_id', $userId)
            ->where('stage_name', 'LIKE', '%qualified%')
            ->value('id');

        $closingStage = DB::table('users_api_customers_stages')
            ->where('user_id', $userId)
            ->where('stage_name', 'LIKE', '%closing%')
            ->value('id');

        $qualified = $qualifiedStage ? DB::table('api_customers')
            ->where('user_id', $userId)
            ->where('stage_id', $qualifiedStage)
            ->count() : 0;

        $closed = $closingStage ? DB::table('api_customers')
            ->where('user_id', $userId)
            ->where('stage_id', $closingStage)
            ->count() : 0;

        $conversionRate = $qualified > 0 ? ($closed / $qualified) * 100 : 0;

        // Average days in pipeline
        $avgDays = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereNotIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->selectRaw('AVG(DATEDIFF(NOW(), created_at)) as avg_days')
            ->value('avg_days');

        // Bottlenecks (stages with > 1.5x avg customers)
        $avgPerStage = $totalCustomers / max(DB::table('users_api_customers_stages')->where('user_id', $userId)->count(), 1);
        $threshold = $avgPerStage * 1.5;

        $bottlenecks = DB::table('api_customers')
            ->join('users_api_customers_stages as s', 'api_customers.stage_id', '=', 's.id')
            ->where('api_customers.user_id', $userId)
            ->groupBy('s.id', 's.stage_name', 's.color')
            ->havingRaw('COUNT(*) > ?', [$threshold])
            ->select([
                's.id as stageId',
                's.stage_name as stageName',
                's.color',
                DB::raw('COUNT(*) as count'),
            ])
            ->get()
            ->map(fn($b) => [
                'stageId' => $b->stageId,
                'stageName' => $b->stageName,
                'color' => $b->color,
                'count' => $b->count,
                'avgCustomersPerStage' => round($avgPerStage),
            ])
            ->toArray();

        return [
            'conversionRate' => round($conversionRate, 2),
            'avgDaysInPipeline' => $avgDays ? (int) round($avgDays) : 0,
            'bottlenecks' => $bottlenecks,
        ];
    }

    /**
     * Move customer to new stage.
     */
    public function moveCustomerToStage(int $userId, int $customerId, int $newStageId): bool
    {
        return DB::table('api_customers')
            ->where('id', $customerId)
            ->where('user_id', $userId)
            ->update([
                'stage_id' => $newStageId,
                'updated_at' => Carbon::now(),
            ]) > 0;
    }

    /**
     * Bulk move customers to stage.
     */
    public function bulkMoveToStage(int $userId, array $customerIds, int $newStageId): int
    {
        return DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereIn('id', $customerIds)
            ->update([
                'stage_id' => $newStageId,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Apply filters to query.
     */
    private function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters): void
    {
        if (!empty($filters['priority']) && is_array($filters['priority'])) {
            $query->whereIn('api_customers.priority_id', $filters['priority']);
        }

        if (!empty($filters['source']) && is_array($filters['source'])) {
            $query->whereIn('api_customers.source', $filters['source']);
        }

        if (!empty($filters['assignedEmployeeId'])) {
            $query->where('api_customers.responsible_employee_id', $filters['assignedEmployeeId']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('api_customers.name', 'like', $search)
                    ->orWhere('api_customers.phone_number', 'like', $search);
            });
        }
    }
}
