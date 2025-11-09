<?php

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * User Repository Interface
 * 
 * Defines the contract for User data access operations
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate users with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get tenants with filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTenants(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a user by email address.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;
}
