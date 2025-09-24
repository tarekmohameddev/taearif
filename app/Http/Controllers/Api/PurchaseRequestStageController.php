<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PurchaseRequestStageController extends Controller
{
    /**
     * Get all stages for a specific purchase request
     */
    public function index($purchaseRequestId)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        
        $stages = $purchaseRequest->stages()
            ->with('updatedBy')
            ->orderBy('stage_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stages,
            'message' => 'Purchase request stages retrieved successfully'
        ]);
    }

    /**
     * Update a specific stage status
     */
    public function updateStatus(Request $request, $purchaseRequestId, $stageId)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['الانتظار', 'قيد التنفيذ', 'مكتمل'])],
            'notes' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stage = $purchaseRequest->stages()->findOrFail($stageId);

        $stage->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $stage->notes,
            'updated_by' => Auth::id(),
        ]);

        // Auto-set timestamps based on status
        if ($validated['status'] === 'قيد التنفيذ' && !$stage->started_at) {
            $stage->update(['started_at' => now()]);
        } elseif ($validated['status'] === 'مكتمل') {
            $stage->update(['completed_at' => now()]);
        } elseif ($validated['status'] === 'الانتظار') {
            $stage->update([
                'started_at' => null,
                'completed_at' => null,
            ]);
        }

        // Refresh the stage with relationships
        $stage = $stage->fresh(['updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $stage,
            'message' => 'Stage status updated successfully'
        ]);
    }

    /**
     * Update stage notes
     */
    public function updateNotes(Request $request, $purchaseRequestId, $stageId)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stage = $purchaseRequest->stages()->findOrFail($stageId);

        $stage->update([
            'notes' => $validated['notes'],
            'updated_by' => Auth::id(),
        ]);

        $stage = $stage->fresh(['updatedBy']);

        return response()->json([
            'success' => true,
            'data' => $stage,
            'message' => 'Stage notes updated successfully'
        ]);
    }

    /**
     * Get stage details
     */
    public function show($purchaseRequestId, $stageId)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stage = $purchaseRequest->stages()
            ->with(['updatedBy', 'purchaseRequest'])
            ->findOrFail($stageId);

        return response()->json([
            'success' => true,
            'data' => $stage,
            'message' => 'Stage details retrieved successfully'
        ]);
    }

    /**
     * Bulk update multiple stages
     */
    public function bulkUpdate(Request $request, $purchaseRequestId)
    {
        $validated = $request->validate([
            'stages' => 'required|array',
            'stages.*.stage_id' => 'required|exists:purchase_request_stages,id',
            'stages.*.status' => ['required', Rule::in(['الانتظار', 'قيد التنفيذ', 'مكتمل'])],
            'stages.*.notes' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $updatedStages = [];

        foreach ($validated['stages'] as $stageData) {
            $stage = $purchaseRequest->stages()->findOrFail($stageData['stage_id']);
            
            $updateData = [
                'status' => $stageData['status'],
                'updated_by' => Auth::id(),
            ];

            if (isset($stageData['notes'])) {
                $updateData['notes'] = $stageData['notes'];
            }

            // Handle timestamps based on status
            if ($stageData['status'] === 'قيد التنفيذ' && !$stage->started_at) {
                $updateData['started_at'] = now();
            } elseif ($stageData['status'] === 'مكتمل') {
                $updateData['completed_at'] = now();
            } elseif ($stageData['status'] === 'الانتظار') {
                $updateData['started_at'] = null;
                $updateData['completed_at'] = null;
            }

            $stage->update($updateData);
            $updatedStages[] = $stage->fresh(['updatedBy']);
        }

        return response()->json([
            'success' => true,
            'data' => $updatedStages,
            'message' => 'Stages updated successfully'
        ]);
    }

    /**
     * Get stage statistics for a purchase request
     */
    public function statistics($purchaseRequestId)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stages = $purchaseRequest->stages;

        $totalStages = $stages->count();
        $completedStages = $stages->where('status', 'مكتمل')->count();
        $inProgressStages = $stages->where('status', 'قيد التنفيذ')->count();
        $pendingStages = $stages->where('status', 'الانتظار')->count();

        $progressPercentage = $totalStages > 0 ? round(($completedStages / $totalStages) * 100, 2) : 0;

        // Get current stage (first non-completed stage)
        $currentStage = $stages->where('status', '!=', 'مكتمل')->sortBy('stage_order')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_stages' => $totalStages,
                'completed_stages' => $completedStages,
                'in_progress_stages' => $inProgressStages,
                'pending_stages' => $pendingStages,
                'progress_percentage' => $progressPercentage,
                'current_stage' => $currentStage,
                'stages_breakdown' => $stages->groupBy('status')->map->count(),
            ],
            'message' => 'Stage statistics retrieved successfully'
        ]);
    }

    /**
     * Mark stage as completed with method helper
     */
    public function markCompleted(Request $request, $purchaseRequestId, $stageId)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stage = $purchaseRequest->stages()->findOrFail($stageId);

        $stage->markAsCompleted($validated['notes'] ?? null, Auth::id());

        return response()->json([
            'success' => true,
            'data' => $stage->fresh(['updatedBy']),
            'message' => 'Stage marked as completed successfully'
        ]);
    }

    /**
     * Mark stage as in progress with method helper
     */
    public function markInProgress(Request $request, $purchaseRequestId, $stageId)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stage = $purchaseRequest->stages()->findOrFail($stageId);

        $stage->markAsInProgress($validated['notes'] ?? null, Auth::id());

        return response()->json([
            'success' => true,
            'data' => $stage->fresh(['updatedBy']),
            'message' => 'Stage marked as in progress successfully'
        ]);
    }

    /**
     * Mark stage as pending with method helper
     */
    public function markPending(Request $request, $purchaseRequestId, $stageId)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $purchaseRequest = PurchaseRequest::findOrFail($purchaseRequestId);
        $stage = $purchaseRequest->stages()->findOrFail($stageId);

        $stage->markAsPending($validated['notes'] ?? null, Auth::id());

        return response()->json([
            'success' => true,
            'data' => $stage->fresh(['updatedBy']),
            'message' => 'Stage marked as pending successfully'
        ]);
    }
}