<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V2\CustomersHub\RequestsListRequest;
use App\Http\Requests\Api\V2\CustomersHub\RequestUpdateRequest;
use App\Http\Requests\Api\V2\CustomersHub\AddNoteRequest;
use App\Http\Requests\Api\V2\CustomersHub\CreateAppointmentRequest;
use App\Http\Requests\Api\V2\CustomersHub\CreateReminderRequest;
use App\Http\Requests\Api\V2\CustomersHub\BulkCompleteRequest;
use App\Http\Requests\Api\V2\CustomersHub\BulkDismissRequest;
use App\Http\Requests\Api\V2\CustomersHub\RequestsBulkRequest;
use App\Http\Requests\Api\V2\CustomersHub\DismissRequest;
use App\Http\Requests\Api\V2\CustomersHub\SnoozeRequest;
use App\Domain\CustomersHub\Services\ActionsAggregatorService;
use App\Domain\CustomersHub\Services\CustomersHubCacheVersion;
use App\Domain\CustomersHub\Services\CustomersHubNotificationService;
use App\Domain\CustomersHub\Services\CustomersHubPropertyRequestNotifier;
use App\Domain\CustomersHub\Services\PropertyRequestDetailBuilder;
use App\Domain\PropertyRequests\Services\PropertyRequestLocationNormalizer;
use App\Domain\PropertyRequests\Services\PropertyRequestLinkSync;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserPropertyRequest;
use App\Models\CustomersHub\CustomersHubStage;
use App\Models\User;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * RequestsController
 *
 * API endpoints for Customers Hub Requests Center.
 * Implements read-only aggregation from legacy tables.
 *
 * Routes:
 * - POST /api/v2/customers-hub/requests/list
 * - GET  /api/v2/customers-hub/requests/filter-options
 * - GET  /api/v2/customers-hub/requests/{requestId}
 * - PATCH /api/v2/customers-hub/requests/{requestId}
 * - POST /api/v2/customers-hub/requests/{requestId}/notes
 * - POST /api/v2/customers-hub/requests/{requestId}/complete
 * - POST /api/v2/customers-hub/requests/{requestId}/dismiss
 * - POST /api/v2/customers-hub/requests/bulk
 * - POST /api/v2/customers-hub/requests/bulk-complete
 * - POST /api/v2/customers-hub/requests/bulk-dismiss
 * - POST /api/v2/customers-hub/requests/mark-viewed
 */
class RequestsController extends ApiController
{
    /**
     * Chunk size for WHERE IN when hydrating list rows (appointments, reminders, property ids).
     * Avoids oversized bound-parameter lists and keeps each round-trip bounded per tenant.
     */
    private const CH_LIST_WHERE_IN_CHUNK = 500;

    private ActionsAggregatorService $aggregator;

    private PropertyRequestDetailBuilder $propertyRequestDetailBuilder;

    private CustomersHubPropertyRequestNotifier $propertyRequestNotifier;

    private CustomersHubNotificationService $notificationService;

    private CustomersHubCacheVersion $cacheVersion;

    public function __construct(
        ActionsAggregatorService $aggregator,
        PropertyRequestDetailBuilder $propertyRequestDetailBuilder,
        CustomersHubPropertyRequestNotifier $propertyRequestNotifier,
        CustomersHubNotificationService $notificationService,
        CustomersHubCacheVersion $cacheVersion
    ) {
        $this->aggregator = $aggregator;
        $this->propertyRequestDetailBuilder = $propertyRequestDetailBuilder;
        $this->propertyRequestNotifier = $propertyRequestNotifier;
        $this->notificationService = $notificationService;
        $this->cacheVersion = $cacheVersion;
    }

    /**
     * POST /api/v2/customers-hub/requests/list
     *
     * Get paginated list of customer actions with filtering.
     *
     * Status filtering: sending `tab` (e.g. `all`, `completed`) applies status rules in ActionsAggregatorService::applyFilters.
     * To include every Customers Hub status (pending through completed/dismissed, etc.), omit both `tab` and `statuses`.
     * To limit to property requests only while keeping all statuses, omit `tab` and `statuses` and pass `objectTypes: ["property_request"]`
     * (and/or `types: ["property_match"]` per your UI).
     */
    public function list(RequestsListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $filters = $validated;
        $limit = $validated['limit'] ?? 25;
        $offset = $validated['offset'] ?? 0;
        $statsFilters = $validated;
        unset($statsFilters['limit'], $statsFilters['offset'], $statsFilters['sort_by'], $statsFilters['sort_dir']);

        // isUpdated flag depends on per-viewer last viewed timestamp, so include it in the cache key.
        // Use a short-lived (10s) micro-cache for the viewed_at value itself.
        $viewerId = $request->user()->id;
        $viewedAtRaw = Cache::remember("ch:viewed:{$viewerId}", 10, function () use ($viewerId) {
            return DB::table('customers_hub_requests_list_viewed')
                ->where('user_id', $viewerId)
                ->value('viewed_at');
        });
        $viewedAt = $viewedAtRaw ? Carbon::parse($viewedAtRaw) : null;

        $unreadPropertyRequestSourceIds = $this->notificationService->getUnreadPropertyRequestSourceIds($viewerId);
        $filters['_unread_property_request_source_ids'] = $unreadPropertyRequestSourceIds;
        $unreadSourceIdSet = array_flip($unreadPropertyRequestSourceIds);

        $cacheKey = 'ch:reqs:list:'
            . $userId . ':v'
            . $this->cacheVersion->getVersion($userId) . ':'
            . $viewerId . ':'
            . ($viewedAt?->toIso8601String() ?? 'null') . ':'
            . md5(json_encode(array_values($unreadPropertyRequestSourceIds))) . ':'
            . md5(json_encode([
                'filters' => $filters,
                'limit' => $limit,
                'offset' => $offset,
            ]));

        $payload = Cache::remember($cacheKey, 30, function () use ($userId, $filters, $statsFilters, $limit, $offset, $viewedAt, $unreadSourceIdSet) {
            // Get list
            $result = $this->aggregator->getList($userId, $filters, $limit, $offset);

            $items = $result['items'];
            $propertyRequestSourceIds = $items->filter(function ($item) {
                return ($item->objectType ?? '') === 'property_request';
            })->pluck('sourceId')->filter()->unique()->values()->all();
            $inquirySourceIds = $items->filter(function ($item) {
                return ($item->objectType ?? '') === 'inquiry';
            })->pluck('sourceId')->filter()->unique()->values()->all();

            // Page-level enrichment to keep the response contract stable while keeping
            // the UNION query slim (no pre-pagination joins for cities/districts/assignee names).
            // - city: derived from users_property_requests.city_id -> user_cities.name_ar
            // - districtAR: derived from users_property_requests.districts_id -> user_districts.name_ar
            // - assignedToName: derived from users.id for any non-null assignedTo values
            if (!empty($propertyRequestSourceIds)) {
                $uprRows = [];
                foreach (array_chunk($propertyRequestSourceIds, self::CH_LIST_WHERE_IN_CHUNK) as $idChunk) {
                    $uprRows = array_merge($uprRows, DB::table('users_property_requests')
                        ->where('user_id', $userId)
                        ->whereIn('id', $idChunk)
                        ->get(['id', 'city_id', 'districts_id'])
                        ->all());
                }

                $cityIds = [];
                $districtIds = [];
                $uprById = [];
                foreach ($uprRows as $r) {
                    $uprById[(int) $r->id] = $r;
                    if (!empty($r->city_id)) {
                        $cityIds[] = (int) $r->city_id;
                    }
                    if (!empty($r->districts_id)) {
                        $districtIds[] = (int) $r->districts_id;
                    }
                }
                $cityIds = array_values(array_unique($cityIds));
                $districtIds = array_values(array_unique($districtIds));

                $cityNameById = empty($cityIds) ? [] : DB::table('user_cities')
                    ->whereIn('id', $cityIds)
                    ->pluck('name_ar', 'id')
                    ->mapWithKeys(fn ($v, $k) => [(int) $k => $v !== null ? (string) $v : null])
                    ->all();

                $districtById = [];
                if (!empty($districtIds)) {
                    $districtById = DB::table('user_districts')
                        ->whereIn('id', $districtIds)
                        ->get(['id', 'name_ar', 'city_name_ar'])
                        ->mapWithKeys(fn ($d) => [(int) $d->id => [
                            'districtAR' => $d->name_ar !== null ? (string) $d->name_ar : null,
                            'cityAR' => $d->city_name_ar !== null ? (string) $d->city_name_ar : null,
                        ]])
                        ->all();
                }

                $items->each(function ($item) use ($uprById, $cityNameById, $districtById) {
                    if (($item->objectType ?? '') !== 'property_request' || empty($item->sourceId)) {
                        return;
                    }
                    $upr = $uprById[(int) $item->sourceId] ?? null;
                    if (!$upr) {
                        return;
                    }

                    if (($item->city ?? null) === null && !empty($upr->city_id)) {
                        $item->city = $cityNameById[(int) $upr->city_id] ?? null;
                    }
                    if (!empty($upr->districts_id) && isset($districtById[(int) $upr->districts_id])) {
                        $d = $districtById[(int) $upr->districts_id];
                        if (($item->districtAR ?? null) === null) {
                            $item->districtAR = $d['districtAR'];
                        }
                        if (($item->city ?? null) === null) {
                            $item->city = $d['cityAR'];
                        }
                    }
                });
            }

            $assigneeIds = $items->pluck('assignedTo')->filter()->unique()->values()->all();
            if (!empty($assigneeIds)) {
                $nameByUserId = DB::table('users')
                    ->whereIn('id', $assigneeIds)
                    ->get(['id', 'first_name', 'last_name'])
                    ->mapWithKeys(fn ($u) => [(int) $u->id => trim((string) ($u->first_name ?? '') . ' ' . (string) ($u->last_name ?? ''))])
                    ->all();

                $items->each(function ($item) use ($nameByUserId) {
                    if (!empty($item->assignedTo) && (empty($item->assignedToName) || trim((string) $item->assignedToName) === '')) {
                        $item->assignedToName = $nameByUserId[(int) $item->assignedTo] ?? '';
                    }
                });
            }

            $now = Carbon::now();
            $appointmentsByRequest = $this->batchLoadFormattedByForeignKey(
                'property_request_appointments',
                $userId,
                'property_request_id',
                $propertyRequestSourceIds,
                'datetime',
                fn ($row) => $this->propertyRequestDetailBuilder->formatPropertyRequestAppointment($row)
            );
            $remindersByRequest = $this->batchLoadFormattedByForeignKey(
                'property_request_reminders',
                $userId,
                'property_request_id',
                $propertyRequestSourceIds,
                'datetime',
                fn ($row) => $this->propertyRequestDetailBuilder->formatPropertyRequestReminder($row, $now)
            );
            $appointmentsByInquiry = $this->batchLoadFormattedByForeignKey(
                'inquiry_appointments',
                $userId,
                'inquiry_id',
                $inquirySourceIds,
                'datetime',
                fn ($row) => $this->propertyRequestDetailBuilder->formatPropertyRequestAppointment($row)
            );
            $remindersByInquiry = $this->batchLoadFormattedByForeignKey(
                'inquiry_reminders',
                $userId,
                'inquiry_id',
                $inquirySourceIds,
                'datetime',
                fn ($row) => $this->propertyRequestDetailBuilder->formatPropertyRequestReminder($row, $now)
            );

            // Load property_ids per property request and batch-load property summaries
            $propertiesByRequestId = [];
            $propertyIdsByRequestId = [];
            $projectsByRequestId = [];
            $projectIdsByRequestId = [];
            if (!empty($propertyRequestSourceIds)) {
                foreach (array_chunk($propertyRequestSourceIds, self::CH_LIST_WHERE_IN_CHUNK) as $idChunk) {
                    $requestRows = DB::table('users_property_requests')
                        ->where('user_id', $userId)
                        ->whereIn('id', $idChunk)
                        ->get(['id', 'property_ids']);
                    foreach ($requestRows as $row) {
                        $ids = $row->property_ids;
                        if (is_string($ids)) {
                            $decoded = json_decode($ids, true);
                            $ids = is_array($decoded) ? $decoded : [];
                        }
                        $ids = is_array($ids) ? $ids : [];
                        $propertyIdsByRequestId[(int) $row->id] = array_values(array_unique(array_filter(array_map(function ($id) {
                            return is_numeric($id) ? (int) $id : null;
                        }, $ids))));
                    }
                }
                $mergedPropertyIds = [];
                foreach ($propertyIdsByRequestId as $ids) {
                    foreach ($ids as $pid) {
                        $mergedPropertyIds[] = $pid;
                    }
                }
                $allPropertyIds = array_values(array_unique($mergedPropertyIds));
                $summariesById = $this->propertyRequestDetailBuilder->getPropertySummariesForIds($userId, $allPropertyIds);
                foreach ($propertyIdsByRequestId as $requestId => $ids) {
                    $propertiesByRequestId[$requestId] = array_values(array_filter(array_map(function ($id) use ($summariesById) {
                        return $summariesById[$id] ?? null;
                    }, $ids)));
                }

                foreach (array_chunk($propertyRequestSourceIds, self::CH_LIST_WHERE_IN_CHUNK) as $idChunk) {
                    $rows = DB::table('property_request_project')
                        ->whereIn('property_request_id', $idChunk)
                        ->orderBy('id')
                        ->get(['property_request_id', 'project_id']);
                    foreach ($rows as $row) {
                        $projectIdsByRequestId[(int) $row->property_request_id][] = (int) $row->project_id;
                    }
                }
                foreach ($propertyRequestSourceIds as $sourceId) {
                    $projectIdsByRequestId[(int) $sourceId] = array_values(array_unique(
                        $projectIdsByRequestId[(int) $sourceId] ?? []
                    ));
                }
                $allProjectIds = array_values(array_unique(array_merge([], ...array_values($projectIdsByRequestId))));
                $projectSummaries = $this->propertyRequestDetailBuilder->getProjectSummariesForIds($userId, $allProjectIds);
                foreach ($projectIdsByRequestId as $requestId => $ids) {
                    $projectsByRequestId[$requestId] = array_values(array_filter(array_map(
                        fn ($id) => $projectSummaries[$id] ?? null,
                        $ids
                    )));
                }
            }

            $items->each(function ($item) use ($appointmentsByRequest, $remindersByRequest, $appointmentsByInquiry, $remindersByInquiry, $propertiesByRequestId, $propertyIdsByRequestId, $projectsByRequestId, $projectIdsByRequestId) {
                if (($item->objectType ?? '') === 'property_request' && isset($item->sourceId)) {
                    $item->appointments = $appointmentsByRequest[$item->sourceId] ?? [];
                    $item->reminders = $remindersByRequest[$item->sourceId] ?? [];
                    $item->properties = $propertiesByRequestId[$item->sourceId] ?? [];
                    $item->property_ids = $propertyIdsByRequestId[$item->sourceId] ?? [];
                    $item->projects = $projectsByRequestId[$item->sourceId] ?? [];
                    $item->project_ids = $projectIdsByRequestId[$item->sourceId] ?? [];
                    $item->project_id = $item->project_ids[0] ?? null;
                } elseif (($item->objectType ?? '') === 'inquiry' && isset($item->sourceId)) {
                    $item->appointments = $appointmentsByInquiry[$item->sourceId] ?? [];
                    $item->reminders = $remindersByInquiry[$item->sourceId] ?? [];
                    $item->properties = [];
                    $item->property_ids = [];
                    $item->projects = [];
                    $item->project_ids = [];
                } else {
                    $item->appointments = [];
                    $item->reminders = [];
                    $item->properties = [];
                    $item->property_ids = [];
                    $item->projects = [];
                    $item->project_ids = [];
                }
            });

            $items->each(function ($item) use ($viewedAt) {
                if ($viewedAt === null) {
                    $item->isUpdated = false;
                    return;
                }
                $createdAt = $item->createdAt ? Carbon::parse($item->createdAt) : null;
                $updatedAt = $item->updatedAt ? Carbon::parse($item->updatedAt) : null;
                $item->isUpdated = $createdAt !== null
                    && $updatedAt !== null
                    && $createdAt->lte($viewedAt)
                    && $updatedAt->gt($viewedAt);
            });

            $items->each(function ($item) use ($unreadSourceIdSet) {
                if (($item->objectType ?? '') === 'property_request' && ! empty($item->sourceId)) {
                    $item->isUnread = isset($unreadSourceIdSet[(int) $item->sourceId]);
                }
            });

            // Get stats
            $stats = $this->aggregator->getStats($userId, $statsFilters);
            $comparison = $this->aggregator->getComparisonStats($userId, $statsFilters);
            $stats = array_merge($stats, $comparison);

            // All-time property-request stats (broker scoped only; intentionally ignores list filters/date ranges)
            // Cache these for 120 seconds since they change infrequently. Keyed by the
            // tenant's cache version so pipeline moves (deal_completed/deal_rejected)
            // invalidate this immediately instead of waiting out the TTL.
            $globalCountsKey = 'ch_global_counts_' . $userId . '_v' . $this->cacheVersion->getVersion($userId);
            $globalCounts = Cache::remember($globalCountsKey, 120, function () use ($userId) {
                $dealClosed = (int) DB::table('users_property_requests as upr')
                    ->where('upr.user_id', $userId)
                    ->where('upr.is_active', 1)
                    ->where('upr.customers_hub_stage_id', 'deal_completed')
                    ->count();

                $dealNotClosed = (int) DB::table('users_property_requests as upr')
                    ->where('upr.user_id', $userId)
                    ->where('upr.is_active', 1)
                    ->where('upr.customers_hub_stage_id', 'deal_rejected')
                    ->count();

                // Align underProcess with dealClosed/dealNotClosed: count by pipeline
                // stage, not workflow status_id. pipeline/move only updates
                // customers_hub_stage_id, so status-based counting left this card stale.
                $underProcessQuery = DB::table('users_property_requests as upr')
                    ->where('upr.user_id', $userId)
                    ->where('upr.is_active', 1)
                    ->where(function ($q) {
                        $q->whereNull('upr.customers_hub_stage_id')
                            ->orWhereNotIn('upr.customers_hub_stage_id', ['deal_completed', 'deal_rejected']);
                    });
                if (Schema::hasColumn('users_property_requests', 'is_archived')) {
                    $underProcessQuery->where(function ($w) {
                        $w->where('upr.is_archived', 0)->orWhereNull('upr.is_archived');
                    });
                }
                $underProcess = (int) $underProcessQuery->count();

                $total = (int) DB::table('users_property_requests as upr')
                    ->where('upr.user_id', $userId)
                    ->where('upr.is_active', 1)
                    ->count();

                return [
                    'underProcess' => $underProcess,
                    'dealClosed' => $dealClosed,
                    'dealNotClosed' => $dealNotClosed,
                    'total' => $total,
                ];
            });

            $stats = array_merge($stats, $globalCounts);

            try {
                $stageFilters = $statsFilters;
                unset($stageFilters['excludeStatuses']);
                $stages = $this->aggregator->getStageStats($userId, $stageFilters);
            } catch (\Throwable $e) {
                $stages = [];
            }

            return [
                'actions' => $items,
                'stats' => $stats,
                'stages' => $stages,
                'pagination' => [
                    'total' => $result['total'],
                    'limit' => $result['limit'],
                    'offset' => $result['offset'],
                    'hasMore' => $result['hasMore'],
                    'sortBy' => $result['sortBy'],
                    'sortDir' => $result['sortDir'],
                ],
            ];
        });

        return $this->success($payload);
    }

    /**
     * POST /api/v2/customers-hub/requests/mark-viewed
     *
     * Mark the requests list as viewed by the current user (viewer). Used to compute isUpdated
     * per action on the next list load. Always uses server now(); client-provided timestamp is ignored.
     */
    public function markListViewed(Request $request): JsonResponse
    {
        $viewerId = $request->user()->id;
        $now = now();
        DB::table('customers_hub_requests_list_viewed')->upsert(
            [
                [
                    'user_id' => $viewerId,
                    'viewed_at' => $now,
                ],
            ],
            ['user_id'],
            ['viewed_at']
        );
        
        // Invalidate the micro-cache so the new viewed_at is picked up on next request
        Cache::forget("ch:viewed:{$viewerId}");
        
        return $this->success(['viewedAt' => $now->toIso8601String()]);
    }

    /**
     * GET /api/v2/customers-hub/requests/filter-options
     *
     * Get available filter options for the requests center.
     * Cached for 30 minutes per user.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $cacheKey = "ch:reqs:filter-options:{$userId}";

        $data = Cache::remember($cacheKey, 1800, function () use ($userId) {
            // Action types
            $types = [
                ['id' => 'new_inquiry', 'label' => 'استفسار جديد', 'labelEn' => 'New Inquiry'],
                ['id' => 'callback_request', 'label' => 'طلب اتصال', 'labelEn' => 'Callback Request'],
                ['id' => 'whatsapp_incoming', 'label' => 'رسالة واتساب', 'labelEn' => 'WhatsApp Message'],
                ['id' => 'property_match', 'label' => 'عقار مطابق', 'labelEn' => 'Property Match'],
                ['id' => 'follow_up', 'label' => 'متابعة', 'labelEn' => 'Follow-up'],
                ['id' => 'site_visit', 'label' => 'معاينة', 'labelEn' => 'Site Visit'],
            ];

            // Statuses
            $statuses = [
                ['id' => 'pending', 'label' => 'قيد الانتظار', 'labelEn' => 'Pending'],
                ['id' => 'in_progress', 'label' => 'قيد التنفيذ', 'labelEn' => 'In Progress'],
                ['id' => 'snoozed', 'label' => 'مؤجل', 'labelEn' => 'Snoozed'],
                ['id' => 'completed', 'label' => 'مكتمل', 'labelEn' => 'Completed'],
                ['id' => 'dismissed', 'label' => 'مرفوض', 'labelEn' => 'Dismissed'],
            ];

            // Priorities
            $priorities = [
                ['id' => 'urgent', 'label' => 'عاجل', 'labelEn' => 'Urgent', 'color' => '#dc3545'],
                ['id' => 'high', 'label' => 'عالي', 'labelEn' => 'High', 'color' => '#fd7e14'],
                ['id' => 'medium', 'label' => 'متوسط', 'labelEn' => 'Medium', 'color' => '#ffc107'],
                ['id' => 'low', 'label' => 'منخفض', 'labelEn' => 'Low', 'color' => '#28a745'],
            ];

            // Sources: distinct values from users_property_requests.source for this tenant
            $sourceValues = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->whereNotNull('source')
                ->distinct()
                ->orderBy('source')
                ->pluck('source');
            $sources = $sourceValues->map(fn (string $value) => [
                'id' => $value,
                'label' => $this->sourceLabel($value, 'ar'),
                'labelEn' => $this->sourceLabel($value, 'en'),
            ])->values()->all();

            // Due date buckets
            $dueDateBuckets = [
                ['id' => 'overdue', 'label' => 'متأخر', 'labelEn' => 'Overdue'],
                ['id' => 'today', 'label' => 'اليوم', 'labelEn' => 'Today'],
                ['id' => 'week', 'label' => 'هذا الأسبوع', 'labelEn' => 'This Week'],
                ['id' => 'no_date', 'label' => 'بدون موعد', 'labelEn' => 'No Date'],
            ];

            // Object types (for filtering by kind of record)
            // request_appointment / request_reminder are nested on property_request rows, not separate list actions
            $objectTypes = [
                ['id' => 'inquiry', 'label' => 'استفسار', 'labelEn' => 'Inquiry'],
                ['id' => 'property_request', 'label' => 'طلب عقار', 'labelEn' => 'Property Request'],
                ['id' => 'reminder', 'label' => 'تذكير', 'labelEn' => 'Reminder'],
            ];

            // Appointment types (for filtering property requests by appointment type)
            $appointmentTypes = [
                ['id' => 'site_visit', 'label' => 'معاينة', 'labelEn' => 'Site visit'],
                ['id' => 'office_meeting', 'label' => 'اجتماع مكتب', 'labelEn' => 'Office meeting'],
                ['id' => 'phone_call', 'label' => 'اتصال هاتفي', 'labelEn' => 'Phone call'],
                ['id' => 'video_call', 'label' => 'مكالمة فيديو', 'labelEn' => 'Video call'],
                ['id' => 'contract_signing', 'label' => 'توقيع عقد', 'labelEn' => 'Contract signing'],
                ['id' => 'other', 'label' => 'أخرى', 'labelEn' => 'Other'],
            ];

            // Pipeline stages (customers_hub_stages) for request list filtering
            $presenter = app(\App\Domain\CustomersHub\Services\CustomersHubStagesPresenter::class);
            $stages = $presenter->listStages($userId, true)
                ->map(fn ($s) => [
                    'id' => (int) $s->id,
                    'stage_id' => $s->stage_id,
                    'label' => $s->stage_name_ar,
                    'labelEn' => $s->stage_name_en ?? $s->stage_name_ar,
                ])
                ->values()
                ->all();

            // Customer types (user-defined)
            $customerTypes = UserApiCustomerType::where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'value', 'icon', 'color']);

            // Customer priorities (user-defined)
            $customerPriorities = UserApiCustomerPriority::where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'value', 'icon', 'color']);

            // Employees (assignees)
            $employees = DB::table('users_property_requests as upr')
                ->join('users as u', 'u.id', '=', 'upr.responsible_employee_id')
                ->where('upr.user_id', $userId)
                ->where('upr.is_active', 1)
                ->whereNotNull('upr.responsible_employee_id')
                ->where('u.tenant_id', $userId)
                ->where('u.account_type', 'employee')
                ->where('u.active', true)
                ->groupBy('u.id', 'u.first_name', 'u.last_name', 'u.email')
                ->selectRaw('u.id, u.first_name, u.last_name, u.email, COUNT(*) as propertyRequestsCount')
                ->orderByDesc('propertyRequestsCount')
                ->get()
                ->map(fn ($e) => [
                    'id' => (int) $e->id,
                    'label' => trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? '')),
                    'email' => $e->email,
                    'propertyRequestsCount' => (int) $e->propertyRequestsCount,
                ])
                ->values();

            // Single query: fetch all distinct districts for this tenant
            $districtRows = DB::table('users_property_requests as upr')
                ->join('user_districts as d', 'upr.districts_id', '=', 'd.id')
                ->where('upr.user_id', $userId)
                ->whereNotNull('upr.districts_id')
                ->distinct()
                ->orderBy('d.name_ar')
                ->get(['d.id', 'd.city_id', 'd.city_name_ar', 'd.city_name_en', 'd.name_ar as label', 'd.name_en as labelEn']);

            // Derive districts list
            $districts = $districtRows->map(fn ($d) => [
                'value'   => (int) $d->id,
                'label'   => $d->label ?? '',
                'labelEn' => $d->labelEn ?? $d->label ?? '',
                'cityId'  => $d->city_id !== null ? (int) $d->city_id : null,
            ])->values()->all();

            // Derive cities list from same rows (unique by city_id, sorted by city_name_ar)
            $cities = $districtRows
                ->filter(fn ($d) => $d->city_id !== null)
                ->unique('city_id')
                ->sortBy('city_name_ar')
                ->map(fn ($d) => [
                    'value'   => (int) $d->city_id,
                    'label'   => $d->city_name_ar ?? '',
                    'labelEn' => $d->city_name_en ?? $d->city_name_ar ?? '',
                ])->values()->all();

            return [
                'types' => $types,
                'statuses' => $statuses,
                'priorities' => $priorities,
                'sources' => $sources,
                'objectTypes' => $objectTypes,
                'appointmentTypes' => $appointmentTypes,
                'dueDateBuckets' => $dueDateBuckets,
                'stages' => $stages,
                'customerTypes' => $customerTypes,
                'customerPriorities' => $customerPriorities,
                'employees' => $employees,
                'cities' => $cities,
                'districts' => $districts,
            ];
        });

        return $this->success($data);
    }

    /**
     * GET /api/v2/customers-hub/requests/{requestId}
     *
     * Get single action detail with related actions.
     */
    public function show(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);

        if (!$action) {
            return $this->error('Action not found', 404);
        }

        $isPropertyRequestAction = (($action->objectType ?? '') === 'property_request')
            && (($action->sourceTable ?? '') === 'users_property_requests')
            && !empty($action->sourceId);
        $isInquiryAction = (($action->objectType ?? '') === 'inquiry')
            && (($action->sourceTable ?? '') === 'api_customer_inquiry')
            && !empty($action->sourceId);

        if ($isPropertyRequestAction) {
            $fullAction = $this->propertyRequestDetailBuilder->buildFullPropertyRequestAction($userId, $action);
            if ($fullAction === null) {
                return $this->error('Action not found', 404);
            }
            $action = $fullAction;

            $viewerId = (int) $request->user()->id;
            $propertyRequestId = (int) $action['sourceId'];
            $unreadCategories = $this->notificationService->buildUnreadCategoriesBreakdown($viewerId, $propertyRequestId);
            $this->notificationService->markPropertyRequestNotificationsRead($viewerId, $propertyRequestId);

            $action['unreadCategories'] = $unreadCategories;
            $action['isUnread'] = false;
        } elseif ($isInquiryAction) {
            $fullAction = $this->propertyRequestDetailBuilder->buildFullInquiryAction($userId, $action);
            if ($fullAction === null) {
                return $this->error('Action not found', 404);
            }
            $action = $fullAction;
        } else {
            $action->appointments = [];
            $action->reminders = [];
        }

        // Get related actions for the same customer
        $related = $this->aggregator->getRelated($userId, $requestId, [], 5);

        return $this->success([
            'action' => $action,
            'related' => $related['items'],
        ]);
    }

    /**
     * GET /api/v2/customers-hub/requests/{requestId}/stats
     *
     * Get stats for a specific action's customer.
     */
    public function actionStats(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);

        if (!$action) {
            return $this->error('Action not found', 404);
        }

        if (!$action->customerId) {
            return $this->success([
                'customerStats' => null,
            ]);
        }

        // Get stats for this customer
        $customerStats = $this->aggregator->getStats($userId, [
            'customer_id' => $action->customerId,
        ]);

        return $this->success([
            'customerStats' => $customerStats,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/complete
     *
     * Mark an action as completed.
     */
    public function complete(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $success = $this->aggregator->completeAction($userId, $requestId);

        if (!$success) {
            return $this->error('Failed to complete action', 422);
        }

        $this->dispatchPropertyRequestNotificationFromAction(
            $userId,
            $requestId,
            CustomersHubNotificationService::TYPE_COMPLETED,
            'Property request completed',
            'A property request was marked as completed',
            (int) $request->user()->id
        );

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action completed successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/dismiss
     *
     * Dismiss an action.
     */
    public function dismiss(DismissRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        // Save dismiss reason as a note on the action
        $this->aggregator->addNoteToAction($userId, $requestId, $validated['reason'], 'current_user');

        $success = $this->aggregator->dismissAction($userId, $requestId);

        if (!$success) {
            return $this->error('Failed to dismiss action', 422);
        }

        $this->dispatchPropertyRequestNotificationFromAction(
            $userId,
            $requestId,
            CustomersHubNotificationService::TYPE_DISMISSED,
            'Property request dismissed',
            'A property request was dismissed',
            (int) $request->user()->id,
            ['reason' => $validated['reason'] ?? null]
        );

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action dismissed successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/snooze
     *
     * Snooze an action (where supported).
     */
    public function snooze(SnoozeRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $snoozedBy = $request->user()->id;

        if (!empty($validated['reason'])) {
            $this->aggregator->addNoteToAction($userId, $requestId, $validated['reason'], 'current_user');
        }

        $success = $this->aggregator->snoozeAction($userId, $requestId, $validated['snoozedUntil'], $snoozedBy);

        if (!$success) {
            return $this->error('Failed to snooze action or snooze is not supported for this request type', 422);
        }

        $this->dispatchPropertyRequestNotificationFromAction(
            $userId,
            $requestId,
            CustomersHubNotificationService::TYPE_SNOOZED,
            'Property request snoozed',
            'A property request was snoozed',
            (int) $snoozedBy,
            [
                'snoozedUntil' => $validated['snoozedUntil'],
                'reason' => $validated['reason'] ?? null,
            ]
        );

        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action snoozed successfully',
            'actionId' => $requestId,
            'snoozedUntil' => $validated['snoozedUntil'],
            'snoozedBy' => $snoozedBy,
        ]);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}
     *
     * Update an action (partial update).
     */
    public function update(RequestUpdateRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);
        if (!$action) {
            return $this->error('Action not found', 404);
        }

        $propertyRequestId = CustomersHubPropertyRequestNotifier::parsePropertyRequestId($requestId);
        $oldStageId = null;
        $oldPriority = null;
        if ($propertyRequestId !== null) {
            $prRow = DB::table('users_property_requests')
                ->where('id', $propertyRequestId)
                ->where('user_id', $userId)
                ->first(['customers_hub_stage_id', 'seriousness']);
            if ($prRow) {
                $oldStageId = $prRow->customers_hub_stage_id;
                $oldPriority = $prRow->seriousness;
            }
        }

        // Resolve pipeline stage: stage_id (string) or status_id (integer -> lookup customers_hub_stages.id)
        $stageIdString = null;
        if (array_key_exists('stage_id', $validated) && $validated['stage_id'] !== null && $validated['stage_id'] !== '') {
            $stageIdString = DB::table('customers_hub_stages')
                ->where('stage_id', $validated['stage_id'])
                ->where('is_active', true)
                ->where(function ($w) use ($userId) {
                    $w->where('is_system', true)->orWhere('user_id', $userId);
                })
                ->value('stage_id');
        } elseif (array_key_exists('status_id', $validated) && $validated['status_id'] !== null) {
            $stageIdString = DB::table('customers_hub_stages')
                ->where('id', (int) $validated['status_id'])
                ->where('is_active', true)
                ->where(function ($w) use ($userId) {
                    $w->where('is_system', true)->orWhere('user_id', $userId);
                })
                ->value('stage_id');
        }

        if ($stageIdString !== null) {
            if (($action->sourceTable ?? '') === 'users_property_requests' && !empty($action->sourceId)) {
                DB::table('users_property_requests')
                    ->where('id', $action->sourceId)
                    ->where('user_id', $userId)
                    ->update(['customers_hub_stage_id' => $stageIdString, 'updated_at' => now()]);
                // Raw DB::table() update bypasses Eloquent model events; bump explicitly.
                $this->cacheVersion->bump($userId);
            } elseif (($action->sourceTable ?? '') === 'api_customer_inquiry' && !empty($action->sourceId)) {
                DB::table('api_customer_inquiry')
                    ->where('id', $action->sourceId)
                    ->where('user_id', $userId)
                    ->update(['stage_id' => $stageIdString, 'updated_at' => now()]);
                // Raw DB::table() update bypasses Eloquent model events; bump explicitly.
                $this->cacheVersion->bump($userId);
            }
        }

        unset($validated['status_id'], $validated['stage_id']);

        $success = $this->aggregator->updateAction($userId, $requestId, $validated);
        if (!$success && !empty($validated)) {
            return $this->error('Failed to update action', 422);
        }

        $this->invalidateFilterOptionsCache($userId);

        $updated = $this->aggregator->getById($userId, $requestId);
        $actorUserId = (int) $request->user()->id;

        if ($propertyRequestId !== null) {
            if ($stageIdString !== null && $stageIdString !== $oldStageId) {
                $this->propertyRequestNotifier->notifyStageChanged(
                    $userId,
                    $propertyRequestId,
                    $oldStageId,
                    $stageIdString,
                    $actorUserId
                );
            }

            if (array_key_exists('priority', $validated) && ($validated['priority'] ?? null) !== null) {
                $newPriority = $validated['priority'];
                if ($newPriority !== $oldPriority) {
                    $this->propertyRequestNotifier->notifyPriorityChanged(
                        $userId,
                        $propertyRequestId,
                        $oldPriority,
                        $newPriority,
                        $actorUserId
                    );
                }
            }

            $otherFields = array_diff(array_keys($validated), ['stage_id', 'status_id', 'priority']);
            if ($otherFields !== []) {
                $this->propertyRequestNotifier->notifyUpdated(
                    $userId,
                    $propertyRequestId,
                    array_values($otherFields),
                    $actorUserId
                );
            }
        }

        return $this->success([
            'message' => 'Action updated successfully',
            'actionId' => $requestId,
            'action' => $updated,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/notes
     *
     * Append a note to an action. For property_request_{id} and inquiry_{id}, saves to crm_hub_notes.
     * For other types (reminder, appointment, etc.) uses legacy note column where supported.
     */
    public function addNote(AddNoteRequest $request, string $requestId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $employeeId = auth()->user()->id;
        $parsed = $this->aggregator->parseActionId($requestId);

        // Request-level leads: save to crm_hub_notes (polymorphic)
        if ($parsed !== null) {
            if ($parsed['table'] === 'users_property_requests') {
                $noteable = UserPropertyRequest::where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first();
                if (!$noteable) {
                    return $this->error('Action not found', 404);
                }
                $note = $noteable->hubNotes()->create([
                    'employee_id' => $employeeId,
                    'note' => $validated['note'],
                ]);
                $note->load('employee.basic_setting');
                return $this->success([
                    'message' => 'Note added successfully',
                    'actionId' => $requestId,
                    'note' => $this->formatHubNote($note),
                ]);
            }
            if ($parsed['table'] === 'api_customer_inquiry') {
                $noteable = ApiCustomerInquiry::where('id', $parsed['sourceId'])
                    ->where('user_id', $userId)
                    ->first();
                if (!$noteable) {
                    return $this->error('Action not found', 404);
                }
                $note = $noteable->hubNotes()->create([
                    'employee_id' => $employeeId,
                    'note' => $validated['note'],
                ]);
                $note->load('employee.basic_setting');
                return $this->success([
                    'message' => 'Note added successfully',
                    'actionId' => $requestId,
                    'note' => $this->formatHubNote($note),
                ]);
            }
        }

        // Legacy: reminder, appointment, etc. (append to notes/note column where supported)
        $action = $this->aggregator->getById($userId, $requestId);
        if (!$action) {
            return $this->error('Action not found', 404);
        }
        $addedBy = $validated['addedBy'] ?? 'current_user';
        $success = $this->aggregator->addNoteToAction($userId, $requestId, $validated['note'], $addedBy);
        if (!$success) {
            return $this->error('Action not found or notes not supported for this request type', 422);
        }
        return $this->success([
            'message' => 'Note added successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/appointments
     *
     * Create an appointment linked to a property request.
     */
    public function createAppointmentForPropertyRequest(CreateAppointmentRequest $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $validated = $request->validated();

        $resolved = $this->resolvePropertyRequestAndCustomer($requestId, $userId);
        $isInquiry = false;
        if ($resolved === null) {
            $resolved = $this->resolveInquiryAndCustomer($requestId, $userId);
            $isInquiry = $resolved !== null;
        }
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST_ID',
                    'message' => 'Request not found',
                    'message_ar' => 'الطلب غير موجود',
                ],
            ], 404);
        }

        $title = !empty($validated['title']) ? $validated['title'] : (
            $validated['type'] === 'site_visit' ? 'معاينة عقار' : 'موعد طلب عقار'
        );
        $duration = array_key_exists('duration', $validated) ? ($validated['duration'] !== null ? (int) $validated['duration'] : null) : null;
        $priorityDb = $this->mapPriorityAppointmentToDb($validated['priority'] ?? 'medium');
        $datetime = null;
        if (array_key_exists('datetime', $validated) && !empty($validated['datetime'])) {
            $dt = Carbon::parse($validated['datetime']);
            if ($dt->lt(Carbon::now())) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_DATETIME',
                        'message' => 'Invalid datetime',
                        'message_ar' => 'تاريخ/وقت غير صالح',
                    ],
                ], 422);
            }
            $datetime = $dt->toDateTimeString();
        }
        $now = now();

        if ($isInquiry) {
            $id = DB::table('inquiry_appointments')->insertGetId([
                'user_id' => $userId,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        } else {
            $id = DB::table('property_request_appointments')->insertGetId([
                'user_id' => $userId,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $title,
                'type' => $validated['type'],
                'datetime' => $datetime,
                'duration' => $duration,
                'status' => 'scheduled',
                'priority' => $priorityDb,
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->propertyRequestNotifier->notifyAppointmentCreated(
                $userId,
                (int) $resolved['propertyRequestId'],
                (int) $id,
                $title,
                $datetime,
                (int) $request->user()->id
            );
        }

        $base = $this->propertyRequestDetailBuilder->formatPropertyRequestAppointment($row);
        $base['requestId'] = $requestId;
        $base['customerId'] = $row->customer_id !== null ? (int) $row->customer_id : null;
        $base['updatedAt'] = Carbon::parse($row->updated_at)->toIso8601String();
        $appointment = $base;

        return response()->json([
            'success' => true,
            'data' => ['appointment' => $appointment],
        ], 201);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/reminders
     *
     * Create a reminder linked to a property request.
     */
    public function createReminderForPropertyRequest(CreateReminderRequest $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $validated = $request->validated();

        $resolved = $this->resolvePropertyRequestAndCustomer($requestId, $userId);
        $isInquiry = false;
        if ($resolved === null) {
            $resolved = $this->resolveInquiryAndCustomer($requestId, $userId);
            $isInquiry = $resolved !== null;
        }
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST_ID',
                    'message' => 'Request not found',
                    'message_ar' => 'الطلب غير موجود',
                ],
            ], 404);
        }

        $priorityDb = $this->mapPriorityReminderToDb($validated['priority']);
        $datetime = Carbon::parse($validated['datetime'])->toDateTimeString();
        $now = now();

        if ($isInquiry) {
            $id = DB::table('inquiry_reminders')->insertGetId([
                'user_id' => $userId,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'inquiry_id' => $resolved['inquiryId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        } else {
            $id = DB::table('property_request_reminders')->insertGetId([
                'user_id' => $userId,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = (object) [
                'id' => $id,
                'property_request_id' => $resolved['propertyRequestId'],
                'customer_id' => $resolved['customerId'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'datetime' => $datetime,
                'priority' => $priorityDb,
                'type' => $validated['type'],
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->propertyRequestNotifier->notifyReminderCreated(
                $userId,
                (int) $resolved['propertyRequestId'],
                (int) $id,
                $validated['title'],
                $datetime,
                (int) $request->user()->id
            );
        }

        $now = Carbon::now();
        $base = $this->propertyRequestDetailBuilder->formatPropertyRequestReminder($row, $now);
        $base['requestId'] = $requestId;
        $base['customerId'] = $row->customer_id !== null ? (int) $row->customer_id : null;
        $base['updatedAt'] = Carbon::parse($row->updated_at)->toIso8601String();
        $reminder = $base;

        return response()->json([
            'success' => true,
            'data' => ['reminder' => $reminder],
        ], 201);
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk
     *
     * Unified bulk actions: complete, dismiss, snooze, assign, change_priority.
     */
    public function bulk(RequestsBulkRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $action = $validated['action'];
        $data = $validated['data'];

        $result = $this->aggregator->bulkAction($userId, $action, $validated['actionIds'], $data);
        $this->invalidateFilterOptionsCache($userId);

        $successIds = $result['success'];
        $this->dispatchBulkPropertyRequestNotifications(
            $userId,
            $action,
            $successIds,
            $data,
            (int) $request->user()->id
        );

        $failedIds = $result['failed'];
        $failures = $result['failures'] ?? [];
        $meta = $result['meta'] ?? [];
        $successCount = count($successIds);
        $failedCount = count($failedIds);

        $httpStatus = $failedCount > 0 && $successCount > 0 ? 207 : ($successCount > 0 ? 200 : ($failedCount > 0 ? 404 : 422));
        $responseStatus = $failedCount > 0 && $successCount > 0 ? 'partial_success' : ($successCount > 0 ? 'success' : 'error');
        $message = $this->bulkResponseMessage($action, $successCount, $failedCount, $responseStatus);

        $responseData = [
            'action' => $action,
            'updatedCount' => $successCount,
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'actionIds' => $successIds,
            'failedActionIds' => $failedIds,
            'failures' => $failures,
        ];

        $responseData = array_merge($responseData, $this->bulkResponseMeta($action, $data, $meta));
        $timestamp = now()->toIso8601String();

        return response()->json([
            'status' => $responseStatus,
            'code' => $httpStatus,
            'message' => $message,
            'data' => $responseData,
            'timestamp' => $timestamp,
        ], $httpStatus);
    }

    /**
     * Build human-readable message for bulk response.
     */
    private function bulkResponseMessage(string $action, int $successCount, int $failedCount, string $responseStatus): string
    {
        if ($responseStatus === 'error') {
            return $failedCount > 0 ? __('Bulk operation failed for all actions.') : __('Validation failed.');
        }
        $actionMessages = [
            'complete' => ['تم إكمال %d إجراء بنجاح', 'تم إكمال %d إجراءات بنجاح'],
            'dismiss' => ['تم رفض إجراء واحد بنجاح', 'تم رفض %d إجراءات بنجاح'],
            'snooze' => ['تم تأجيل إجراء واحد بنجاح', 'تم تأجيل %d إجراءات بنجاح'],
            'assign' => ['تم تعيين إجراء واحد بنجاح', 'تم تعيين %d إجراءات بنجاح'],
            'change_priority' => ['تم تغيير أولوية إجراء واحد بنجاح', 'تم تغيير أولوية %d إجراءات بنجاح'],
        ];
        $tpl = $actionMessages[$action] ?? ['%d action(s) processed', '%d actions processed'];
        $msg = $successCount === 1 ? $tpl[0] : sprintf($tpl[1], $successCount);
        if ($failedCount > 0) {
            $msg .= '، ' . sprintf(__('%d failed'), $failedCount);
        }
        return $msg;
    }

    /**
     * Add operation-specific meta (completedBy, dismissedBy, etc.) to response data.
     */
    private function bulkResponseMeta(string $action, array $data, array $meta): array
    {
        $out = [];
        $userId = null;
        $fieldMap = [
            'complete' => ['completedBy', 'completedAt'],
            'dismiss' => ['dismissedBy', 'dismissedAt'],
            'snooze' => ['snoozedBy', 'snoozedAt', 'snoozedUntil', 'reason'],
            'assign' => ['assignedTo', 'assignedBy', 'assignedAt'],
            'change_priority' => ['changedBy', 'changedAt', 'priority'],
        ];
        $userFields = ['complete' => 'completedBy', 'dismiss' => 'dismissedBy', 'snooze' => 'snoozedBy', 'assign' => ['assignedTo', 'assignedBy'], 'change_priority' => 'changedBy'];
        $now = now()->toIso8601String();
        if ($action === 'complete') {
            $id = $data['completedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['completedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['completedAt'] = $meta['completedAt'] ?? $now;
        } elseif ($action === 'dismiss') {
            $id = $data['dismissedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['dismissedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['dismissedAt'] = $meta['dismissedAt'] ?? $now;
            if (isset($data['reason'])) $out['reason'] = $data['reason'];
        } elseif ($action === 'snooze') {
            $id = $data['snoozedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['snoozedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['snoozedAt'] = $meta['snoozedAt'] ?? $now;
            $out['snoozedUntil'] = $data['snoozedUntil'] ?? null;
            if (isset($data['reason'])) $out['reason'] = $data['reason'];
        } elseif ($action === 'assign') {
            foreach (['assignedTo', 'assignedBy'] as $f) {
                $id = $data[$f] ?? null;
                if ($id) {
                    $u = User::find($id);
                    $out[$f] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')), 'email' => $u->email ?? null] : null;
                }
            }
            $out['assignedAt'] = $meta['assignedAt'] ?? $now;
        } elseif ($action === 'change_priority') {
            $id = $data['changedBy'] ?? null;
            if ($id) {
                $u = User::find($id);
                $out['changedBy'] = $u ? ['id' => $u->id, 'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))] : null;
            }
            $out['changedAt'] = $meta['changedAt'] ?? $now;
            $out['priority'] = $data['priority'] ?? null;
        }
        return $out;
    }

    /**
     * Check that the given user ID is the tenant owner or an active employee of the tenant.
     */
    private function isValidTenantUserOrEmployee(int $tenantUserId, int $employeeId): bool
    {
        if ($employeeId === $tenantUserId) {
            return true;
        }
        return User::where('id', $employeeId)
            ->where('tenant_id', $tenantUserId)
            ->where('account_type', 'employee')
            ->where('active', true)
            ->exists();
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk-complete
     *
     * Bulk complete multiple actions.
     */
    public function bulkComplete(BulkCompleteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkComplete($userId, $validated['actionIds']);

        $this->dispatchBulkPropertyRequestNotifications(
            $userId,
            'complete',
            $results['success'],
            [],
            (int) $request->user()->id
        );

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => sprintf(
                '%d actions completed, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk-dismiss
     *
     * Bulk dismiss multiple actions.
     */
    public function bulkDismiss(BulkDismissRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkDismiss($userId, $validated['actionIds'], $validated['reason']);

        $this->dispatchBulkPropertyRequestNotifications(
            $userId,
            'dismiss',
            $results['success'],
            ['reason' => $validated['reason'] ?? null],
            (int) $request->user()->id
        );

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => sprintf(
                '%d actions dismissed, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
        ]);
    }

    // =========================================================================
    // MATCHING APIs (V2)
    // =========================================================================

    /**
     * GET /api/v2/customers-hub/requests/{requestId}/matches
     *
     * Returns matched properties for a property request along with completeness status.
     * Only works for property_request_* composite IDs.
     */
    public function matches(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found or not matchable', 404);
        }

        $completeness = app(\App\Services\Matching\RequestCompletenessService::class)
            ->validateMinimal('web', $propertyRequest->id);

        $matches = DB::table('property_matches as pm')
            ->where('pm.user_id', $userId)
            ->where('pm.request_type', 'web')
            ->where('pm.request_id', $propertyRequest->id)
            ->orderByDesc('pm.match_score')
            ->get(['pm.id', 'pm.property_id', 'pm.match_score', 'pm.database_score', 'pm.ai_score',
                   'pm.match_explanation', 'pm.matched_criteria', 'pm.is_reviewed']);

        $matchItems = [];
        if ($matches->isNotEmpty()) {
            // Batch-load all properties instead of per-row (N+1 fix)
            $propertyIds = $matches->pluck('property_id')->unique()->all();
            $properties  = \App\Models\User\RealestateManagement\Property::findMany($propertyIds)->keyBy('id');

            $matchItems = $matches->map(function ($m) use ($properties) {
                $prop = $properties->get($m->property_id);
                return [
                    'match_id'        => $m->id,
                    'property_id'     => $m->property_id,
                    'match_score'     => (int) $m->match_score,
                    'database_score'  => (int) $m->database_score,
                    'ai_score'        => (int) $m->ai_score,
                    'match_explanation' => $m->match_explanation,
                    'matched_criteria'  => is_string($m->matched_criteria)
                        ? json_decode($m->matched_criteria, true)
                        : $m->matched_criteria,
                    'is_reviewed'     => (bool) $m->is_reviewed,
                    'property'        => $prop ? [
                        'id'             => $prop->id,
                        'title'          => optional($prop->first_content)->title ?? null,
                        'featured_image' => $prop->featured_image_url ?? null,
                        'address'        => optional($prop->first_content)->address ?? $prop->address ?? null,
                        'price'          => $prop->price ?? null,
                        'purpose'        => $prop->purpose ?? null,
                        'property_type'  => $prop->property_type ?? null,
                        'beds'           => $prop->beds ?? null,
                        'baths'          => $prop->bath ?? null,
                        'area'           => $prop->area ?? null,
                    ] : null,
                ];
            })->values()->all();
        }

        return $this->success([
            'request_id'             => $requestId,
            'source'                 => $propertyRequest->source ?? 'website',
            'has_minimal_data'       => $completeness['has_minimal_data'],
            'minimal_missing_fields' => $completeness['minimal_missing_fields'],
            'is_complete'            => $completeness['is_complete'],
            'missing_fields'         => $completeness['missing_fields'],
            'is_ignored'             => (bool) ($propertyRequest->is_ignored ?? false),
            'matches'                => $matchItems,
            'total_matches'          => count($matchItems),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/complete-data
     *
     * Fill in missing fields on a property request to enable matching.
     * The observer auto-triggers matching after the update.
     */
    public function completeData(\App\Http\Requests\Api\V2\CustomersHub\CompleteDataRequest $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found or not matchable', 404);
        }

        $fields = $request->onlyProvided();
        $linkSync = app(PropertyRequestLinkSync::class);
        $projectIds = $linkSync->resolveIncomingProjectIds($fields);
        $propertyIdsProvided = array_key_exists('property_ids', $fields);
        $propertyIds = $propertyIdsProvided
            ? $linkSync->assertOwnedPropertyIds($userId, $fields['property_ids'])
            : null;
        if ($projectIds !== null) {
            $projectIds = $linkSync->assertOwnedProjectIds($userId, $projectIds);
        }
        unset($fields['property_ids'], $fields['project_ids'], $fields['project_id']);

        if (empty($fields) && ! $propertyIdsProvided && $projectIds === null) {
            return $this->error('No fields provided to update', 422);
        }

        // Map purpose aliases to canonical values for users_property_requests
        if (isset($fields['purpose'])) {
            $purposeMap = ['buy' => 'sale', 'invest' => 'sale'];
            $fields['purpose'] = $purposeMap[$fields['purpose']] ?? $fields['purpose'];
        }

        $propertyRequest->fill($fields);
        if ($propertyIdsProvided) {
            $propertyRequest->property_ids = $propertyIds;
        }
        $propertyRequest->save();
        if ($projectIds !== null) {
            $linkSync->syncProjectIds($propertyRequest, $projectIds);
        }

        $normalizer = app(PropertyRequestLocationNormalizer::class);
        if ($normalizer->hasLocationFields($fields)) {
            $locationFields = ['region', 'city_id', 'districts_id', 'city', 'district', 'latitude', 'longitude'];
            $base = $propertyRequest->only($locationFields);
            $normalized = $normalizer->normalize($base, $propertyRequest->source);
            $propertyRequest->fill(array_intersect_key($normalized, array_flip($locationFields)));
            $propertyRequest->save();
        }

        $completeness = app(\App\Services\Matching\RequestCompletenessService::class)
            ->validateMinimal('web', $propertyRequest->id);
        $propertyRequest->load('projects:id');

        return $this->success([
            'request_id'             => $requestId,
            'has_minimal_data'       => $completeness['has_minimal_data'],
            'minimal_missing_fields' => $completeness['minimal_missing_fields'],
            'is_complete'            => $completeness['is_complete'],
            'missing_fields'         => $completeness['missing_fields'],
            'property_ids'           => $propertyRequest->property_ids,
            'project_ids'            => $propertyRequest->toArray()['project_ids'],
            'message'                => 'Request updated. Matching will run automatically.',
        ]);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}/read
     *
     * Mark a property request as read.
     */
    public function markRead(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        $propertyRequest->update(['is_read' => true]);

        return $this->success(['message' => 'Request marked as read']);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}/unread
     *
     * Mark a property request as unread.
     */
    public function markUnread(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        $propertyRequest->update(['is_read' => false]);

        return $this->success(['message' => 'Request marked as unread']);
    }

    /**
     * PATCH /api/v2/customers-hub/requests/{requestId}/ignore
     *
     * Toggle the ignored flag for matching.
     * Body: { "is_ignored": true|false }  (defaults to true when not provided)
     */
    public function ignore(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        $isIgnored = $request->boolean('is_ignored', true);
        $propertyRequest->update(['is_ignored' => $isIgnored]);

        $msg = $isIgnored
            ? 'Request ignored. Matching will be skipped.'
            : 'Request un-ignored. Matching will resume on next update.';

        return $this->success(['is_ignored' => $isIgnored, 'message' => $msg]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/rematch
     *
     * Manually trigger property matching for a request.
     */
    public function rematch(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $propertyRequest = $this->resolvePropertyRequestForMatching($requestId, $userId);

        if ($propertyRequest === null) {
            return $this->error('Request not found', 404);
        }

        if ($propertyRequest->is_ignored) {
            return $this->error('Cannot rematch an ignored request. Un-ignore it first.', 422);
        }

        $completeness = app(\App\Services\Matching\RequestCompletenessService::class)
            ->validateMinimal('web', $propertyRequest->id);

        if (!$completeness['has_minimal_data']) {
            return $this->error('Request has insufficient data for matching.', 422, [
                'minimal_missing_fields' => $completeness['minimal_missing_fields'],
            ]);
        }

        $forceAi = $completeness['is_complete'];
        $limit = $forceAi ? 25 : 10;

        $results = app(\App\Services\Matching\MatchingService::class)
            ->generateMatchesForRequest('web', $propertyRequest->id, $limit, $forceAi, $userId);

        return $this->success([
            'request_id'    => $requestId,
            'matched_count' => count($results),
            'is_complete'   => $completeness['is_complete'],
            'message'       => count($results) > 0
                ? 'Matching completed successfully.'
                : 'No matching properties found.',
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Load rows in chunks of foreign-key IDs, format each row, and group by that FK (int => list of formatted values).
     * Keeps one query shape per chunk (tenant-scoped via user_id) instead of unbounded IN lists.
     *
     * @param  callable(object): mixed  $formatter
     * @return array<int, array<int, mixed>>
     */
    private function batchLoadFormattedByForeignKey(
        string $table,
        int $userId,
        string $foreignKeyColumn,
        array $foreignIds,
        string $orderColumn,
        callable $formatter
    ): array {
        $foreignIds = array_values(array_unique(array_filter(array_map('intval', $foreignIds))));
        if ($foreignIds === []) {
            return [];
        }

        $bucket = [];
        foreach (array_chunk($foreignIds, self::CH_LIST_WHERE_IN_CHUNK) as $chunk) {
            $rows = DB::table($table)
                ->where('user_id', $userId)
                ->whereIn($foreignKeyColumn, $chunk)
                ->orderBy($orderColumn, 'asc')
                ->get();

            foreach ($rows as $row) {
                $fk = (int) ($row->{$foreignKeyColumn} ?? 0);
                if ($fk <= 0) {
                    continue;
                }
                $bucket[$fk][] = $formatter($row);
            }
        }

        return $bucket;
    }

    /**
     * Get the tenant user ID from request.
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }

    /**
     * Known source value labels for filter-options. Unknown values use raw value or "Other".
     */
    private function sourceLabel(string $value, string $lang): string
    {
        $map = [
            'inquiry' => ['ar' => 'استفسار', 'en' => 'Inquiry'],
            'manual' => ['ar' => 'يدوي', 'en' => 'Manual'],
            'whatsapp' => ['ar' => 'واتساب', 'en' => 'WhatsApp'],
            'import' => ['ar' => 'استيراد', 'en' => 'Import'],
            'referral' => ['ar' => 'إحالة', 'en' => 'Referral'],
            'property_request' => ['ar' => 'طلب عقار', 'en' => 'Property Request'],
            'website' => ['ar' => 'الموقع', 'en' => 'Website'],
            'property_interest' => ['ar' => 'اهتمام بعقار', 'en' => 'Property Interest'],
            'public_form' => ['ar' => 'نموذج عام', 'en' => 'Public Form'],
            'employee_dashboard' => ['ar' => 'لوحة الموظف', 'en' => 'Employee Dashboard'],
            'whatsapp_bot' => ['ar' => 'واتساب بوت', 'en' => 'WhatsApp Bot'],
        ];
        if (isset($map[$value][$lang])) {
            return $map[$value][$lang];
        }
        return $value;
    }

    /**
     * Invalidate filter options cache for a user.
     */
    private function invalidateFilterOptionsCache(int $userId): void
    {
        Cache::forget("ch:reqs:filter-options:{$userId}");
    }

    private function dispatchPropertyRequestNotificationFromAction(
        int $tenantUserId,
        string $actionId,
        string $type,
        string $title,
        string $body,
        ?int $actorUserId = null,
        array $payload = []
    ): void {
        $propertyRequestId = CustomersHubPropertyRequestNotifier::parsePropertyRequestId($actionId);
        if ($propertyRequestId === null) {
            return;
        }

        $this->propertyRequestNotifier->notifyStatusEvent(
            $tenantUserId,
            $propertyRequestId,
            $type,
            $title,
            $body,
            $payload,
            $actorUserId
        );
    }

    /**
     * @param  list<string>  $successActionIds
     */
    private function dispatchBulkPropertyRequestNotifications(
        int $tenantUserId,
        string $action,
        array $successActionIds,
        array $data,
        int $actorUserId
    ): void {
        foreach ($successActionIds as $actionId) {
            if (!is_string($actionId)) {
                continue;
            }

            match ($action) {
                'complete' => $this->dispatchPropertyRequestNotificationFromAction(
                    $tenantUserId,
                    $actionId,
                    CustomersHubNotificationService::TYPE_COMPLETED,
                    'Property request completed',
                    'A property request was marked as completed',
                    $actorUserId
                ),
                'dismiss' => $this->dispatchPropertyRequestNotificationFromAction(
                    $tenantUserId,
                    $actionId,
                    CustomersHubNotificationService::TYPE_DISMISSED,
                    'Property request dismissed',
                    'A property request was dismissed',
                    $actorUserId,
                    ['reason' => $data['reason'] ?? null]
                ),
                'snooze' => $this->dispatchPropertyRequestNotificationFromAction(
                    $tenantUserId,
                    $actionId,
                    CustomersHubNotificationService::TYPE_SNOOZED,
                    'Property request snoozed',
                    'A property request was snoozed',
                    $actorUserId,
                    [
                        'snoozedUntil' => $data['snoozedUntil'] ?? null,
                        'reason' => $data['reason'] ?? null,
                    ]
                ),
                'assign' => $this->notifyBulkAssign($tenantUserId, $actionId, $data, $actorUserId),
                'change_priority' => $this->notifyBulkPriorityChange($tenantUserId, $actionId, $data, $actorUserId),
                default => null,
            };
        }
    }

    private function notifyBulkAssign(int $tenantUserId, string $actionId, array $data, int $actorUserId): void
    {
        $propertyRequestId = CustomersHubPropertyRequestNotifier::parsePropertyRequestId($actionId);
        if ($propertyRequestId === null) {
            return;
        }

        $this->propertyRequestNotifier->notifyAssigned(
            $tenantUserId,
            $propertyRequestId,
            isset($data['assignedTo']) ? (int) $data['assignedTo'] : null,
            $actorUserId
        );
    }

    private function notifyBulkPriorityChange(int $tenantUserId, string $actionId, array $data, int $actorUserId): void
    {
        $propertyRequestId = CustomersHubPropertyRequestNotifier::parsePropertyRequestId($actionId);
        if ($propertyRequestId === null) {
            return;
        }

        $this->propertyRequestNotifier->notifyPriorityChanged(
            $tenantUserId,
            $propertyRequestId,
            null,
            $data['priority'] ?? null,
            $actorUserId
        );
    }

    /**
     * Resolve property request and optional customer from requestId.
     * Returns ['propertyRequestId' => int, 'customerId' => int|null] or null if not found / not a property_request.
     */
    private function resolvePropertyRequestAndCustomer(string $requestId, int $userId): ?array
    {
        $parsed = $this->aggregator->parseActionId($requestId);
        if ($parsed === null || ($parsed['table'] ?? '') !== 'users_property_requests') {
            return null;
        }
        $sourceId = (int) $parsed['sourceId'];

        $exists = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->where('id', $sourceId)
            ->where('is_active', 1)
            ->exists();
        if (!$exists) {
            return null;
        }

        $row = DB::table('users_property_requests as upr')
            ->leftJoin('api_customers as ac', function ($join) {
                $join->on('upr.user_id', '=', 'ac.user_id')
                    ->on('upr.phone', '=', 'ac.phone_number');
            })
            ->where('upr.user_id', $userId)
            ->where('upr.id', $sourceId)
            ->select('upr.id as property_request_id', 'ac.id as customer_id')
            ->first();

        return [
            'propertyRequestId' => (int) $row->property_request_id,
            'customerId' => $row->customer_id !== null ? (int) $row->customer_id : null,
        ];
    }

    /**
     * Resolve inquiry and customer from requestId.
     * Returns ['inquiryId' => int, 'customerId' => int|null] or null if not found / not an inquiry.
     */
    private function resolveInquiryAndCustomer(string $requestId, int $userId): ?array
    {
        $parsed = $this->aggregator->parseActionId($requestId);
        if ($parsed === null || ($parsed['table'] ?? '') !== 'api_customer_inquiry') {
            return null;
        }
        $sourceId = (int) $parsed['sourceId'];

        $row = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->where('id', $sourceId)
            ->first(['id', 'customer_id']);
        if (!$row) {
            return null;
        }

        return [
            'inquiryId' => (int) $row->id,
            'customerId' => $row->customer_id !== null ? (int) $row->customer_id : null,
        ];
    }

    /**
     * Map API priority string to appointments table (1=low, 2=medium, 3=high, 4=urgent).
     */
    private function mapPriorityAppointmentToDb(?string $priority): int
    {
        return match ($priority) {
            'urgent' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 2,
        };
    }

    /**
     * Map API priority string to reminders table (0=low, 1=medium, 2=high, 3=urgent).
     */
    private function mapPriorityReminderToDb(?string $priority): int
    {
        return match ($priority) {
            'urgent' => 3,
            'high' => 2,
            'medium' => 1,
            'low' => 0,
            default => 1,
        };
    }

    /**
     * Resolve a composite requestId to a UserPropertyRequest model for matching endpoints.
     * Returns null if not found, not owned by $userId, or not a property_request_ composite ID.
     */
    private function resolvePropertyRequestForMatching(string $requestId, int $userId): ?UserPropertyRequest
    {
        $parsed = $this->aggregator->parseActionId($requestId);
        if ($parsed === null || ($parsed['table'] ?? '') !== 'users_property_requests') {
            return null;
        }
        $sourceId = (int) $parsed['sourceId'];

        return UserPropertyRequest::where('id', $sourceId)
            ->where('user_id', $userId)
            ->first();
    }
}
