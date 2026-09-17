<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAddon;
use App\Models\WhatsappAddonAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsappAddonController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');

        $addonsQuery = WhatsappAddon::query()
            ->select(
                'whatsapp_addons.*',
                'whatsapp_users.number as whatsapp_number',
                'whatsapp_users.name as whatsapp_name',
                'whatsapp_users.status as whatsapp_status',
                'users.username as tenant_username',
                'users.email as tenant_email',
                'whatsapp_addon_plans.name as plan_name'
            )
            ->leftJoin('whatsapp_users', 'whatsapp_users.id', '=', 'whatsapp_addons.whatsapp_number_id')
            ->leftJoin('users', 'users.id', '=', 'whatsapp_users.user_id')
            ->leftJoin('whatsapp_addon_plans', 'whatsapp_addon_plans.id', '=', 'whatsapp_addons.plan_id')
            ->orderByDesc('whatsapp_addons.created_at');

        if ($status) {
            $addonsQuery->where('whatsapp_addons.status', $status);
        }

        $addons = $addonsQuery->paginate(15)->withQueryString();

        $statusOptions = [
            WhatsappAddon::STATUS_PENDING,
            WhatsappAddon::STATUS_APPROVED,
            WhatsappAddon::STATUS_REJECTED,
        ];

        return view('admin.whatsapp_addons.index', compact('addons', 'statusOptions', 'status'));
    }

    public function show($id)
    {
        $addon = WhatsappAddon::with(['whatsappUser.user', 'plan', 'audits.admin'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $addon
        ]);
    }

    public function approve($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $addon = WhatsappAddon::query()->lockForUpdate()->findOrFail($id);
                if ($addon->status !== WhatsappAddon::STATUS_PENDING) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'يمكن الموافقة على الطلبات المعلقة فقط',
                    ]);
                }

                $oldStatus = $addon->status;
                $addon->status = WhatsappAddon::STATUS_APPROVED;
                $addon->saveQuietly();

                WhatsappAddonAudit::create([
                    'tenant_id' => $addon->user_id ?? optional($addon->whatsappUser)->user_id,
                    'whatsapp_addon_id' => $addon->id,
                    'entity_type' => 'addon',
                    'action' => 'approve',
                    'quantity' => $addon->qty,
                    'changed_by' => Auth::guard('admin')->id(),
                    'old_status' => $oldStatus,
                    'new_status' => WhatsappAddon::STATUS_APPROVED,
                    'note' => 'Admin approved add-on request',
                    'correlation_id' => (string) Str::uuid(),
                    'ip_address' => request()->ip(),
                    'changed_at' => now(),
                ]);
            }, 3);

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تمت الموافقة بنجاح']);
            }

            return back()->with('success', 'تمت الموافقة بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'فشلت الموافقة'], 500);
            }

            return back()->with('error', 'فشلت الموافقة');
        }
    }

    public function reject($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $addon = WhatsappAddon::query()->lockForUpdate()->findOrFail($id);
                if ($addon->status !== WhatsappAddon::STATUS_PENDING) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'يمكن رفض الطلبات المعلقة فقط',
                    ]);
                }

                $oldStatus = $addon->status;
                $addon->status = WhatsappAddon::STATUS_REJECTED;
                $addon->saveQuietly();

                WhatsappAddonAudit::create([
                    'tenant_id' => $addon->user_id ?? optional($addon->whatsappUser)->user_id,
                    'whatsapp_addon_id' => $addon->id,
                    'entity_type' => 'addon',
                    'action' => 'reject',
                    'quantity' => $addon->qty,
                    'changed_by' => Auth::guard('admin')->id(),
                    'old_status' => $oldStatus,
                    'new_status' => WhatsappAddon::STATUS_REJECTED,
                    'note' => 'Admin rejected add-on request',
                    'correlation_id' => (string) Str::uuid(),
                    'ip_address' => request()->ip(),
                    'changed_at' => now(),
                ]);
            }, 3);

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم الرفض بنجاح']);
            }

            return back()->with('success', 'تم الرفض بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'فشل الرفض'], 500);
            }

            return back()->with('error', 'فشل الرفض');
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $addon = WhatsappAddon::query()->lockForUpdate()->findOrFail($id);
                if ($addon->status !== WhatsappAddon::STATUS_PENDING || $addon->audits()->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'لا يمكن حذف إضافة تمت معالجتها أو لديها سجل تدقيق',
                    ]);
                }

                $addon->delete();
            }, 3);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'تم الحذف بنجاح']);
            }

            return back()->with('success', 'تم الحذف بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'فشل الحذف'], 500);
            }

            return back()->with('error', 'فشل الحذف');
        }
    }
}
