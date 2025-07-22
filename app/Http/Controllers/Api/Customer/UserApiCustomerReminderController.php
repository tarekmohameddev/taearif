<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiCustomer;
use Illuminate\Validation\Rule;
use App\Models\Api\UserApiCustomerReminder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserApiCustomerReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $reminders = UserApiCustomerReminder::with('customer')
            ->where('user_id', $user->id)
            ->orderBy('datetime', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reminders->map(fn($reminder) => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
            ])
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

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized user.'], 401);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'title'       => 'required|string|max:255',
            'priority'    => 'required|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'datetime'    => 'required|date',
        ]);

        // Check if customer exists & belongs to this user
        $customer = ApiCustomer::where('id', $validated['customer_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $validated['user_id'] = $user->id;

        $reminder = UserApiCustomerReminder::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder created successfully',
            'data' => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $customer->only(['id', 'name']),
            ]
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

        $reminder = UserApiCustomerReminder::with('customer')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
            ]
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

        $reminder = UserApiCustomerReminder::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'title'    => 'sometimes|string|max:255',
            'priority'    => 'required|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'datetime' => 'sometimes|date',
        ]);

        $reminder->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder updated successfully',
            'data' => $reminder
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

        $reminder = UserApiCustomerReminder::where('user_id', $user->id)->findOrFail($id);

        $reminder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder deleted successfully'
        ]);
    }

}
