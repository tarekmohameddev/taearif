<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\ApiCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $total_of_customers = ApiCustomer::where('user_id', $user->id)->count();

        $summary = [
            'total_of_customers' => $total_of_customers,
        ];

        $customers = ApiCustomer::where('user_id', $user->id)->latest()->paginate(10);
        // Log::info("message");
        return response()->json([
            'status' => 'success',
            'summary' => $summary,
            'data' => $customers
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
        try {
            $request->validate([
                'name'          => 'required|string|max:255',
                'email'         => 'nullable|email|unique:api_customers,email',
                'phone_number'  => 'required|string|max:20|unique:api_customers,phone_number',
                'city_id'       => 'nullable|exists:user_cities,id',
                'district_id'   => 'nullable|exists:user_districts,id',
                'note'          => 'nullable|string',
                'customer_type' => 'nullable|string|max:50',
                'password'      => 'required|string|min:6',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $user = $request->user();

        $customer = ApiCustomer::create([
            'user_id'       => $user->id,
            'name'          => $request->name,
            'email'         => $request->email,
            'city_id'       => $request->city_id,
            'district_id'   => $request->district_id,
            'note'          => $request->note,
            'customer_type' => $request->customer_type,
            'phone_number'  => $request->phone_number,
            'password'      => bcrypt($request->password),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer created successfully',
            'data'    => $customer,
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

        $customer = ApiCustomer::where('user_id', $user->id)->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $customer
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
        //
        $user = $request->user();
        $customer = ApiCustomer::where('user_id', $user->id)->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $request->validate([
            'name'          => 'sometimes|string|max:255',
            'email'         => 'sometimes|email|unique:api_customers,email,' . $customer->id,
            'city_id'       => 'nullable|exists:user_cities,id',
            'district_id'   => 'nullable|exists:user_districts,id',
            'note'          => 'nullable|string',
            'customer_type' => 'nullable|string',
            'phone_number' => 'sometimes|string|max:20|unique:api_customers,phone_number,' . $customer->id,
            'password'      => 'nullable|string|min:6',
        ]);

        $customer->update([
            'name'          => $request->name ?? $customer->name,
            'email'         => $request->email ?? $customer->email,
            'note'          => $request->note ?? $customer->note,
            'customer_type' => $request->customer_type ?? $customer->customer_type,
            'city_id'       => $request->city_id ?? $customer->city_id,
            'district_id'   => $request->district_id ?? $customer->district_id,
            'phone_number'  => $request->phone_number ?? $customer->phone_number,
            'password'      => $request->filled('password') ? bcrypt($request->password) : $customer->password,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer updated successfully',
            'data' => $customer->fresh()
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
        //
        $user = $request->user();
        $customer = ApiCustomer::where('user_id', $user->id)->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer deleted successfully'
        ]);

    }

    /**
     * Search customers by name, email or phone
     */
    public function search(Request $request)
    {
        $user = $request->user();
        $query = $request->get('q');
        $customers = ApiCustomer::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%$query%")
                    ->orWhere('email', 'LIKE', "%$query%")
                    ->orWhere('phone_number', 'LIKE', "%$query%");
            })
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }
}
