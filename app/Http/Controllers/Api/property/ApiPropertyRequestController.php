<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Validation\Rule;
use App\Models\User\UserCity;

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
        $perPage = $request->input('per_page', 10);

        $propertyRequests = UserPropertyRequest::where('user_id', $user->id)
            ->when($request->filled('property_type'), fn($q) => $q->where('property_type', $request->property_type))
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->filled('region'), fn($q) => $q->where('region', 'like', '%' . $request->region . '%'))
            ->when($request->filled('budget_from'), fn($q) => $q->where('budget_from', '>=', $request->budget_from))
            ->when($request->filled('budget_to'), fn($q) => $q->where('budget_to', '<=', $request->budget_to))
            ->orderByDesc('id')
            ->paginate($perPage);

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
            ],
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
        $propertyRequest->update($request->all());
        return response()->json(['message' => 'Property request updated successfully']);
    }
}
