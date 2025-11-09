<?php

namespace App\Domain\Admin\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Admin\Models\Admin;

/**
 * Admin Repository Interface
 * 
 * Defines the contract for Admin data access operations
 */
interface AdminRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find admin by email
     *
     * @param string $email
     * @return Admin|null
     */
    public function findByEmail(string $email): ?Admin;
}
