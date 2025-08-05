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
        $contracts = RmContract::where('rental_id', $rentalId)
            ->select('id', 'contract_number', 'start_date', 'end_date', 'status')
            ->orderBy('start_date')
            ->get();

        return response()->json(['status' => true, 'data' => $contracts]);
    }

    public function store(Request $request, $rentalId)
    {
        $validated = $request->validate([
            'contract_number' => 'required|string|max:64',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:pending,active',
            'file_path' => 'nullable|string|max:255',
            'generate_schedule' => 'nullable|boolean'
        ]);

        $contract = $this->contractService->createContract($rentalId, $validated, auth()->id());

        return response()->json(['status' => true, 'data' => $contract], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:pending,active,expired,terminated',
            'file_path' => 'sometimes|string|max:255'
        ]);

        $contract = $this->contractService->updateContract($id, $validated, auth()->id());

        return response()->json(['status' => true, 'data' => $contract]);
    }

    public function terminate(Request $request, $id)
    {
        $validated = $request->validate([
            'termination_reason' => 'required|string|max:255',
            'terminate_on' => 'required|date'
        ]);

        $contract = $this->contractService->terminateContract($id, $validated, auth()->id());

        return response()->json(['status' => true, 'data' => $contract]);
    }
}
