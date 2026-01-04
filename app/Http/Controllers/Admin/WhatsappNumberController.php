<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappNumberController extends Controller
{
    public function edit($id)
    {
        $whatsappNumber = WhatsappUser::with(['user', 'employee'])->findOrFail($id);
        
        // Get employees for the tenant
        $employees = User::where('tenant_id', $whatsappNumber->user_id)
            ->where('account_type', 'employee')
            ->where('active', true)
            ->get();

        return view('admin.whatsapp_numbers.edit', compact('whatsappNumber', 'employees'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
        ]);

        try {
            $whatsappNumber = WhatsappUser::findOrFail($id);
            $whatsappNumber->update($validated);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم التحديث بنجاح']);
            }

            return back()->with('success', 'تم التحديث بنجاح');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'فشل التحديث'], 500);
            }

            return back()->with('error', 'فشل التحديث');
        }
    }

    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $whatsappNumber = WhatsappUser::findOrFail($id);
            
            // Toggle between 'active' and 'inactive'
            $oldStatus = $whatsappNumber->status;
            $newStatus = $oldStatus === 'active' ? 'inactive' : 'active';
            $whatsappNumber->update(['status' => $newStatus]);

            // Log audit
            \App\Models\WhatsappAddonAudit::create([
                'whatsapp_number_id' => $whatsappNumber->id,
                'entity_type' => 'number',
                'changed_by' => \Illuminate\Support\Facades\Auth::guard('admin')->id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => 'Admin toggled WhatsApp number status',
                'changed_at' => now(),
            ]);

            DB::commit();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تغيير الحالة بنجاح',
                    'new_status' => $newStatus
                ]);
            }

            return back()->with('success', 'تم تغيير الحالة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'فشل تغيير الحالة'], 500);
            }

            return back()->with('error', 'فشل تغيير الحالة');
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $whatsappNumber = WhatsappUser::findOrFail($id);
            
            // Check if there are associated add-ons
            $hasAddons = \App\Models\WhatsappAddon::where('whatsapp_number_id', $id)->exists();
            
            if ($hasAddons) {
                DB::rollBack();
                
                if (request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لا يمكن حذف الرقم لوجود إضافات مرتبطة به'
                    ], 422);
                }

                return back()->with('error', 'لا يمكن حذف الرقم لوجود إضافات مرتبطة به');
            }
            
            $whatsappNumber->delete();

            DB::commit();

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم الحذف بنجاح']);
            }

            return back()->with('success', 'تم الحذف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'فشل الحذف'], 500);
            }

            return back()->with('error', 'فشل الحذف');
        }
    }
}

