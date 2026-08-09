<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Contract\StoreApiContractRequest;
use App\Http\Requests\Api\Contract\UpdateApiContractRequest;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Api\Rms\RmContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApiContractController extends BaseApiController
{
    /**
     * Display a listing of contracts.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            if ($denied = $this->ensureContractPermission('view', $type)) {
                return $denied;
            }

            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');
            $contractType = $request->get('contract_type');
            $ownerId = $this->getUserId();

            if ($type === 'rms') {
                $query = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                    ->where('user_id', $ownerId);

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('property_name', 'like', "%{$search}%")
                          ->orWhere('project_name', 'like', "%{$search}%");
                    });
                }

                if ($status) {
                    $query->where('status', $status);
                }

                $contracts = $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
            } else {
                $query = $this->regularContractsQuery($ownerId);

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('subject', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhereHas('customer', function ($customerQuery) use ($search) {
                              $customerQuery->where('name', 'like', "%{$search}%")
                                          ->orWhere('email', 'like', "%{$search}%");
                          });
                    });
                }

                if ($status) {
                    $query->where('contract_status', $status);
                }

                if ($contractType) {
                    $query->where('contract_type', $contractType);
                }

                $contracts = $query->orderBy('created_at', 'desc')
                    ->paginate($perPage);
            }

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
                'message' => 'Error retrieving contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified contract.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            if ($denied = $this->ensureContractPermission('view', $type)) {
                return $denied;
            }

            $ownerId = $this->getUserId();

            if ($type === 'rms') {
                $contract = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                    ->where('user_id', $ownerId)
                    ->findOrFail($id);
            } else {
                $contract = $this->regularContractsQuery($ownerId)->findOrFail($id);
            }

            return response()->json([
                'status' => true,
                'data' => $contract
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Contract not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a newly created contract.
     */
    public function store(StoreApiContractRequest $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            if ($denied = $this->ensureContractPermission('create', $type)) {
                return $denied;
            }

            $validated = $request->validated();
            unset($validated['type']);
            $ownerId = $this->getUserId();

            if ($type === 'rms') {
                $contract = RmContract::create(array_merge($validated, [
                    'user_id' => $ownerId,
                    'created_by' => Auth::id(),
                ]));
            } else {
                $contract = Contract::create($validated);
            }

            return response()->json([
                'status' => true,
                'data' => $contract,
                'message' => 'Contract created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified contract.
     */
    public function update(UpdateApiContractRequest $request, int $id): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            if ($denied = $this->ensureContractPermission('update', $type)) {
                return $denied;
            }

            $validated = $request->validated();
            unset($validated['type']);
            $ownerId = $this->getUserId();

            if ($type === 'rms') {
                $contract = RmContract::where('user_id', $ownerId)->findOrFail($id);

                $contract->update(array_merge($validated, [
                    'updated_by' => Auth::id(),
                ]));
            } else {
                $contract = $this->regularContractsQuery($ownerId)->findOrFail($id);
                $contract->update($validated);
            }

            return response()->json([
                'status' => true,
                'data' => $contract,
                'message' => 'Contract updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified contract.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            if ($denied = $this->ensureContractPermission('delete', $type)) {
                return $denied;
            }

            $ownerId = $this->getUserId();

            if ($type === 'rms') {
                $contract = RmContract::where('user_id', $ownerId)->findOrFail($id);
            } else {
                $contract = $this->regularContractsQuery($ownerId)->findOrFail($id);
            }

            $contract->delete();

            return response()->json([
                'status' => true,
                'message' => 'Contract deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contract statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            if ($denied = $this->ensureContractPermission('view', $type)) {
                return $denied;
            }

            $ownerId = $this->getUserId();

            if ($type === 'rms') {
                $stats = [
                    'total' => RmContract::where('user_id', $ownerId)->count(),
                    'active' => RmContract::where('user_id', $ownerId)->where('status', 'active')->count(),
                    'pending' => RmContract::where('user_id', $ownerId)->where('status', 'pending')->count(),
                    'expired' => RmContract::where('user_id', $ownerId)->where('status', 'expired')->count(),
                    'terminated' => RmContract::where('user_id', $ownerId)->where('status', 'terminated')->count(),
                ];
            } else {
                $base = $this->regularContractsQuery($ownerId);
                $stats = [
                    'total' => (clone $base)->count(),
                    'draft' => (clone $base)->where('contract_status', 'draft')->count(),
                    'signed' => (clone $base)->where('contract_status', 'signed')->count(),
                    'expired' => (clone $base)->where('contract_status', 'expired')->count(),
                ];
            }

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
     * Get contracts by customer (for regular contracts).
     */
    public function getByCustomer(int $customerId): JsonResponse
    {
        try {
            if ($denied = $this->ensureContractPermission('view', 'regular')) {
                return $denied;
            }

            $ownerId = $this->getUserId();

            $customer = Customer::where('id', $customerId)
                ->where('user_id', $ownerId)
                ->first();

            if (!$customer) {
                return response()->json([
                    'status' => false,
                    'message' => 'Customer not found',
                ], 404);
            }

            $contracts = $this->regularContractsQuery($ownerId)
                ->where('customer_id', $customerId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $contracts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving customer contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contracts by rental (for RMS contracts).
     */
    public function getByRental(int $rentalId): JsonResponse
    {
        try {
            if ($denied = $this->ensureContractPermission('view', 'rms')) {
                return $denied;
            }

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
     * Regular CRM contracts scoped to the tenant owner via customer.user_id.
     */
    private function regularContractsQuery(int $ownerId)
    {
        return Contract::with(['customer:id,name,email,phone'])
            ->whereHas('customer', function ($q) use ($ownerId) {
                $q->where('user_id', $ownerId);
            });
    }

    /**
     * Enforce Spatie permission for regular (crm.*) vs RMS (rentals.*) contract ops.
     */
    private function ensureContractPermission(string $action, string $type): ?JsonResponse
    {
        $permission = $type === 'rms'
            ? "rentals.{$action}"
            : "crm.{$action}";

        if (!Auth::user() || !Auth::user()->can($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        return null;
    }
}
