<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAddonPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeAddonPlanController extends Controller
{
    /**
     * Get all plans
     */
    public function index(Request $request): JsonResponse
    {
        $query = EmployeeAddonPlan::query();

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
            'duration_unit' => 'required|in:day,month,year',
            'is_active' => 'boolean',
        ]);

        $plan = EmployeeAddonPlan::create($validated);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'تم إنشاء الباقة بنجاح'
        ], 201);
    }

    /**
     * Update plan
     */
    public function update(Request $request, $id): JsonResponse
    {
        $plan = EmployeeAddonPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric|min:0',
            'duration' => 'integer|min:1',
            'duration_unit' => 'in:day,month,year',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'تم تحديث الباقة بنجاح'
        ]);
    }

    /**
     * Delete plan
     */
    public function destroy($id): JsonResponse
    {
        $plan = EmployeeAddonPlan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الباقة بنجاح'
        ]);
    }

    /**
     * Toggle status
     */
    public function toggleStatus($id): JsonResponse
    {
        $plan = EmployeeAddonPlan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        return response()->json([
            'success' => true,
            'data' => $plan,
            'message' => 'تم تغيير حالة الباقة بنجاح'
        ]);
    }
}

