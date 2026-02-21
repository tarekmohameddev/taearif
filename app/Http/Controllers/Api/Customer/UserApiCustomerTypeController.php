<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\MoveCustomerTypeRequest;
use App\Http\Requests\Api\Customer\ReorderCustomerTypesRequest;
use App\Http\Requests\Api\Customer\StoreCustomerTypeRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Api\UserApiCustomerType;

class UserApiCustomerTypeController extends Controller
{
    public function moveTypes(MoveCustomerTypeRequest $request, $id)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        $row = UserApiCustomerType::where('user_id', $ownerId)->findOrFail($id);
        $currentOrder = $row->order;

        $adjacent = $validated['direction'] === 'up'
            ? UserApiCustomerType::where('user_id', $ownerId)->where('order', '<', $currentOrder)->orderBy('order', 'desc')->first()
            : UserApiCustomerType::where('user_id', $ownerId)->where('order', '>', $currentOrder)->orderBy('order', 'asc')->first();

        if (!$adjacent) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot move further ' . $validated['direction']
            ], 400);
        }

        DB::transaction(function () use ($row, $adjacent) {
            $tmp = $row->order;
            $row->order = $adjacent->order;
            $adjacent->order = $tmp;
            $row->save();
            $adjacent->save();
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Type moved ' . $validated['direction'] . ' successfully'
        ]);
    }

    public function reorderTypes(ReorderCustomerTypesRequest $request)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        DB::transaction(function () use ($validated, $ownerId) {
            foreach ($validated['order'] as $idx => $id) {
                UserApiCustomerType::where('user_id', $ownerId)
                    ->where('id', $id)
                    ->update(['order' => $idx + 1]);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Types reordered successfully'
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $rows = UserApiCustomerType::where('user_id', $user->tenantOwnerId())
            ->orderBy('order')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $rows
        ]);
    }

    public function store(StoreCustomerTypeRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->user()->tenantOwnerId();

        $row = UserApiCustomerType::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Type created successfully',
            'data'    => $row
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $row = UserApiCustomerType::where('user_id', $user->tenantOwnerId())->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $row
        ]);
    }

    public function update(UpdateCustomerTypeRequest $request, $id)
    {
        $user = auth()->user();
        $row = UserApiCustomerType::where('user_id', $user->tenantOwnerId())->findOrFail($id);
        $validated = $request->validated();

        $row->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Type updated successfully',
            'data'    => $row
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $row = UserApiCustomerType::where('user_id', $user->tenantOwnerId())->findOrFail($id);
        $row->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Type deleted successfully'
        ]);
    }
}
