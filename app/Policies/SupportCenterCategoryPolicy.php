<?php

namespace App\Policies;

use App\Domain\Admin\Models\Admin;
use App\Models\SupportCenterCategory;

class SupportCenterCategoryPolicy
{
    /**
     * Determine if the admin can view any categories.
     */
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can view the category.
     */
    public function view(Admin $admin, SupportCenterCategory $category): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can create categories.
     */
    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can update the category.
     */
    public function update(Admin $admin, SupportCenterCategory $category): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can delete the category.
     */
    public function delete(Admin $admin, SupportCenterCategory $category): bool
    {
        return $admin->hasPermission('Center of Support');
    }
}
