<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Http\Resources\Api\BuildingPublicResource;
use App\Services\Building\BuildingPublicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    use ResolvesTenant;

    public function __construct(
        private readonly BuildingPublicService $buildingService,
    ) {}

    public function index(Request $request, string $tenantId): JsonResponse
    {
        $tenant = $this->resolveTenant($request, $tenantId);
        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $paginator = $this->buildingService->listPublished($tenant->id, $perPage);

        return response()->json([
            'data' => BuildingPublicResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, string $tenantId, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($request, $tenantId);
        $building = $this->buildingService->findBySlug($tenant->id, $slug);

        if (! $building) {
            return response()->json(['message' => 'Building not found'], 404);
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $units = $this->buildingService->publishedUnitsQuery($building)->paginate($perPage);

        $unitsData = $units->getCollection()->map(function ($property) {
            $content = $property->contents->first();

            return [
                'id' => $property->id,
                'title' => $content?->title ?? '',
                'slug' => $content?->slug ?? '',
                'price' => $property->price,
                'unit_status' => $property->unit_status ?? 'available',
                'listing_purpose' => $property->listing_purpose,
                'publish_status' => $property->publish_status,
                'featured_image' => $property->featured_image ? asset($property->featured_image) : null,
            ];
        });

        return response()->json([
            'building' => new BuildingPublicResource($building),
            'units' => [
                'data' => $unitsData,
                'meta' => [
                    'current_page' => $units->currentPage(),
                    'last_page' => $units->lastPage(),
                    'per_page' => $units->perPage(),
                    'total' => $units->total(),
                ],
            ],
        ]);
    }
}
