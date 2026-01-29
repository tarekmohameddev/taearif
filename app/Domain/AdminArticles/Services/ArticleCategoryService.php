<?php

namespace App\Domain\AdminArticles\Services;

use App\Models\AdminArticleCategory;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Database\Eloquent\Collection;

class ArticleCategoryService extends BaseService
{
    /**
     * Get all categories with optional filters
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllCategories(array $filters = []): Collection
    {
        $query = AdminArticleCategory::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get category by ID
     *
     * @param int $id
     * @return AdminArticleCategory
     * @throws ResourceNotFoundException
     */
    public function getCategoryById(int $id): AdminArticleCategory
    {
        $category = AdminArticleCategory::find($id);

        return $this->ensureFound($category, 'Category not found');
    }

    /**
     * Create a new category
     *
     * @param array $data
     * @return AdminArticleCategory
     */
    public function createCategory(array $data): AdminArticleCategory
    {
        return $this->executeInTransaction(function () use ($data) {
            return AdminArticleCategory::create($data);
        });
    }

    /**
     * Update a category
     *
     * @param int $id
     * @param array $data
     * @return AdminArticleCategory
     * @throws ResourceNotFoundException
     */
    public function updateCategory(int $id, array $data): AdminArticleCategory
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $category = $this->getCategoryById($id);
            $category->update($data);
            return $category->fresh();
        });
    }

    /**
     * Delete a category
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function deleteCategory(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $category = $this->getCategoryById($id);

            if (!$this->canDeleteCategory($id)) {
                $articleCount = $category->articles()->count();
                throw new BusinessLogicException(
                    "Cannot delete category. It has {$articleCount} article(s).",
                    'CATEGORY_HAS_ARTICLES',
                    422
                );
            }

            return $category->delete();
        });
    }

    /**
     * Check if category can be deleted
     *
     * @param int $id
     * @return bool
     */
    public function canDeleteCategory(int $id): bool
    {
        $category = AdminArticleCategory::find($id);

        if (!$category) {
            return false;
        }

        return $category->articles()->count() === 0;
    }
}
