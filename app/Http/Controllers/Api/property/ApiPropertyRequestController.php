<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Validation\Rule;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\PropertyRequestStatus;
use App\Models\User;

class ApiPropertyRequestController extends Controller
{
    /**
     * Store a new property request.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_username' => 'required|string|exists:users,username',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'property_type' => 'nullable',
            'category' => 'nullable|string',
            'region'          => ['required','integer', Rule::exists('user_cities','id')],
            'districts_id'       => ['nullable','integer', Rule::exists('user_districts','id')],
            'area_from' => 'nullable|integer|min:0',
            'area_to' => 'nullable|integer|min:0',
            'purchase_method' => 'nullable',
            'budget_from' => 'nullable',
            'budget_to' => 'nullable',
            'seriousness' => 'nullable|in:مستعد فورًا,خلال شهر,خلال 3 أشهر,لاحقًا / استكشاف فقط',
            'purchase_goal' => 'nullable|in:سكن خاص,استثمار وتأجير,بناء وبيع,مشروع تجاري',
            'wants_similar_offers' => 'nullable|boolean',
            'contact_on_whatsapp' => 'nullable|boolean',
            'notes' => 'nullable|string|max:5000',
            'status_id' => 'nullable|integer|exists:property_request_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the tenant user by username
        $tenant = \App\Models\User::where('username', $request->tenant_username)->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found.',
                'errors' => ['tenant_username' => ['The specified tenant username does not exist.']]
            ], 404);
        }

        $data = $validator->validated();
        unset($data['tenant_username']); // Remove tenant_username from data
        $data['user_id'] = $tenant->id; // Use tenant's ID

        // Map region (city_id) → set city_id and Arabic name into region
        $regionId = (int) $request->input('region');
        $city = UserCity::find($regionId);
        $data['city_id'] = $regionId;
        $data['region'] = $city ? $city->name_ar : null;

        // Map property/category fields
        // property_type from request should go into category_id
        if (array_key_exists('property_type', $data) && !is_null($data['property_type']) && $data['property_type'] !== '') {
            $data['category_id'] = $data['property_type'];
            unset($data['property_type']);
        }

        // category from request should go into property_type (Arabic → English)
        if ($request->filled('category')) {
            $categoryInput = $request->input('category');
            $categoryMap = [
                'تجاري' => 'Commercial',
                'سكني' => 'Residential',
                'صناعي' => 'Industrial',
                'زراعي' => 'Agricultural',
            ];
            $data['property_type'] = $categoryMap[$categoryInput] ?? $categoryInput;
            unset($data['category']);
        }

        $data['is_read'] = false;
        $data['is_active'] = true;

        if ($request->filled('status_id')) {
            $data['status_id'] = (int) $request->input('status_id');
        }

        $propertyRequest = UserPropertyRequest::create($data);

        return response()->json([
            'message' => 'تم إرسال الطلب بنجاح.',
            'data' => $propertyRequest
        ], 201);
    }

    /**
     * List all property requests for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $validated = $request->validate([
            'q' => 'nullable|string|max:255',

            'property_type' => 'nullable|string|max:255',
            'category_id'   => 'nullable',
            // backward compatibility (old clients may send `category`)
            'category'      => 'nullable',

            'city_id'       => 'nullable|integer',
            'districts_id'  => 'nullable|integer',
            // alias
            'district_id'   => 'nullable|integer',

            // backward compatibility (old clients may send `region` string)
            'region'        => 'nullable|string|max:255',

            'budget_from'   => 'nullable|numeric|min:0',
            'budget_to'     => 'nullable|numeric|min:0',
            'area_from'     => 'nullable|integer|min:0',
            'area_to'       => 'nullable|integer|min:0',

            'purchase_method'      => 'nullable|string|max:50',
            'seriousness'          => 'nullable|string|max:80',
            'purchase_goal'        => 'nullable|string|max:80',
            'wants_similar_offers' => 'nullable|boolean',
            'contact_on_whatsapp'  => 'nullable|boolean',
            'is_read'              => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
            'status_id'            => 'nullable|integer|exists:property_request_statuses,id',
            'responsible_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($ownerId) {
                    $query->where('tenant_id', $ownerId)
                        ->where('account_type', 'employee');
                }),
            ],

            'created_from' => 'nullable|date',
            'created_to'   => 'nullable|date',

            'per_page'     => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);

        $categoryId = $validated['category_id'] ?? ($validated['category'] ?? null);
        $districtId = $validated['districts_id'] ?? ($validated['district_id'] ?? null);

        $query = UserPropertyRequest::query()
            ->with([
                'statusOption:id,name_ar,name_en',
                'customer.responsibleEmployee:id,first_name,last_name,email',
            ])
            ->where('user_id', $ownerId);

        // Calculate statistics before applying filters
        $totalRequests = $query->count();
        $totalCustomers = $query->distinct('phone')->count('phone');

        if (!empty($validated['q'])) {
            $term = trim((string) $validated['q']);
            $query->where(function ($sub) use ($term) {
                $sub->where('full_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if (!empty($validated['property_type'])) {
            $query->where('property_type', $validated['property_type']);
        }
        if (!is_null($categoryId) && $categoryId !== '') {
            $query->where('category_id', $categoryId);
        }
        if (!empty($validated['city_id'])) {
            $query->where('city_id', (int) $validated['city_id']);
        }
        if (!empty($districtId)) {
            $query->where('districts_id', (int) $districtId);
        }
        if (!empty($validated['region'])) {
            $query->where('region', 'like', '%' . $validated['region'] . '%');
        }

        if (!is_null($validated['budget_from'] ?? null)) {
            $query->where('budget_from', '>=', $validated['budget_from']);
        }
        if (!is_null($validated['budget_to'] ?? null)) {
            $query->where('budget_to', '<=', $validated['budget_to']);
        }
        if (!is_null($validated['area_from'] ?? null)) {
            $query->where('area_from', '>=', (int) $validated['area_from']);
        }
        if (!is_null($validated['area_to'] ?? null)) {
            $query->where('area_to', '<=', (int) $validated['area_to']);
        }

        foreach (['purchase_method','seriousness','purchase_goal'] as $field) {
            if (!empty($validated[$field] ?? null)) {
                $query->where($field, $validated[$field]);
            }
        }

        foreach (['wants_similar_offers','contact_on_whatsapp','is_read','is_active'] as $boolField) {
            if ($request->has($boolField)) {
                $query->where($boolField, (bool) ($validated[$boolField] ?? false));
            }
        }

        if (!empty($validated['created_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }
        if (!empty($validated['created_to'] ?? null)) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        if (!empty($validated['status_id'])) {
            $query->where('status_id', (int) $validated['status_id']);
        }

        if (!empty($validated['responsible_employee_id'])) {
            $employeeId = (int) $validated['responsible_employee_id'];

            // Only property requests that already have associated customers can match this filter
            $query->whereExists(function ($sub) use ($employeeId, $ownerId) {
                $sub->select(DB::raw(1))
                    ->from('api_customers')
                    ->whereColumn('api_customers.property_request_id', 'users_property_requests.id')
                    ->where('api_customers.user_id', $ownerId)
                    ->where('api_customers.responsible_employee_id', $employeeId);
            });
        }

        $propertyRequests = $query->orderByDesc('id')->paginate($perPage);

        /*
         * Example payload per record:
         * "status": {"id":1,"name_ar":"جديد","name_en":"New"},
         * "employee": {"id":12,"name":"Ahmad Saleh"}
         */

        return response()->json([
            'status' => 'success',
            'data' => [
                'property_requests' => $propertyRequests->items(),
                'pagination' => [
                    'total' => $propertyRequests->total(),
                    'per_page' => $propertyRequests->perPage(),
                    'current_page' => $propertyRequests->currentPage(),
                    'last_page' => $propertyRequests->lastPage(),
                ],
                'statistics' => [
                    'total_requests' => $totalRequests,
                    'total_customers' => $totalCustomers,
                ],
            ],
        ]);
    }

    /**
     * Get filter options for property requests (dropdown data).
     * Query params:
     * - used_only (bool, default true): return only cities/districts used in this tenant's requests
     * - city_id (int): optionally scope districts to a city
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $usedOnly = (bool) $request->boolean('used_only', true);
        $cityId   = $request->input('city_id');

        // Cache filter options (1 hour TTL)
        $cacheKey = "property_request_filter_options_{$ownerId}_{$usedOnly}_" . ($cityId ?? 'all');
        $filterData = Cache::remember($cacheKey, 3600, function () use ($ownerId, $usedOnly, $cityId) {
            // Cities
            if ($usedOnly) {
                $cityIds = UserPropertyRequest::where('user_id', $ownerId)
                    ->whereNotNull('city_id')
                    ->distinct()
                    ->pluck('city_id');
                $cities = UserCity::whereIn('id', $cityIds)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
            } else {
                $cities = UserCity::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
            }

            // Districts
            $districtQuery = UserDistrict::query();
            if ($usedOnly) {
                $districtIds = UserPropertyRequest::where('user_id', $ownerId)
                    ->whereNotNull('districts_id')
                    ->distinct()
                    ->pluck('districts_id');
                $districtQuery->whereIn('id', $districtIds);
            }
            if ($cityId) {
                $districtQuery->where('city_id', (int) $cityId);
            }
            $districts = $districtQuery->orderBy('name_ar')->get(['id', 'city_id', 'name_ar', 'name_en']);

            // Categories (tenant-visible)
            $categories = ApiUserCategory::query()
                ->visibleForUser($ownerId)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'icon']);

            // Property types used by this tenant (e.g., Residential/Commercial/etc.)
            $propertyTypes = UserPropertyRequest::where('user_id', $ownerId)
                ->whereNotNull('property_type')
                ->distinct()
                ->orderBy('property_type')
                ->pluck('property_type')
                ->filter()
                ->values();

            return [
                'cities' => $cities,
                'districts' => $districts,
                'categories' => $categories,
                'property_types' => $propertyTypes,
            ];
        });

        // Dynamic options derived from existing property requests
        $statuses = PropertyRequestStatus::ordered()
            ->get(['id', 'name_ar', 'name_en']);

        $purchaseGoals = UserPropertyRequest::where('user_id', $ownerId)
            ->whereNotNull('purchase_goal')
            ->distinct()
            ->orderBy('purchase_goal')
            ->pluck('purchase_goal')
            ->filter()
            ->values();

        $seriousnessOptions = UserPropertyRequest::where('user_id', $ownerId)
            ->whereNotNull('seriousness')
            ->distinct()
            ->orderBy('seriousness')
            ->pluck('seriousness')
            ->filter()
            ->values();

        // Get customer stages (same as customers use)
        $stages = UserApiCustomerStage::where('user_id', $ownerId)
            ->orderBy('order')
            ->get(['id', 'stage_name as name', 'icon', 'color']);

        // Get customer procedures (same as customers use)
        $procedures = UserApiCustomerProcedure::where('user_id', $ownerId)
            ->orderBy('order')
            ->get(['id', 'procedure_name as name', 'icon', 'color']);

        // Get customer types (same as customers use)
        $types = UserApiCustomerType::where('user_id', $ownerId)
            ->orderBy('order')
            ->get(['id', 'name', 'value', 'icon', 'color']);

        // Get customer priorities (same as customers use)
        $priorities = UserApiCustomerPriority::where('user_id', $ownerId)
            ->orderBy('order')
            ->get(['id', 'name', 'value', 'icon', 'color']);

        // Get employees (same as customers use)
        $employees = User::where('tenant_id', $ownerId)
            ->where('account_type', 'employee')
            ->where('active', true)
            ->with('activeWhatsappUser')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $employeesList = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')),
                'email' => $emp->email,
                'whatsapp_number' => $emp->activeWhatsappUser ? $emp->activeWhatsappUser->number : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => array_merge($filterData, [
                'status' => $statuses,
                'purchase_goals' => $purchaseGoals,
                'seriousness_options' => $seriousnessOptions,
                'stages' => $stages,
                'procedures' => $procedures,
                'types' => $types,
                'priorities' => $priorities,
                'employees' => $employeesList,
            ]),
        ]);
    }
    
    public function destroy($id)
    {
        $user = Auth::user();
        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $propertyRequest->delete();
        return response()->json(['message' => 'Property request deleted successfully']);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->all();
        
        if ($request->filled('status_id')) {
            $statusRecord = PropertyRequestStatus::find($request->input('status_id'));
            if (!$statusRecord) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid property request status provided.',
                    'errors' => [
                        'status_id' => ['The selected property request status is invalid.'],
                    ],
                ], 422);
            }
            $data['status_id'] = $statusRecord->id;
        }

        $propertyRequest->update($data);
        return response()->json(['message' => 'Property request updated successfully']);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status_id' => 'required|integer|exists:property_request_statuses,id',
        ]);

        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $propertyRequest->update([
            'status_id' => (int) $validated['status_id'],
        ]);

        $propertyRequest->load('statusOption');

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة العميل بنجاح',
            'data' => $propertyRequest
        ]);
    }

}
