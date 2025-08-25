<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Api\UserApiCustomerPriority;

class UserApiCustomerPriorityController extends Controller
{
    public function movePriority(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        $row = UserApiCustomerPriority::where('user_id',$user->id)->findOrFail($id);
        $currentOrder = $row->order;

        $adjacent = $validated['direction']==='up'
            ? UserApiCustomerPriority::where('user_id',$user->id)->where('order','<',$currentOrder)->orderBy('order','desc')->first()
            : UserApiCustomerPriority::where('user_id',$user->id)->where('order','>',$currentOrder)->orderBy('order','asc')->first();

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

    public function reorderPriorities(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:users_api_customers_priorities,id',
        ]);

        DB::transaction(function () use ($validated, $user) {
            foreach ($validated['order'] as $idx => $id) {
                UserApiCustomerPriority::where('user_id',$user->id)->where('id',$id)->update(['order'=>$idx+1]);
            }
        });

        return response()->json(['status'=>'success','message'=>'Priorities reordered successfully']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $rows = UserApiCustomerPriority::where('user_id',$user->id)->orderBy('order')->get();

        return response()->json(['status'=>'success','data'=>$rows]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'value' => [
                'required','integer',
                Rule::unique('users_api_customers_priorities','value')->where(fn($q)=>$q->where('user_id',$user->id))
            ],
            'color' => 'nullable|string|max:50',
            'icon'  => 'nullable|string|max:50',
            'order' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = $user->id;

        $row = UserApiCustomerPriority::create($validated);

        return response()->json(['status'=>'success','message'=>'Priority created successfully','data'=>$row], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $row = UserApiCustomerPriority::where('user_id',$user->id)->findOrFail($id);

        return response()->json(['status'=>'success','data'=>$row]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $row = UserApiCustomerPriority::where('user_id',$user->id)->findOrFail($id);

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'value' => [
                'sometimes','integer','in:1,2,3',
                Rule::unique('users_api_customers_priorities','value')
                    ->where(fn($q)=>$q->where('user_id',$user->id))
                    ->ignore($row->id),
            ],
            'color' => 'nullable|string|max:50',
            'icon'  => 'nullable|string|max:50',
            'order' => 'sometimes|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $row->update($validated);

        return response()->json(['status'=>'success','message'=>'Priority updated successfully','data'=>$row]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $row = UserApiCustomerPriority::where('user_id',$user->id)->findOrFail($id);
        $row->delete();

        return response()->json(['status'=>'success','message'=>'Priority deleted successfully']);
    }
}
