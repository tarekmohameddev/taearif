<?php

namespace App\Policies;

use App\Domain\Admin\Models\Admin;
use App\Models\SupportCenterArticle;

class SupportCenterArticlePolicy
{
    /**
     * Determine if the admin can view any articles.
     */
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can view the article.
     */
    public function view(Admin $admin, SupportCenterArticle $article): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can create articles.
     */
    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('Center of Support');
    }

    /**
     * Determine if the admin can update the article.
     */
    public function update(Admin $admin, SupportCenterArticle $article): bool
    {
        return $admin->hasPermission('Center of Support') || $article->admin_id === $admin->id;
    }

    /**
     * Determine if the admin can delete the article.
     */
    public function delete(Admin $admin, SupportCenterArticle $article): bool
    {
        return $admin->hasPermission('Center of Support');
    }
}
