<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\UserApiCustomerAppointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\ApiCustomer;

class UserApiCustomerAppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $appointments = UserApiCustomerAppointment::with('customer')
            ->where('user_id', $user->id)
            ->orderBy('datetime', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $appointments->map(function ($appt) {
                return [
                    'id'           => $appt->id,
                    'title'        => $appt->title,
                    'type'         => $appt->type,
                    'priority'     => $appt->priority,
                    'priority_label' => $appt->priority_label,
                    'note'         => $appt->note,
                    'datetime'     => $appt->datetime,
                    'duration'     => $appt->duration,
                    'customer'     => $appt->customer ? $appt->customer->only(['id', 'name']) : null,
                ];
            })
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
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized user.'
            ], 401);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'title'       => 'required|string|max:255',
            'type'        => 'required|string|max:100',
            'priority'    => 'required|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'note'        => 'nullable|string',
            'datetime'    => 'required|date',
            'duration'    => 'required|integer|min:1',
        ]);

        // Check if the customer belongs to the user
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

        $appointment = UserApiCustomerAppointment::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment created successfully',
            'data' => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'type' => $appointment->type,
                'priority' => $appointment->priority,
                'priority_label' => $appointment->priority_label,
                'note' => $appointment->note,
                'datetime' => $appointment->datetime,
                'duration' => $appointment->duration,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name
                ]
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

        $appointment = UserApiCustomerAppointment::with('customer')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'           => $appointment->id,
                'title'        => $appointment->title,
                'type'         => $appointment->type,
                'priority'     => $appointment->priority,
                'priority_label' => $appointment->priority_label,
                'note'         => $appointment->note,
                'datetime'     => $appointment->datetime,
                'duration'     => $appointment->duration,
                'customer'     => $appointment->customer ? $appointment->customer->only(['id', 'name']) : null,
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

        $appointment = UserApiCustomerAppointment::where('user_id', $user->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'type'        => 'sometimes|string|max:100',
            'priority'    => 'required|integer|in:1,2,3',
            'note'        => 'nullable|string',
            'datetime'    => 'sometimes|date',
            'duration'    => 'sometimes|integer|min:1',
        ]);

        $appointment->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment updated successfully',
            'data' => $appointment
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

        $appointment = UserApiCustomerAppointment::where('user_id', $user->id)
            ->findOrFail($id);

        $appointment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment deleted successfully'
        ]);
    }
}
