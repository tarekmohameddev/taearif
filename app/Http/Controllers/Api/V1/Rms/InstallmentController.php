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
        try {
            $filters = $request->only(['rental_id', 'contract_id', 'status', 'from', 'to']);
            $installments = $this->installmentService->listInstallments(auth()->id(), $filters);

            return response()->json(['status' => true, 'data' => $installments]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|in:pending,paid,partial,overdue,void',
                'paid_amount' => 'nullable|numeric|min:0',
                'paid_at' => 'nullable|date',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:255',
            ]);

            $updated = $this->installmentService->updateInstallment($id, $validated, auth()->id());

            return response()->json(['status' => true, 'data' => $updated]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Installment not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
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
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

}
