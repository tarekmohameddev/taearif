<?php

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
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
}
