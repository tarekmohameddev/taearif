<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Reservation;
use App\Models\User\RealestateManagement\Property;
use App\Http\Requests\Reservations\IndexRequest;
use App\Http\Requests\Reservations\DecisionRequest;
use App\Http\Requests\Reservations\BulkActionRequest;

class ReservationsController extends Controller
{
    protected function tenantId(): int
    {
        return (int) auth()->id();
    }

    public function index(IndexRequest $request)
    {
        $tenantId = $this->tenantId();
        $validated = $request->validated();

        $query = Reservation::query()
            ->with(['property.contents', 'property.building', 'property.project.contents'])
            ->where('tenant_id', $tenantId);

        // Filters
        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }
        if (($validated['type'] ?? 'all') !== 'all') {
            $query->where('type', $validated['type']);
        }
        if (!empty($validated['search'])) {
            $term = '%' . $validated['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', $term)
                    ->orWhereHas('property.contents', function ($q2) use ($term) {
                        $q2->where('title', 'like', $term)->orWhere('address', 'like', $term);
                    })
                    ->orWhereHas('property.building', function ($q2) use ($term) {
                        $q2->where('name', 'like', $term);
                    })
                    ->orWhereHas('property.project.contents', function ($q2) use ($term) {
                        $q2->where('title', 'like', $term)->orWhere('address', 'like', $term);
                    });
            });
        }

        // Sorting
        $sortBy = $validated['sort_by'] ?? 'date';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        switch ($sortBy) {
            case 'price':
                $query->join('user_properties as up', 'up.id', '=', 'reservations.property_id')
                      ->select('reservations.*')
                      ->orderBy('up.price', $sortOrder);
                break;
            case 'name':
                $query->orderBy('customer_name', $sortOrder);
                break;
            case 'date':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $reservations = $query->paginate($perPage);

        // Stats
        $baseStats = Reservation::query()->where('tenant_id', $tenantId);
        if (($validated['type'] ?? 'all') !== 'all') {
            $baseStats->where('type', $validated['type']);
        }
        if (!empty($validated['search'])) {
            $term = '%' . $validated['search'] . '%';
            $baseStats->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', $term);
            });
        }
        $stats = [
            'total' => (clone $baseStats)->count(),
            'pending' => (clone $baseStats)->where('status', 'pending')->count(),
            'accepted' => (clone $baseStats)->where('status', 'accepted')->count(),
            'rejected' => (clone $baseStats)->where('status', 'rejected')->count(),
        ];

        $items = $reservations->getCollection()->map(function (Reservation $r) {
            $p = $r->property;
            $content = $p?->contents?->first();
            $projectContent = $p?->project?->contents?->first();
            return [
                'id' => (string) $r->id,
                'type' => $r->type,
                'status' => $r->status,
                'customer' => [
                    'id' => null,
                    'name' => $r->customer_name,
                    'email' => null,
                    'phone' => $r->customer_phone,
                    'avatar' => null,
                ],
                'property' => [
                    'id' => $p?->id ? (string) $p->id : null,
                    'title' => $content?->title,
                    'address' => $content?->address,
                    'price' => $p?->price ? (float) $p->price : null,
                    'type' => $p?->type,
                    'bedrooms' => $p?->beds,
                    'bathrooms' => $p?->bath,
                    'image' => $p?->featured_image ? asset($p->featured_image) : null,
                    'projectName' => $projectContent?->title ?? null,
                    'buildingName' => $p?->building?->name ?? null,
                ],
                'requestDate' => $r->created_at?->toISOString(),
                'desiredDate' => $r->desired_date?->toDateString(),
                'duration' => null,
                'paymentRequired' => false,
                'depositAmount' => $r->deposit_amount ? (float) $r->deposit_amount : null,
                'notes' => $r->notes,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'reservations' => $items,
                'pagination' => [
                    'current_page' => $reservations->currentPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                    'last_page' => $reservations->lastPage(),
                    'from' => $reservations->firstItem(),
                    'to' => $reservations->lastItem(),
                ],
                'stats' => $stats,
            ],
        ]);
    }

    public function show(string $id)
    {
        $tenantId = $this->tenantId();
        $r = Reservation::with(['property.contents', 'property.building', 'property.project.contents'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        $p = $r->property;
        $content = $p?->contents?->first();
        $projectContent = $p?->project?->contents?->first();
        $timeline = $r->metadata['timeline'] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $r->id,
                'type' => $r->type,
                'status' => $r->status,
                'customer' => [
                    'id' => null,
                    'name' => $r->customer_name,
                    'email' => null,
                    'phone' => $r->customer_phone,
                    'avatar' => null,
                ],
                'property' => [
                    'id' => $p?->id ? (string) $p->id : null,
                    'title' => $content?->title,
                    'address' => $content?->address,
                    'price' => $p?->price ? (float) $p->price : null,
                    'type' => $p?->type,
                    'bedrooms' => $p?->beds,
                    'bathrooms' => $p?->bath,
                    'image' => $p?->featured_image ? asset($p->featured_image) : null,
                    'projectName' => $projectContent?->title ?? null,
                    'buildingName' => $p?->building?->name ?? null,
                ],
                'requestDate' => $r->created_at?->toISOString(),
                'desiredDate' => $r->desired_date?->toDateString(),
                'duration' => null,
                'paymentRequired' => false,
                'depositAmount' => $r->deposit_amount ? (float) $r->deposit_amount : null,
                'notes' => $r->notes,
                'documents' => [],
                'messages' => [],
                'timeline' => $timeline,
            ],
        ]);
    }

    public function accept(DecisionRequest $request, string $id)
    {
        $tenantId = $this->tenantId();
        $r = Reservation::where('tenant_id', $tenantId)->findOrFail($id);

        $data = $request->validated();
        $r->status = 'accepted';

        $timeline = $r->metadata['timeline'] ?? [];
        $timeline[] = [
            'id' => 't-' . (string) (count($timeline) + 1),
            'action' => 'تم قبول الحجز',
            'timestamp' => now()->toISOString(),
            'actor' => 'المسؤول',
            'notes' => $data['notes'] ?? null,
        ];
        $meta = $r->metadata ?? [];
        $meta['timeline'] = $timeline;
        $meta['confirmPayment'] = (bool) ($data['confirmPayment'] ?? false);
        $r->metadata = $meta;
        $r->save();

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الحجز بنجاح',
            'data' => [
                'id' => (string) $r->id,
                'status' => $r->status,
                'updatedAt' => now()->toISOString(),
                'timeline' => end($timeline),
            ],
        ]);
    }

    public function reject(DecisionRequest $request, string $id)
    {
        $tenantId = $this->tenantId();
        $r = Reservation::where('tenant_id', $tenantId)->findOrFail($id);
        $data = $request->validated();

        if (empty($data['reason'])) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => ['reason' => ['حقل السبب مطلوب']]
            ], 422);
        }

        $r->status = 'rejected';
        $timeline = $r->metadata['timeline'] ?? [];
        $timeline[] = [
            'id' => 't-' . (string) (count($timeline) + 1),
            'action' => 'تم رفض الحجز',
            'timestamp' => now()->toISOString(),
            'actor' => 'المسؤول',
            'notes' => $data['reason'],
        ];
        $meta = $r->metadata ?? [];
        $meta['timeline'] = $timeline;
        $r->metadata = $meta;
        $r->save();

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الحجز',
            'data' => [
                'id' => (string) $r->id,
                'status' => $r->status,
                'updatedAt' => now()->toISOString(),
                'timeline' => end($timeline),
            ],
        ]);
    }

    public function bulkAction(BulkActionRequest $request)
    {
        $tenantId = $this->tenantId();
        $data = $request->validated();
        $action = $data['action'];
        $ids = $data['reservationIds'];

        $successful = [];
        $failed = [];

        foreach ($ids as $rid) {
            $r = Reservation::where('tenant_id', $tenantId)->find($rid);
            if (!$r) {
                $failed[] = (string) $rid;
                continue;
            }
            if ($action === 'accept') {
                $r->status = 'accepted';
            } elseif ($action === 'reject') {
                $r->status = 'rejected';
            }
            $r->save();
            $successful[] = (string) $r->id;
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تنفيذ الإجراء على ' . count($successful) . ' حجوزات',
            'data' => [
                'successful' => $successful,
                'failed' => $failed,
                'action' => $action,
                'updatedAt' => now()->toISOString(),
            ]
        ]);
    }

    public function stats()
    {
        $tenantId = $this->tenantId();
        $base = Reservation::where('tenant_id', $tenantId);

        $total = (clone $base)->count();
        $pending = (clone $base)->where('status', 'pending')->count();
        $accepted = (clone $base)->where('status', 'accepted')->count();
        $rejected = (clone $base)->where('status', 'rejected')->count();
        $acceptanceRate = $total > 0 ? (int) round(($accepted / $total) * 100) : 0;

        // Total revenue: sum of property price for accepted reservations (best-effort)
        $totalRevenue = (clone $base)
            ->where('reservations.status', 'accepted')
            ->join('user_properties as up', 'up.id', '=', 'reservations.property_id')
            ->sum('up.price');

        // byType
        $byType = [
            'rent' => (clone $base)->where('type', 'rent')->count(),
            'buy' => (clone $base)->where('type', 'buy')->count(),
        ];

        // byMonth (last 12 months)
        $byMonth = (clone $base)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as cnt'))
            ->groupBy('ym')
            ->orderBy('ym', 'desc')
            ->limit(12)
            ->get()
            ->map(function ($row) {
                return [
                    'month' => $row->ym,
                    'reservations' => (int) $row->cnt,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'accepted' => $accepted,
                'rejected' => $rejected,
                'acceptanceRate' => $acceptanceRate,
                'totalRevenue' => (float) $totalRevenue,
                'byType' => $byType,
                'byMonth' => $byMonth,
            ],
        ]);
    }

    public function exportCsv(IndexRequest $request): StreamedResponse
    {
        $tenantId = $this->tenantId();
        $validated = $request->validated();

        $query = Reservation::query()
            ->with(['property.contents'])
            ->where('tenant_id', $tenantId);

        if (($validated['status'] ?? 'all') !== 'all') {
            $query->where('status', $validated['status']);
        }
        if (($validated['type'] ?? 'all') !== 'all') {
            $query->where('type', $validated['type']);
        }
        if (!empty($validated['search'])) {
            $term = '%' . $validated['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', $term);
            });
        }

        $filename = 'reservations-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
            $out = fopen('php://output', 'w');
            // Header
            fputcsv($out, ['ID', 'Status', 'Type', 'Customer', 'Phone', 'Property', 'Address', 'Price', 'Requested At']);
            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    $p = $r->property;
                    $content = $p?->contents?->first();
                    fputcsv($out, [
                        $r->id,
                        $r->status,
                        $r->type,
                        $r->customer_name,
                        $r->customer_phone,
                        $content?->title,
                        $content?->address,
                        $p?->price,
                        optional($r->created_at)->toDateTimeString(),
                    ]);
                }
            });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}


