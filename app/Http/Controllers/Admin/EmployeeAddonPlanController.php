<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAddonPlan;
use Illuminate\Http\Request;

class EmployeeAddonPlanController extends Controller
{
    /**
     * Display a listing of Employee addon plans
     */
    public function index(Request $request)
    {
        $query = EmployeeAddonPlan::query();

        // Filter by status if provided
        if ($request->filled('status')) {
            $isActive = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $isActive);
        }

        $plans = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.employee_addon_plans.index', compact('plans'));
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'duration_unit' => 'required|in:month,year',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'اسم الخطة مطلوب',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'duration.required' => 'المدة مطلوبة',
            'duration_unit.required' => 'وحدة المدة مطلوبة',
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        EmployeeAddonPlan::create($validated);

        return back()->with('success', 'تم إنشاء خطة إضافة الموظفين بنجاح');
    }

    /**
     * Update the specified plan
     */
    public function update(Request $request, $id)
    {
        $plan = EmployeeAddonPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'duration_unit' => 'required|in:month,year',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'اسم الخطة مطلوب',
            'price.required' => 'السعر مطلوب',
            'price.numeric' => 'السعر يجب أن يكون رقماً',
            'duration.required' => 'المدة مطلوبة',
            'duration_unit.required' => 'وحدة المدة مطلوبة',
        ]);

        $validated['is_active'] = $request->has('is_active') ? 1 : 0;

        $plan->update($validated);

        return back()->with('success', 'تم تحديث خطة إضافة الموظفين بنجاح');
    }

    /**
     * Remove the specified plan
     */
    public function destroy($id)
    {
        $plan = EmployeeAddonPlan::findOrFail($id);
        $plan->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الخطة بنجاح']);
        }

        return back()->with('success', 'تم حذف الخطة بنجاح');
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus($id)
    {
        $plan = EmployeeAddonPlan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        if (request()->ajax()) {
            return response()->json([
                'success' => true, 
                'message' => 'تم تغيير حالة الخطة بنجاح',
                'is_active' => $plan->is_active
            ]);
        }

        return back()->with('success', 'تم تغيير حالة الخطة بنجاح');
    }
}

