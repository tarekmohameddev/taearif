<?php

namespace App\Http\Controllers\Api\V1\Rms;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\InstallmentService;


class InstallmentController extends Controller
{
    protected $installmentService;

    public function __construct(InstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['rental_id', 'contract_id', 'status', 'from', 'to']);
        $installments = $this->installmentService->listInstallments(auth()->id(), $filters);

        return response()->json(['status' => true, 'data' => $installments]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,paid,partial,overdue,void',
            'paid_amount' => 'nullable|numeric|min:0',
            'paid_at' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $updated = $this->installmentService->updateInstallment($id, $validated, auth()->id());

        return response()->json(['status' => true, 'data' => $updated]);
    }

    // regenerate
    public function regenerate(Request $request, $rentalId)
    {
        try {
            $this->installmentService->regenerateSchedule($rentalId, auth()->id());

            return response()->json([
                'status' => true,
                'message' => 'Installment schedule regenerated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

}
