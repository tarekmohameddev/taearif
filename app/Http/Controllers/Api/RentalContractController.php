<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RentalContract\ChangeRentalContractStatusRequest;
use App\Http\Requests\Api\RentalContract\StoreRentalContractRequest;
use App\Http\Requests\Api\RentalContract\TerminateRentalContractRequest;
use App\Http\Requests\Api\RentalContract\UpdateRentalContractRequest;
use App\Models\Api\Rms\RmContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RentalContractController extends BaseApiController
{
    /**
     * Display a listing of rental contracts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');
            $rentalId = $request->get('rental_id');

            $query = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                ->where('user_id', $this->getUserId());

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('property_name', 'like', "%{$search}%")
                      ->orWhere('project_name', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($rentalId) {
                $query->where('rental_id', $rentalId);
            }

            $contracts = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => $contracts->items(),
                'pagination' => [
                    'current_page' => $contracts->currentPage(),
                    'last_page' => $contracts->lastPage(),
                    'per_page' => $contracts->perPage(),
                    'total' => $contracts->total(),
                    'from' => $contracts->firstItem(),
                    'to' => $contracts->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving rental contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified rental contract.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $contract = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                ->where('user_id', $this->getUserId())
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $contract
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Rental contract not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a newly created rental contract.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreRentalContractRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $contract = RmContract::create(array_merge($validated, [
                'user_id' => $this->getUserId(),
                'created_by' => Auth::id(),
            ]));

            return response()->json([
                'status' => true,
                'data' => $contract,
                'message' => 'Rental contract created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating rental contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified rental contract.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateRentalContractRequest $request, int $id): JsonResponse
    {
        try {
            $contract = RmContract::where('user_id', $this->getUserId())->findOrFail($id);

            $validated = $request->validated();

            $contract->update(array_merge($validated, [
                'updated_by' => Auth::id(),
            ]));

            return response()->json([
                'status' => true,
                'data' => $contract,
                'message' => 'Rental contract updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating rental contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified rental contract.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $contract = RmContract::where('user_id', $this->getUserId())->findOrFail($id);
            $contract->delete();

            return response()->json([
                'status' => true,
                'message' => 'Rental contract deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting rental contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rental contract statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            
            $stats = [
                'total' => RmContract::where('user_id', $userId)->count(),
                'active' => RmContract::where('user_id', $userId)->where('status', 'active')->count(),
                'pending' => RmContract::where('user_id', $userId)->where('status', 'pending')->count(),
                'expired' => RmContract::where('user_id', $userId)->where('status', 'expired')->count(),
                'terminated' => RmContract::where('user_id', $userId)->where('status', 'terminated')->count(),
            ];

            return response()->json([
                'status' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rental contracts by rental ID.
     *
     * @param int $rentalId
     * @return JsonResponse
     */
    public function getByRental(int $rentalId): JsonResponse
    {
        try {
            $contracts = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                ->where('rental_id', $rentalId)
                ->where('user_id', $this->getUserId())
                ->orderBy('start_date')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $contracts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving rental contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Terminate a rental contract.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function terminate(TerminateRentalContractRequest $request, int $id): JsonResponse
    {
        try {
            $contract = RmContract::where('user_id', $this->getUserId())->findOrFail($id);

            $validated = $request->validated();

            $contract->update([
                'status' => 'terminated',
                'termination_reason' => $validated['termination_reason'],
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => true,
                'data' => $contract,
                'message' => 'Rental contract terminated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error terminating rental contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change rental contract status.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changeStatus(ChangeRentalContractStatusRequest $request, int $id): JsonResponse
    {
        try {
            $contract = RmContract::where('user_id', $this->getUserId())->findOrFail($id);

            $validated = $request->validated();

            $contract->update([
                'status' => $validated['status'],
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => true,
                'data' => $contract,
                'message' => 'Rental contract status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating rental contract status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily follow-up for rental contracts.
     * Shows contracts that need attention today with all required information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function dailyFollowUp(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            $date = $request->get('date', now()->toDateString());
            $status = $request->get('status', 'active'); // Default to active contracts

            // Get active contracts with their rental information
            $contracts = RmContract::with([
                'rental:id,contract_number,tenant_full_name,tenant_phone,unit_id,project_id,base_rent_amount,paying_plan',
                'rental.property:id,building_id',
                'rental.property.building:id,name',
                'installments' => function($query) use ($date) {
                    $query->where('due_date', '<=', $date)
                          ->whereIn('status', ['pending', 'overdue', 'partial'])
                          ->orderBy('due_date', 'desc');
                }
            ])
            ->where('user_id', $userId)
            ->where('status', $status)
            ->whereHas('rental') // Only get contracts that have a rental
            ->get();

            $followUpData = $contracts->map(function ($contract) use ($date) {
                $rental = $contract->rental;
                
                // Skip contracts without rental data
                if (!$rental) {
                    return null;
                }
                
                $nextInstallment = $contract->installments->first();
                $overdueAmount = $contract->installments->sum(function ($installment) {
                    return $installment->amount - $installment->paid_amount;
                });

                return [
                    'contract_id' => $contract->id,
                    'contract_number' => $rental->contract_number ?? 'N/A',
                    'tenant_name' => $rental->tenant_full_name ?? 'N/A',
                    'tenant_phone' => $rental->tenant_phone ?? 'N/A',
                    'unit_info' => [
                        'property_id' => $rental->unit_id ?? 'N/A',
                        'building_name' => $rental->property->building->name ?? 'N/A',
                        'project_id' => $rental->project_id ?? 'N/A',
                    ],
                    'next_payment_amount' => $nextInstallment ? $nextInstallment->amount : 0,
                    'rental_method' => $rental->paying_plan ?? 'N/A',
                    'overdue_amount' => $overdueAmount,
                    'due_date' => $nextInstallment ? $nextInstallment->due_date->format('Y-m-d') : null,
                    'contract_end_date' => $contract->end_date->format('Y-m-d'),
                    'contract_status' => $contract->status,
                    'days_overdue' => $nextInstallment ? now()->diffInDays($nextInstallment->due_date, false) : 0,
                ];
            })->filter(); // Remove null entries

            // Sort by overdue amount (highest first) and then by due date
            $followUpData = $followUpData->sortByDesc('overdue_amount')
                ->sortBy('due_date')
                ->values();

            return response()->json([
                'status' => true,
                'data' => $followUpData,
                'summary' => [
                    'total_contracts' => $followUpData->count(),
                    'total_overdue_amount' => $followUpData->sum('overdue_amount'),
                    'contracts_with_overdue' => $followUpData->where('overdue_amount', '>', 0)->count(),
                    'contracts_due_today' => $followUpData->where('due_date', $date)->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving daily follow-up: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active contracts with color-coded status.
     * Shows contracts for contract management with payment status colors.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allContracts(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $buildingId = $request->get('building_id');
            $contractStatus = $request->get('contract_status', 'active'); // Default to active

            // Get contracts with their rental information
            $contracts = RmContract::with([
                'rental:id,contract_number,tenant_full_name,tenant_phone,unit_id,project_id,base_rent_amount,paying_plan',
                'rental.property:id,building_id',
                'rental.property.building:id,name',
                'installments' => function($query) {
                    $query->orderBy('due_date', 'desc');
                }
            ])
            ->where('user_id', $userId)
            ->where('status', $contractStatus)
            ->whereHas('rental')
            ->when($search, function($query, $search) {
                $query->whereHas('rental', function($q) use ($search) {
                    $q->where('contract_number', 'like', "%{$search}%")
                      ->orWhere('tenant_full_name', 'like', "%{$search}%")
                      ->orWhere('tenant_phone', 'like', "%{$search}%");
                });
            })
            ->when($buildingId, function($query, $buildingId) {
                $query->whereHas('rental.property', function($q) use ($buildingId) {
                    $q->where('building_id', $buildingId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

            $contractsData = $contracts->map(function ($contract) {
                $rental = $contract->rental;
                $installments = $contract->installments;
                
                // Calculate color status based on overall contract status
                $colorStatus = $this->calculateContractColorStatus($installments);
                
                // Get next payment due date
                $nextPayment = $installments->where('status', 'pending')->sortBy('due_date')->first();
                
                return [
                    'contract_id' => $contract->id,
                    'contract_number' => $rental->contract_number ?? 'N/A',
                    'tenant_data' => [
                        'name' => $rental->tenant_full_name ?? 'N/A',
                        'phone' => $rental->tenant_phone ?? 'N/A',
                    ],
                    'unit_data' => [
                        'property_id' => $rental->unit_id ?? 'N/A',
                        'building_name' => $rental->property->building->name ?? 'N/A',
                        'project_id' => $rental->project_id ?? 'N/A',
                    ],
                    'rental_amount' => $rental->base_rent_amount ?? 0,
                    'rental_period' => [
                        'start_date' => $contract->start_date->format('Y-m-d'),
                        'end_date' => $contract->end_date->format('Y-m-d'),
                    ],
                    'building' => $rental->property->building->name ?? 'N/A',
                    'rental_method' => $rental->paying_plan ?? 'N/A',
                    'color_status' => $colorStatus,
                    'next_payment_due' => $nextPayment ? $nextPayment->due_date->format('Y-m-d') : null,
                    'days_until_next_payment' => $nextPayment ? now()->diffInDays($nextPayment->due_date, false) : null,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $contractsData,
                'pagination' => [
                    'current_page' => $contracts->currentPage(),
                    'last_page' => $contracts->lastPage(),
                    'per_page' => $contracts->perPage(),
                    'total' => $contracts->total(),
                    'from' => $contracts->firstItem(),
                    'to' => $contracts->lastItem(),
                ],
                'summary' => [
                    'total_contracts' => $contracts->total(),
                    'red_contracts' => $contractsData->where('color_status', 'red')->count(),
                    'green_contracts' => $contractsData->where('color_status', 'green')->count(),
                    'yellow_contracts' => $contractsData->where('color_status', 'yellow')->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving all contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate contract color status based on overall contract status.
     *
     * @param Collection $installments
     * @return string
     */
    private function calculateContractColorStatus($installments)
    {
        $now = now();
        $dueSoonThreshold = 14; // days
        
        // Check if there are any overdue payments
        $hasOverdue = $installments->where('status', 'overdue')
            ->where('due_date', '<', $now->toDateString())
            ->isNotEmpty();
            
        if ($hasOverdue) {
            return 'red'; // Overdue
        }
        
        // Check if all payments are paid
        $allPaid = $installments->where('status', '!=', 'paid')->isEmpty();
        if ($allPaid) {
            return 'green'; // All paid
        }
        
        // Check if next payment is due within 14 days
        $nextPayment = $installments->where('status', 'pending')
            ->where('due_date', '>=', $now->toDateString())
            ->sortBy('due_date')
            ->first();
            
        if ($nextPayment && $now->diffInDays($nextPayment->due_date, false) <= $dueSoonThreshold) {
            return 'yellow'; // Due soon
        }
        
        return 'green'; // Default to green if no issues
    }

    /**
     * Get contracts with advanced filtering options.
     * Separate function for contract filtering with multiple filter options.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filterContracts(Request $request): JsonResponse
    {
        try {
            $userId = $this->getUserId();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $buildingId = $request->get('building_id');
            $contractStatus = $request->get('contract_status');
            $colorStatus = $request->get('color_status'); // red, green, yellow
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            // Build the query
            $query = RmContract::with([
                'rental:id,contract_number,tenant_full_name,tenant_phone,unit_id,project_id,base_rent_amount,paying_plan',
                'rental.property:id,building_id',
                'rental.property.building:id,name',
                'installments' => function($query) {
                    $query->orderBy('due_date', 'desc');
                }
            ])
            ->where('user_id', $userId)
            ->whereHas('rental');

            // Apply filters
            if ($contractStatus) {
                $query->where('status', $contractStatus);
            }

            if ($buildingId) {
                $query->whereHas('rental.property', function($q) use ($buildingId) {
                    $q->where('building_id', $buildingId);
                });
            }

            if ($search) {
                $query->whereHas('rental', function($q) use ($search) {
                    $q->where('contract_number', 'like', "%{$search}%")
                      ->orWhere('tenant_full_name', 'like', "%{$search}%")
                      ->orWhere('tenant_phone', 'like', "%{$search}%");
                });
            }

            if ($dateFrom) {
                $query->where('start_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('end_date', '<=', $dateTo);
            }

            $contracts = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $contractsData = $contracts->map(function ($contract) {
                $rental = $contract->rental;
                $installments = $contract->installments;
                
                // Calculate color status
                $calculatedColorStatus = $this->calculateContractColorStatus($installments);
                
                // Get next payment due date
                $nextPayment = $installments->where('status', 'pending')->sortBy('due_date')->first();
                
                return [
                    'contract_id' => $contract->id,
                    'contract_number' => $rental->contract_number ?? 'N/A',
                    'contract_status' => $contract->status,
                    'tenant_data' => [
                        'name' => $rental->tenant_full_name ?? 'N/A',
                        'phone' => $rental->tenant_phone ?? 'N/A',
                    ],
                    'unit_data' => [
                        'property_id' => $rental->unit_id ?? 'N/A',
                        'building_name' => $rental->property->building->name ?? 'N/A',
                        'project_id' => $rental->project_id ?? 'N/A',
                    ],
                    'rental_amount' => $rental->base_rent_amount ?? 0,
                    'rental_period' => [
                        'start_date' => $contract->start_date->format('Y-m-d'),
                        'end_date' => $contract->end_date->format('Y-m-d'),
                    ],
                    'building' => $rental->property->building->name ?? 'N/A',
                    'rental_method' => $rental->paying_plan ?? 'N/A',
                    'color_status' => $calculatedColorStatus,
                    'next_payment_due' => $nextPayment ? $nextPayment->due_date->format('Y-m-d') : null,
                    'days_until_next_payment' => $nextPayment ? now()->diffInDays($nextPayment->due_date, false) : null,
                ];
            });

            // Apply color status filter if provided
            if ($colorStatus) {
                $contractsData = $contractsData->where('color_status', $colorStatus);
            }

            return response()->json([
                'status' => true,
                'data' => $contractsData->values(),
                'pagination' => [
                    'current_page' => $contracts->currentPage(),
                    'last_page' => $contracts->lastPage(),
                    'per_page' => $contracts->perPage(),
                    'total' => $contracts->total(),
                    'from' => $contracts->firstItem(),
                    'to' => $contracts->lastItem(),
                ],
                'summary' => [
                    'total_contracts' => $contracts->total(),
                    'red_contracts' => $contractsData->where('color_status', 'red')->count(),
                    'green_contracts' => $contractsData->where('color_status', 'green')->count(),
                    'yellow_contracts' => $contractsData->where('color_status', 'yellow')->count(),
                ],
                'filters_applied' => [
                    'contract_status' => $contractStatus,
                    'building_id' => $buildingId,
                    'color_status' => $colorStatus,
                    'search' => $search,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error filtering contracts: ' . $e->getMessage()
            ], 500);
        }
    }
}
