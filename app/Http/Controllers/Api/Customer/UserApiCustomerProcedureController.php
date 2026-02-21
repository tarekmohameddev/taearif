<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\MoveCustomerProcedureRequest;
use App\Http\Requests\Api\Customer\ReorderCustomerProceduresRequest;
use App\Http\Requests\Api\Customer\StoreCustomerProcedureRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerProcedureRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Api\UserApiCustomerProcedure;

class UserApiCustomerProcedureController extends Controller
{
    public function moveProcedure(MoveCustomerProcedureRequest $request, $id)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        $proc = UserApiCustomerProcedure::where('user_id', $ownerId)->findOrFail($id);

        $currentOrder = $proc->order;

        if ($validated['direction'] === 'up') {
            $adjacent = UserApiCustomerProcedure::where('user_id', $ownerId)
                ->where('order', '<', $currentOrder)
                ->orderBy('order', 'desc')
                ->first();
        } else {
            $adjacent = UserApiCustomerProcedure::where('user_id', $ownerId)
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

    public function reorderProcedures(ReorderCustomerProceduresRequest $request)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        DB::transaction(function () use ($validated, $ownerId) {
            foreach ($validated['order'] as $index => $procId) {
                UserApiCustomerProcedure::where('user_id', $ownerId)
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

        $procs = UserApiCustomerProcedure::where('user_id', $user->tenantOwnerId())
            ->orderBy('order')
            ->get();

        return response()->json(['status'=>'success','data'=>$procs]);
    }

    public function store(StoreCustomerProcedureRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->user()->tenantOwnerId();

        $proc = UserApiCustomerProcedure::create($validated);

        return response()->json([
            'status'=>'success','message'=>'Procedure type created successfully','data'=>$proc
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $proc = UserApiCustomerProcedure::where('user_id', $user->tenantOwnerId())->findOrFail($id);

        return response()->json(['status'=>'success','data'=>$proc]);
    }

    public function update(UpdateCustomerProcedureRequest $request, $id)
    {
        $ownerId = auth()->user()->tenantOwnerId();
        $proc = UserApiCustomerProcedure::where('user_id', $ownerId)->findOrFail($id);
        $validated = $request->validated();

        $proc->update($validated);

        return response()->json(['status'=>'success','message'=>'Procedure type updated successfully','data'=>$proc]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $proc = UserApiCustomerProcedure::where('user_id', $user->tenantOwnerId())->findOrFail($id);
        $proc->delete();

        return response()->json(['status'=>'success','message'=>'Procedure type deleted successfully']);
    }
}
