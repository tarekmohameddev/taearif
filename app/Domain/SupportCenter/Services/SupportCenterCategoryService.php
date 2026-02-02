<?php

namespace App\Domain\SupportCenter\Services;

use App\Models\SupportCenterCategory;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SupportCenterCategoryService extends BaseService
{
    /**
     * Get all categories with optional filters
     *
     * @param array $filters
     * @return Collection<int, SupportCenterCategory>
     */
    public function getAllCategories(array $filters = []): Collection
    {
        $query = SupportCenterCategory::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('short_description', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get category by ID
     *
     * @param int $id
     * @return SupportCenterCategory
     * @throws ResourceNotFoundException
     */
    public function getCategoryById(int $id): SupportCenterCategory
    {
        $category = SupportCenterCategory::find($id);

        return $this->ensureFound($category, 'Category not found');
    }

    /**
     * Get category by slug
     *
     * @param string $slug
     * @return SupportCenterCategory|null
     */
    public function getCategoryBySlug(string $slug): ?SupportCenterCategory
    {
        return SupportCenterCategory::where('slug', $slug)->first();
    }

    /**
     * Create a new category
     *
     * @param array $data
     * @return SupportCenterCategory
     */
    public function createCategory(array $data): SupportCenterCategory
    {
        return $this->executeInTransaction(function () use ($data) {
            if (isset($data['icon_image']) && $data['icon_image'] instanceof UploadedFile) {
                $data['icon_image'] = $this->handleIconUpload($data['icon_image']);
            }
            return SupportCenterCategory::create($data);
        });
    }

    /**
     * Update a category
     *
     * @param int $id
     * @param array $data
     * @return SupportCenterCategory
     * @throws ResourceNotFoundException
     */
    public function updateCategory(int $id, array $data): SupportCenterCategory
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $category = $this->getCategoryById($id);
            if (isset($data['icon_image']) && $data['icon_image'] instanceof UploadedFile) {
                $data['icon_image'] = $this->handleIconUpload($data['icon_image'], $category->icon_image);
            }
            $category->update($data);
            return $category->fresh();
        });
    }

    /**
     * Handle icon image upload
     *
     * @param UploadedFile|null $file
     * @param string|null $oldPath
     * @return string|null
     */
    public function handleIconUpload(?UploadedFile $file, ?string $oldPath = null): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed types: JPG, JPEG, PNG, WEBP');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('Image size exceeds maximum allowed size of 5MB');
        }

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $directory = public_path('assets/front/img/support_center/icons');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file->move($directory, $filename);
        $imagePath = 'assets/front/img/support_center/icons/' . $filename;

        if ($oldPath && File::exists(public_path($oldPath))) {
            File::delete(public_path($oldPath));
        }

        return $imagePath;
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
        $category = SupportCenterCategory::find($id);

        if (!$category) {
            return false;
        }

        return $category->articles()->count() === 0;
    }
}
