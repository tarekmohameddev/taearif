<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Contract\StoreApiContractRequest;
use App\Http\Requests\Api\Contract\UpdateApiContractRequest;
use App\Models\Contract;
use App\Models\Api\Rms\RmContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApiContractController extends Controller
{
    /**
     * Display a listing of contracts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular'); // 'regular' or 'rms'
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');
            $contractType = $request->get('contract_type');

            if ($type === 'rms') {
                $query = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                    ->where('user_id', Auth::id());

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
                $query = Contract::with(['customer:id,name,email,phone']);

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
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');

            if ($type === 'rms') {
                $contract = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                    ->where('user_id', Auth::id())
                    ->findOrFail($id);
            } else {
                $contract = Contract::with(['customer:id,name,email,phone'])
                    ->findOrFail($id);
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreApiContractRequest $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            $validated = $request->validated();
            unset($validated['type']);

            if ($type === 'rms') {
                $contract = RmContract::create(array_merge($validated, [
                    'user_id' => Auth::id(),
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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateApiContractRequest $request, int $id): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');
            $validated = $request->validated();
            unset($validated['type']);

            if ($type === 'rms') {
                $contract = RmContract::where('user_id', Auth::id())->findOrFail($id);

                $contract->update(array_merge($validated, [
                    'updated_by' => Auth::id(),
                ]));

            } else {
                $contract = Contract::findOrFail($id);

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
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');

            if ($type === 'rms') {
                $contract = RmContract::where('user_id', Auth::id())->findOrFail($id);
            } else {
                $contract = Contract::findOrFail($id);
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'regular');

            if ($type === 'rms') {
                $userId = Auth::id();
                
                $stats = [
                    'total' => RmContract::where('user_id', $userId)->count(),
                    'active' => RmContract::where('user_id', $userId)->where('status', 'active')->count(),
                    'pending' => RmContract::where('user_id', $userId)->where('status', 'pending')->count(),
                    'expired' => RmContract::where('user_id', $userId)->where('status', 'expired')->count(),
                    'terminated' => RmContract::where('user_id', $userId)->where('status', 'terminated')->count(),
                ];
            } else {
                $stats = [
                    'total' => Contract::count(),
                    'draft' => Contract::where('contract_status', 'draft')->count(),
                    'signed' => Contract::where('contract_status', 'signed')->count(),
                    'expired' => Contract::where('contract_status', 'expired')->count(),
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
     *
     * @param int $customerId
     * @return JsonResponse
     */
    public function getByCustomer(int $customerId): JsonResponse
    {
        try {
            $contracts = Contract::with(['customer:id,name,email,phone'])
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
     *
     * @param int $rentalId
     * @return JsonResponse
     */
    public function getByRental(int $rentalId): JsonResponse
    {
        try {
            $contracts = RmContract::with(['rental:id,contract_number,property_id,project_id'])
                ->where('rental_id', $rentalId)
                ->where('user_id', Auth::id())
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
}
