<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 20));

        $query = Property::query()
            ->where('user_id', $userId)
            ->with([
                'contents:id,property_id,title,language_id',
                'category:id,name',
            ])
            ->orderByDesc('created_at');

        if (($status = $request->query('status')) !== null && $status !== '') {
            $query->where('status', (string) $status);
        }

        if (($cityId = $request->query('city_id')) !== null && $cityId !== '') {
            $query->where('region_id', (int) $cityId);
        }

        if (($search = $request->query('search')) !== null && $search !== '') {
            $s = '%' + $search + '%';
            $query->whereHas('contents', function ($q) use ($s) {
                $q->where('title', 'like', $s);
            });
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(function (Property $p) {
                $title = optional($p->contents->first())->title;

                return [
                    'id' => (int) $p->id,
                    'title' => $title,
                    'price' => $p->price,
                    'status' => $p->status,
                    'thumbnail' => $p->featured_image,
                    'city' => null,
                    'type' => $p->category?->name,
                    'bedrooms' => $p->beds,
                ];
            })
            ->values()
            ->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $row = Property::where('user_id', $userId)
            ->with(['contents', 'category', 'galleryImages', 'proertyAmenities', 'specifications', 'building', 'project'])
            ->find($id);

        if (! $row) {
            return $this->error('Not found', 404);
        }

        return $this->success($row->toArray());
    }

    private function tenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
