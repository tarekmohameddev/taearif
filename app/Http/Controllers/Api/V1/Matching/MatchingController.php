<?php

namespace App\Http\Controllers\Api\V1\Matching;

use App\Http\Controllers\Controller;
use App\Models\PropertyMatch;
use App\Repositories\RequestRepository;
use App\Services\Matching\MatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MatchingController extends Controller
{
    public function __construct(
        private MatchingService $matching,
        private RequestRepository $requests,
    ) {}

    // 1) GET /api/v1/matching/customers
    public function customers(Request $request)
    {
        $userId = $request->user()->id;
        $rows = PropertyMatch::query()
            ->selectRaw('customer_key, 
                MIN(COALESCE(NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT(match_explanation, "$.customer_name"))), ""), NULL)) as customer_name,
                COUNT(DISTINCT CONCAT(request_type,":",request_id)) as number_of_requests,
                COUNT(DISTINCT property_id) as number_of_matching_properties')
            ->where('user_id', $userId)
            ->whereNotNull('customer_key')
            ->groupBy('customer_key')
            ->orderByDesc('number_of_matching_properties')
            ->get()
            ->map(function ($r) {
                return [
                    'customer_name' => $r->customer_name,
                    'phone' => $r->customer_key,
                    'number_of_requests' => (int) $r->number_of_requests,
                    'number_of_matching_properties' => (int) $r->number_of_matching_properties,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    // 2) GET /api/v1/matching/customers/{customer_key}/properties
    public function customerProperties(Request $request, string $customerKey)
    {
        $userId = $request->user()->id;
        $rows = PropertyMatch::query()
            ->where('user_id', $userId)
            ->where('customer_key', $customerKey)
            ->orderByDesc('match_score')
            ->get();

        $items = $rows->map(function ($m) {
            $prop = \App\Models\User\RealestateManagement\Property::find($m->property_id);
            return [
                'property_name' => optional($prop->first_content)->title ?? null,
                'featured_image' => $prop->featured_image_url ?? null,
                'address' => $prop->address ?? null,
                'matching_score' => (int) $m->match_score,
                'match_explanation' => $m->match_explanation,
                'matched_criteria' => $m->matched_criteria,
                'for_rent_or_sale' => $prop->purpose ?? null,
                'rent_or_buy_amount' => $prop->price ?? null,
                'bedrooms' => $prop->beds ?? null,
                'baths' => $prop->bath ?? null,
                'area' => $prop->area ?? null,
                'unread' => !$m->is_reviewed,
                'match_id' => $m->id,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    // 3) GET /api/v1/matching/matches/{id}
    public function showMatch(Request $request, int $id)
    {
        $userId = $request->user()->id;
        $m = PropertyMatch::query()->where('user_id', $userId)->findOrFail($id);

        $prop = \App\Models\User\RealestateManagement\Property::find($m->property_id);

        // Fetch request data from source
        $requestData = null;
        if ($m->request_type === 'web') {
            $req = \App\Models\Api\UserPropertyRequest::find($m->request_id);
            if ($req) {
                $requestData = $req->toArray();
            }
        } else {
            $req = \App\Models\Api\ApiCustomerInquiry::find($m->request_id);
            if ($req) {
                $requestData = $req->toArray();
            }
        }

        // Mark as reviewed
        if (!$m->is_reviewed) {
            $m->is_reviewed = true;
            $m->save();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'match' => [
                    'id' => $m->id,
                    'matching_score' => (int) $m->match_score,
                    'match_explanation' => $m->match_explanation,
                    'matched_criteria' => $m->matched_criteria,
                ],
                'request' => $requestData,
                'property' => $prop ? [
                    'id' => $prop->id,
                    'name' => optional($prop->first_content)->title ?? null,
                    'featured_image' => $prop->featured_image_url ?? null,
                    'address' => $prop->address ?? null,
                    'for_rent_or_sale' => $prop->purpose ?? null,
                    'rent_or_buy_amount' => $prop->price ?? null,
                    'bedrooms' => $prop->beds ?? null,
                    'baths' => $prop->bath ?? null,
                    'area' => $prop->area ?? null,
                ] : null,
            ],
        ]);
    }

    // Deprecated legacy endpoints removed in observer-only architecture
}


