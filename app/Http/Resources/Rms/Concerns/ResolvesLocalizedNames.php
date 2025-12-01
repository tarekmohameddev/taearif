<?php

namespace App\Http\Resources\Rms\Concerns;

use App\Models\Building;
use App\Models\User\Language as UserLanguage;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait ResolvesLocalizedNames
{
    protected static array $languageCache = [];
    protected static array $propertyNameCache = [];
    protected static array $projectNameCache = [];
    protected static array $buildingNameCache = [];

    /**
     * Resolve the default language ID for an owner.
     */
    protected function resolveLanguageIdForUser(?int $userId): int
    {
        if (!$userId) {
            return 1;
        }

        if (!array_key_exists($userId, self::$languageCache)) {
            self::$languageCache[$userId] = UserLanguage::query()
                ->where('user_id', $userId)
                ->where('is_default', 1)
                ->value('id') ?? 1;
        }

        return self::$languageCache[$userId];
    }

    /**
     * Resolve a localized property name.
     */
    protected function resolvePropertyName(?Model $property, ?int $propertyId, ?int $ownerId): ?string
    {
        $cacheKey = $property?->getKey() ?? $propertyId;
        if ($cacheKey && array_key_exists($cacheKey, self::$propertyNameCache)) {
            return self::$propertyNameCache[$cacheKey];
        }

        $languageId = $this->resolveLanguageIdForUser($ownerId);
        $title = $this->extractPropertyTitle($property, $propertyId, $languageId);

        if ($cacheKey) {
            self::$propertyNameCache[$cacheKey] = $title;
        }

        return $title;
    }

    /**
     * Resolve a localized project name.
     */
    protected function resolveProjectName(?Model $project, ?int $projectId, ?int $ownerId): ?string
    {
        $cacheKey = $project?->getKey() ?? $projectId;
        if ($cacheKey && array_key_exists($cacheKey, self::$projectNameCache)) {
            return self::$projectNameCache[$cacheKey];
        }

        $languageId = $this->resolveLanguageIdForUser($ownerId);
        $title = $this->extractProjectTitle($project, $projectId, $languageId);

        if ($cacheKey) {
            self::$projectNameCache[$cacheKey] = $title;
        }

        return $title;
    }

    /**
     * Resolve building name.
     */
    protected function resolveBuildingName(?Model $building, ?int $buildingId): ?string
    {
        $cacheKey = $building?->getKey() ?? $buildingId;
        if ($cacheKey && array_key_exists($cacheKey, self::$buildingNameCache)) {
            return self::$buildingNameCache[$cacheKey];
        }

        if ($building && !is_null($building->name)) {
            $name = $building->name;
        } elseif ($buildingId) {
            $name = Building::query()
                ->where('id', $buildingId)
                ->value('name');
        } else {
            $name = null;
        }

        if ($cacheKey) {
            self::$buildingNameCache[$cacheKey] = $name;
        }

        return $name;
    }

    private function extractPropertyTitle(?Model $property, ?int $propertyId, int $languageId): ?string
    {
        if ($property) {
            $property->loadMissing('contents');
            $contents = $property->getRelation('contents');
            $content = $this->pickLocalizedContent($contents, $languageId);

            if ($content?->title) {
                return $content->title;
            }
        }

        if (!$propertyId) {
            return null;
        }

        $content = PropertyContent::query()
            ->where('property_id', $propertyId)
            ->orderByRaw('language_id = ? DESC', [$languageId])
            ->orderBy('id')
            ->first();

        return $content?->title;
    }

    private function extractProjectTitle(?Model $project, ?int $projectId, int $languageId): ?string
    {
        if ($project) {
            $project->loadMissing('contents');
            $contents = $project->getRelation('contents');
            $content = $this->pickLocalizedContent($contents, $languageId);

            if ($content?->title) {
                return $content->title;
            }
        }

        if (!$projectId) {
            return null;
        }

        $content = ProjectContent::query()
            ->where('project_id', $projectId)
            ->orderByRaw('language_id = ? DESC', [$languageId])
            ->orderBy('id')
            ->first();

        return $content?->title;
    }

    /**
     * Select localized content from a collection.
     */
    private function pickLocalizedContent(Collection $contents, int $languageId): ?Model
    {
        if ($contents->isEmpty()) {
            return null;
        }

        return $contents->firstWhere('language_id', $languageId)
            ?? $contents->first();
    }
}

