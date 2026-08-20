<?php

namespace App\Domain\PropertyRequests\Services;

use App\Models\Api\UserPropertyRequest;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Validation\ValidationException;

class PropertyRequestLinkSync
{
    /**
     * @param  array<int, mixed>  $ids
     * @return array<int>
     */
    public function assertOwnedPropertyIds(int $ownerId, array $ids): array
    {
        return $this->assertOwnedIds(
            Property::query()->where('user_id', $ownerId),
            $ids,
            'property_ids',
            'The selected property IDs are invalid or unauthorized for this tenant.'
        );
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int>
     */
    public function assertOwnedProjectIds(int $ownerId, array $ids): array
    {
        return $this->assertOwnedIds(
            Project::query()->where('user_id', $ownerId),
            $ids,
            'project_ids',
            'The selected project IDs are invalid or unauthorized for this tenant.'
        );
    }

    /**
     * project_ids takes precedence over legacy project_id, including an empty array.
     *
     * @return array<int>|null
     */
    public function resolveIncomingProjectIds(array $input): ?array
    {
        if (array_key_exists('project_ids', $input)) {
            return $this->normalizeIds(is_array($input['project_ids']) ? $input['project_ids'] : []);
        }

        if (array_key_exists('project_id', $input)) {
            return $input['project_id'] === null || $input['project_id'] === ''
                ? []
                : $this->normalizeIds([$input['project_id']]);
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    public function syncProjectIds(UserPropertyRequest $propertyRequest, array $ids): void
    {
        $ids = $this->normalizeIds($ids);

        $propertyRequest->projects()->sync($ids);
        $propertyRequest->forceFill(['project_id' => $ids[0] ?? null])->save();
        $propertyRequest->setRelation(
            'projects',
            $propertyRequest->projects()->get(['user_projects.id'])
        );
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    public function attachProjectIds(UserPropertyRequest $propertyRequest, array $ids): void
    {
        $existing = $propertyRequest->projects()
            ->orderBy('property_request_project.id')
            ->pluck('user_projects.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->syncProjectIds($propertyRequest, array_merge($existing, $ids));
    }

    public function detachProjectId(UserPropertyRequest $propertyRequest, int $projectId): void
    {
        $remaining = $propertyRequest->projects()
            ->where('user_projects.id', '!=', $projectId)
            ->orderBy('property_request_project.id')
            ->pluck('user_projects.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->syncProjectIds($propertyRequest, $remaining);
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int>
     */
    public function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, mixed>  $ids
     * @return array<int>
     */
    private function assertOwnedIds($query, array $ids, string $field, string $message): array
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return [];
        }

        $ownedIds = $query->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($ownedIds) !== count($ids) || array_diff($ids, $ownedIds) !== []) {
            throw ValidationException::withMessages([$field => [$message]]);
        }

        return $ids;
    }
}
