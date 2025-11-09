<?php

namespace App\Domain\Domain\Repositories;

use App\Domain\Domain\Models\CustomDomain;
use App\Domain\Shared\Repositories\BaseRepositoryInterface;

/**
 * Custom Domain Repository Interface
 *
 * Contract for Custom Domain data access operations
 */
interface DomainRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find domain by name
     *
     * @param string $domainName
     * @return CustomDomain|null
     */
    public function findByName(string $domainName): ?CustomDomain;

    /**
     * Get domains by user
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(int $userId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get verified domains
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVerified(): \Illuminate\Database\Eloquent\Collection;
}

