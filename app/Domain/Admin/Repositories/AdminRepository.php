<?php

namespace App\Domain\Admin\Repositories;

use App\Domain\Admin\Models\Admin;
use App\Domain\Shared\Repositories\BaseRepository;

/**
 * Admin Repository
 *
 * Handles data access for Admin model
 */
class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{
    /**
     * AdminRepository constructor.
     *
     * @param Admin $model
     */
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    /**
     * Find admin by email
     *
     * @param string $email
     * @return Admin|null
     */
    public function findByEmail(string $email): ?Admin
    {
        return $this->model
            ->where('email', $email)
            ->first();
    }

    /**
     * Get active admins
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->where('status', true)
            ->with('role')
            ->get();
    }

    /**
     * Find admin by UUID with role
     *
     * @param string $uuid
     * @return Admin|null
     */
    public function findByUuidWithRole(string $uuid): ?Admin
    {
        return $this->model
            ->where('uuid', $uuid)
            ->with('role')
            ->first();
    }

    /**
     * Update admin's last login
     *
     * @param Admin $admin
     * @return bool
     */
    public function updateLastLogin(Admin $admin): bool
    {
        $admin->last_login_at = now();
        return $admin->save();
    }
}

