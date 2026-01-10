<?php

namespace App\Repositories\Crm;

use App\Models\Reminder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ReminderRepository
{
    /**
     * Get all reminders for a user with pagination
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $userId, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Reminder::forUser($userId)
            ->with(['reminderType', 'customer.city', 'customer.district'])
            ->orderBy('datetime', 'asc');

        // Apply filters
        if (isset($filters['customer_id'])) {
            $query->forCustomer($filters['customer_id']);
        }

        if (isset($filters['reminder_type_id'])) {
            $query->forReminderType($filters['reminder_type_id']);
        }

        if (isset($filters['status'])) {
            $query->forStatus($filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->forPriority($filters['priority']);
        }

        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->paginate(min($perPage, 100));
    }

    /**
     * Get all reminders for a user
     *
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getAll(int $userId, array $filters = []): Collection
    {
        $query = Reminder::forUser($userId)
            ->with(['reminderType', 'customer.city', 'customer.district'])
            ->orderBy('datetime', 'asc');

        // Apply filters
        if (isset($filters['customer_id'])) {
            $query->forCustomer($filters['customer_id']);
        }

        if (isset($filters['reminder_type_id'])) {
            $query->forReminderType($filters['reminder_type_id']);
        }

        if (isset($filters['status'])) {
            $query->forStatus($filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->forPriority($filters['priority']);
        }

        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $query->dateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->get();
    }

    /**
     * Find reminder by ID for a user
     *
     * @param int $id
     * @param int $userId
     * @return Reminder|null
     */
    public function findByIdForUser(int $id, int $userId): ?Reminder
    {
        return Reminder::forUser($userId)
            ->with(['reminderType', 'customer.city', 'customer.district'])
            ->find($id);
    }

    /**
     * Create a new reminder
     *
     * @param array $data
     * @return Reminder
     */
    public function create(array $data): Reminder
    {
        return Reminder::create($data);
    }

    /**
     * Update reminder
     *
     * @param Reminder $reminder
     * @param array $data
     * @return Reminder
     */
    public function update(Reminder $reminder, array $data): Reminder
    {
        $reminder->update($data);
        return $reminder->fresh(['reminderType', 'customer.city', 'customer.district']);
    }

    /**
     * Delete reminder (soft delete)
     *
     * @param Reminder $reminder
     * @return bool
     */
    public function delete(Reminder $reminder): bool
    {
        return $reminder->delete();
    }
}
