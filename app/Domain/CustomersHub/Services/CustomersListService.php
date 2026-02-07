<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CustomersListService
 * 
 * Handles customer list operations with filtering, stats, and bulk operations.
 * Main table: api_customers
 */
class CustomersListService
{
    /**
     * Get customers list with filters.
     */
    public function getList(int $userId, array $filters = [], int $page = 1, int $limit = 50): array
    {
        $query = $this->buildQuery($userId, $filters);

        // Get total count
        $total = $query->count();

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $items = $query->limit($limit)->offset($offset)->get();

        // Transform items
        $items = $items->map(function ($item) {
            return $this->transformCustomer($item);
        });

        return [
            'customers' => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
                'hasMore' => ($page * $limit) < $total,
            ],
        ];
    }

    /**
     * Get customer statistics.
     * Uses fresh minimal queries for aggregate calculations to avoid SQL errors
     * and improve performance (no unnecessary JOINs for aggregates).
     */
    public function getStats(int $userId, array $filters = []): array
    {
        // Build base filter query (minimal, no JOINs) for aggregate calculations
        $baseFilterQuery = $this->buildBaseFilterQuery($userId, $filters);
        
        // Total count - use simple count without DISTINCT (no JOINs means no duplicates)
        $total = $baseFilterQuery->count();

        // New today
        $newToday = (clone $baseFilterQuery)
            ->where('created_at', '>=', Carbon::today())
            ->count();

        // New this week
        $newThisWeek = (clone $baseFilterQuery)
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        // New this month
        $newThisMonth = (clone $baseFilterQuery)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        // Total deal value (if column exists, otherwise 0)
        $dealValue = 0;
        try {
            $dealValue = (clone $baseFilterQuery)
                ->whereIn('stage_id', function ($q) use ($userId) {
                    $q->select('id')->from('users_api_customers_stages')
                        ->where('user_id', $userId)
                        ->where(function($query) {
                            $query->where('stage_name', 'LIKE', '%closing%')
                                  ->orWhere('stage_name', 'LIKE', '%post_sale%');
                        });
                })
                ->sum(DB::raw('COALESCE(deal_value, 0)'));
        } catch (\Exception $e) {
            // Column doesn't exist, use 0
            $dealValue = 0;
        }

        // Closed this month
        $closedThisMonth = (clone $baseFilterQuery)
            ->whereIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->where('updated_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        // Conversion rate
        $conversionRate = $newThisMonth > 0 ? ($closedThisMonth / $newThisMonth) * 100 : 0;

        // Average days in pipeline - use fresh query with direct aggregate method
        $avgDays = (clone $baseFilterQuery)
            ->whereNotIn('stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->avg(DB::raw('DATEDIFF(NOW(), created_at)'));

        // Average days in current stage - use fresh query with direct aggregate method
        $avgDaysInStage = (clone $baseFilterQuery)
            ->avg(DB::raw('DATEDIFF(NOW(), updated_at)'));

        // By stage (customers_hub_stages)
        $byStageRaw = DB::table('api_customers')
            ->leftJoin('customers_hub_stages as s', 'api_customers.customers_hub_stage_id', '=', 's.stage_id')
            ->where('api_customers.user_id', $userId)
            ->groupBy('s.stage_id', 's.stage_name_ar')
            ->select('s.stage_id', 's.stage_name_ar as stage_name', DB::raw('COUNT(*) as count'))
            ->get();

        $byStage = [];
        foreach ($byStageRaw as $item) {
            $key = $item->stage_id ?? 'unassigned';
            $byStage[$key] = $item->count;
        }

        // By priority
        $byPriorityRaw = DB::table('api_customers')
            ->join('users_api_customers_priorities as p', 'api_customers.priority_id', '=', 'p.id')
            ->where('api_customers.user_id', $userId)
            ->groupBy('p.id', 'p.name')
            ->select('p.name', DB::raw('COUNT(*) as count'))
            ->get();

        $byPriority = [];
        foreach ($byPriorityRaw as $item) {
            $key = strtolower($item->name);
            $byPriority[$key] = $item->count;
        }

        // By type
        $byTypeRaw = DB::table('api_customers')
            ->join('users_api_customers_types as t', 'api_customers.type_id', '=', 't.id')
            ->where('api_customers.user_id', $userId)
            ->groupBy('t.id', 't.name')
            ->select('t.name', DB::raw('COUNT(*) as count'))
            ->get();

        $byType = [];
        foreach ($byTypeRaw as $item) {
            $key = strtolower($item->name);
            $byType[$key] = $item->count;
        }

        return [
            'totalCustomers' => $total,
            'newToday' => $newToday,
            'newThisWeek' => $newThisWeek,
            'newThisMonth' => $newThisMonth,
            'totalDealValue' => (float) $dealValue,
            'closedThisMonth' => $closedThisMonth,
            'conversionRate' => round($conversionRate, 2),
            'avgDaysInPipeline' => $avgDays ? (int) round($avgDays) : 0,
            'avgDaysInStage' => $avgDaysInStage ? (int) round($avgDaysInStage) : 0,
            'byStage' => $byStage,
            'byPriority' => $byPriority,
            'byType' => $byType,
        ];
    }

    /**
     * Get filter options.
     */
    public function getFilterOptions(int $userId): array
    {
        return [
            'stages' => DB::table('customers_hub_stages')
                ->where('is_active', true)
                ->orderBy('order')
                ->get([
                    DB::raw('stage_id as id'),
                    'stage_name_ar as label',
                    'stage_name_en as labelEn',
                    'color',
                ]),
            
            'priorities' => DB::table('users_api_customers_priorities')
                ->where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'color', 'icon']),
            
            'types' => DB::table('users_api_customers_types')
                ->where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'color', 'icon']),
            
            'sources' => [
                ['id' => 'inquiry', 'label' => 'استفسار', 'labelEn' => 'Inquiry'],
                ['id' => 'manual', 'label' => 'يدوي', 'labelEn' => 'Manual'],
                ['id' => 'whatsapp', 'label' => 'واتساب', 'labelEn' => 'WhatsApp'],
                ['id' => 'import', 'label' => 'استيراد', 'labelEn' => 'Import'],
                ['id' => 'referral', 'label' => 'إحالة', 'labelEn' => 'Referral'],
            ],
            
            'employees' => DB::table('users')
                ->where('tenant_id', $userId)
                ->where('account_type', 'employee')
                ->where('active', true)
                ->get(['id', DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as label"), 'email']),
        ];
    }

    /**
     * Bulk update customers.
     */
    public function bulkUpdate(int $userId, array $customerIds, array $data): int
    {
        $query = DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereIn('id', $customerIds);

        return $query->update(array_merge($data, ['updated_at' => Carbon::now()]));
    }

    /**
     * Build a minimal query with only WHERE filters (no JOINs, no SELECT).
     * Use for aggregate calculations to avoid mixing aggregates with non-aggregate columns.
     * 
     * @param int $userId
     * @param array $filters
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildBaseFilterQuery(int $userId, array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('api_customers')
            ->where('user_id', $userId);
        
        // Apply only filters that work on api_customers table directly
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('phone_number', 'like', $search)
                  ->orWhere('email', 'like', $search);
            });
        }
        
        if (!empty($filters['stage']) && is_array($filters['stage'])) {
            $query->whereIn('customers_hub_stage_id', $filters['stage']);
        }
        
        if (!empty($filters['priority']) && is_array($filters['priority'])) {
            $query->whereIn('priority_id', $filters['priority']);
        }
        
        if (!empty($filters['type']) && is_array($filters['type'])) {
            $query->whereIn('type_id', $filters['type']);
        }
        
        if (!empty($filters['source']) && is_array($filters['source'])) {
            $query->whereIn('source', $filters['source']);
        }
        
        if (!empty($filters['assignedEmployeeId'])) {
            $query->where('responsible_employee_id', $filters['assignedEmployeeId']);
        }
        
        if (!empty($filters['city'])) {
            $query->where('city_id', $filters['city']);
        }
        
        if (!empty($filters['createdFrom'])) {
            $query->where('created_at', '>=', $filters['createdFrom']);
        }
        
        if (!empty($filters['createdTo'])) {
            $query->where('created_at', '<=', $filters['createdTo']);
        }
        
        return $query;
    }

    /**
     * Build query with filters.
     */
    private function buildQuery(int $userId, array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('api_customers')
            ->where('api_customers.user_id', $userId)
            ->leftJoin('customers_hub_stages as stage', 'api_customers.customers_hub_stage_id', '=', 'stage.stage_id')
            ->leftJoin('users_api_customers_priorities as priority', 'api_customers.priority_id', '=', 'priority.id')
            ->leftJoin('users_api_customers_types as type', 'api_customers.type_id', '=', 'type.id')
            ->leftJoin('users as employee', 'api_customers.responsible_employee_id', '=', 'employee.id')
            ->select([
                'api_customers.*',
                'stage.stage_id as hub_stage_id',
                'stage.stage_name_ar as stage_name',
                'stage.stage_name_en as stage_name_en',
                'stage.color as stage_color',
                'priority.name as priority_name',
                'priority.color as priority_color',
                'type.name as type_name',
                DB::raw("CONCAT(COALESCE(employee.first_name, ''), ' ', COALESCE(employee.last_name, '')) as assigned_to_name"),
            ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('api_customers.name', 'like', $search)
                    ->orWhere('api_customers.phone_number', 'like', $search)
                    ->orWhere('api_customers.email', 'like', $search);
            });
        }

        if (!empty($filters['stage']) && is_array($filters['stage'])) {
            $query->whereIn('api_customers.customers_hub_stage_id', $filters['stage']);
        }

        if (!empty($filters['priority']) && is_array($filters['priority'])) {
            $query->whereIn('api_customers.priority_id', $filters['priority']);
        }

        if (!empty($filters['type']) && is_array($filters['type'])) {
            $query->whereIn('api_customers.type_id', $filters['type']);
        }

        if (!empty($filters['source']) && is_array($filters['source'])) {
            $query->whereIn('api_customers.source', $filters['source']);
        }

        if (!empty($filters['assignedEmployeeId'])) {
            $query->where('api_customers.responsible_employee_id', $filters['assignedEmployeeId']);
        }

        if (!empty($filters['city'])) {
            $query->where('api_customers.city_id', $filters['city']);
        }

        if (!empty($filters['createdFrom'])) {
            $query->where('api_customers.created_at', '>=', $filters['createdFrom']);
        }

        if (!empty($filters['createdTo'])) {
            $query->where('api_customers.created_at', '<=', $filters['createdTo']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy('api_customers.' . $sortBy, $sortDir);

        return $query;
    }

    /**
     * Transform customer record.
     */
    private function transformCustomer(object $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone_number,
            'email' => $customer->email,
            'source' => $customer->source,
            'stage' => [
                'id' => $customer->hub_stage_id ?? $customer->customers_hub_stage_id ?? null,
                'name' => $customer->stage_name ?? null,
                'nameEn' => $customer->stage_name_en ?? null,
                'color' => $customer->stage_color ?? null,
            ],
            'priority' => [
                'id' => $customer->priority_id,
                'name' => $customer->priority_name,
                'color' => $customer->priority_color,
            ],
            'type' => [
                'id' => $customer->type_id,
                'name' => $customer->type_name,
            ],
            'assignedTo' => [
                'id' => $customer->responsible_employee_id,
                'name' => $customer->assigned_to_name,
            ],
            'createdAt' => $customer->created_at ? Carbon::parse($customer->created_at)->toIso8601String() : null,
            'updatedAt' => $customer->updated_at ? Carbon::parse($customer->updated_at)->toIso8601String() : null,
        ];
    }
}
