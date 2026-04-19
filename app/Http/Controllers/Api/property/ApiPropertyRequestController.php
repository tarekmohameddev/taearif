<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Models\Api\UserPropertyRequest;
use App\Models\User\UserCity;
use App\Models\User\RealestateManagement\Property;
use App\Http\Requests\Api\Property\StorePropertyRequestRequest;
use App\Http\Requests\Api\Property\UpdatePropertyRequestRequest;
use App\Http\Requests\Api\Property\UpdateStatusPropertyRequestRequest;
use App\Http\Requests\Api\Property\UpdateEmployeePropertyRequestRequest;
use App\Http\Requests\Api\Property\AssignEmployeeToCustomerRequest;
use App\Http\Requests\Api\Property\AttachPropertiesToPropertyRequestRequest;
use App\Http\Requests\Api\Property\IndexPropertyRequestsRequest;
use App\Http\Requests\Api\Property\UpdatePriorityPropertyRequestRequest;
use App\Models\User\UserDistrict;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\PropertyRequestStatus;
use App\Models\User;
use App\Models\ApiCustomer;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Rules\PropertyTypeRule;
use Illuminate\Support\Carbon;

class ApiPropertyRequestController extends Controller
{
    use ResolvesTenant;

    /**
     * Store a new property request.
     */
    public function store(StorePropertyRequestRequest $request): JsonResponse
    {
        $tenantResult = $this->resolveTenantForStore($request);
        if ($tenantResult instanceof JsonResponse) {
            return $tenantResult;
        }
        $tenant = $tenantResult;

        $data = $request->validated();
        $rawPropertyIds = $data['property_ids'] ?? [];
        unset($data['property_ids']);
        unset($data['tenant_username']);
        $data['user_id'] = $tenant->id;

        // Normalize property_ids into a clean integer array
        $propertyIds = [];
        if (is_array($rawPropertyIds)) {
            $propertyIds = array_values(array_unique(
                array_filter(
                    array_map('intval', $rawPropertyIds),
                    static fn (int $id): bool => $id > 0
                )
            ));
        }

        // Ensure provided property IDs belong to the resolved tenant
        if ($propertyIds !== []) {
            $validIds = \App\Models\User\RealestateManagement\Property::query()
                ->where('user_id', $tenant->id)
                ->whereIn('id', $propertyIds)
                ->pluck('id')
                ->all();

            sort($validIds);
            $sortedRequested = $propertyIds;
            sort($sortedRequested);

            if ($validIds !== $sortedRequested) {
                return response()->json([
                    'message' => 'One or more property IDs are invalid or do not belong to this tenant.',
                    'errors' => [
                        'property_ids' => ['The selected property IDs are invalid or unauthorized for this tenant.'],
                    ],
                ], 422);
            }

            $propertyIds = $validIds;
        }

        // Map region (city_id) → set city_id and Arabic name into region
        $regionId = (int) ($data['region'] ?? 0);
        $city = UserCity::find($regionId);
        $data['city_id'] = $regionId;
        $data['region'] = $city ? $city->name_ar : null;

        // category from request should go into property_type (Arabic → English)
        if (isset($data['category']) && $data['category'] !== null && $data['category'] !== '') {
            $categoryMap = [
                'تجاري' => 'Commercial',
                'سكني' => 'Residential',
                'صناعي' => 'Industrial',
                'زراعي' => 'Agricultural',
            ];
            $data['property_type'] = $categoryMap[$data['category']] ?? $data['category'];
            unset($data['category']);
        }

        $data['is_read'] = false;
        $data['is_active'] = true;
        $data['source'] = $request->user() ? 'employee_dashboard' : ($data['source'] ?? 'employee_dashboard');
        $data['referral_source'] = $data['referral_source'] ?? null;

        if (isset($data['status_id'])) {
            $data['status_id'] = (int) $data['status_id'];
        }

        $data['property_ids'] = $propertyIds;

        $propertyRequest = UserPropertyRequest::create($data);

        return response()->json([
            'message' => 'تم إرسال الطلب بنجاح.',
            'data' => $propertyRequest
        ], 201);
    }

    /**
     * Resolve tenant for store: from request tenant_username or from authenticated user.
     *
     * @return User|JsonResponse
     */
    private function resolveTenantForStore(Request $request)
    {
        if ($request->filled('tenant_username')) {
            try {
                return $this->resolveTenant($request, (string) $request->input('tenant_username'));
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Tenant not found.',
                    'errors' => ['tenant_username' => ['The specified tenant username does not exist.']],
                ], 404);
            }
        }

        $user = $request->user();
        $ownerId = $user->tenantOwnerId();
        $tenant = User::find($ownerId);

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant could not be determined.',
                'errors' => ['tenant_username' => ['Tenant could not be determined for the authenticated user.']],
            ], 403);
        }

        return $tenant;
    }

    /**
     * Store property request from property interest button.
     *
     * POST /api/v1/property-requests/interest
     */
    public function storeFromInterest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_username' => 'required|string|max:255',
            'property_id' => 'required|integer|exists:user_properties,id',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $tenant = $this->resolveTenant($request, $validated['tenant_username']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Tenant not found.',
                'errors' => ['tenant_username' => ['The specified tenant username does not exist.']],
            ], 404);
        }

        $property = \App\Models\User\RealestateManagement\Property::with('contents')
            ->where('id', $validated['property_id'])
            ->where('user_id', $tenant->id)
            ->first();

        if (!$property) {
            return response()->json([
                'message' => 'Property not found.',
                'errors' => ['property_id' => ['Property does not exist or does not belong to this tenant.']],
            ], 404);
        }

        $content = $property->contents->first();
        $cityId = $content ? $content->city_id : null;
        $city = $cityId ? UserCity::find($cityId) : null;

        $data = [
            'user_id' => $tenant->id,
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'notes' => $validated['notes'] ?? null,
            'property_type' => \App\Rules\PropertyTypeRule::normalize($property->property_type ?? null),
            'category_id' => $property->category_id ?? null,
            'city_id' => $cityId,
            'region' => $city ? $city->name_ar : null,
            'purpose' => $property->purpose ?? null,
            'source' => 'property_interest',
            'referral_source' => null,
            'is_read' => false,
            'is_active' => true,
            'is_archived' => false,
            'property_ids' => [$property->id],
        ];

        $propertyRequest = UserPropertyRequest::create($data);

        return response()->json([
            'message' => 'تم إرسال طلبك بنجاح. سيتم التواصل معك قريباً.',
            'message_en' => 'Your interest has been submitted successfully. We will contact you soon.',
            'data' => [
                'request_id' => $propertyRequest->id,
                'property_id' => $property->id,
            ],
        ], 201);
    }

    /**
     * List all property requests for the authenticated user.
     */
    public function index(IndexPropertyRequestsRequest $request): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 10);
        $withStatistics = $request->boolean('with_statistics', false);

        $categoryId = $validated['category_id'] ?? ($validated['category'] ?? null);
        $districtId = $validated['districts_id'] ?? ($validated['district_id'] ?? null);

        $query = UserPropertyRequest::query()
            ->with([
                'statusOption:id,name_ar,name_en',
                'customer:id,user_id,responsible_employee_id',
                'customer.responsibleEmployee:id,first_name,last_name,email',
                'customer.responsibleEmployee.activeWhatsappUser:id,employee_id,number',
                'district:id,name_ar',
            ])
            ->where('user_id', $ownerId);

        $totalRequests = null;
        $totalCustomers = null;
        $byStatus = [];

        if ($withStatistics) {
            $cacheKey = 'property_requests_statistics_' . $ownerId;
            $ttlSeconds = 120;

            $stats = Cache::remember($cacheKey, $ttlSeconds, function () use ($ownerId) {
                $totalRequests = UserPropertyRequest::where('user_id', $ownerId)->count();
                $totalCustomers = (int) DB::table('users_property_requests')
                    ->where('user_id', $ownerId)
                    ->selectRaw('COUNT(DISTINCT phone) as c')
                    ->value('c');

                $statusCounts = UserPropertyRequest::query()
                    ->select('property_request_statuses.name_ar', DB::raw('COUNT(*) as count'))
                    ->leftJoin('property_request_statuses', 'users_property_requests.status_id', '=', 'property_request_statuses.id')
                    ->where('users_property_requests.user_id', $ownerId)
                    ->whereNotNull('property_request_statuses.name_ar')
                    ->groupBy('property_request_statuses.id', 'property_request_statuses.name_ar')
                    ->pluck('count', 'name_ar')
                    ->filter(fn ($v, $k) => $k !== null && $k !== '')
                    ->toArray();

                $allStatuses = PropertyRequestStatus::forTenant($ownerId)->active()->ordered()->pluck('name_ar')->toArray();
                $byStatus = [];
                foreach ($allStatuses as $statusName) {
                    $byStatus[$statusName] = $statusCounts[$statusName] ?? 0;
                }
                foreach ($statusCounts as $statusName => $count) {
                    if (!isset($byStatus[$statusName])) {
                        $byStatus[$statusName] = $count;
                    }
                }

                return ['total_requests' => $totalRequests, 'total_customers' => $totalCustomers, 'by_status' => $byStatus];
            });

            $totalRequests = $stats['total_requests'];
            $totalCustomers = $stats['total_customers'];
            $byStatus = $stats['by_status'];
        }

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

            $query->where(function ($q) use ($employeeId, $ownerId) {
                $q->where('users_property_requests.responsible_employee_id', $employeeId)
                    ->orWhereHas('customer', function ($sub) use ($employeeId, $ownerId) {
                        $sub->where('user_id', $ownerId)
                            ->where('responsible_employee_id', $employeeId);
                    });
            });
        }

        $useCursor = $request->filled('cursor');
        $propertyRequests = $useCursor
            ? $query->orderByDesc('id')->cursorPaginate($perPage)
            : $query->orderByDesc('id')->paginate($perPage);

        $pagination = $useCursor
            ? [
                'next_cursor' => $propertyRequests->nextCursor()?->encode(),
                'prev_cursor' => $propertyRequests->previousCursor()?->encode(),
                'has_more'    => $propertyRequests->hasMorePages(),
                'per_page'    => $propertyRequests->perPage(),
            ]
            : [
                'total'        => $propertyRequests->total(),
                'per_page'     => $propertyRequests->perPage(),
                'current_page' => $propertyRequests->currentPage(),
                'last_page'    => $propertyRequests->lastPage(),
            ];

        $data = [
            'property_requests' => $propertyRequests->items(),
            'pagination'        => $pagination,
        ];

        if ($withStatistics) {
            $data['statistics'] = [
                'total_requests' => $totalRequests,
                'total_customers' => $totalCustomers,
                'by_status' => $byStatus,
            ];
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * GET /api/v1/property-requests/stats
     *
     * Per-day aggregates for the last 7 calendar days (inclusive), oldest → newest.
     *
     * Timezone: calendar boundaries use config('app.timezone'); DB timestamps are treated as UTC
     * for CONVERT_TZ(..., 'UTC', app_tz) before DATE().
     *
     * Metrics: incoming = created_at day; completed = updated_at day when status slug is
     * "completed" (no completed_at column); followups = calendar day of earliest pending
     * appointment/reminder with datetime > now.
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $tenantId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $tz = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        $windowStart = $now->copy()->subDays(6)->startOfDay()->utc();
        $windowEnd = $now->copy()->endOfDay()->utc();

        $incoming = DB::table('users_property_requests')
            ->selectRaw("DATE(CONVERT_TZ(created_at,'UTC',?)) AS day, COUNT(*) AS cnt", [$tz])
            ->where('user_id', $tenantId)
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->groupByRaw("DATE(CONVERT_TZ(created_at,'UTC',?))", [$tz])
            ->pluck('cnt', 'day');

        $completed = DB::table('users_property_requests as upr')
            ->join('property_request_statuses as prs', function ($join) use ($tenantId) {
                $join->on('prs.id', '=', 'upr.status_id')
                    ->where('prs.slug', 'completed')
                    ->where(function ($q) use ($tenantId) {
                        $q->whereNull('prs.user_id')
                            ->orWhere('prs.user_id', $tenantId);
                    });
            })
            ->selectRaw("DATE(CONVERT_TZ(upr.updated_at,'UTC',?)) AS day, COUNT(*) AS cnt", [$tz])
            ->where('upr.user_id', $tenantId)
            ->whereBetween('upr.updated_at', [$windowStart, $windowEnd])
            ->groupByRaw("DATE(CONVERT_TZ(upr.updated_at,'UTC',?))", [$tz])
            ->pluck('cnt', 'day');

        $nowUtc = $now->copy()->utc();

        $pendingActionsUnion = DB::table('property_request_appointments')
            ->select('property_request_id', 'datetime')
            ->where('status', 'pending')
            ->where('datetime', '>', $nowUtc)
            ->unionAll(
                DB::table('property_request_reminders')
                    ->select('property_request_id', 'datetime')
                    ->where('status', 'pending')
                    ->where('datetime', '>', $nowUtc)
            );

        $earliestPerRequest = DB::query()
            ->fromSub($pendingActionsUnion, 'sub')
            ->select('property_request_id', DB::raw('MIN(`datetime`) AS earliest'))
            ->groupBy('property_request_id');

        $followups = DB::query()
            ->fromSub($earliestPerRequest, 'fu')
            ->join('users_property_requests as upr', 'upr.id', '=', 'fu.property_request_id')
            ->selectRaw("DATE(CONVERT_TZ(fu.earliest,'UTC',?)) AS day, COUNT(*) AS cnt", [$tz])
            ->where('upr.user_id', $tenantId)
            ->whereBetween('fu.earliest', [$windowStart, $windowEnd])
            ->groupByRaw("DATE(CONVERT_TZ(fu.earliest,'UTC',?))", [$tz])
            ->pluck('cnt', 'day');

        $series = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->format('Y-m-d');
            $series[] = [
                'date' => $date,
                'incoming' => (int) ($incoming[$date] ?? 0),
                'completed' => (int) ($completed[$date] ?? 0),
                'followups' => (int) ($followups[$date] ?? 0),
            ];
        }

        return response()->json([
            'meta' => [
                'generatedAt' => $now->copy()->utc()->toIso8601String(),
                'timezone' => $tz,
            ],
            'series' => $series,
        ]);
    }

    /**
     * Get filter options for property requests (dropdown data).
     *
     * Query params:
     * - used_only (bool, default true): return only cities/districts used in this tenant's requests.
     * - city_id (int): optionally scope districts to a city.
     * - groups (string): optional comma-separated list to return only those groups. When set, only
     *   the requested groups are queried and returned (no cache). Valid: cities, districts,
     *   categories, property_types, status, purchase_goals, seriousness_options, stages,
     *   procedures, types, priorities, employees.
     *
     * When used_only=false, cities and districts are capped at 500 for performance. Pass city_id
     * to scope districts to one city when you need the full list for that city.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $usedOnly = (bool) $request->boolean('used_only', true);
        $cityId   = $request->input('city_id');

        $allFilterGroups = ['cities', 'districts', 'categories', 'property_types'];
        $allMetaGroups = ['status', 'purchase_goals', 'seriousness_options', 'stages', 'procedures', 'types', 'priorities', 'employees'];

        $groupsInput = $request->input('groups');
        $requested = $groupsInput ? array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $groupsInput))))) : [];

        if ($requested === []) {
            $doFilter = $allFilterGroups;
            $doMeta = $allMetaGroups;
            $useCache = true;
        } else {
            $doFilter = array_values(array_intersect($requested, $allFilterGroups));
            $doMeta = array_values(array_intersect($requested, $allMetaGroups));
            $useCache = false;
        }

        if ($useCache) {
            // Cache filter options (1 hour TTL)
            $cacheKey = "property_request_filter_options_v2_{$ownerId}_{$usedOnly}_" . ($cityId ?? 'all');
            $filterData = Cache::remember($cacheKey, 3600, function () use ($ownerId, $usedOnly, $cityId) {
                // Districts (fetch districtIds first when used_only — needed for cities-from-districts)
                $districtQuery = UserDistrict::query();
                $districtIds = collect();
                if ($usedOnly) {
                    $districtIds = UserPropertyRequest::where('user_id', $ownerId)
                        ->whereNotNull('districts_id')
                        ->distinct()
                        ->pluck('districts_id');
                    $districtQuery->whereIn('id', $districtIds);
                }

                // Cities: when used_only, include city_id from requests and from districts (requests may have districts_id but null city_id)
                if ($usedOnly) {
                    $cityIdsFromRequests = UserPropertyRequest::where('user_id', $ownerId)
                        ->whereNotNull('city_id')
                        ->distinct()
                        ->pluck('city_id');
                    $cityIdsFromDistricts = $districtIds->isNotEmpty()
                        ? UserDistrict::whereIn('id', $districtIds)->whereNotNull('city_id')->distinct()->pluck('city_id')
                        : collect();
                    $cityIds = $cityIdsFromRequests->merge($cityIdsFromDistricts)->unique()->values();
                    $cities = UserCity::whereIn('id', $cityIds)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
                } else {
                    $cities = UserCity::orderBy('name_ar')->limit(500)->get(['id', 'name_ar', 'name_en']);
                }
                if ($cityId) {
                    $districtQuery->where('city_id', (int) $cityId);
                }
                if (!$usedOnly && !$cityId) {
                    $districtQuery->limit(500);
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

            // Cache dynamic/meta options (1 hour TTL) — statuses, purchase_goals, seriousness, stages, procedures, types, priorities, employees
            $metaCacheKey = "property_request_filter_options_meta_{$ownerId}";
            $metaData = Cache::remember($metaCacheKey, 3600, function () use ($ownerId) {
                $statuses = PropertyRequestStatus::forTenant($ownerId)->ordered()
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

                $stages = UserApiCustomerStage::where('user_id', $ownerId)
                    ->orderBy('order')
                    ->get(['id', 'stage_name as name', 'icon', 'color']);

                $procedures = UserApiCustomerProcedure::where('user_id', $ownerId)
                    ->orderBy('order')
                    ->get(['id', 'procedure_name as name', 'icon', 'color']);

                $types = UserApiCustomerType::where('user_id', $ownerId)
                    ->orderBy('order')
                    ->get(['id', 'name', 'value', 'icon', 'color']);

                $priorities = UserApiCustomerPriority::where('user_id', $ownerId)
                    ->orderBy('order')
                    ->get(['id', 'name', 'value', 'icon', 'color']);

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

                return [
                    'status' => $statuses,
                    'purchase_goals' => $purchaseGoals,
                    'seriousness_options' => $seriousnessOptions,
                    'stages' => $stages,
                    'procedures' => $procedures,
                    'types' => $types,
                    'priorities' => $priorities,
                    'employees' => $employeesList,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => array_merge($filterData, $metaData),
            ]);
        }

        // groups= specified: only fetch requested groups (no cache). Preserve full response structure; non-requested groups are [].
        $filterData = array_fill_keys($allFilterGroups, []);
        $metaData = array_fill_keys($allMetaGroups, []);

        if (in_array('cities', $doFilter)) {
            if ($usedOnly) {
                $cityIdsFromRequests = UserPropertyRequest::where('user_id', $ownerId)
                    ->whereNotNull('city_id')
                    ->distinct()
                    ->pluck('city_id');
                $districtIds = UserPropertyRequest::where('user_id', $ownerId)
                    ->whereNotNull('districts_id')
                    ->distinct()
                    ->pluck('districts_id');
                $cityIdsFromDistricts = $districtIds->isNotEmpty()
                    ? UserDistrict::whereIn('id', $districtIds)->whereNotNull('city_id')->distinct()->pluck('city_id')
                    : collect();
                $cityIds = $cityIdsFromRequests->merge($cityIdsFromDistricts)->unique()->values();
                $filterData['cities'] = UserCity::whereIn('id', $cityIds)->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
            } else {
                $filterData['cities'] = UserCity::orderBy('name_ar')->limit(500)->get(['id', 'name_ar', 'name_en']);
            }
        }

        if (in_array('districts', $doFilter)) {
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
            if (!$usedOnly && !$cityId) {
                $districtQuery->limit(500);
            }
            $filterData['districts'] = $districtQuery->orderBy('name_ar')->get(['id', 'city_id', 'name_ar', 'name_en']);
        }

        if (in_array('categories', $doFilter)) {
            $filterData['categories'] = ApiUserCategory::query()
                ->visibleForUser($ownerId)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'icon']);
        }

        if (in_array('property_types', $doFilter)) {
            $filterData['property_types'] = UserPropertyRequest::where('user_id', $ownerId)
                ->whereNotNull('property_type')
                ->distinct()
                ->orderBy('property_type')
                ->pluck('property_type')
                ->filter()
                ->values();
        }

        if (in_array('status', $doMeta)) {
            $metaData['status'] = PropertyRequestStatus::forTenant($ownerId)->ordered()->get(['id', 'name_ar', 'name_en']);
        }

        if (in_array('purchase_goals', $doMeta)) {
            $metaData['purchase_goals'] = UserPropertyRequest::where('user_id', $ownerId)
                ->whereNotNull('purchase_goal')
                ->distinct()
                ->orderBy('purchase_goal')
                ->pluck('purchase_goal')
                ->filter()
                ->values();
        }

        if (in_array('seriousness_options', $doMeta)) {
            $metaData['seriousness_options'] = UserPropertyRequest::where('user_id', $ownerId)
                ->whereNotNull('seriousness')
                ->distinct()
                ->orderBy('seriousness')
                ->pluck('seriousness')
                ->filter()
                ->values();
        }

        if (in_array('stages', $doMeta)) {
            $metaData['stages'] = UserApiCustomerStage::where('user_id', $ownerId)
                ->orderBy('order')
                ->get(['id', 'stage_name as name', 'icon', 'color']);
        }

        if (in_array('procedures', $doMeta)) {
            $metaData['procedures'] = UserApiCustomerProcedure::where('user_id', $ownerId)
                ->orderBy('order')
                ->get(['id', 'procedure_name as name', 'icon', 'color']);
        }

        if (in_array('types', $doMeta)) {
            $metaData['types'] = UserApiCustomerType::where('user_id', $ownerId)
                ->orderBy('order')
                ->get(['id', 'name', 'value', 'icon', 'color']);
        }

        if (in_array('priorities', $doMeta)) {
            $metaData['priorities'] = UserApiCustomerPriority::where('user_id', $ownerId)
                ->orderBy('order')
                ->get(['id', 'name', 'value', 'icon', 'color']);
        }

        if (in_array('employees', $doMeta)) {
            $employees = User::where('tenant_id', $ownerId)
                ->where('account_type', 'employee')
                ->where('active', true)
                ->with('activeWhatsappUser')
                ->get(['id', 'first_name', 'last_name', 'email']);
            $metaData['employees'] = $employees->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')),
                    'email' => $emp->email,
                    'whatsapp_number' => $emp->activeWhatsappUser ? $emp->activeWhatsappUser->number : null,
                ];
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge($filterData, $metaData),
        ]);
    }

    /**
     * Get a single property request by ID.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->with('customer')
            ->firstOrFail();

        return response()->json($propertyRequest);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $propertyRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Property request deleted successfully'
        ]);
    }

    public function update(UpdatePropertyRequestRequest $request, $id)
    {
        $user = Auth::user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $data = $request->validated();

        // Normalize property_type to canonical lowercase value
        if (array_key_exists('property_type', $data) && $data['property_type'] !== null) {
            $data['property_type'] = PropertyTypeRule::normalize(is_string($data['property_type']) ? $data['property_type'] : null);
        }

        // Map region (city_id) → set city_id and Arabic name into region (mirrors store() behavior)
        if (array_key_exists('region', $data) && $data['region'] !== null) {
            $regionId = (int) $data['region'];
            $city = UserCity::find($regionId);
            $data['city_id'] = $regionId;
            $data['region'] = $city ? $city->name_ar : null;
        }

        // Validate property_ids: ensure they exist and belong to this tenant
        if (array_key_exists('property_ids', $data) && is_array($data['property_ids'])) {
            $requested = array_values(array_unique(array_filter(array_map('intval', $data['property_ids']), static fn (int $pid): bool => $pid > 0)));
            if ($requested !== []) {
                $validIds = Property::query()
                    ->where('user_id', $ownerId)
                    ->whereIn('id', $requested)
                    ->pluck('id')
                    ->all();

                sort($validIds);
                $sortedRequested = $requested;
                sort($sortedRequested);

                if ($validIds !== $sortedRequested) {
                    return response()->json([
                        'message' => 'One or more property IDs are invalid or do not belong to this tenant.',
                        'errors' => [
                            'property_ids' => ['The selected property IDs are invalid or unauthorized for this tenant.'],
                        ],
                    ], 422);
                }
            }
            $data['property_ids'] = $requested;
        }

        $propertyRequest->update($data);

        $propertyRequest->load(['statusOption', 'customer', 'district']);

        return response()->json([
            'message' => 'Property request updated successfully',
            'data' => $propertyRequest,
        ]);
    }

    public function updateStatus(UpdateStatusPropertyRequestRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

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

    public function updatePriority(UpdatePriorityPropertyRequestRequest $request, $id): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $priorityToSeriousness = [
            'urgent' => 'مستعد فورًا',
            'high'   => 'خلال شهر',
            'medium' => 'خلال 3 أشهر',
            'low'    => 'لاحقًا / استكشاف فقط',
        ];

        $propertyRequest->update([
            'seriousness' => $priorityToSeriousness[$validated['priority']],
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => 'تم تحديث أولوية طلب العميل بنجاح',
            'data'     => [
                'id'       => $propertyRequest->id,
                'priority' => $validated['priority'],
            ],
        ]);
    }

    public function updateEmployee(UpdateEmployeePropertyRequestRequest $request, $id): JsonResponse
    {
        $user = auth()->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $validated = $request->validated();
        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        // Get or check for associated customer (first linked via pivot for this tenant)
        $customer = $propertyRequest->customers()->where('api_customers.user_id', $ownerId)->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'No associated customer found for this property request. Please create a customer first.',
                'errors' => [
                    'customer' => ['This property request does not have an associated customer.'],
                ],
            ], 404);
        }

        $customer->update([
            'responsible_employee_id' => (int) $validated['responsible_employee_id'],
        ]);

        // Reload property request with customer and employee relationships
        $propertyRequest->load(['customers.responsibleEmployee']);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعيين الموظف المسؤول بنجاح',
            'data' => $propertyRequest
        ]);
    }

    /**
     * Assign employee to customer via property request (direct customer update).
     *
     * PUT api/v1/property-requests/{customerID}/employee
     *
     * @param Request $request
     * @param int $customerID
     * @return JsonResponse
     */
    public function assignEmployeeToCustomer(AssignEmployeeToCustomerRequest $request, $customerID): JsonResponse
    {
        $user = auth()->user();
        $ownerId = $user && method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        try {
            $validated = $request->validated();

            // Find the customer
            $customer = ApiCustomer::where('id', $customerID)
                ->where('user_id', $ownerId)
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found',
                    'errors' => [
                        'customer' => ['The specified customer does not exist.'],
                    ],
                ], 422);
            }

            // Validate employee exists if provided (already validated by Rule::exists, but double-check for clarity)
            if (!is_null($validated['responsible_employee_id'])) {
                $employee = User::where('id', $validated['responsible_employee_id'])
                    ->where('tenant_id', $ownerId)
                    ->where('account_type', 'employee')
                    ->where('active', true)
                    ->first();

                if (!$employee) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found',
                        'errors' => [
                            'responsible_employee_id' => ['The specified employee does not exist or is not active.'],
                        ],
                    ], 422);
                }
            }

            // Update the customer's responsible_employee_id
            $customer->update([
                'responsible_employee_id' => $validated['responsible_employee_id'] ?? null,
            ]);

            // Reload customer with relationships
            $customer->load('responsibleEmployee');

            // Format response data
            $customerData = $customer->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Employee assigned successfully',
                'data' => $customerData,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error assigning employee to customer', [
                'customer_id' => $customerID,
                'employee_id' => $request->input('responsible_employee_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning the employee',
                'errors' => config('app.debug') ? ['exception' => $e->getMessage()] : [],
            ], 500);
        }
    }

    /**
     * Attach property IDs to a property request (append to property_ids).
     *
     * POST api/v1/property-requests/{id}/properties
     */
    public function attachProperties(AttachPropertiesToPropertyRequestRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $newIds = array_map('intval', $request->validated('propertyIds'));
        $existing = $propertyRequest->property_ids ?? [];
        $merged = array_values(array_unique(array_merge($existing, $newIds)));
        $propertyRequest->property_ids = $merged;
        $propertyRequest->save();

        $propertyRequest->load('customer');

        return response()->json([
            'status' => 'success',
            'message' => 'Properties attached successfully.',
            'data' => ['property_request' => $propertyRequest],
        ]);
    }

    /**
     * Detach one property ID from a property request.
     *
     * DELETE api/v1/property-requests/{id}/properties/{propertyId}
     */
    public function detachProperty(Request $request, $id, $propertyId): JsonResponse
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;

        $propertyRequest = UserPropertyRequest::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $ids = $propertyRequest->property_ids ?? [];
        $propertyIdInt = (int) $propertyId;
        $ids = array_values(array_filter($ids, fn ($id) => (int) $id !== $propertyIdInt));
        $propertyRequest->property_ids = $ids;
        $propertyRequest->save();

        $propertyRequest->load('customer');

        return response()->json([
            'status' => 'success',
            'message' => 'Property detached successfully.',
            'data' => ['property_request' => $propertyRequest],
        ]);
    }
}
