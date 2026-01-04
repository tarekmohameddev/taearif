<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Api\UserApiCustomerProcedure;

class UserApiCustomerProcedureController extends Controller
{
    public function moveProcedure(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        $proc = UserApiCustomerProcedure::where('user_id', $request->user()->tenantOwnerId())->findOrFail($id);

        $currentOrder = $proc->order;

        if ($validated['direction'] === 'up') {
            $adjacent = UserApiCustomerProcedure::where('user_id', $request->user()->tenantOwnerId())
                ->where('order', '<', $currentOrder)
                ->orderBy('order', 'desc')
                ->first();
        } else {
            $adjacent = UserApiCustomerProcedure::where('user_id', $request->user()->tenantOwnerId())
                ->where('order', '>', $currentOrder)
                ->orderBy('order', 'asc')
                ->first();
        }

        if (!$adjacent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot move further ' . $validated['direction']
            ], 400);
        }

        DB::transaction(function () use ($proc, $adjacent) {
            $tmp = $proc->order;
            $proc->order = $adjacent->order;
            $adjacent->order = $tmp;
            $proc->save();
            $adjacent->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Procedure moved ' . $validated['direction'] . ' successfully'
        ]);
    }

    public function reorderProcedures(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:users_api_customers_procedures,id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            foreach ($validated['order'] as $index => $procId) {
                UserApiCustomerProcedure::where('user_id', $request->user()->tenantOwnerId())
                    ->where('id', $procId)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Procedures reordered successfully'
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $procs = UserApiCustomerProcedure::where('user_id', $request->user()->tenantOwnerId())
            ->orderBy('order')
            ->get();

        return response()->json(['status'=>'success','data'=>$procs]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'procedure_name' => [
                'required','string','max:255',
                Rule::unique('users_api_customers_procedures','procedure_name')->where(fn($q)=>$q->where('user_id',$user->id))
            ],
            'color'       => 'nullable|string|max:50',
            'icon'        => 'nullable|string|max:50',
            'order'       => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $validated['user_id'] = $request->user()->tenantOwnerId();

        $proc = UserApiCustomerProcedure::create($validated);

        return response()->json([
            'status'=>'success','message'=>'Procedure type created successfully','data'=>$proc
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $proc = UserApiCustomerProcedure::where('user_id',$request->user()->tenantOwnerId())->findOrFail($id);

        return response()->json(['status'=>'success','data'=>$proc]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $proc = UserApiCustomerProcedure::where('user_id',$request->user()->tenantOwnerId())->findOrFail($id);

        $validated = $request->validate([
            'procedure_name' => [
                'sometimes','string','max:255',
                Rule::unique('users_api_customers_procedures','procedure_name')
                    ->where(fn($q)=>$q->where('user_id',$user->id))
                    ->ignore($proc->id),
            ],
            'color'       => 'nullable|string|max:50',
            'icon'        => 'nullable|string|max:50',
            'order'       => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $proc->update($validated);

        return response()->json(['status'=>'success','message'=>'Procedure type updated successfully','data'=>$proc]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $proc = UserApiCustomerProcedure::where('user_id',$request->user()->tenantOwnerId())->findOrFail($id);
        $proc->delete();

        return response()->json(['status'=>'success','message'=>'Procedure type deleted successfully']);
    }
}
