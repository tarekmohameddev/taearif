<?php

namespace App\Domain\Shared\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Implementation
 *
 * Provides common data access logic for all repositories
 * Concrete repositories should extend this class
 */
abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * BaseRepository constructor
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records
     *
     * @param array $columns
     * @return Collection
     */
    public function getAll(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Get paginated records with filters
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query = $this->applySearch($query, $filters['search']);
        }

        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply sorting
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = $filters['per_page'] ?? config('admin-api.pagination.per_page', 20);
        $perPage = min($perPage, config('admin-api.pagination.max_per_page', 100));

        return $query->paginate($perPage);
    }

    /**
     * Apply search logic (override in child classes)
     *
     * @param $query
     * @param string $search
     * @return mixed
     */
    protected function applySearch($query, string $search)
    {
        return $query;
    }

    /**
     * Find record by UUID
     *
     * @param string $uuid
     * @return Model|null
     */
    public function findByUuid(string $uuid): ?Model
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * Find record by UUID with relationships.
     *
     * @param string $uuid
     * @param array $relations
     * @param array $columns
     * @return Model|null
     */
    public function findByUuidWith(string $uuid, array $relations = [], array $columns = ['*']): ?Model
    {
        $query = $this->model->newQuery();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->where('uuid', $uuid)->first($columns);
    }

    /**
     * Find record by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update existing record
     *
     * @param Model $entity
     * @param array $data
     * @return Model
     */
    public function update(Model $entity, array $data): Model
    {
        $entity->update($data);
        return $entity->fresh();
    }

    /**
     * Delete record
     *
     * @param Model $entity
     * @return bool
     */
    public function delete(Model $entity): bool
    {
        return $entity->delete();
    }

    /**
     * Get record count
     *
     * @param array $filters
     * @return int
     */
    public function count(array $filters = []): int
    {
        $query = $this->model->newQuery();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->count();
    }

    /**
     * Check if record exists
     *
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function exists(string $column, mixed $value): bool
    {
        return $this->model->where($column, $value)->exists();
    }
}

