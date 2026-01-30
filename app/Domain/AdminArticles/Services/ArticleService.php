<?php

namespace App\Domain\AdminArticles\Services;

use App\Models\AdminArticle;
use App\Enums\ArticleStatus;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArticleService extends BaseService
{
    /**
     * Get articles with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getArticles(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AdminArticle::with(['category', 'admin']);

        // Only published articles for public API (must be first to override status filter)
        if (isset($filters['published_only']) && $filters['published_only']) {
            $query->where('status', ArticleStatus::PUBLISHED)
                ->where(function ($q) {
                    $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
        } elseif (isset($filters['status'])) {
            // Filter by status (only if not published_only)
            $status = ArticleStatus::from($filters['status']);
            $query->where('status', $status);
        }

        // Filter by category (single or multiple)
        if (!empty($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        } elseif (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Search
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('excerpt', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('body', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get article by ID
     *
     * @param int $id
     * @return AdminArticle
     * @throws ResourceNotFoundException
     */
    public function getArticleById(int $id): AdminArticle
    {
        $article = AdminArticle::with(['category', 'admin'])->find($id);

        return $this->ensureFound($article, 'Article not found');
    }

    /**
     * Get article by slug
     *
     * @param string $slug
     * @return AdminArticle
     * @throws ResourceNotFoundException
     */
    public function getArticleBySlug(string $slug): AdminArticle
    {
        $article = AdminArticle::with(['category', 'admin'])
            ->where('slug', $slug)
            ->first();

        return $this->ensureFound($article, 'Article not found');
    }

    /**
     * Create a new article
     *
     * @param array $data
     * @param int $adminId
     * @return AdminArticle
     */
    public function createArticle(array $data, int $adminId): AdminArticle
    {
        return $this->executeInTransaction(function () use ($data, $adminId) {
            $data['admin_id'] = $adminId;

            // Handle image upload
            if (isset($data['main_image']) && $data['main_image'] instanceof UploadedFile) {
                $data['main_image'] = $this->handleImageUpload($data['main_image']);
            }

            // Handle OG image upload
            if (isset($data['og_image']) && $data['og_image'] instanceof UploadedFile) {
                $data['og_image'] = $this->handleImageUpload($data['og_image']);
            }

            return AdminArticle::create($data);
        });
    }

    /**
     * Update an article
     *
     * @param int $id
     * @param array $data
     * @return AdminArticle
     * @throws ResourceNotFoundException
     */
    public function updateArticle(int $id, array $data): AdminArticle
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $article = $this->getArticleById($id);

            // Handle image upload
            if (isset($data['main_image']) && $data['main_image'] instanceof UploadedFile) {
                $data['main_image'] = $this->handleImageUpload($data['main_image'], $article->main_image);
            }

            // Handle OG image upload
            if (isset($data['og_image']) && $data['og_image'] instanceof UploadedFile) {
                $data['og_image'] = $this->handleImageUpload($data['og_image'], $article->og_image);
            }

            $article->update($data);
            return $article->fresh(['category', 'admin']);
        });
    }

    /**
     * Delete an article
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteArticle(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $article = $this->getArticleById($id);

            // Delete images
            if ($article->main_image) {
                $this->deleteImage($article->main_image);
            }
            if ($article->og_image) {
                $this->deleteImage($article->og_image);
            }

            return $article->delete();
        });
    }

    /**
     * Change article status
     *
     * @param int $id
     * @param ArticleStatus $status
     * @return AdminArticle
     * @throws ResourceNotFoundException
     */
    public function changeStatus(int $id, ArticleStatus $status): AdminArticle
    {
        return $this->executeInTransaction(function () use ($id, $status) {
            $article = $this->getArticleById($id);
            $article->status = $status;

            if ($status === ArticleStatus::PUBLISHED && $article->published_at === null) {
                $article->published_at = now();
            }

            $article->save();
            return $article->fresh(['category', 'admin']);
        });
    }

    /**
     * Handle image upload
     *
     * @param UploadedFile|null $file
     * @param string|null $oldPath
     * @return string|null
     */
    public function handleImageUpload(?UploadedFile $file, ?string $oldPath = null): ?string
    {
        if (!$file) {
            return $oldPath;
        }

        // Validate file
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed types: JPG, JPEG, PNG, WEBP');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('Image size exceeds maximum allowed size of 5MB');
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $directory = public_path('assets/front/img/admin-articles');

        // Create directory if it doesn't exist
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Move file
        $file->move($directory, $filename);
        $imagePath = 'assets/front/img/admin-articles/' . $filename;

        // Delete old image if provided
        if ($oldPath && File::exists(public_path($oldPath))) {
            File::delete(public_path($oldPath));
        }

        return $imagePath;
    }

    /**
     * Delete image file
     *
     * @param string $path
     * @return void
     */
    protected function deleteImage(string $path): void
    {
        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
