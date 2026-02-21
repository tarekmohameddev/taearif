<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Customer\MoveCustomerStageRequest;
use App\Http\Requests\Api\Customer\ReorderCustomerStagesRequest;
use App\Http\Requests\Api\Customer\StoreCustomerStageRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerStageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Api\UserApiCustomerStage;

class UserApiCustomerStageController extends Controller
{

    public function moveStage(MoveCustomerStageRequest $request, $id)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        $stage = UserApiCustomerStage::where('user_id', $ownerId)->findOrFail($id);

        if (!$stage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stage not found'
            ], 404);
        }

        // Get the current order
        $currentOrder = $stage->order;

        // Find the adjacent stage depending on direction
        if ($validated['direction'] === 'up') { // up
            $adjacent = UserApiCustomerStage::where('user_id', $ownerId)
                ->where('order', '<', $currentOrder)
                ->orderBy('order', 'desc')
                ->first();
        } else { // down
            $adjacent = UserApiCustomerStage::where('user_id', $ownerId)
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

        // Swap orders
        $tempOrder = $stage->order;
        $stage->order = $adjacent->order;
        $adjacent->order = $tempOrder;

        DB::transaction(function () use ($stage, $adjacent) {
            $stage->save();
            $adjacent->save();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Stage moved ' . $validated['direction'] . ' successfully'
        ]);
    }

    public function reorderStages(ReorderCustomerStagesRequest $request)
    {
        $validated = $request->validated();
        $ownerId = auth()->user()->tenantOwnerId();

        DB::transaction(function () use ($validated, $ownerId) {
            foreach ($validated['order'] as $index => $stageId) {
                UserApiCustomerStage::where('user_id', $ownerId)
                    ->where('id', $stageId)
                    ->update(['order' => $index + 1]);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Stages reordered successfully'
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $stages = UserApiCustomerStage::where('user_id', $user->tenantOwnerId())
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stages
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerStageRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->user()->tenantOwnerId();

        $stage = UserApiCustomerStage::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Stage created successfully',
            'data' => $stage
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $stage = UserApiCustomerStage::where('id', $id)
            ->where('user_id', $user->tenantOwnerId())
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $stage
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerStageRequest $request, $id)
    {
        $ownerId = auth()->user()->tenantOwnerId();
        $stage = UserApiCustomerStage::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();
        $validated = $request->validated();

        $stage->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Stage updated successfully',
            'data' => $stage
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $stage = UserApiCustomerStage::where('id', $id)
            ->where('user_id', $user->tenantOwnerId())
            ->firstOrFail();

        $stage->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stage deleted successfully'
        ]);
    }
}
