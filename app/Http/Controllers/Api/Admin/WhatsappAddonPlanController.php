<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAddonPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WhatsappAddonPlanController extends Controller
{
    /**
     * Get all plans
     */
    public function index(Request $request): JsonResponse
    {
        $query = WhatsappAddonPlan::query();

        if ($request->has('active_only')) {
            $query->active();
        }

        $plans = $query->orderBy('price', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    /**
     * Store new plan
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'duration_unit' => 'required|in:month,year',
            'is_active' => 'boolean',
        ]);

        $plan = WhatsappAddonPlan::create($validated);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'Plan created successfully'
        ], 201);
    }

    /**
     * Update plan
     */
    public function update(Request $request, $id): JsonResponse
    {
        $plan = WhatsappAddonPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
            'duration' => 'integer|min:1',
            'duration_unit' => 'in:month,year',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'Plan updated successfully'
        ]);
    }

    /**
     * Delete plan
     */
    public function destroy($id): JsonResponse
    {
        $plan = WhatsappAddonPlan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully'
        ]);
    }

    /**
     * Toggle status
     */
    public function toggleStatus($id): JsonResponse
    {
        $plan = WhatsappAddonPlan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'Plan status toggled successfully'
        ]);
    }
}
