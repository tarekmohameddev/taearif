<?php

namespace App\Http\Controllers\Api\V1\Rms;

use Illuminate\Http\Request;
use App\Models\Api\Rms\RmContract;
use App\Http\Controllers\Controller;
use App\Services\Rms\ContractService;

class ContractController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function listByRental($rentalId)
    {
        try {
            $userId = auth()->id();

            $contracts = RmContract::where('rental_id', $rentalId)
                ->where('user_id', $userId)
                ->with('rental:id,contract_number')
                ->select(
                    'id', 'rental_id', 'start_date', 'end_date', 'status',
                    'property_id','project_id','property_name','project_name',
                    'grace_period_months'
                )
                ->orderBy('start_date')
                ->get();

            // Add contract_number from rental to each contract
            $contracts->each(function ($contract) {
                $contract->contract_number = $contract->rental->contract_number ?? null;
            });

            return response()->json(['status' => true, 'data' => $contracts]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $rentalId)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:pending,active',
                'file_path' => 'nullable|string|max:255',
                'generate_schedule' => 'nullable|boolean',
                'property_id'     => 'nullable|integer|min:1',
                'project_id'      => 'nullable|integer|min:1',
                'property_name'   => 'nullable|string|max:150',
                'project_name'    => 'nullable|string|max:150',
                'grace_period_months'     => 'nullable|integer|min:0|max:2',

            ]);

            $contract = $this->contractService->createContract($rentalId, $validated, auth()->id());

            return response()->json(['status' => true, 'data' => $contract], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
                'status' => 'sometimes|in:pending,active,expired,terminated',
                'file_path' => 'sometimes|string|max:255',
                'property_id'     => 'sometimes|nullable|integer|min:1',
                'project_id'      => 'sometimes|nullable|integer|min:1',
                'property_name'   => 'sometimes|nullable|string|max:150',
                'project_name'    => 'sometimes|nullable|string|max:150',
                'grace_period_months'     => 'sometimes|nullable|integer|min:0|max:2',
            ]);

            $contract = $this->contractService->updateContract($id, $validated, auth()->id());

            return response()->json(['status' => true, 'data' => $contract]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function terminate(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'termination_reason' => 'required|string|max:255',
                'terminate_on' => 'required|date'
            ]);

            $contract = $this->contractService->terminateContract($id, $validated, auth()->id());

            return response()->json(['status' => true, 'data' => $contract]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,active,expired,terminated',
                'reason' => 'nullable|string|max:255',
                'effective_date' => 'nullable|date'
            ]);

            $contract = $this->contractService->changeContractStatus($id, $validated, auth()->id());

            return response()->json(['status' => true, 'data' => $contract]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
