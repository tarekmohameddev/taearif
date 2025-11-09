<?php

namespace App\Domain\Shared\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base Repository Interface
 *
 * Defines common data access methods for all repositories
 */
interface BaseRepositoryInterface
{
    /**
     * Get all records
     *
     * @param array $columns
     * @return Collection
     */
    public function getAll(array $columns = ['*']): Collection;

    /**
     * Get paginated records with filters
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters): LengthAwarePaginator;

    /**
     * Find record by UUID
     *
     * @param string $uuid
     * @return Model|null
     */
    public function findByUuid(string $uuid): ?Model;

    /**
     * Find record by UUID with relationships.
     *
     * @param string $uuid
     * @param array $relations
     * @param array $columns
     * @return Model|null
     */
    public function findByUuidWith(string $uuid, array $relations = [], array $columns = ['*']): ?Model;

    /**
     * Find record by ID
     *
     * @param int $id
     * @return Model|null
     */
    public function findById(int $id): ?Model;

    /**
     * Find record by ID with relationships.
     *
     * @param int $id
     * @param array $relations
     * @param array $columns
     * @return Model|null
     */
    public function findByIdWith(int $id, array $relations = [], array $columns = ['*']): ?Model;

    /**
     * Create a new record
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Update existing record
     *
     * @param Model $entity
     * @param array $data
     * @return Model
     */
    public function update(Model $entity, array $data): Model;

    /**
     * Delete record
     *
     * @param Model $entity
     * @return bool
     */
    public function delete(Model $entity): bool;

    /**
     * Get record count
     *
     * @param array $filters
     * @return int
     */
    public function count(array $filters = []): int;

    /**
     * Check if record exists
     *
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function exists(string $column, mixed $value): bool;
}

