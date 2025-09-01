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
            ->select(
                'id', 'contract_number', 'start_date', 'end_date', 'status',
                'property_id','project_id','property_name','project_name',
                'water_fee_monthly','platform_fee','grace_period_months'
            )
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
            'generate_schedule' => 'nullable|boolean',
            'property_id'     => 'nullable|integer|min:1',
            'project_id'      => 'nullable|integer|min:1',
            'property_name'   => 'nullable|string|max:150',
            'project_name'    => 'nullable|string|max:150',
            'water_fee_monthly'       => 'nullable|numeric|min:0',
            'office_commission_type'  => 'nullable|in:percentage,amount',
            'office_commission_value' => 'nullable|numeric|min:0',
            'platform_fee'            => 'nullable|numeric|min:0',
            'grace_period_months'     => 'nullable|integer|min:0|max:2',

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
            'file_path' => 'sometimes|string|max:255',
            'property_id'     => 'sometimes|nullable|integer|min:1',
            'project_id'      => 'sometimes|nullable|integer|min:1',
            'property_name'   => 'sometimes|nullable|string|max:150',
            'project_name'    => 'sometimes|nullable|string|max:150',
            'water_fee_monthly'       => 'sometimes|nullable|numeric|min:0',
            'office_commission_type'  => 'sometimes|nullable|in:percentage,amount',
            'office_commission_value' => 'sometimes|nullable|numeric|min:0',
            'platform_fee'            => 'sometimes|nullable|numeric|min:0',
            'grace_period_months'     => 'sometimes|nullable|integer|min:0|max:2',
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
