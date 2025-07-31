<?php

namespace App\Http\Controllers\Api\V1\Rms;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\InstallmentService;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;


class MaintenanceController extends Controller
{
    protected $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'priority', 'category', 'rental_id', 'from', 'to']);
        $results = $this->maintenanceService->list(auth()->id(), $filters);

        return response()->json(['status' => true, 'data' => $results]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rental_id' => 'required|integer|exists:rm_rentals,id',
            'category' => 'required|string|max:50',
            'priority' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'estimated_cost' => 'nullable|numeric',
            'payer' => 'nullable|in:landlord,tenant,shared',
            'payer_share_percent' => 'nullable|integer|min:0|max:100',
            'scheduled_date' => 'nullable|date',
            'assigned_to_vendor_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $ticket = $this->maintenanceService->create($validated, auth()->id());

        return response()->json(['status' => true, 'data' => $ticket], 201);
    }

    public function show($id)
    {
        $ticket = $this->maintenanceService->find(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $ticket]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:150',
            'description' => 'sometimes|string',
            'estimated_cost' => 'nullable|numeric',
            'payer' => 'nullable|in:landlord,tenant,shared',
            'payer_share_percent' => 'nullable|integer|min:0|max:100',
            'scheduled_date' => 'nullable|date',
            'assigned_to_vendor_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $ticket = $this->maintenanceService->update($id, $validated, auth()->id());

        return response()->json(['status' => true, 'data' => $ticket]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,on_hold,resolved,cancelled'
        ]);

        $ticket = $this->maintenanceService->changeStatus($id, $validated['status'], auth()->id());

        return response()->json(['status' => true, 'data' => $ticket]);
    }

    public function destroy($id)
    {
        $this->maintenanceService->delete($id, auth()->id());
        return response()->json([], 204);
    }
}
