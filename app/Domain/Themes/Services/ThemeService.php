<?php

namespace App\Domain\Themes\Services;

use App\Models\Api\ApiThemeSettings;
use App\Models\UserTheme;
use App\Models\User;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class ThemeService
{
    /**
     * Get all themes with user access status
     * Optimized to prevent N+1 queries
     */
    public function getAllThemes(User $user, array $filters = []): array
    {
        $query = ApiThemeSettings::query();

        // Apply filters
        if (isset($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_enabled'])) {
            $query->where('is_enabled', $filters['is_enabled']);
        }

        if (isset($filters['is_free'])) {
            $query->where('is_free', $filters['is_free']);
        }

        $themes = $query->enabled()->orderBy('is_free', 'desc')->orderBy('name')->get();

        // Eager load user purchases to prevent N+1 queries
        $themeIds = $themes->pluck('theme_id')->toArray();
        $userPurchases = UserTheme::where('user_id', $user->id)
            ->whereIn('theme_id', $themeIds)
            ->where('status', UserTheme::STATUS_ACTIVE)
            ->get()
            ->keyBy('theme_id');

        return $themes->map(function ($theme) use ($user, $userPurchases) {
            $purchase = $userPurchases->get($theme->theme_id);
            
            return [
                'id' => $theme->theme_id,
                'name' => $theme->name,
                'description' => $theme->description,
                'thumbnail' => asset($theme->thumbnail),
                'category' => $theme->category,
                'is_free' => $theme->isFree(),
                'is_enabled' => $theme->isEnabled(),
                'price' => $theme->price,
                'currency' => $theme->currency,
                'popular' => $theme->popular,
                'has_access' => $theme->userHasAccess($user->id),
                'purchased_at' => $purchase ? $purchase->purchased_at->toIso8601String() : null,
            ];
        })->toArray();
    }

    /**
     * Get theme by ID
     */
    public function getThemeById(string $themeId): ApiThemeSettings
    {
        $theme = ApiThemeSettings::where('theme_id', $themeId)->first();
        
        if (!$theme) {
            throw new ResourceNotFoundException("Theme with ID '{$themeId}' not found.");
        }

        return $theme;
    }

    /**
     * Check if user can purchase theme
     */
    public function canPurchaseTheme(User $user, string $themeId): array
    {
        $theme = $this->getThemeById($themeId);

        // Free themes don't need purchase
        if ($theme->isFree()) {
            return [
                'can_purchase' => false,
                'reason' => 'Theme is free and already accessible',
            ];
        }

        // Check if already purchased
        if ($theme->userHasAccess($user->id)) {
            return [
                'can_purchase' => false,
                'reason' => 'Theme already purchased',
            ];
        }

        // Check if theme is enabled
        if (!$theme->isEnabled()) {
            return [
                'can_purchase' => false,
                'reason' => 'Theme is not available for purchase',
            ];
        }

        // Check if theme has price
        if (!$theme->price || $theme->price <= 0) {
            return [
                'can_purchase' => false,
                'reason' => 'Theme price not set',
            ];
        }

        return [
            'can_purchase' => true,
            'theme' => $theme,
            'amount' => $theme->price,
            'currency' => $theme->currency,
        ];
    }

    /**
     * Create pending purchase record
     * Handles unique constraint conflicts by reusing rejected purchases or updating existing pending ones
     */
    public function createPendingPurchase(User $user, string $themeId): UserTheme
    {
        return DB::transaction(function () use ($user, $themeId) {
            $theme = $this->getThemeById($themeId);

            // Check if already has active purchase
            if ($theme->userHasAccess($user->id)) {
                throw new BusinessLogicException('Theme already purchased');
            }

            // Check for existing purchase record (pending or rejected)
            $existingPurchase = UserTheme::where('user_id', $user->id)
                ->where('theme_id', $themeId)
                ->first();

            if ($existingPurchase) {
                // If rejected, reuse it by resetting to pending
                if ($existingPurchase->status === UserTheme::STATUS_REJECTED) {
                    $existingPurchase->update([
                        'status' => UserTheme::STATUS_PENDING,
                        'purchased_at' => now(),
                        'amount_paid' => $theme->price,
                        'currency' => $theme->currency,
                        'payment_ref' => $this->generatePaymentReference(),
                        'gateway_transaction_id' => null,
                        'payment_method' => null,
                    ]);
                    return $existingPurchase->fresh();
                }

                // If pending, return existing
                if ($existingPurchase->status === UserTheme::STATUS_PENDING) {
                    return $existingPurchase;
                }
            }

            // Generate unique payment reference
            $paymentRef = $this->generatePaymentReference();

            // Create new purchase record
            try {
                return UserTheme::create([
                    'user_id' => $user->id,
                    'theme_id' => $themeId,
                    'purchased_at' => now(),
                    'status' => UserTheme::STATUS_PENDING,
                    'payment_ref' => $paymentRef,
                    'amount_paid' => $theme->price,
                    'currency' => $theme->currency,
                ]);
            } catch (QueryException $e) {
                // Handle unique constraint violation (race condition)
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    // Retry by fetching existing record
                    $existing = UserTheme::where('user_id', $user->id)
                        ->where('theme_id', $themeId)
                        ->first();

                    if ($existing && $existing->status === UserTheme::STATUS_PENDING) {
                        return $existing;
                    }

                    throw new BusinessLogicException('Purchase record already exists. Please try again.');
                }
                throw $e;
            }
        });
    }

    /**
     * Generate unique payment reference
     * Uses UUID for better uniqueness guarantee
     */
    private function generatePaymentReference(): string
    {
        do {
            $paymentRef = 'THEME_' . Str::uuid()->toString();
        } while (UserTheme::where('payment_ref', $paymentRef)->exists());

        return $paymentRef;
    }

    /**
     * Activate theme purchase after successful payment
     * Wrapped in transaction for data integrity
     */
    public function activatePurchase(
        int $userThemeId,
        string $gatewayTransactionId,
        string $paymentMethod
    ): UserTheme {
        return DB::transaction(function () use ($userThemeId, $gatewayTransactionId, $paymentMethod) {
            $userTheme = UserTheme::findOrFail($userThemeId);

            if ($userTheme->status !== UserTheme::STATUS_PENDING) {
                throw new BusinessLogicException('Purchase is not in pending status');
            }

            // Check if another active purchase already exists (race condition protection)
            $existingActive = UserTheme::where('user_id', $userTheme->user_id)
                ->where('theme_id', $userTheme->theme_id)
                ->where('status', UserTheme::STATUS_ACTIVE)
                ->where('id', '!=', $userThemeId)
                ->first();

            if ($existingActive) {
                // Another purchase was already activated, mark this one as duplicate
                $userTheme->update([
                    'status' => UserTheme::STATUS_REJECTED,
                    'gateway_transaction_id' => $gatewayTransactionId,
                    'payment_method' => $paymentMethod,
                ]);
                throw new BusinessLogicException('Theme purchase was already activated by another transaction');
            }

            $userTheme->update([
                'status' => UserTheme::STATUS_ACTIVE,
                'gateway_transaction_id' => $gatewayTransactionId,
                'payment_method' => $paymentMethod,
            ]);

            Log::info('Theme purchase activated', [
                'user_theme_id' => $userTheme->id,
                'user_id' => $userTheme->user_id,
                'theme_id' => $userTheme->theme_id,
                'transaction_id' => $gatewayTransactionId,
                'payment_method' => $paymentMethod,
            ]);

            return $userTheme->fresh();
        });
    }

}
