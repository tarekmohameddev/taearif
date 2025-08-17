<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\ApiCustomer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ApiCustomerPropertyInterested;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

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
            $interestedCategories = ApiCustomerPropertyInterested::where('customer_id', $customer->id)
            ->join('api_user_categories', 'api_user_categories.id', '=', 'api_customer_property_interested.category_id')
            ->select('api_user_categories.id', 'api_user_categories.name')
            ->distinct()
            ->get();

        $interestedProperties = ApiCustomerPropertyInterested::where('customer_id', $customer->id)
            ->join('user_properties as up', 'up.id', '=', 'api_customer_property_interested.property_id')
            ->leftJoin('user_property_contents as upc', 'upc.property_id', '=', 'up.id')
            ->select('up.id', DB::raw('MAX(upc.title) as name'))
            ->groupBy('up.id')
            ->get();

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
                'interested_categories' => $interestedCategories,
                'interested_properties' => $interestedProperties,

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
                'interested_category_ids' => 'nullable|array',
                'interested_category_ids.*' => 'exists:api_user_categories,id'
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

        if ($request->filled('interested_category_ids')) {
            $categories = $request->interested_category_ids;
            foreach ($categories as $catId) {
                ApiCustomerPropertyInterested::firstOrCreate([
                    'user_id'     => $user->id,
                    'customer_id' => $customer->id,
                    'category_id' => $catId
                ]);
            }
        }
        $interestedCategories = ApiCustomerPropertyInterested::where('customer_id', $customer->id)
        ->join('api_user_categories', 'api_user_categories.id', '=', 'api_customer_property_interested.category_id')
        ->select('api_user_categories.id', 'api_user_categories.name')
        ->distinct()
        ->get();
        $customer->interested_categories = $interestedCategories;

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
        $user = $request->user();

        try {
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
                        ->where(fn($query) => $query->where('user_id', $user->id))
                        ->ignore($customer->id),
                ],
                'phone_number'  => [
                    'sometimes',
                    'string',
                    'max:20',
                    Rule::unique('api_customers', 'phone_number')
                        ->where(fn($query) => $query->where('user_id', $user->id))
                        ->ignore($customer->id),
                ],
                'city_id'       => 'nullable|exists:user_cities,id',
                'district_id'   => 'nullable|exists:user_districts,id',
                'note'          => 'nullable|string',
                'customer_type' => 'nullable|string|max:50',
                'priority'      => 'sometimes|integer|in:1,2,3',
                'stage_id'      => 'nullable|exists:users_api_customers_stages,id',
                'password'      => 'nullable|string|min:6',
                'interested_category_ids' => 'nullable|array',
                'interested_category_ids.*' => 'exists:api_user_categories,id',
            ]);

            $data = [];
            foreach ([
                'name', 'email', 'note', 'customer_type', 'priority',
                'stage_id', 'city_id', 'district_id', 'phone_number'
            ] as $field) {
                if (array_key_exists($field, $request->all())) {
                    $data[$field] = $request->$field;
                }
            }

            if ($request->filled('password')) {
                $data['password'] = bcrypt($request->password);
            }

            $customer->update($data);

            if ($request->filled('interested_category_ids')) {
                ApiCustomerPropertyInterested::where('customer_id', $customer->id)->delete();

                foreach ($request->interested_category_ids as $catId) {
                    ApiCustomerPropertyInterested::firstOrCreate([
                        'user_id'     => $user->id,
                        'customer_id' => $customer->id,
                        'category_id' => $catId,
                    ]);
                }
            }

            $interestedCategories = ApiCustomerPropertyInterested::where('customer_id', $customer->id)
                ->join('api_user_categories', 'api_user_categories.id', '=', 'api_customer_property_interested.category_id')
                ->select('api_user_categories.id', 'api_user_categories.name')
                ->distinct()
                ->get();

            $customer->interested_categories = $interestedCategories;

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer,
            ]);
        }

        // Catch validation errors
        catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        // Catch DB errors
        catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database error',
                'sql_error' => $e->getMessage(),
            ], 500);
        }

        // Catch all other exceptions
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong',
                'exception' => $e->getMessage(),
            ], 500);
        }
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
        $user  = $request->user();
        $qText = $request->get('q');

        // ---- parse arrays or CSV for category/property filters ----
        $toIntArray = function ($v): array {
            if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
            if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
            return [];
        };
        $catIds  = $toIntArray($request->input('interested_category_ids'));
        $propIds = $toIntArray($request->input('interested_property_ids'));

        // ---- validate simple filters (keep it light; add rules if you want) ----
        $request->validate([
            'city_id'       => 'nullable|integer',
            'district_id'   => 'nullable|integer',
            'customer_type' => 'nullable|string|max:50',
            'page'          => 'nullable|integer|min:1',
            'per_page'      => 'nullable|integer|min:1|max:100',
            'sort_by'       => 'nullable|in:name,created_at,priority',
            'sort_dir'      => 'nullable|in:asc,desc',
        ]);

        $perPage = (int) ($request->input('per_page') ?: 10);
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        // ---- base query + eager loads (to build district payload nicely) ----
        $query = \App\Models\ApiCustomer::where('user_id', $user->id)
            ->with(['district.city', 'city']);

        // text search
        if (!empty($qText)) {
            $query->where(function ($sub) use ($qText) {
                $sub->where('name', 'like', "%{$qText}%")
                    ->orWhere('email', 'like', "%{$qText}%")
                    ->orWhere('phone_number', 'like', "%{$qText}%");
            });
        }

        // simple filters
        if ($request->filled('city_id')) {
            $query->where('city_id', (int)$request->input('city_id'));
        }
        if ($request->filled('district_id')) {
            $query->where('district_id', (int)$request->input('district_id'));
        }
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->input('customer_type'));
        }

        // interested_category_ids filter (exists on pivot)
        if (!empty($catIds)) {
            $query->whereExists(function ($sub) use ($catIds) {
                $sub->select(DB::raw(1))
                    ->from('api_customer_property_interested as ac1')
                    ->whereColumn('ac1.customer_id', 'api_customers.id')
                    ->whereIn('ac1.category_id', $catIds);
            });
        }

        // interested_property_ids filter (exists on pivot)
        if (!empty($propIds)) {
            $query->whereExists(function ($sub) use ($propIds) {
                $sub->select(DB::raw(1))
                    ->from('api_customer_property_interested as ac2')
                    ->whereColumn('ac2.customer_id', 'api_customers.id')
                    ->whereIn('ac2.property_id', $propIds);
            });
        }

        // sort + paginate
        $query->orderBy($sortBy, $sortDir);
        $paginator = $query->paginate($perPage);

        // ---- batch load interested categories/properties for current page ----
        $customerIds = $paginator->getCollection()->pluck('id')->all();

        $catRows = DB::table('api_customer_property_interested as ac')
            ->whereIn('ac.customer_id', $customerIds)
            ->whereNotNull('ac.category_id')
            ->join('api_user_categories as c', 'c.id', '=', 'ac.category_id')
            ->select('ac.customer_id', 'c.id', 'c.name')
            ->distinct()
            ->get()
            ->groupBy('customer_id');


        $propRows = DB::table('api_customer_property_interested as ac')
            ->whereIn('ac.customer_id', $customerIds)
            ->whereNotNull('ac.property_id')
            ->join('user_properties as up', 'up.id', '=', 'ac.property_id')
            ->leftJoin('user_property_contents as upc', 'upc.property_id', '=', 'up.id')
            ->select('ac.customer_id', 'up.id as id', DB::raw('MAX(upc.title) as name'))
            ->groupBy('ac.customer_id', 'up.id')
            ->get()
            ->groupBy('customer_id');

        $customers = $paginator->getCollection()->map(function ($customer) use ($catRows, $propRows) {
            $district     = $customer->district;
            $districtCity = $district?->city;
            $rootCity     = $customer->city;

            $districtPayload = $district ? [
                'id'              => $district->id,
                'name_ar'         => $district->name_ar ?? null,
                'name_en'         => $district->name_en ?? null,
                'city_id'         => $district->city_id ?? $rootCity?->id,
                'city_name_ar'    => $districtCity?->name_ar ?? $rootCity?->name_ar ?? null,
                'city_name_en'    => $districtCity?->name_en ?? $rootCity?->name_en ?? null,
                'country_name_ar' => $district->country_name_ar ?? null,
                'country_name_en' => $district->country_name_en ?? null,
                'created_at'      => optional($district->created_at)->toISOString(),
                'updated_at'      => optional($district->updated_at)->toISOString(),
            ] : 'N/A';

            $cats = collect($catRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            $props = collect($propRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            return [
                'id'                    => $customer->id,
                'name'                  => $customer->name,
                'email'                 => $customer->email,
                'phone_number'          => $customer->phone_number,
                'customer_type'         => $customer->customer_type ?? 'unknown',
                'district'              => $districtPayload,
                'priority'              => $customer->priority ?? 1,
                'stage_id'              => $customer->stage_id ?? null,
                'note'                  => $customer->note ?? '',
                'city_id'               => $customer->city_id ?? null,
                'created_by'            => $customer->user_id,
                'created_at'            => optional($customer->created_at)->toISOString(),
                'updated_at'            => optional($customer->updated_at)->toISOString(),
                'interested_categories' => $cats,
                'interested_properties' => $props,
            ];
        })->values();

        $totalAll = \App\Models\ApiCustomer::where('user_id', $user->id)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => ['total_customers' => $totalAll],
                'customers' => $customers,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
            ],
        ]);
    }
}
