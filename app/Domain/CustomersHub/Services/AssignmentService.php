<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use App\Models\CustomerAssignmentRule;
use Carbon\Carbon;

class AssignmentService
{
    /**
     * Get all employees with their workload statistics.
     *
     * @param int $userId Tenant owner ID
     * @return array
     */
    public function getEmployees(int $userId): array
    {
        $employees = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee')
            ->select([
                'u.id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone',
                'u.active as is_active',
                'u.max_capacity',
                DB::raw('(SELECT COUNT(*) FROM api_customers WHERE responsible_employee_id = u.id AND user_id = ?) as customer_count'),
                DB::raw('(SELECT COUNT(*) FROM api_customers WHERE responsible_employee_id = u.id AND user_id = ? AND deleted_at IS NULL) as active_count')
            ])
            ->addBinding($userId, 'select')
            ->addBinding($userId, 'select')
            ->get();

        return array_map(function ($employee) {
            $customerCount = $employee->customer_count ?? 0;
            $maxCapacity = $employee->max_capacity ?? 50;
            $loadPercentage = $maxCapacity > 0 ? round(($customerCount / $maxCapacity) * 100, 2) : 0;

            return [
                'id' => (string) $employee->id,
                'name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                'role' => 'Sales Agent', // Could be enhanced to pull from a role field
                'email' => $employee->email,
                'phone' => $employee->phone,
                'customerCount' => $customerCount,
                'activeCount' => $employee->active_count ?? 0,
                'maxCapacity' => $maxCapacity,
                'isActive' => (bool) $employee->is_active,
                'loadPercentage' => $loadPercentage,
            ];
        }, $employees->toArray());
    }

    /**
     * Get count of unassigned customers.
     *
     * @param int $userId Tenant owner ID
     * @return int
     */
    public function getUnassignedCount(int $userId): int
    {
        return DB::table('api_customers')
            ->where('user_id', $userId)
            ->whereNull('responsible_employee_id')
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Auto-assign customers based on employee rules.
     *
     * @param int $userId Tenant owner ID
     * @param array $employeeRules Array of employee rules configuration
     * @return array
     */
    public function autoAssign(int $userId, array $employeeRules): array
    {
        $assignedCount = 0;
        $failedCount = 0;
        $assignments = [];

        // Get unassigned customers with related data
        $customers = $this->getUnassignedCustomers($userId);

        // Get employee capacities
        $employeeCapacities = $this->getEmployeeCapacities($userId);

        foreach ($customers as $customer) {
            $assigned = false;
            
            // Find matching employees (sorted by load percentage)
            $matchingEmployees = $this->findMatchingEmployees($customer, $employeeRules, $employeeCapacities);

            foreach ($matchingEmployees as $employeeId) {
                // Check if employee has capacity
                if ($this->hasCapacity($employeeId, $employeeCapacities)) {
                    // Assign customer
                    $success = $this->assignCustomer($userId, $customer->id, $employeeId);
                    
                    if ($success) {
                        $assignedCount++;
                        $assignments[] = [
                            'customerId' => (string) $customer->id,
                            'employeeId' => (string) $employeeId,
                            'assignedAt' => Carbon::now()->toIso8601String(),
                        ];
                        
                        // Update capacity tracking
                        $employeeCapacities[$employeeId]['current']++;
                        $assigned = true;
                        break;
                    }
                }
            }

            if (!$assigned) {
                $failedCount++;
            }
        }

        return [
            'assignedCount' => $assignedCount,
            'failedCount' => $failedCount,
            'assignments' => $assignments,
        ];
    }

    /**
     * Manually assign customers to an employee.
     *
     * @param int $userId Tenant owner ID
     * @param array $customerIds Array of customer IDs
     * @param string $employeeId Employee ID
     * @return array
     */
    public function manualAssign(int $userId, array $customerIds, string $employeeId): array
    {
        // Validate employee exists and is active
        $employee = DB::table('users')
            ->where('id', $employeeId)
            ->where('tenant_id', $userId)
            ->where('account_type', 'employee')
            ->where('active', true)
            ->first();

        if (!$employee) {
            throw new \InvalidArgumentException('Employee not found or inactive');
        }

        $assignedCount = 0;
        $assignments = [];

        foreach ($customerIds as $customerId) {
            $success = $this->assignCustomer($userId, $customerId, $employeeId);
            
            if ($success) {
                $assignedCount++;
                $assignments[] = [
                    'customerId' => (string) $customerId,
                    'employeeId' => (string) $employeeId,
                    'assignedAt' => Carbon::now()->toIso8601String(),
                ];
            }
        }

        return [
            'assignedCount' => $assignedCount,
            'assignments' => $assignments,
        ];
    }

    /**
     * Save assignment rules for employees.
     *
     * @param int $userId Tenant owner ID
     * @param array $employeeRules Array of employee rules
     * @return array
     */
    public function saveRules(int $userId, array $employeeRules): array
    {
        $savedCount = 0;
        $savedRules = [];

        DB::beginTransaction();
        
        try {
            foreach ($employeeRules as $employeeRule) {
                $employeeId = $employeeRule['employeeId'];
                
                // Delete existing rules for this employee
                CustomerAssignmentRule::where('user_id', $userId)
                    ->where('employee_id', $employeeId)
                    ->delete();

                // Create new rule
                $rule = CustomerAssignmentRule::create([
                    'user_id' => $userId,
                    'employee_id' => $employeeId,
                    'is_active' => $employeeRule['isActive'] ?? true,
                    'rules' => $employeeRule['rules'] ?? [],
                ]);

                $savedCount++;
                $savedRules[] = [
                    'employeeId' => (string) $employeeId,
                    'isActive' => $rule->is_active,
                    'rules' => $rule->rules,
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'savedCount' => $savedCount,
            'rules' => $savedRules,
        ];
    }

    /**
     * Get assignment rules for all employees.
     *
     * @param int $userId Tenant owner ID
     * @return array
     */
    public function getRules(int $userId): array
    {
        $rules = CustomerAssignmentRule::where('user_id', $userId)
            ->orderBy('employee_id')
            ->get();

        return $rules->map(function ($rule) {
            return [
                'employeeId' => (string) $rule->employee_id,
                'isActive' => $rule->is_active,
                'rules' => $rule->rules,
            ];
        })->values()->all();
    }

    /**
     * Get unassigned customers with related data for rule matching.
     *
     * @param int $userId Tenant owner ID
     * @return \Illuminate\Support\Collection
     */
    private function getUnassignedCustomers(int $userId)
    {
        return DB::table('api_customers as c')
            ->leftJoinSub(
                DB::table('api_customer_property_request')->select('customer_id')->selectRaw('MIN(property_request_id) as property_request_id')->groupBy('customer_id'),
                'acpr',
                'acpr.customer_id',
                '=',
                'c.id'
            )
            ->leftJoin('users_property_requests as pr', 'pr.id', '=', 'acpr.property_request_id')
            ->leftJoin('user_districts as d', 'c.city_id', '=', 'd.id')
            ->where('c.user_id', $userId)
            ->whereNull('c.responsible_employee_id')
            ->whereNull('c.deleted_at')
            ->select([
                'c.id',
                'c.name',
                'c.source',
                'c.city_id',
                'd.city_name_ar as city_name',
                'pr.budget_from',
                'pr.budget_to',
                'pr.category_id',
                'pr.property_type',
            ])
            ->get();
    }

    /**
     * Get employee capacities (current customer count and max capacity).
     *
     * @param int $userId Tenant owner ID
     * @return array
     */
    private function getEmployeeCapacities(int $userId): array
    {
        $capacities = [];
        
        $employees = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee')
            ->where('u.active', true)
            ->select([
                'u.id',
                'u.max_capacity',
                DB::raw('(SELECT COUNT(*) FROM api_customers WHERE responsible_employee_id = u.id AND user_id = ?) as customer_count')
            ])
            ->addBinding($userId, 'select')
            ->get();

        foreach ($employees as $employee) {
            $capacities[$employee->id] = [
                'current' => $employee->customer_count ?? 0,
                'max' => $employee->max_capacity ?? 50,
            ];
        }

        return $capacities;
    }

    /**
     * Find employees whose rules match the customer.
     *
     * @param object $customer Customer data
     * @param array $employeeRules Array of employee rules
     * @param array $employeeCapacities Employee capacity data
     * @return array Employee IDs sorted by load percentage
     */
    private function findMatchingEmployees(object $customer, array $employeeRules, array $employeeCapacities): array
    {
        $matchingEmployees = [];

        foreach ($employeeRules as $employeeRule) {
            $employeeId = $employeeRule['employeeId'];
            $isActive = $employeeRule['isActive'] ?? true;
            $rules = $employeeRule['rules'] ?? [];

            // Skip inactive employees
            if (!$isActive) {
                continue;
            }

            // Check if all rules match (AND logic)
            if ($this->matchesRules($customer, $rules)) {
                $capacity = $employeeCapacities[$employeeId] ?? ['current' => 0, 'max' => 50];
                $loadPercentage = $capacity['max'] > 0 ? ($capacity['current'] / $capacity['max']) * 100 : 0;
                
                $matchingEmployees[] = [
                    'id' => $employeeId,
                    'loadPercentage' => $loadPercentage,
                    'customerCount' => $capacity['current'],
                ];
            }
        }

        // Sort by load percentage (lowest first), then by customer count
        usort($matchingEmployees, function ($a, $b) {
            if ($a['loadPercentage'] != $b['loadPercentage']) {
                return $a['loadPercentage'] <=> $b['loadPercentage'];
            }
            if ($a['customerCount'] != $b['customerCount']) {
                return $a['customerCount'] <=> $b['customerCount'];
            }
            return $a['id'] <=> $b['id'];
        });

        return array_column($matchingEmployees, 'id');
    }

    /**
     * Check if all rules match for a customer.
     *
     * @param object $customer Customer data
     * @param array $rules Array of rules
     * @return bool
     */
    private function matchesRules(object $customer, array $rules): bool
    {
        if (empty($rules)) {
            return false; // No rules means no match
        }

        foreach ($rules as $rule) {
            if (!$this->evaluateRule($customer, $rule)) {
                return false; // AND logic - all rules must match
            }
        }
        
        return true;
    }

    /**
     * Evaluate a single rule against customer data.
     *
     * @param object $customer Customer data
     * @param array $rule Rule configuration
     * @return bool
     */
    private function evaluateRule(object $customer, array $rule): bool
    {
        $field = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? '';
        $value = $rule['value'] ?? '';

        $customerValue = $this->getCustomerFieldValue($customer, $field);

        return match($operator) {
            'equals' => strcasecmp((string)$customerValue, (string)$value) === 0,
            'greaterThan' => (float)$customerValue > (float)$value,
            'lessThan' => (float)$customerValue < (float)$value,
            'contains' => stripos((string)$customerValue, (string)$value) !== false,
            default => false
        };
    }

    /**
     * Get customer field value for rule evaluation.
     *
     * @param object $customer Customer data
     * @param string $field Field name
     * @return mixed
     */
    private function getCustomerFieldValue(object $customer, string $field)
    {
        return match($field) {
            'budgetMin' => $customer->budget_from ?? 0,
            'budgetMax' => $customer->budget_to ?? 0,
            'propertyType' => $this->getPropertyType($customer),
            'city' => $customer->city_name ?? '',
            'source' => $customer->source ?? '',
            default => null
        };
    }

    /**
     * Get property type - checks both category_id and property_type.
     *
     * @param object $customer Customer data
     * @return string
     */
    private function getPropertyType(object $customer): string
    {
        // Check category_id first (villa, apartment, etc.)
        if (!empty($customer->category_id)) {
            return $customer->category_id;
        }
        
        // Fall back to property_type (residential, commercial, etc.)
        return $customer->property_type ?? '';
    }

    /**
     * Check if employee has capacity for more assignments.
     *
     * @param int $employeeId Employee ID
     * @param array $employeeCapacities Capacity data
     * @return bool
     */
    private function hasCapacity(int $employeeId, array $employeeCapacities): bool
    {
        $capacity = $employeeCapacities[$employeeId] ?? null;
        
        if (!$capacity) {
            return false;
        }

        return $capacity['current'] < $capacity['max'];
    }

    /**
     * Assign a customer to an employee.
     *
     * @param int $userId Tenant owner ID
     * @param int $customerId Customer ID
     * @param int $employeeId Employee ID
     * @return bool
     */
    private function assignCustomer(int $userId, int $customerId, int $employeeId): bool
    {
        try {
            $updated = DB::table('api_customers')
                ->where('id', $customerId)
                ->where('user_id', $userId)
                ->update([
                    'responsible_employee_id' => $employeeId,
                    'updated_at' => Carbon::now(),
                ]);

            return $updated > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
