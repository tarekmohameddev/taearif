<?php

namespace App\Domain\SupportCenter\Services;

use App\Models\SupportCenterArticle;
use App\Enums\ArticleStatus;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SupportCenterArticleService extends BaseService
{
    /**
     * Get articles with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator<SupportCenterArticle>
     */
    public function getArticles(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SupportCenterArticle::with(['category', 'admin']);

        if (isset($filters['published_only']) && $filters['published_only']) {
            $query->where('status', ArticleStatus::PUBLISHED)
                ->where(function ($q) {
                    $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                });
        } elseif (isset($filters['status'])) {
            $status = ArticleStatus::from($filters['status']);
            $query->where('status', $status);
        }

        if (!empty($filters['category_ids'])) {
            $query->whereIn('category_id', $filters['category_ids']);
        } elseif (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('excerpt', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get article by ID
     *
     * @param int $id
     * @return SupportCenterArticle
     * @throws ResourceNotFoundException
     */
    public function getArticleById(int $id): SupportCenterArticle
    {
        $article = SupportCenterArticle::with(['category', 'admin'])->find($id);

        return $this->ensureFound($article, 'Article not found');
    }

    /**
     * Get article by slug
     *
     * @param string $slug
     * @return SupportCenterArticle
     * @throws ResourceNotFoundException
     */
    public function getArticleBySlug(string $slug): SupportCenterArticle
    {
        $article = SupportCenterArticle::with(['category', 'admin'])
            ->where('slug', $slug)
            ->first();

        return $this->ensureFound($article, 'Article not found');
    }

    /**
     * Create a new article
     *
     * @param array $data
     * @param int $adminId
     * @return SupportCenterArticle
     */
    public function createArticle(array $data, int $adminId): SupportCenterArticle
    {
        return $this->executeInTransaction(function () use ($data, $adminId) {
            $data['admin_id'] = $adminId;

            if (isset($data['main_image']) && $data['main_image'] instanceof UploadedFile) {
                $data['main_image'] = $this->handleImageUpload($data['main_image'], 'support_center/articles');
            }

            return SupportCenterArticle::create($data);
        });
    }

    /**
     * Update an article
     *
     * @param int $id
     * @param array $data
     * @return SupportCenterArticle
     * @throws ResourceNotFoundException
     */
    public function updateArticle(int $id, array $data): SupportCenterArticle
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $article = $this->getArticleById($id);

            if (isset($data['main_image']) && $data['main_image'] instanceof UploadedFile) {
                $data['main_image'] = $this->handleImageUpload(
                    $data['main_image'],
                    'support_center/articles',
                    $article->main_image
                );
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

            if ($article->main_image) {
                $this->deleteImage($article->main_image);
            }

            return $article->delete();
        });
    }

    /**
     * Handle image upload
     *
     * @param UploadedFile|null $file
     * @param string $subDir
     * @param string|null $oldPath
     * @return string|null
     */
    public function handleImageUpload(?UploadedFile $file, string $subDir = 'support_center', ?string $oldPath = null): ?string
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
        $directory = public_path('assets/front/img/' . $subDir);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $file->move($directory, $filename);
        $imagePath = 'assets/front/img/' . $subDir . '/' . $filename;

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
