<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use App\Models\CustomerAssignmentRule;
use Carbon\Carbon;

class AssignmentService
{
    /**
     * Get all employees with their workload statistics (request-level: count of property requests assigned).
     *
     * @param int $userId Tenant owner ID
     * @return array
     */
    public function getEmployees(int $userId): array
    {
        $requestCountSub = 'SELECT COUNT(DISTINCT upr.id) FROM users_property_requests upr ' .
            'INNER JOIN api_customer_property_request acpr ON acpr.property_request_id = upr.id ' .
            'INNER JOIN api_customers ac ON ac.id = acpr.customer_id AND ac.user_id = upr.user_id ' .
            'WHERE ac.responsible_employee_id = u.id AND upr.user_id = ? AND upr.is_active = 1';

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
                DB::raw("({$requestCountSub}) as request_count"),
            ])
            ->addBinding($userId, 'select')
            ->get();

        return array_map(function ($employee) {
            $requestCount = $employee->request_count ?? 0;
            $maxCapacity = $employee->max_capacity ?? 50;
            $loadPercentage = $maxCapacity > 0 ? round(($requestCount / $maxCapacity) * 100, 2) : 0;

            return [
                'id' => (string) $employee->id,
                'name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')),
                'role' => 'Sales Agent',
                'email' => $employee->email,
                'phone' => $employee->phone,
                'customerCount' => $requestCount,
                'activeCount' => $requestCount,
                'maxCapacity' => $maxCapacity,
                'isActive' => (bool) $employee->is_active,
                'loadPercentage' => $loadPercentage,
            ];
        }, $employees->toArray());
    }

    /**
     * Get count of unassigned property requests (request-level: requests whose linked customer has no assignee).
     *
     * @param int $userId Tenant owner ID
     * @return int
     */
    public function getUnassignedCount(int $userId): int
    {
        return (int) DB::table('users_property_requests as upr')
            ->join('api_customer_property_request as acpr', 'acpr.property_request_id', '=', 'upr.id')
            ->join('api_customers as ac', function ($j) use ($userId) {
                $j->on('ac.id', '=', 'acpr.customer_id')->where('ac.user_id', '=', $userId);
            })
            ->where('upr.user_id', $userId)
            ->where('upr.is_active', 1)
            ->whereNull('ac.responsible_employee_id')
            ->selectRaw('COUNT(DISTINCT upr.id) as c')
            ->value('c');
    }

    /**
     * Auto-assign property requests (leads) based on employee rules. Assigns via linked customer.
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

        $requests = $this->getUnassignedPropertyRequests($userId);
        $employeeCapacities = $this->getEmployeeCapacities($userId);

        foreach ($requests as $requestRow) {
            $assigned = false;
            $matchingEmployees = $this->findMatchingEmployees($requestRow, $employeeRules, $employeeCapacities);

            foreach ($matchingEmployees as $employeeId) {
                if ($this->hasCapacity($employeeId, $employeeCapacities)) {
                    $success = $this->assignCustomer($userId, $requestRow->customer_id, $employeeId);
                    if ($success) {
                        $assignedCount++;
                        $assignments[] = [
                            'requestId' => (string) $requestRow->id,
                            'customerId' => (string) $requestRow->customer_id,
                            'employeeId' => (string) $employeeId,
                            'assignedAt' => Carbon::now()->toIso8601String(),
                        ];
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
     * Manually assign property requests or inquiries (leads) to an employee.
     * For inquiry_* IDs: sets responsible_employee_id on the inquiry row.
     * For other IDs: resolves to linked customer and sets api_customers.responsible_employee_id.
     *
     * @param int $userId Tenant owner ID
     * @param array $requestIds Array of composite IDs (e.g. property_request_42, inquiry_17) or numeric strings/ints
     * @param string $employeeId Employee ID
     * @return array
     */
    public function manualAssign(int $userId, array $requestIds, string $employeeId): array
    {
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
        $now = Carbon::now();

        foreach ($requestIds as $requestOrCustomerId) {
            $idString = is_string($requestOrCustomerId) ? $requestOrCustomerId : (string) $requestOrCustomerId;

            if (str_starts_with($idString, 'inquiry_')) {
                $inquiryId = (int) substr($idString, 7);
                if ($inquiryId <= 0) {
                    continue;
                }
                $updated = DB::table('api_customer_inquiry')
                    ->where('user_id', $userId)
                    ->where('id', $inquiryId)
                    ->update([
                        'responsible_employee_id' => $employeeId,
                        'updated_at' => $now,
                    ]);
                if ($updated > 0) {
                    $assignedCount++;
                    $inquiry = DB::table('api_customer_inquiry')
                        ->where('user_id', $userId)
                        ->where('id', $inquiryId)
                        ->first(['customer_id']);
                    $assignments[] = [
                        'requestId' => $idString,
                        'customerId' => $inquiry && $inquiry->customer_id !== null ? (string) $inquiry->customer_id : null,
                        'employeeId' => (string) $employeeId,
                        'assignedAt' => $now->toIso8601String(),
                    ];
                }
                continue;
            }

            $customerId = $this->resolveCompositeRequestIdToCustomerId($userId, $idString);
            if ($customerId === null) {
                continue;
            }
            $success = $this->assignCustomer($userId, $customerId, $employeeId);
            if ($success) {
                $assignedCount++;
                $assignments[] = [
                    'requestId' => $idString,
                    'customerId' => (string) $customerId,
                    'employeeId' => (string) $employeeId,
                    'assignedAt' => $now->toIso8601String(),
                ];
            }
        }

        return [
            'assignedCount' => $assignedCount,
            'assignments' => $assignments,
        ];
    }

    /**
     * Resolve composite request ID (inquiry_17, property_request_42) or numeric ID to customer ID.
     */
    private function resolveCompositeRequestIdToCustomerId(int $userId, string $compositeOrNumericId): ?int
    {
        if (str_starts_with($compositeOrNumericId, 'inquiry_')) {
            $inquiryId = (int) substr($compositeOrNumericId, 7);
            if ($inquiryId <= 0) {
                return null;
            }
            $customerId = DB::table('api_customer_inquiry')
                ->where('user_id', $userId)
                ->where('id', $inquiryId)
                ->value('customer_id');
            return $customerId !== null ? (int) $customerId : null;
        }
        if (str_starts_with($compositeOrNumericId, 'property_request_')) {
            $requestId = (int) substr($compositeOrNumericId, 17);
            return $requestId > 0 ? $this->resolveRequestToCustomerId($userId, $requestId) : null;
        }
        $numeric = (int) $compositeOrNumericId;
        return $numeric > 0 ? $this->resolveRequestToCustomerId($userId, $numeric) : null;
    }

    /**
     * Resolve a property request ID (or customer ID if linked to single request) to customer ID for assignment.
     */
    private function resolveRequestToCustomerId(int $userId, int $requestOrCustomerId): ?int
    {
        $customerId = DB::table('api_customer_property_request as acpr')
            ->join('users_property_requests as upr', 'upr.id', '=', 'acpr.property_request_id')
            ->where('upr.user_id', $userId)
            ->where('upr.is_active', 1)
            ->where('acpr.property_request_id', $requestOrCustomerId)
            ->value('acpr.customer_id');
        if ($customerId !== null) {
            return (int) $customerId;
        }
        $asCustomer = DB::table('api_customers')
            ->where('id', $requestOrCustomerId)
            ->where('user_id', $userId)
            ->value('id');
        return $asCustomer !== null ? (int) $asCustomer : null;
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
     * Get unassigned property requests with linked customer and request data for rule matching.
     *
     * @param int $userId Tenant owner ID
     * @return \Illuminate\Support\Collection
     */
    private function getUnassignedPropertyRequests(int $userId)
    {
        $sub = DB::table('users_property_requests as upr')
            ->join('api_customer_property_request as acpr', 'acpr.property_request_id', '=', 'upr.id')
            ->join('api_customers as c', function ($j) use ($userId) {
                $j->on('c.id', '=', 'acpr.customer_id')->where('c.user_id', '=', $userId);
            })
            ->leftJoin('user_cities as uc', 'upr.city_id', '=', 'uc.id')
            ->where('upr.user_id', $userId)
            ->where('upr.is_active', 1)
            ->whereNull('c.responsible_employee_id')
            ->whereNull('c.deleted_at')
            ->select([
                'upr.id',
                'c.id as customer_id',
                'c.source',
                'upr.budget_from',
                'upr.budget_to',
                'upr.category_id',
                'upr.property_type',
                DB::raw('uc.name_ar as city_name'),
            ]);

        return $sub->get()->unique('id')->values();
    }

    /**
     * Get employee capacities (current request count and max capacity).
     *
     * @param int $userId Tenant owner ID
     * @return array
     */
    private function getEmployeeCapacities(int $userId): array
    {
        $capacities = [];
        
        $requestCountSub = 'SELECT COUNT(DISTINCT upr.id) FROM users_property_requests upr ' .
            'INNER JOIN api_customer_property_request acpr ON acpr.property_request_id = upr.id ' .
            'INNER JOIN api_customers ac ON ac.id = acpr.customer_id AND ac.user_id = upr.user_id ' .
            'WHERE ac.responsible_employee_id = u.id AND upr.user_id = ? AND upr.is_active = 1';

        $employees = DB::table('users as u')
            ->where('u.tenant_id', $userId)
            ->where('u.account_type', 'employee')
            ->where('u.active', true)
            ->select([
                'u.id',
                'u.max_capacity',
                DB::raw("({$requestCountSub}) as request_count"),
            ])
            ->addBinding($userId, 'select')
            ->get();

        foreach ($employees as $employee) {
            $capacities[$employee->id] = [
                'current' => $employee->request_count ?? 0,
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
