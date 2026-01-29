<?php

namespace App\Policies;

use App\Domain\Admin\Models\Admin;
use App\Models\AdminArticleCategory;

class AdminArticleCategoryPolicy
{
    /**
     * Determine if the admin can view any categories.
     */
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can view the category.
     */
    public function view(Admin $admin, AdminArticleCategory $category): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can create categories.
     */
    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can update the category.
     */
    public function update(Admin $admin, AdminArticleCategory $category): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can delete the category.
     */
    public function delete(Admin $admin, AdminArticleCategory $category): bool
    {
        return $admin->hasPermission('Admin Articles');
    }
}
