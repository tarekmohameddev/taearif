<?php

namespace App\Services\Project;

use App\Models\Membership;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\UserDistrict;
use App\Services\MembershipCacheService;
use App\Services\Property\PropertyProjectLinkGuard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectPropertyService
{
    public function __construct(
        private readonly PropertyProjectLinkGuard $projectLinkGuard,
    ) {
    }

    public function listForProject(int $tenantOwnerId, int $projectId, int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $this->resolveProjectForTenant($tenantOwnerId, $projectId);

        $query = Property::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', $this->allowedUserIds($tenantOwnerId))
            ->with(['contents', 'galleryImages', 'category', 'creator']);

        if (! empty($filters['unit_status'])) {
            $query->where('unit_status', $filters['unit_status']);
        }
        if (! empty($filters['listing_purpose'])) {
            $query->where('listing_purpose', $filters['listing_purpose']);
        }
        if (! empty($filters['publish_status'])) {
            $query->where('publish_status', $filters['publish_status']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['property_type'])) {
            $query->where('property_type', $filters['property_type']);
        }
        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        if (isset($filters['price_from'])) {
            $query->where('price', '>=', (float) $filters['price_from']);
        }
        if (isset($filters['price_to'])) {
            $query->where('price', '<=', (float) $filters['price_to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('contents', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['city_id']) || ! empty($filters['state_id'])) {
            $query->whereHas('contents', function ($q) use ($filters) {
                if (! empty($filters['city_id'])) {
                    $q->where('city_id', (int) $filters['city_id']);
                }
                if (! empty($filters['state_id'])) {
                    $q->where('state_id', (int) $filters['state_id']);
                }
            });
        }
        if (! empty($filters['floor_number'])) {
            $query->whereHas('UserPropertyCharacteristics', function ($q) use ($filters) {
                $q->where('floor_number', (int) $filters['floor_number']);
            });
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function createForProject(
        int $tenantOwnerId,
        int $projectId,
        array $payload,
        ?int $actorUserId = null,
    ): Property {
        $this->assertPropertyQuota($tenantOwnerId);
        $project = $this->resolveProjectForTenant($tenantOwnerId, $projectId);
        $defaultLanguage = $this->resolveDefaultLanguage($tenantOwnerId);
        $projectContent = ProjectContent::query()
            ->where('project_id', $project->id)
            ->where('language_id', $defaultLanguage->id)
            ->first();

        $payload = $this->mergeInheritedProjectLocation($project, $projectContent, $payload);

        $effectiveAddress = trim((string) ($payload['address'] ?? ''));
        if ($effectiveAddress === '') {
            throw new HttpException(422, 'Address is required when not provided by the project content.');
        }
        if (strlen($effectiveAddress) > 255) {
            throw new HttpException(422, 'The address may not be greater than 255 characters.');
        }

        $location = $this->normalizeLocation($payload);

        $property = null;

        DB::transaction(function () use (
            $tenantOwnerId,
            $project,
            $projectId,
            $payload,
            $location,
            $defaultLanguage,
            $actorUserId,
            &$property
        ): void {
            $propertyData = array_merge([
                'price' => null,
                'area' => null,
                'status' => 0,
                'latitude' => null,
                'longitude' => null,
            ], $this->buildPropertyPayload($payload, $projectId));
            $featured = $payload['featured'] ?? false;
            $floorPlanningImage = $payload['floor_planning_image'] ?? null;

            $property = Property::storeProperty(
                $tenantOwnerId,
                $propertyData,
                $payload['featured_image'],
                $floorPlanningImage,
                $payload['video_image'] ?? null,
                $featured,
                $actorUserId ?? auth()->id(),
            );

            if (!empty($payload['gallery']) && is_array($payload['gallery'])) {
                foreach ($payload['gallery'] as $imagePath) {
                    PropertySliderImg::storeSliderImage($tenantOwnerId, $property->id, $imagePath);
                }
            }

            PropertyContent::storePropertyContent($tenantOwnerId, $property->id, [
                'language_id' => $defaultLanguage->id,
                'category_id' => $payload['category_id'] ?? ApiUserCategory::where('slug', 'other')->value('id'),
                'state_id' => $location['state_id'],
                'city_id' => $location['city_id'],
                'title' => $payload['title'],
                'slug' => str_replace('.', '', Str::slug($payload['title'])),
                'address' => $payload['address'],
                'description' => $payload['description'],
                'meta_keyword' => $payload['meta_keyword'] ?? null,
                'meta_description' => $payload['meta_description'] ?? null,
            ]);
        });

        return $property->load(['contents', 'galleryImages', 'category']);
    }

    public function attachMany(int $tenantOwnerId, int $projectId, array $propertyIds): Collection
    {
        $this->resolveProjectForTenant($tenantOwnerId, $projectId);
        $allowedUserIds = $this->allowedUserIds($tenantOwnerId);
        $uniqueIds = array_values(array_unique(array_map('intval', $propertyIds)));

        $properties = Property::query()
            ->whereIn('id', $uniqueIds)
            ->whereIn('user_id', $allowedUserIds)
            ->get();

        if ($properties->count() !== count($uniqueIds)) {
            throw new ModelNotFoundException('One or more properties were not found for this tenant.');
        }

        foreach ($properties as $property) {
            if ($property->project_id !== null && (int) $property->project_id !== $projectId) {
                throw new ConflictHttpException('Property is already linked to another project.');
            }
        }

        DB::transaction(function () use ($properties, $projectId): void {
            foreach ($properties as $property) {
                if ((int) $property->project_id !== $projectId) {
                    $property->update(['project_id' => $projectId]);
                }
            }
        });

        return Property::query()
            ->whereIn('id', $uniqueIds)
            ->with(['contents', 'galleryImages', 'category'])
            ->get();
    }

    public function updateForProject(
        int $tenantOwnerId,
        int $projectId,
        int $propertyId,
        array $payload,
    ): Property {
        if (array_key_exists('project_id', $payload)) {
            throw new HttpException(422, 'project_id cannot be changed after creation.');
        }

        $this->resolveProjectForTenant($tenantOwnerId, $projectId);
        $property = $this->resolvePropertyForTenant($tenantOwnerId, $propertyId);
        $this->assertPropertyOnProject($property, $projectId);

        $defaultLanguage = $this->resolveDefaultLanguage($tenantOwnerId);
        $location = $this->normalizeLocation($payload, false);

        DB::transaction(function () use ($property, $payload, $location, $defaultLanguage, $tenantOwnerId): void {
            $propertyData = $this->buildPropertyPayload($payload, (int) $property->project_id, false);
            if ($propertyData !== []) {
                $property->updateProperty($propertyData);
            }

            if (array_key_exists('gallery', $payload) && is_array($payload['gallery'])) {
                PropertySliderImg::where('property_id', $property->id)->delete();
                foreach ($payload['gallery'] as $imagePath) {
                    PropertySliderImg::storeSliderImage($tenantOwnerId, $property->id, $imagePath);
                }
            }

            $content = $property->contents()
                ->where('language_id', $defaultLanguage->id)
                ->first();

            $contentPayload = $this->buildContentPayload($payload, $location, $defaultLanguage->id, $content);

            if ($contentPayload !== []) {
                if ($content) {
                    $content->update($contentPayload);
                } else {
                    PropertyContent::storePropertyContent($tenantOwnerId, $property->id, array_merge([
                        'category_id' => $property->category_id ?? ApiUserCategory::where('slug', 'other')->value('id'),
                        'title' => $payload['title'] ?? 'Untitled',
                        'slug' => str_replace('.', '', Str::slug($payload['title'] ?? 'untitled-' . $property->id)),
                        'address' => $payload['address'] ?? '',
                        'description' => $payload['description'] ?? '',
                    ], $contentPayload));
                }
            }
        });

        return $property->fresh(['contents', 'galleryImages', 'category']);
    }

    public function detachFromProject(
        int $tenantOwnerId,
        int $projectId,
        int $propertyId,
        bool $hardDelete = false,
    ): void {
        $this->resolveProjectForTenant($tenantOwnerId, $projectId);
        $property = $this->resolvePropertyForTenant($tenantOwnerId, $propertyId);
        $this->assertPropertyOnProject($property, $projectId);

        if ($hardDelete) {
            if ($property->activeRentals()->exists()) {
                throw new ConflictHttpException('Property cannot be deleted while active rentals exist.');
            }

            DB::transaction(function () use ($property): void {
                $property->galleryImages()->delete();
                $property->proertyAmenities()->delete();
                $property->contents()->delete();
                $property->wishlists()->delete();
                $property->delete();
            });

            return;
        }

        $this->projectLinkGuard->assertProjectIdImmutable($property, null);
    }

    public function resolveProjectForTenant(int $tenantOwnerId, int $projectId): Project
    {
        $project = Project::query()
            ->whereIn('user_id', $this->allowedUserIds($tenantOwnerId))
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            throw new ModelNotFoundException('Project not found for this tenant.');
        }

        return $project;
    }

    public function resolvePropertyForTenant(int $tenantOwnerId, int $propertyId): Property
    {
        $property = Property::query()
            ->whereIn('user_id', $this->allowedUserIds($tenantOwnerId))
            ->where('id', $propertyId)
            ->first();

        if (!$property) {
            throw new ModelNotFoundException('Property not found for this tenant.');
        }

        return $property;
    }

    public function assertPropertyOnProject(Property $property, int $projectId): void
    {
        if ((int) $property->project_id !== $projectId) {
            throw new ModelNotFoundException('Property is not linked to this project.');
        }
    }

    public function normalizeLocation(array $payload, bool $required = true): array
    {
        $districtId = $payload['state_id'] ?? $payload['district_id'] ?? null;

        if ($districtId === null || $districtId === '') {
            if ($required) {
                throw new HttpException(422, 'A district_id or state_id is required.');
            }

            return [];
        }

        $district = UserDistrict::query()->findOrFail((int) $districtId);

        return [
            'state_id' => (int) $district->id,
            'city_id' => isset($payload['city_id']) ? (int) $payload['city_id'] : (int) $district->city_id,
        ];
    }

    public function assertPropertyQuota(int $tenantOwnerId): void
    {
        $membership = MembershipCacheService::getActiveMembership($tenantOwnerId);

        if (!($membership instanceof Membership) || !$membership->package) {
            throw new AccessDeniedHttpException('No active package found for the user.');
        }

        $realEstateLimit = $membership->package->real_estate_limit_number;
        $currentPropertyCount = Property::query()
            ->where('user_id', $tenantOwnerId)
            ->where('completion_status', 'complete')
            ->count();

        if (!is_null($realEstateLimit) && $currentPropertyCount >= $realEstateLimit) {
            throw new AccessDeniedHttpException('You have reached your property listing limit.');
        }
    }

    private function allowedUserIds(int $tenantOwnerId): array
    {
        $allowedUserIds = [$tenantOwnerId];

        try {
            $employeeIds = User::query()
                ->where('tenant_id', $tenantOwnerId)
                ->pluck('id')
                ->all();
            $allowedUserIds = array_values(array_unique(array_merge($allowedUserIds, $employeeIds)));
        } catch (\Throwable $e) {
            // Fall back to tenant owner only.
        }

        return $allowedUserIds;
    }

    private function resolveDefaultLanguage(int $tenantOwnerId): Language
    {
        $language = Language::query()
            ->where('user_id', $tenantOwnerId)
            ->where('is_default', 1)
            ->first();

        if (!$language) {
            throw new ModelNotFoundException('Default language not configured for this tenant.');
        }

        return $language;
    }

    private function mergeInheritedProjectLocation(
        Project $project,
        ?ProjectContent $projectContent,
        array $payload,
    ): array {
        $out = array_merge([], $payload);

        $trimmedRequestAddress = array_key_exists('address', $out)
            ? trim((string) $out['address'])
            : '';
        if ($trimmedRequestAddress !== '') {
            $out['address'] = $trimmedRequestAddress;
        } elseif ($projectContent && trim((string) ($projectContent->address ?? '')) !== '') {
            $out['address'] = trim((string) $projectContent->address);
        } else {
            $out['address'] = $trimmedRequestAddress;
        }

        foreach (['latitude', 'longitude'] as $coord) {
            $hasKey = array_key_exists($coord, $out);
            $val = $hasKey ? $out[$coord] : null;
            $missing = !$hasKey || $val === null || $val === '';
            if ($missing) {
                $inherited = $project->{$coord};
                if ($inherited !== null && $inherited !== '') {
                    $out[$coord] = $inherited;
                }
            }
        }

        return $out;
    }

    private function buildPropertyPayload(array $payload, int $projectId, bool $includeProjectId = true): array
    {
        $fields = [
            'price',
            'pricePerMeter',
            'purpose',
            'listing_purpose',
            'unit_status',
            'publish_status',
            'area',
            'status',
            'latitude',
            'longitude',
            'category_id',
            'advertising_license',
            'featured',
            'featured_image',
            'payment_method',
            'video_url',
            'virtual_tour',
            'building_id',
            'beds',
            'bath',
            'size',
            'source_broker_type',
            'source_broker_id',
            'source_broker_name',
            'source_broker_phone',
        ];

        $data = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }

        if ($includeProjectId) {
            $data['project_id'] = $projectId;
        }

        return $data;
    }

    private function buildContentPayload(array $payload, array $location, int $languageId, ?PropertyContent $content): array
    {
        $data = [];

        foreach (['title', 'address', 'description', 'meta_keyword', 'meta_description'] as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }

        if (array_key_exists('title', $payload)) {
            $data['slug'] = PropertyContent::generateUniqueSlug($payload['title'], $content?->property_id);
        }

        if ($location !== []) {
            $data['state_id'] = $location['state_id'];
            $data['city_id'] = $location['city_id'];
        }

        if (array_key_exists('category_id', $payload)) {
            $data['category_id'] = $payload['category_id'];
        }

        if ($data === []) {
            return [];
        }

        $data['language_id'] = $languageId;

        return $data;
    }
}
