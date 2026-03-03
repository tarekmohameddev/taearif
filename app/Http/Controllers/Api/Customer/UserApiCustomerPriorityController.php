<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\MoveCustomerPriorityRequest;
use App\Http\Requests\Api\Customer\ReorderCustomerPrioritiesRequest;
use App\Http\Requests\Api\Customer\StoreCustomerPriorityRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerPriorityRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Api\UserApiCustomerPriority;

class UserApiCustomerPriorityController extends Controller
{
    public function movePriority(MoveCustomerPriorityRequest $request, $id)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        $row = UserApiCustomerPriority::where('user_id', $ownerId)->findOrFail($id);
        $currentOrder = $row->order;

        $adjacent = $validated['direction']==='up'
            ? UserApiCustomerPriority::where('user_id', $ownerId)->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first()
            : UserApiCustomerPriority::where('user_id', $ownerId)->where('order', '>', $currentOrder)->orderBy('order', 'asc')->first();

        if (!$adjacent) {
            return response()->json(['status'=>'error','message'=>'Cannot move further '.$validated['direction']], 400);
        }

        DB::transaction(function () use ($row, $adjacent) {
            $tmp = $row->order;
            $row->order = $adjacent->order;
            $adjacent->order = $tmp;
            $row->save();
            $adjacent->save();
        });

        return response()->json(['status'=>'success','message'=>'Priority moved '.$validated['direction'].' successfully']);
    }

    public function reorderPriorities(ReorderCustomerPrioritiesRequest $request)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        DB::transaction(function () use ($validated, $ownerId) {
            foreach ($validated['order'] as $idx => $id) {
                UserApiCustomerPriority::where('user_id', $ownerId)->where('id', $id)->update(['order' => $idx + 1]);
            }
        });

        return response()->json(['status'=>'success','message'=>'Priorities reordered successfully']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $rows = UserApiCustomerPriority::where('user_id', $user->tenantOwnerId())->orderBy('order')->get();

        return response()->json(['status'=>'success','data'=>$rows]);
    }

    public function store(StoreCustomerPriorityRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        $validated['user_id'] = $user->tenantOwnerId();

        $row = UserApiCustomerPriority::create($validated);

        return response()->json(['status'=>'success','message'=>'Priority created successfully','data'=>$row], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $row = UserApiCustomerPriority::where('user_id', $user->tenantOwnerId())->findOrFail($id);

        return response()->json(['status'=>'success','data'=>$row]);
    }

    public function update(UpdateCustomerPriorityRequest $request, $id)
    {
        $ownerId = auth()->user()->tenantOwnerId();
        $row = UserApiCustomerPriority::where('user_id', $ownerId)->findOrFail($id);
        $validated = $request->validated();

        $row->update($validated);

        return response()->json(['status'=>'success','message'=>'Priority updated successfully','data'=>$row]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $row = UserApiCustomerPriority::where('user_id', $user->tenantOwnerId())->findOrFail($id);
        $row->delete();

        return response()->json(['status'=>'success','message'=>'Priority deleted successfully']);
    }
}
