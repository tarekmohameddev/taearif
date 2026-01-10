<?php

namespace App\Repositories\Crm;

use App\Models\ReminderType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReminderTypeRepository
{
    /**
     * Get all reminder types for a user with pagination
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $userId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = ReminderType::forUser($userId)
            ->withCount('reminders')
            ->orderBy('order')
            ->orderBy('name');

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->paginate(min($perPage, 100));
    }

    /**
     * Get all reminder types for a user
     *
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getAll(int $userId, array $filters = []): Collection
    {
        $query = ReminderType::forUser($userId)
            ->withCount('reminders')
            ->orderBy('order')
            ->orderBy('name');

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    /**
     * Find reminder type by ID for a user
     *
     * @param int $id
     * @param int $userId
     * @return ReminderType|null
     */
    public function findByIdForUser(int $id, int $userId): ?ReminderType
    {
        return ReminderType::forUser($userId)->find($id);
    }

    /**
     * Create a new reminder type
     *
     * @param array $data
     * @return ReminderType
     */
    public function create(array $data): ReminderType
    {
        return ReminderType::create($data);
    }

    /**
     * Update reminder type
     *
     * @param ReminderType $reminderType
     * @param array $data
     * @return ReminderType
     */
    public function update(ReminderType $reminderType, array $data): ReminderType
    {
        $reminderType->update($data);
        return $reminderType->fresh();
    }

    /**
     * Delete reminder type (soft delete)
     *
     * @param ReminderType $reminderType
     * @return bool
     */
    public function delete(ReminderType $reminderType): bool
    {
        return $reminderType->delete();
    }
}
