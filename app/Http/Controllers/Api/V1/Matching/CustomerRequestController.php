<?php

namespace App\Http\Controllers\Api\V1\Matching;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\UserPropertyRequest;
use App\Services\Matching\MatchingService;
use App\Services\Matching\RequestCompletenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerRequestController extends Controller
{
    public function __construct(
        private RequestCompletenessService $completeness,
        private MatchingService $matching,
    ) {}

    /**
     * GET /api/v1/matching/requests
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $source = $request->query('source'); // web|whatsapp
        $purpose = $request->query('purpose'); // rent|sale
        $categoryId = $request->query('category_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $isComplete = $request->query('is_complete'); // 0/1
        $isRead = $request->query('is_read'); // 0/1
        $isArchived = $request->query('is_archived'); // 0/1
        $priceMin = $request->query('price_min');
        $priceMax = $request->query('price_max');
        $cityId = $request->query('city_id');
        $regionId = $request->query('region_id'); // best-effort: matches web.region or whatsapp.region_code
        $search = $request->query('search');

        $perPage = (int) ($request->query('per_page', 20));
        $perPage = max(1, min(100, $perPage));
        $page = max(1, (int) $request->query('page', 1));

        $web = DB::table('users_property_requests as r')
            ->where('r.user_id', $userId)
            ->selectRaw('
                r.id as id,
                "web" as source,
                r.full_name as customer_name,
                r.phone as phone,
                r.purpose as purpose,
                r.category_id as category_id,
                r.property_type as property_type,
                r.budget_from as budget_from,
                r.budget_to as budget_to,
                NULL as budget,
                r.area_from as area_from,
                r.area_to as area_to,
                NULL as min_area_sqm,
                NULL as max_area_sqm,
                r.city_id as city_id,
                NULL as city,
                r.districts_id as district_id,
                NULL as district,
                r.region as region,
                r.is_read as is_read,
                r.is_archived as is_archived,
                r.created_at as created_at,
                r.updated_at as updated_at,
                (
                    r.purpose is not null
                    and (r.budget_from is not null or r.budget_to is not null)
                    and (r.area_from is not null or r.area_to is not null)
                    and (r.category_id is not null or r.property_type is not null)
                    and (r.region is not null or r.city_id is not null or r.districts_id is not null)
                ) as is_complete
            ');

        $whatsapp = DB::table('api_customer_inquiry as i')
            ->leftJoin('api_customers as c', 'c.id', '=', 'i.customer_id')
            ->where('i.user_id', $userId)
            ->selectRaw('
                i.id as id,
                "whatsapp" as source,
                c.name as customer_name,
                COALESCE(i.phone_number, c.phone_number) as phone,
                i.inquiry_type as purpose,
                NULL as category_id,
                i.property_type as property_type,
                NULL as budget_from,
                NULL as budget_to,
                i.budget as budget,
                NULL as area_from,
                NULL as area_to,
                i.min_area_sqm as min_area_sqm,
                i.max_area_sqm as max_area_sqm,
                NULL as city_id,
                i.city as city,
                NULL as district_id,
                i.district as district,
                COALESCE(i.region_name, i.location) as region,
                i.is_read as is_read,
                i.is_archived as is_archived,
                i.created_at as created_at,
                i.updated_at as updated_at,
                (
                    i.inquiry_type is not null
                    and i.budget is not null
                    and (i.min_area_sqm is not null or i.max_area_sqm is not null)
                    and (i.property_type is not null)
                    and (i.city is not null or i.district is not null or (i.latitude is not null and i.longitude is not null) or i.region_name is not null or i.location is not null)
                ) as is_complete
            ');

        // Source filter
        if ($source === 'web') {
            $union = $web;
        } elseif ($source === 'whatsapp') {
            $union = $whatsapp;
        } else {
            $union = $web->unionAll($whatsapp);
        }

        // Apply filters (push to both sides when unioned)
        if ($source !== 'whatsapp') {
            if ($purpose) $web->where('r.purpose', $purpose);
            if ($categoryId) $web->where('r.category_id', $categoryId);
            if ($cityId) $web->where('r.city_id', $cityId);
            if ($regionId) $web->where('r.region', $regionId);
            if ($dateFrom) $web->where('r.created_at', '>=', $dateFrom);
            if ($dateTo) $web->where('r.created_at', '<=', $dateTo);
            if ($search) {
                $web->where(function ($q) use ($search) {
                    $q->where('r.full_name', 'like', "%{$search}%")
                        ->orWhere('r.phone', 'like', "%{$search}%");
                });
            }

            if ($priceMin !== null) {
                $web->whereRaw('COALESCE(GREATEST(r.budget_from, r.budget_to), r.budget_from, r.budget_to) >= ?', [$priceMin]);
            }
            if ($priceMax !== null) {
                $web->whereRaw('COALESCE(LEAST(r.budget_from, r.budget_to), r.budget_from, r.budget_to) <= ?', [$priceMax]);
            }
            if ($isRead !== null && $isRead !== '') $web->where('r.is_read', (int) $isRead);
            if ($isArchived !== null && $isArchived !== '') $web->where('r.is_archived', (int) $isArchived);
        }

        if ($source !== 'web') {
            if ($purpose) $whatsapp->where('i.inquiry_type', $purpose);
            if ($regionId) $whatsapp->where(function ($q) use ($regionId) {
                $q->where('i.region_code', $regionId)->orWhere('i.region_name', $regionId)->orWhere('i.location', 'like', "%{$regionId}%");
            });
            if ($dateFrom) $whatsapp->where('i.created_at', '>=', $dateFrom);
            if ($dateTo) $whatsapp->where('i.created_at', '<=', $dateTo);
            if ($search) {
                $whatsapp->where(function ($q) use ($search) {
                    $q->where('c.name', 'like', "%{$search}%")
                        ->orWhere('i.phone_number', 'like', "%{$search}%")
                        ->orWhere('c.phone_number', 'like', "%{$search}%");
                });
            }
            if ($priceMin !== null) $whatsapp->where('i.budget', '>=', $priceMin);
            if ($priceMax !== null) $whatsapp->where('i.budget', '<=', $priceMax);
            if ($isRead !== null && $isRead !== '') $whatsapp->where('i.is_read', (int) $isRead);
            if ($isArchived !== null && $isArchived !== '') $whatsapp->where('i.is_archived', (int) $isArchived);
        }

        $base = DB::query()->fromSub($union, 'u')->orderByDesc('created_at');
        if ($isComplete !== null && $isComplete !== '') {
            $base->where('is_complete', (int) $isComplete);
        }
        if ($isRead !== null && $isRead !== '') {
            $base->where('is_read', (int) $isRead);
        }
        if ($isArchived !== null && $isArchived !== '') {
            $base->where('is_archived', (int) $isArchived);
        }

        $total = (clone $base)->count();
        $rows = (clone $base)->forPage($page, $perPage)->get();

        $data = $rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'source' => $r->source,
                'customer_name' => $r->customer_name,
                'phone' => $r->phone,
                'purpose' => $r->purpose,
                'category_id' => $r->category_id !== null ? (int) $r->category_id : null,
                'property_type' => $r->property_type,
                'budget_from' => $r->budget_from !== null ? (float) $r->budget_from : null,
                'budget_to' => $r->budget_to !== null ? (float) $r->budget_to : null,
                'budget' => $r->budget !== null ? (float) $r->budget : null,
                'area_from' => $r->area_from !== null ? (int) $r->area_from : null,
                'area_to' => $r->area_to !== null ? (int) $r->area_to : null,
                'min_area_sqm' => $r->min_area_sqm !== null ? (int) $r->min_area_sqm : null,
                'max_area_sqm' => $r->max_area_sqm !== null ? (int) $r->max_area_sqm : null,
                'region' => $r->region,
                'city_id' => $r->city_id !== null ? (int) $r->city_id : null,
                'city' => $r->city,
                'district_id' => $r->district_id !== null ? (int) $r->district_id : null,
                'district' => $r->district,
                'is_read' => (bool) $r->is_read,
                'is_archived' => (bool) $r->is_archived,
                'is_complete' => (bool) $r->is_complete,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * GET /api/v1/matching/requests/{type}/{id}
     */
    public function show(Request $request, string $type, int $id)
    {
        $userId = $request->user()->id;
        $type = $this->normalizeType($type);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        $row = $type === 'web'
            ? UserPropertyRequest::query()->where('user_id', $userId)->findOrFail($id)
            : ApiCustomerInquiry::query()->where('user_id', $userId)->findOrFail($id);

        // Auto-mark request as read when opening details
        if (!$row->is_read) {
            $row->is_read = true;
            $row->save();
        }

        $check = $this->completeness->validate($type, $id);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'id' => $id,
                'request' => $row->toArray(),
                'is_complete' => $check['is_complete'],
                'missing_fields' => $check['missing_fields'],
                'is_read' => (bool) ($row->is_read ?? false),
                'is_archived' => (bool) ($row->is_archived ?? false),
            ],
        ]);
    }

    /**
     * PUT /api/v1/matching/requests/{type}/{id}
     */
    public function update(Request $request, string $type, int $id)
    {
        $userId = $request->user()->id;
        $type = $this->normalizeType($type);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        if ($type === 'web') {
            $row = UserPropertyRequest::query()->where('user_id', $userId)->findOrFail($id);

            $payload = $request->only([
                'purpose',
                'category_id',
                'property_type',
                'budget_from',
                'budget_to',
                'area_from',
                'area_to',
                'city_id',
                'districts_id',
                'region',
                'notes',
            ]);

            $row->fill($payload);
            $row->save();
        } else {
            $row = ApiCustomerInquiry::query()->where('user_id', $userId)->findOrFail($id);

            $payload = $request->only([
                'inquiry_type',
                'property_type',
                'budget',
                'currency',
                'bedrooms',
                'bathrooms',
                'min_area_sqm',
                'max_area_sqm',
                'furnished',
                'urgency',
                'location',
                'region_name',
                'region_code',
                'city',
                'district',
                'latitude',
                'longitude',
                'message',
                'lang',
            ]);

            // Allow clients to send purpose instead of inquiry_type
            if ($request->filled('purpose')) {
                $payload['inquiry_type'] = $request->input('purpose');
            }

            $row->fill($payload);
            $row->save();
        }

        // Re-run matching (explicit "re-submit") with ownership guard.
        $check = $this->completeness->validate($type, $id);
        $forceAi = (bool) $check['is_complete'];
        $limit = $forceAi ? 25 : 10;
        $this->matching->generateMatchesForRequest($type, $id, $limit, $forceAi, $userId);

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'id' => $id,
                'request' => $row->fresh()->toArray(),
                'is_complete' => $check['is_complete'],
                'missing_fields' => $check['missing_fields'],
                'is_read' => (bool) ($row->fresh()->is_read ?? false),
                'is_archived' => (bool) ($row->fresh()->is_archived ?? false),
            ],
        ]);
    }

    // PATCH /api/v1/matching/requests/{type}/{id}/read
    public function markAsRead(Request $request, string $type, int $id)
    {
        $userId = $request->user()->id;
        $type = $this->normalizeType($type);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        $row = $type === 'web'
            ? UserPropertyRequest::query()->where('user_id', $userId)->findOrFail($id)
            : ApiCustomerInquiry::query()->where('user_id', $userId)->findOrFail($id);

        if (!$row->is_read) {
            $row->is_read = true;
            $row->save();
        }

        return response()->json(['success' => true]);
    }

    // PATCH /api/v1/matching/requests/{type}/{id}/unread
    public function markAsUnread(Request $request, string $type, int $id)
    {
        $userId = $request->user()->id;
        $type = $this->normalizeType($type);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        $row = $type === 'web'
            ? UserPropertyRequest::query()->where('user_id', $userId)->findOrFail($id)
            : ApiCustomerInquiry::query()->where('user_id', $userId)->findOrFail($id);

        if ($row->is_read) {
            $row->is_read = false;
            $row->save();
        }

        return response()->json(['success' => true]);
    }

    // PATCH /api/v1/matching/requests/{type}/{id}/archive
    public function archive(Request $request, string $type, int $id)
    {
        $userId = $request->user()->id;
        $type = $this->normalizeType($type);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        $row = $type === 'web'
            ? UserPropertyRequest::query()->where('user_id', $userId)->findOrFail($id)
            : ApiCustomerInquiry::query()->where('user_id', $userId)->findOrFail($id);

        if (!$row->is_archived) {
            $row->is_archived = true;
            $row->save();
        }

        return response()->json(['success' => true]);
    }

    // PATCH /api/v1/matching/requests/{type}/{id}/unarchive
    public function unarchive(Request $request, string $type, int $id)
    {
        $userId = $request->user()->id;
        $type = $this->normalizeType($type);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Invalid type'], 422);
        }

        $row = $type === 'web'
            ? UserPropertyRequest::query()->where('user_id', $userId)->findOrFail($id)
            : ApiCustomerInquiry::query()->where('user_id', $userId)->findOrFail($id);

        if ($row->is_archived) {
            $row->is_archived = false;
            $row->save();
        }

        return response()->json(['success' => true]);
    }

    private function normalizeType(string $type): ?string
    {
        $t = strtolower(trim($type));
        if (in_array($t, ['web', 'whatsapp'], true)) {
            return $t;
        }
        return null;
    }
}

