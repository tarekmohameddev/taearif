<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\ApiCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;


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

        // Fetch customers with pagination
        $customers = ApiCustomer::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Format the customers output (customize fields as needed)
        $formattedCustomers = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'customer_type' => $customer->customer_type ?? 'unknown',
                'district' => $customer->district ?? 'N/A',
                'priority' => $customer->priority ?? 'normal',
                'stage_id' => $customer->stage_id ?? null,
                'note' => $customer->note ?? '',
                'city_id' => $customer->city_id ?? null,
                'created_by' => $customer->user_id,
                'created_at' => $customer->created_at->toISOString(),
                'updated_at' => $customer->updated_at->toISOString(),
            ];
        });

        // Total customers (summary)
        $totalCustomers = ApiCustomer::where('user_id', $user->id)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_customers' => $totalCustomers,
                ],
                'customers' => $formattedCustomers,
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ]
            ]
        ], 200);
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

        try {
            $request->validate([
                'name'          => 'required|string|max:255',
                'email'         => [
                    'nullable',
                    'email',
                    Rule::unique('api_customers', 'email')->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    }),
                ],
                'phone_number'  => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('api_customers', 'phone_number')->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    }),
                ],
                'city_id'       => 'nullable|exists:user_cities,id',
                'district_id'   => 'nullable|exists:user_districts,id',
                'note'          => 'nullable|string',
                'customer_type' => 'nullable|string|max:50',
                'stage_id'      => 'nullable|exists:users_api_customers_stages,id',
                'password'      => 'required|string|min:6',
                'priority'      => 'nullable|integer|in:1,2,3',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $customer = ApiCustomer::create([
            'user_id'       => $user->id,
            'name'          => $request->name,
            'email'         => $request->email,
            'city_id'       => $request->city_id,
            'district_id'   => $request->district_id,
            'note'          => $request->note,
            'customer_type' => $request->customer_type,
            'priority'      => $request->priority ?? 1, // Default to medium if not provided
            'stage_id'      => $request->stage_id ?? null,
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
            'email'         => [
                'sometimes',
                'nullable',
                'email',
                Rule::unique('api_customers', 'email')
                    ->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    })
                    ->ignore($customer->id),
            ],
            'phone_number'  => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('api_customers', 'phone_number')
                    ->where(function ($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    })
                    ->ignore($customer->id),
            ],
            'city_id'       => 'nullable|exists:user_cities,id',
            'district_id'   => 'nullable|exists:user_districts,id',
            'note'          => 'nullable|string',
            'customer_type' => 'nullable|string',
            'priority'      => 'sometimes|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'stage_id'      => 'nullable|exists:users_api_customers_stages,id',
            'password'      => 'nullable|string|min:6',
        ]);

        $customer->update([
            'name'          => $request->name ?? $customer->name,
            'email'         => $request->email ?? $customer->email,
            'note'          => $request->note ?? $customer->note,
            'customer_type' => $request->customer_type ?? $customer->customer_type,
            'priority'      => $request->priority ?? $customer->priority,
            'stage_id'      => $request->stage_id ?? $customer->stage_id,
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
