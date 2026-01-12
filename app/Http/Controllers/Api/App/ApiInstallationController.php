<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiApp;
use App\Models\Api\ApiMenuItem;
use App\Models\Api\ApiInstallation;
use App\Enums\InstallStatus;
use App\Services\InstallationService;
use App\Services\InstallationStateMachine;
use App\Traits\ApiResponseTrait;
use App\Exceptions\Installation\InvalidInstallationException;
use App\Exceptions\Installation\ConcurrentInstallationException;
use App\Exceptions\Installation\PaymentInitiationException;
use App\Exceptions\Installation\InvalidStatusTransitionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApiInstallationController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the installed apps for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Display a listing of the installed apps for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $userId = auth()->id();

            // Optimize query with eager loading
            $apps = ApiApp::where('is_enabled', true)
                ->with(['installations' => function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->with(['settings', 'paymentTransactions']);
                }])
                ->get();

            $apps = $apps->map(function ($app) {
                $installation = $app->installations->first();

                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'img' => $app->img,
                    'description' => $app->description,
                    'price' => number_format($app->price, 2),
                    'type' => $app->type,
                    'rating' => round($app->rating, 1),
                    'path' => $app->path,
                    'billing_type' => $app->billing_type->value,
                    'trial_days' => $app->trial_days ?? 0,
                    'installed' => $installation?->installed ?? false,
                    'trial_ends_at' => $installation?->trial_ends_at?->toIso8601String(),
                    'current_period_end' => $installation?->current_period_end?->toIso8601String(),
                    'activated_at' => $installation?->activated_at?->toIso8601String(),
                    'status' => $installation?->status->value ?? 'pending',
                    'payment_status' => $this->getPaymentStatus($installation, $app),
                    'settings' => $installation?->settings?->settings ?? null,
                    'installed_at' => $installation?->installed_at?->toIso8601String(),
                    'uninstalled_at' => $installation?->uninstalled_at?->toIso8601String(),
                ];
            });

            return $this->successResponse([
                'apps' => $apps,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list apps', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve apps',
                'APPS_LIST_ERROR',
                500
            );
        }
    }

    /**
     * Install an app for the authenticated user.
     */
    // public function install(Request $request,InstallAppRequest $req, InstallationService $svc)
    // {
    //     $userId = Auth::id();
    //     if (!$userId) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'User not authenticated.',
    //         ], 401);
    //     }

    //     $request->validate([
    //         'app_id'                => 'required|exists:api_apps,id',
    //         'settings'              => 'nullable|array',
    //         'settings.phone_number' => 'nullable|string|max:20',
    //         'settings.token'        => 'nullable|string|max:255',
    //     ]);

    //     /** @var \App\Models\ApiApp $app */
    //     $app = ApiApp::findOrFail($request->app_id);

    //     // ── Billing path ──────────────────────────────────────────────
    //     $isTrial = $app->billing_type === 'subscription' && $app->trial_days > 0;
    //     $isFree  = $app->billing_type === 'free';
    //     $now     = CarbonImmutable::now();

    //     $trialEndsAt       = $isTrial ? $now->addDays($app->trial_days) : null;
    //     $currentPeriodEnd  = $isTrial ? $trialEndsAt : null;   // real value set by Stripe later
    //     $status            = $isTrial ? 'trialing' : 'installed';

    //     // ── Persist installation ─────────────────────────────────────
    //     $installation = ApiInstallation::updateOrCreate(
    //         ['user_id' => $userId, 'app_id' => $app->id],
    //         [
    //             // NEW
    //             'status'             => $status,
    //             'activated_at'       => $now,
    //             'trial_ends_at'      => $trialEndsAt,
    //             'current_period_end' => $currentPeriodEnd,

    //             // LEGACY
    //             'installed'          => 1 ,
    //             'installed_at'       => $now,
    //             'uninstalled_at'     => null,
    //         ]
    //     );

    //     // settings relationship
    //     $installation->settings()->updateOrCreate(
    //         ['installation_id' => $installation->id],
    //         ['settings' => $request->input('settings', [])]
    //     );

    //     // user request record (unchanged)
    //     $settings = $request->input('settings', []);
    //     AppRequest::updateOrCreate(
    //         ['user_id' => $userId, 'app_id' => $app->id],
    //         [
    //             'phone_number' => $settings['phone_number'] ?? null,
    //             'token'        => $settings['token']        ?? null,
    //             'status'       => 'approved',
    //         ]
    //     );

    //     if ($app->name === 'واتس اب') {
    //         $menuItem = \App\Models\Api\ApiMenuItem::firstOrCreate(
    //             ['user_id' => $userId, 'url' => '/whatsapp-ai'],
    //             [
    //                 'label' => 'واتس اب',
    //                 'is_external' => false,
    //                 'is_active' => true,
    //                 'order' => 8,
    //                 'parent_id' => null,
    //                 'show_on_mobile' => true,
    //                 'show_on_desktop' => true,
    //             ]
    //         );

    //         // If it existed but was inactive, activate it
    //         if (!$menuItem->is_active) {
    //             $menuItem->is_active = true;
    //             $menuItem->save();
    //         }
    //     }

    //     Log::info("App installed: {$app->name} (ID: {$app->id}) for user ID: {$userId}");

    //     // ── Response ─────────────────────────────────────────────────
    //     return response()->json([
    //         'status'  => 'success',
    //         'message' => 'App installed successfully.',
    //         'data'    => ['installation' => $installation],
    //     ]);
    // }
    /**
     * Install an app for the authenticated user.
     *
     * @param Request $request
     * @param InstallationService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function install(Request $request, InstallationService $service)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'app_id' => 'required|exists:api_apps,id',
                'settings' => 'nullable|array',
                'settings.phone_number' => 'nullable|string|max:20',
                'settings.token' => 'nullable|string|max:255',
            ]);

            $user = $request->user();
            $app = ApiApp::where('id', $validated['app_id'])
                ->where('is_enabled', true)
                ->first();

            if (!$app) {
                return $this->errorResponse(
                    'App not found or not enabled',
                    'APP_NOT_FOUND',
                    404
                );
            }

            // Install app
            $result = $service->install($user, $app, $validated['settings'] ?? []);

            // Handle app-specific post-installation logic
            $this->handleAppSpecificInstallation($user, $app, $result['installation']);

            return $this->successResponse([
                'installation' => [
                    'id' => $result['installation']->id,
                    'status' => $result['installation']->status->value,
                    'installed' => $result['installation']->installed,
                    'trial_ends_at' => $result['installation']->trial_ends_at?->toIso8601String(),
                    'activated_at' => $result['installation']->activated_at?->toIso8601String(),
                    'payment_status' => $this->getPaymentStatus($result['installation'], $app),
                ],
                'app' => [
                    'id' => $app->id,
                    'billing_type' => $app->billing_type->value,
                    'trial_days' => $app->trial_days,
                    'price' => $app->price,
                    'name' => $app->name,
                ],
                'payment_url' => $result['payment_url'],
            ], 'App installed successfully');

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());

        } catch (ConcurrentInstallationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getStatusCode()
            );

        } catch (InvalidInstallationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getStatusCode()
            );

        } catch (PaymentInitiationException $e) {
            Log::error('Payment initiation failed during install', [
                'user_id' => $request->user()?->id,
                'app_id' => $request->input('app_id'),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to initiate payment. Please try again.',
                $e->getErrorCode(),
                500
            );

        } catch (\Exception $e) {
            Log::error('Unexpected error during app installation', [
                'user_id' => $request->user()?->id,
                'app_id' => $request->input('app_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'An error occurred during installation',
                'INSTALLATION_ERROR',
                500
            );
        }
    }

    /**
     * Handle app-specific post-installation logic
     *
     * @param \App\Models\User $user
     * @param ApiApp $app
     * @param ApiInstallation $installation
     * @return void
     */
    protected function handleAppSpecificInstallation($user, ApiApp $app, ApiInstallation $installation): void
    {
        // TODO: Replace hardcoded check with event system or strategy pattern
        if ($app->name === 'واتس اب') {
            $menuItem = ApiMenuItem::firstOrCreate(
                ['user_id' => $user->id, 'url' => '/whatsapp-ai'],
                [
                    'label' => 'واتس اب',
                    'is_external' => false,
                    'is_active' => true,
                    'order' => 8,
                    'parent_id' => null,
                    'show_on_mobile' => true,
                    'show_on_desktop' => true,
                ]
            );

            if (!$menuItem->is_active) {
                $menuItem->update(['is_active' => true]);
            }
        }
    }


    /**
     * Uninstall an app for the authenticated user.
     *
     * @param int $appId
     * @param InstallationStateMachine $stateMachine
     * @return \Illuminate\Http\JsonResponse
     */
    public function uninstall(int $appId, InstallationStateMachine $stateMachine)
    {
        try {
            $userId = Auth::id();

            $installation = ApiInstallation::where('user_id', $userId)
                ->where('app_id', $appId)
                ->first();

            if (!$installation) {
                return $this->notFound('Installation not found');
            }

            // Use state machine for safe transition
            $stateMachine->transition($installation, InstallStatus::Uninstalled);

            // Delete settings
            $installation->settings()->delete();

            // Deactivate menu items (app-specific)
            $this->handleAppSpecificUninstallation($userId, $installation->app);

            return $this->successResponse(
                null,
                'App uninstalled successfully'
            );

        } catch (InvalidStatusTransitionException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getStatusCode()
            );

        } catch (\Exception $e) {
            Log::error('Failed to uninstall app', [
                'user_id' => Auth::id(),
                'app_id' => $appId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to uninstall app',
                'UNINSTALL_ERROR',
                500
            );
        }
    }

    /**
     * Handle app-specific uninstallation logic
     *
     * @param int $userId
     * @param ApiApp $app
     * @return void
     */
    protected function handleAppSpecificUninstallation(int $userId, ApiApp $app): void
    {
        // TODO: Replace hardcoded check with event system
        if ($app->name === 'واتس اب') {
            ApiMenuItem::where('user_id', $userId)
                ->where('url', '/whatsapp-ai')
                ->update(['is_active' => false]);
        }
    }

    /**
     * Get WhatsApp app information and installation status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function whatsapp()
    {
        try {
            $userId = auth()->id();
            $app = ApiApp::where('name', 'واتس اب')
                ->where('is_enabled', true)
                ->first();

            if (!$app) {
                return $this->notFound('WhatsApp app not found');
            }

            $installation = ApiInstallation::with('settings')
                ->where('user_id', $userId)
                ->where('app_id', $app->id)
                ->first();

            return $this->successResponse([
                'app' => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'img' => $app->img,
                    'description' => $app->description,
                    'price' => number_format($app->price, 2),
                    'billing_type' => $app->billing_type->value,
                    'trial_days' => $app->trial_days ?? 0,
                ],
                'installation' => $installation ? [
                    'installed' => $installation->installed ?? false,
                    'status' => $installation->status->value ?? null,
                    'payment_status' => $this->getPaymentStatus($installation, $app),
                    'trial_ends_at' => $installation->trial_ends_at?->toIso8601String(),
                    'activated_at' => $installation->activated_at?->toIso8601String(),
                    'installed_at' => $installation->installed_at?->toIso8601String(),
                    'settings' => $installation->settings?->settings ?? null,
                ] : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get WhatsApp app info', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve WhatsApp app information',
                'WHATSAPP_INFO_ERROR',
                500
            );
        }
    }

    /**
     * Install WhatsApp app for the authenticated user.
     *
     * @param Request $request
     * @param InstallationService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function installWhatsapp(Request $request, InstallationService $service)
    {
        try {
            $app = ApiApp::where('name', 'واتس اب')
                ->where('is_enabled', true)
                ->first();

            if (!$app) {
                return $this->notFound('WhatsApp app not found');
            }

            $user = $request->user();
            $result = $service->install($user, $app, $request->input('settings', []));

            // Handle app-specific installation
            $this->handleAppSpecificInstallation($user, $app, $result['installation']);

            return $this->successResponse([
                'installation' => [
                    'id' => $result['installation']->id,
                    'status' => $result['installation']->status->value,
                    'installed' => $result['installation']->installed,
                    'payment_status' => $this->getPaymentStatus($result['installation'], $app),
                ],
                'app' => [
                    'id' => $app->id,
                    'billing_type' => $app->billing_type->value,
                    'trial_days' => $app->trial_days,
                    'price' => $app->price,
                    'name' => $app->name,
                ],
                'payment_url' => $result['payment_url'],
            ], 'WhatsApp app installed successfully');

        } catch (ConcurrentInstallationException | InvalidInstallationException | PaymentInitiationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getStatusCode()
            );

        } catch (\Exception $e) {
            Log::error('Failed to install WhatsApp app', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to install WhatsApp app',
                'WHATSAPP_INSTALL_ERROR',
                500
            );
        }
    }

    /**
     * Uninstall WhatsApp app for the authenticated user.
     *
     * @param InstallationStateMachine $stateMachine
     * @return \Illuminate\Http\JsonResponse
     */
    public function uninstallWhatsapp(InstallationStateMachine $stateMachine)
    {
        try {
            $userId = Auth::id();
            $app = ApiApp::where('name', 'واتس اب')
                ->where('is_enabled', true)
                ->first();

            if (!$app) {
                return $this->notFound('WhatsApp app not found');
            }

            $installation = ApiInstallation::where('user_id', $userId)
                ->where('app_id', $app->id)
                ->first();

            if (!$installation) {
                return $this->notFound('WhatsApp installation not found');
            }

            // Use state machine for safe transition
            $stateMachine->transition($installation, InstallStatus::Uninstalled);

            // Delete settings
            $installation->settings()->delete();

            // Handle app-specific uninstallation
            $this->handleAppSpecificUninstallation($userId, $app);

            return $this->successResponse(
                null,
                'WhatsApp app uninstalled successfully'
            );

        } catch (InvalidStatusTransitionException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getStatusCode()
            );

        } catch (\Exception $e) {
            Log::error('Failed to uninstall WhatsApp app', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to uninstall WhatsApp app',
                'WHATSAPP_UNINSTALL_ERROR',
                500
            );
        }
    }

    /**
     * Get payment status for an installation
     *
     * @param ApiInstallation|null $installation
     * @param ApiApp $app
     * @return string
     */
    private function getPaymentStatus(?ApiInstallation $installation, ApiApp $app): string
    {
        // Free apps don't require payment
        if ($app->billing_type === \App\Enums\BillingType::Free) {
            return 'not_required';
        }

        // No installation means not installed yet
        if (!$installation) {
            return 'unpaid';
        }

        // Check if there's a completed payment transaction
        if ($installation->hasCompletedPayment()) {
            return 'paid';
        }

        // Check if there's a pending payment transaction
        $hasPendingPayment = $installation->paymentTransactions()
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingPayment) {
            return 'pending';
        }

        // For paid apps with trial, check if trial is active
        if ($app->billing_type === \App\Enums\BillingType::PaidTrial) {
            if ($installation->status === \App\Enums\InstallStatus::Trialing) {
                return 'trial';
            }
        }

        // Default to unpaid
        return 'unpaid';
    }

}
