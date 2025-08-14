<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\Api\UserPropertyRequest;

class ApiPropertyRequestController extends Controller
{
    /**
     * Store a new property request.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'property_type' => 'nullable',
            'category' => 'nullable|in:سكني,تجاري,صناعي,زراعي',
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

        $data = $validator->validated();
        $data['user_id'] = Auth::id();
        $data['region'] = $request->input('region', 'الرياض');
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
}
