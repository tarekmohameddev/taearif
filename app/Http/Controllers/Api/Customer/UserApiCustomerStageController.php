<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Api\UserApiCustomerStage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserApiCustomerStageController extends Controller
{

    public function moveStage(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        $stage = UserApiCustomerStage::where('user_id', $user->id)->findOrFail($id);

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
            $adjacent = UserApiCustomerStage::where('user_id', $user->id)
                ->where('order', '<', $currentOrder)
                ->orderBy('order', 'desc')
                ->first();
        } else { // down
            $adjacent = UserApiCustomerStage::where('user_id', $user->id)
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

    public function reorderStages(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:users_api_customers_stages,id',
        ]);

        DB::transaction(function () use ($validated, $user) {
            foreach ($validated['order'] as $index => $stageId) {
                UserApiCustomerStage::where('user_id', $user->id)
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

        $stages = UserApiCustomerStage::where('user_id', $user->id)
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
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'stage_name' => 'required|string|max:255',
            'color'      => 'nullable|string',
            'icon'       => 'nullable|string',
            'order'      => 'required|integer',
            'description'=> 'nullable|string',
            'is_active'  => 'boolean'
        ]);

        $validated['user_id'] = $user->id;

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
            ->where('user_id', $user->id)
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
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $stage = UserApiCustomerStage::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'stage_name' => 'sometimes|string|max:255',
            'color'      => 'nullable|string',
            'icon'       => 'nullable|string',
            'order'      => 'sometimes|integer',
            'description'=> 'nullable|string',
            'is_active'  => 'boolean'
        ]);

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
            ->where('user_id', $user->id)
            ->firstOrFail();

        $stage->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stage deleted successfully'
        ]);
    }
}
