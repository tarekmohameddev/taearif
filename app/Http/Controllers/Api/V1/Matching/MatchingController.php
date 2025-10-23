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

    // 1) GET /api/v1/matching/requests
    public function indexRequests(Request $request)
    {
        DB::listen(function ($query) {
            Log::info('SQL Query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });
        $authUserId = $request->user()->id ?? null;
        Log::info('authUserId', ['authUserId' => $authUserId]);
        $source = $request->query('source', 'all');
        $status = $request->query('status', 'active');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        $requests = [];
        if ($source === 'all' || $source === 'web') {
            log::info('web query', ['authUserId' => $authUserId]);
            $web = \App\Models\Api\UserPropertyRequest::query()
                ->where('user_id', $authUserId)
                ->when($status === 'active', fn($q) => $q->where('is_active', 1))
                ->paginate($perPage, ['*'], 'page', $page);
            foreach ($web->items() as $row) {
                $matchAgg = PropertyMatch::selectRaw('count(*) as cnt, max(match_score) as top')
                    ->where('request_type', 'web')
                    ->where('user_id', $authUserId)
                    ->where('request_id', $row->id)
                    ->first();
                $requests[] = [
                    'id' => $row->id,
                    'source' => 'web',
                    'customer_name' => $row->full_name,
                    'phone' => $row->phone,
                    'property_type' => $row->property_type,
                    'location' => $row->region,
                    'budget_range' => ($row->budget_from && $row->budget_to) ? ($row->budget_from . ' - ' . $row->budget_to) : null,
                    'area_range' => ($row->area_from && $row->area_to) ? ($row->area_from . ' - ' . $row->area_to) : null,
                    'bedrooms' => null,
                    'urgency' => $row->seriousness,
                    'created_at' => $row->created_at,
                    'is_read' => (bool) $row->is_read,
                    'match_count' => (int) ($matchAgg->cnt ?? 0),
                    'top_match_score' => (int) ($matchAgg->top ?? 0),
                    'notes' => $row->notes,
                ];
            }
        }
        if ($source === 'all' || $source === 'whatsapp') {
            $wa = \App\Models\Api\ApiCustomerInquiry::query()
                ->where('user_id', $authUserId)
                ->paginate($perPage, ['*'], 'page', $page);
            foreach ($wa->items() as $row) {
                $matchAgg = PropertyMatch::selectRaw('count(*) as cnt, max(match_score) as top')
                    ->where('request_type', 'whatsapp')
                    ->where('user_id', $authUserId)
                    ->where('request_id', $row->id)
                    ->first();
                $requests[] = [
                    'id' => $row->id,
                    'source' => 'whatsapp',
                    'customer_name' => null,
                    'phone' => $row->phone_number,
                    'property_type' => $row->property_type,
                    'location' => $row->city ? ($row->city . ($row->district ? ', ' . $row->district : '')) : null,
                    'budget_range' => $row->budget,
                    'area_range' => ($row->min_area_sqm && $row->max_area_sqm) ? ($row->min_area_sqm . ' - ' . $row->max_area_sqm) : null,
                    'bedrooms' => $row->bedrooms,
                    'urgency' => $row->urgency,
                    'created_at' => $row->created_at,
                    'is_read' => null,
                    'match_count' => (int) ($matchAgg->cnt ?? 0),
                    'top_match_score' => (int) ($matchAgg->top ?? 0),
                    'notes' => $row->message,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $requests,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => null,
                    'last_page' => null,
                ],
            ],
        ]);
    }

    // 2) GET /api/v1/matching/requests/{id}/matches
    public function requestMatches(Request $request, int $id)
    {
        log::info('requestMatches', ['id' => $id]);
        $source = $request->query('source', 'web');
        $minScore = (int) $request->query('min_score', 60);
        $limit = (int) $request->query('limit', 50);
        $includeExplanation = filter_var($request->query('include_explanation', 'true'), FILTER_VALIDATE_BOOLEAN);
log::info('generateMatchesForRequest', ['source' => $source, 'id' => $id, 'limit' => $limit, 'user_id' => $request->user()->id ?? null]);
        $this->matching->generateMatchesForRequest($source, $id, $limit, true, $request->user()->id ?? null);

        $rows = PropertyMatch::query()
            ->where('request_type', $source)
            ->where('user_id', $request->user()->id)
            ->where('request_id', $id)
            ->where('match_score', '>=', $minScore)
            ->orderByDesc('match_score')
            ->limit($limit)
            ->get();

        $matches = $rows->map(function ($m) use ($includeExplanation) {
            $prop = \App\Models\User\RealestateManagement\Property::find($m->property_id);
            return [
                'property_id' => $m->property_id,
                'title' => optional($prop->first_content)->title ?? null,
                'price' => $prop->price ?? null,
                'currency' => null,
                'area' => $prop->area ?? null,
                'bedrooms' => $prop->beds ?? null,
                'bathrooms' => $prop->bath ?? null,
                'location' => null,
                'featured_image' => $prop->featured_image_url ?? null,
                'match_score' => (int) $m->match_score,
                'database_score' => (int) $m->database_score,
                'ai_score' => (int) $m->ai_score,
                'match_explanation' => $includeExplanation ? $m->match_explanation : null,
                'matched_criteria' => $m->matched_criteria,
                'property_status' => $prop->property_status ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'request' => [
                    'id' => $id,
                    'source' => $source,
                ],
                'matches' => $matches,
                'total_matches' => $rows->count(),
            ],
        ]);
    }

    // 3) GET /api/v1/matching/properties/{id}/requests
    public function propertyRequests(Request $request, int $id)
    {
        $minScore = (int) $request->query('min_score', 60);
        $status = $request->query('status', 'active');

        $rows = PropertyMatch::query()
            ->where('property_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('match_score', '>=', $minScore)
            ->orderByDesc('match_score')
            ->get();

        $matchingRequests = $rows->map(function ($m) {
            return [
                'request_id' => $m->request_id,
                'source' => $m->request_type,
                'match_score' => (int) $m->match_score,
                'urgency' => null,
                'created_at' => $m->created_at,
                'is_contacted' => (bool) $m->is_contacted,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'property' => [ 'id' => $id ],
                'matching_requests' => $matchingRequests,
                'total_matches' => $rows->count(),
            ],
        ]);
    }

    // 4) POST /api/v1/matching/requests/{id}/mark-read
    public function markRead(Request $request, int $id)
    {
        $source = $request->query('source', 'web');
        $isRead = (bool) $request->boolean('is_read', true);

        if ($source === 'web') {
            $row = \App\Models\Api\UserPropertyRequest::findOrFail($id);
            $row->is_read = $isRead;
            $row->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Request marked as read',
            'data' => [
                'request_id' => (string) $id,
                'is_read' => $isRead,
                'updated_at' => now(),
            ],
        ]);
    }

    // 5) POST /api/v1/matching/bulk-rematch
    public function bulkRematch(Request $request)
    {
        $entityType = $request->input('entity_type');
        $ids = $request->input('entity_ids', []);
        $forceAi = (bool) $request->boolean('force_ai_rescore', true);

        if ($entityType === 'requests') {
            foreach ($ids as $id) {
                foreach (['web', 'whatsapp'] as $src) {
                    $this->matching->generateMatchesForRequest($src, (int) $id, 25, $forceAi, $request->user()->id ?? null);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk matching initiated',
            'data' => [
                'job_id' => null,
                'entities_queued' => count($ids),
                'estimated_completion' => now()->addMinutes(1),
            ],
        ]);
    }

    // 6) GET /api/v1/matching/stats
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_active_requests' => \App\Models\Api\UserPropertyRequest::where('user_id', request()->user()->id)->where('is_active', 1)->count(),
                'unread_requests' => \App\Models\Api\UserPropertyRequest::where('user_id', request()->user()->id)->where('is_read', 0)->count(),
                'total_matches_generated' => PropertyMatch::where('user_id', request()->user()->id)->count(),
                'average_match_score' => (int) (PropertyMatch::where('user_id', request()->user()->id)->avg('match_score') ?? 0),
                'requests_by_source' => [
                    'web' => PropertyMatch::where('user_id', request()->user()->id)->where('request_type', 'web')->distinct('request_id')->count('request_id'),
                    'whatsapp' => PropertyMatch::where('user_id', request()->user()->id)->where('request_type', 'whatsapp')->distinct('request_id')->count('request_id'),
                ],
                'high_urgency_requests' => 0,
            ],
        ]);
    }
}


