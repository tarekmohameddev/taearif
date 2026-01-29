<?php

namespace App\Policies;

use App\Domain\Admin\Models\Admin;
use App\Models\AdminArticle;

class AdminArticlePolicy
{
    /**
     * Determine if the admin can view any articles.
     */
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can view the article.
     */
    public function view(Admin $admin, AdminArticle $article): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can create articles.
     */
    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('Admin Articles');
    }

    /**
     * Determine if the admin can update the article.
     */
    public function update(Admin $admin, AdminArticle $article): bool
    {
        // Admin can update their own articles or if they have permission
        return $admin->hasPermission('Admin Articles') || $article->admin_id === $admin->id;
    }

    /**
     * Determine if the admin can delete the article.
     */
    public function delete(Admin $admin, AdminArticle $article): bool
    {
        return $admin->hasPermission('Admin Articles');
    }
}
