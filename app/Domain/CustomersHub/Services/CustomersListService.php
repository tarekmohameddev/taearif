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
     */
    public function getStats(int $userId, array $filters = []): array
    {
        $query = $this->buildQuery($userId, $filters);

        $total = $query->count();

        // New this month
        $newThisMonth = (clone $query)
            ->where('api_customers.created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        // Total deal value (if column exists, otherwise 0)
        $dealValue = 0;
        try {
            $dealValue = (clone $query)
                ->whereIn('api_customers.stage_id', function ($q) use ($userId) {
                    $q->select('id')->from('users_api_customers_stages')
                        ->where('user_id', $userId)
                        ->where('stage_name', 'LIKE', '%closing%')
                        ->orWhere('stage_name', 'LIKE', '%post_sale%');
                })
                ->sum(DB::raw('COALESCE(deal_value, 0)'));
        } catch (\Exception $e) {
            // Column doesn't exist, use 0
            $dealValue = 0;
        }

        // Closed this month
        $closedThisMonth = (clone $query)
            ->whereIn('api_customers.stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->where('api_customers.updated_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        // Conversion rate
        $conversionRate = $newThisMonth > 0 ? ($closedThisMonth / $newThisMonth) * 100 : 0;

        // Average days in pipeline
        $avgDaysQuery = DB::table('api_customers')
            ->where('api_customers.user_id', $userId)
            ->whereNotIn('api_customers.stage_id', function ($q) use ($userId) {
                $q->select('id')->from('users_api_customers_stages')
                    ->where('user_id', $userId)
                    ->where('stage_name', 'LIKE', '%post_sale%');
            })
            ->selectRaw('AVG(DATEDIFF(NOW(), api_customers.created_at)) as avg_days');
        
        $avgDays = $avgDaysQuery->value('avg_days');

        // By stage
        $byStage = DB::table('api_customers')
            ->join('users_api_customers_stages as s', 'api_customers.stage_id', '=', 's.id')
            ->where('api_customers.user_id', $userId)
            ->groupBy('s.id', 's.stage_name')
            ->select('s.stage_name', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'stage_name')
            ->toArray();

        return [
            'total' => $total,
            'newThisMonth' => $newThisMonth,
            'totalDealValue' => (float) $dealValue,
            'closedThisMonth' => $closedThisMonth,
            'conversionRate' => round($conversionRate, 2),
            'avgDaysInPipeline' => $avgDays ? (int) round($avgDays) : 0,
            'byStage' => $byStage,
        ];
    }

    /**
     * Get filter options.
     */
    public function getFilterOptions(int $userId): array
    {
        return [
            'stages' => DB::table('users_api_customers_stages')
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'stage_name as label', 'color', 'icon']),
            
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
     * Build query with filters.
     */
    private function buildQuery(int $userId, array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('api_customers')
            ->where('api_customers.user_id', $userId)
            ->leftJoin('users_api_customers_stages as stage', 'api_customers.stage_id', '=', 'stage.id')
            ->leftJoin('users_api_customers_priorities as priority', 'api_customers.priority_id', '=', 'priority.id')
            ->leftJoin('users_api_customers_types as type', 'api_customers.type_id', '=', 'type.id')
            ->leftJoin('users as employee', 'api_customers.responsible_employee_id', '=', 'employee.id')
            ->select([
                'api_customers.*',
                'stage.stage_name',
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
            $query->whereIn('api_customers.stage_id', $filters['stage']);
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
                'id' => $customer->stage_id,
                'name' => $customer->stage_name,
                'color' => $customer->stage_color,
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
