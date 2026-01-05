<?php

namespace App\Domain\Marketplace\Services;

use App\Domain\Shared\Services\BaseService;
use App\Enums\BillingType;
use App\Exceptions\Marketplace\AppHasInstallationsException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Api\ApiApp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

/**
 * Marketplace App Service
 *
 * Business logic for managing marketplace apps
 */
class MarketplaceAppService extends BaseService
{
    public function __construct(
        private MarketplaceAppImageService $imageService
    ) {
    }

    /**
     * Get paginated apps with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getApps(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ApiApp::query();

        // Search filter
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Type filter
        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Billing type filter
        if (isset($filters['billing_type']) && !empty($filters['billing_type'])) {
            $query->where('billing_type', $filters['billing_type']);
        }

        return $query->orderBy('created_at', 'DESC')->paginate($perPage);
    }

    /**
     * Get app by ID
     *
     * @param int $id
     * @return ApiApp
     * @throws ResourceNotFoundException
     */
    public function getAppById(int $id): ApiApp
    {
        $app = ApiApp::find($id);
        return $this->ensureFound($app, 'Marketplace app not found');
    }

    /**
     * Create a new app
     *
     * @param array $data
     * @param UploadedFile|null $image
     * @return ApiApp
     */
    public function createApp(array $data, ?UploadedFile $image = null): ApiApp
    {
        // Generate a unique key for this submission to prevent duplicates
        // Use request fingerprint: name + price + billing_type + user_id (NO timestamp - ensures same data = same lock key)
        $requestFingerprint = md5(
            ($data['name'] ?? '') . 
            ($data['price'] ?? '') . 
            ($data['billing_type'] ?? '') . 
            (auth('admin')->id() ?? 'guest')
        );
        
        // Use database-level lock for true atomicity (works with all cache drivers)
        $lockKey = 'marketplace_app_submission_' . $requestFingerprint;
        $lockAcquired = false;
        $lockTimeout = 2; // Wait up to 2 seconds to acquire lock
        
        // Use MySQL GET_LOCK for atomic locking (release with RELEASE_LOCK)
        $lockResult = \Illuminate\Support\Facades\DB::selectOne(
            "SELECT GET_LOCK(?, ?) as lock_acquired",
            [$lockKey, $lockTimeout]
        );
        
        if (!isset($lockResult->lock_acquired) || $lockResult->lock_acquired != 1) {
            throw new \App\Exceptions\BusinessLogicException(
                'Duplicate submission detected. Please wait a moment and try again.',
                'DUPLICATE_SUBMISSION',
                429
            );
        }
        
        $lockAcquired = true;

        // Step 1: Handle image upload (outside transaction)
        $imagePath = null;
        if ($image) {
            $imagePath = $this->imageService->uploadFile($image);
        } elseif (isset($data['img']) && !empty($data['img'])) {
            $imagePath = $this->imageService->validateUrl($data['img']);
        }

        // Step 2: Prepare app data
        $appData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'type' => $data['type'] ?? 'marketplace',
            'rating' => $data['rating'] ?? 0,
            'billing_type' => BillingType::from($data['billing_type']),
            'trial_days' => $data['billing_type'] === 'paid_trial' ? ($data['trial_days'] ?? null) : null,
        ];

        if ($imagePath) {
            $appData['img'] = $imagePath;
        }

        // Step 3: Create app (inside transaction)
        try {
            $result = $this->executeInTransaction(function () use ($appData) {
                // Double-check: Check if an identical app was just created in the last 30 seconds
                // This provides additional protection against race conditions even if locks fail
                // Extended window to catch rapid duplicate submissions while allowing legitimate duplicates later
                $recentDuplicate = ApiApp::where('name', $appData['name'])
                    ->where('price', $appData['price'])
                    ->where('billing_type', $appData['billing_type'])
                    ->where('created_at', '>=', now()->subSeconds(30))
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($recentDuplicate) {
                    // Return existing app instead of creating duplicate
                    return $recentDuplicate;
                }
                
                $app = ApiApp::create($appData);
                $this->logActivity('marketplace_app.created', ['app_id' => $app->id]);
                
                return $app;
            });
            
            // Release database lock AFTER transaction commits successfully
            if ($lockAcquired) {
                \Illuminate\Support\Facades\DB::selectOne("SELECT RELEASE_LOCK(?)", [$lockKey]);
            }
            
            return $result;
        } catch (\Exception $e) {
            // Release database lock on error if it was acquired
            if (isset($lockAcquired) && $lockAcquired) {
                \Illuminate\Support\Facades\DB::selectOne("SELECT RELEASE_LOCK(?)", [$lockKey]);
            }
            
            // If transaction fails, clean up uploaded file
            if ($imagePath && $this->imageService->isLocalFile($imagePath)) {
                $this->imageService->deleteImage($imagePath);
            }
            throw $e;
        }
    }

    /**
     * Update an existing app
     *
     * @param int $id
     * @param array $data
     * @param UploadedFile|null $image
     * @return ApiApp
     * @throws ResourceNotFoundException
     */
    public function updateApp(int $id, array $data, ?UploadedFile $image = null): ApiApp
    {
        $app = $this->getAppById($id);
        $oldImagePath = $app->img;
        $imagePath = $oldImagePath;

        // Step 1: Handle image upload/update (outside transaction)
        if ($image) {
            // New file uploaded
            $imagePath = $this->imageService->uploadFile($image, $oldImagePath);
        } elseif (isset($data['img']) && !empty($data['img']) && $data['img'] !== $oldImagePath) {
            // New URL provided
            $newImgValue = $data['img'];
            
            // Check if it's an external URL
            if (strpos($newImgValue, 'http://') === 0 || strpos($newImgValue, 'https://') === 0) {
                $imagePath = $this->imageService->validateUrl($newImgValue);
                
                // Delete old local file if it exists
                if ($oldImagePath && $this->imageService->isLocalFile($oldImagePath)) {
                    $this->imageService->deleteImage($oldImagePath);
                }
            }
            // If it's the same local path, keep it
        }

        // Step 2: Prepare update data
        $appData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? $app->description,
            'price' => $data['price'] ?? $app->price,
            'type' => $data['type'] ?? $app->type,
            'rating' => $data['rating'] ?? $app->rating,
            'billing_type' => BillingType::from($data['billing_type']),
            'trial_days' => $data['billing_type'] === 'paid_trial' ? ($data['trial_days'] ?? null) : null,
            'img' => $imagePath,
        ];

        // Step 3: Update app (inside transaction)
        try {
            return $this->executeInTransaction(function () use ($app, $appData) {
                $app->update($appData);
                $this->logActivity('marketplace_app.updated', ['app_id' => $app->id]);
                return $app->fresh();
            });
        } catch (\Exception $e) {
            // If transaction fails and we uploaded a new file, clean it up
            if ($imagePath !== $oldImagePath && $this->imageService->isLocalFile($imagePath)) {
                $this->imageService->deleteImage($imagePath);
            }
            throw $e;
        }
    }

    /**
     * Delete an app
     *
     * @param int $id
     * @return void
     * @throws ResourceNotFoundException
     * @throws AppHasInstallationsException
     */
    public function deleteApp(int $id): void
    {
        $app = $this->getAppById($id);

        // Check if app has installations
        $installationCount = $app->installations()->count();
        $this->validateBusinessRule(
            $installationCount === 0,
            'Cannot delete app with active installations. Please remove all installations first.',
            'MARKETPLACE_APP_HAS_INSTALLATIONS',
            409
        );

        // Delete image file (outside transaction)
        if ($app->img && $this->imageService->isLocalFile($app->img)) {
            $this->imageService->deleteImage($app->img);
        }

        // Delete app (inside transaction)
        $this->executeInTransaction(function () use ($app) {
            $appId = $app->id;
            $app->delete();
            $this->logActivity('marketplace_app.deleted', ['app_id' => $appId]);
        });
    }

    /**
     * Bulk delete apps
     *
     * @param array $ids
     * @return int Number of apps deleted
     */
    public function bulkDeleteApps(array $ids): int
    {
        $apps = ApiApp::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($apps as $app) {
            // Skip apps with installations
            if ($app->installations()->count() > 0) {
                continue;
            }

            // Delete image file (outside transaction)
            if ($app->img && $this->imageService->isLocalFile($app->img)) {
                $this->imageService->deleteImage($app->img);
            }

            // Delete app (inside transaction)
            $this->executeInTransaction(function () use ($app, &$deletedCount) {
                $appId = $app->id;
                $app->delete();
                $deletedCount++;
                $this->logActivity('marketplace_app.deleted', ['app_id' => $appId]);
            });
        }

        return $deletedCount;
    }
}

