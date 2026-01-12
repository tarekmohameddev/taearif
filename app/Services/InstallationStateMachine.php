<?php

namespace App\Services;

use App\Models\Api\ApiInstallation;
use App\Enums\InstallStatus;
use App\Exceptions\Installation\InvalidStatusTransitionException;
use Illuminate\Support\Facades\Log;

/**
 * Installation State Machine
 *
 * Manages valid status transitions for app installations
 */
class InstallationStateMachine
{
    /**
     * Valid status transitions
     *
     * @var array<string, array<string>>
     */
    protected array $allowedTransitions = [
        // PendingPayment is deprecated - kept for backward compatibility during migration
        // Will be removed after all pending_payment installations are migrated
        InstallStatus::PendingPayment->value => [
            InstallStatus::Installed->value,
            InstallStatus::Uninstalled->value,
        ],
        InstallStatus::Trialing->value => [
            InstallStatus::Installed->value,
            InstallStatus::Uninstalled->value,
        ],
        InstallStatus::Installed->value => [
            InstallStatus::Uninstalled->value,
        ],
        InstallStatus::Uninstalled->value => [
            InstallStatus::Trialing->value,
            InstallStatus::Installed->value,
        ],
    ];

    /**
     * Check if transition is allowed
     *
     * @param InstallStatus $from
     * @param InstallStatus $to
     * @return bool
     */
    public function canTransition(InstallStatus $from, InstallStatus $to): bool
    {
        $fromValue = $from->value;
        $toValue = $to->value;

        // Same status is always allowed (idempotent)
        if ($fromValue === $toValue) {
            return true;
        }

        $allowed = $this->allowedTransitions[$fromValue] ?? [];

        return in_array($toValue, $allowed, true);
    }

    /**
     * Validate and perform status transition
     *
     * @param ApiInstallation $installation
     * @param InstallStatus $newStatus
     * @param array $additionalData
     * @return ApiInstallation
     * @throws InvalidStatusTransitionException
     */
    public function transition(
        ApiInstallation $installation,
        InstallStatus $newStatus,
        array $additionalData = []
    ): ApiInstallation {
        $currentStatus = $installation->status;

        if (!$this->canTransition($currentStatus, $newStatus)) {
            throw new InvalidStatusTransitionException(
                $currentStatus,
                $newStatus,
                'Transition not allowed by state machine'
            );
        }

        // Prepare update data
        $updateData = array_merge([
            'status' => $newStatus,
        ], $additionalData);

        // Set installed flag based on status
        $updateData['installed'] = in_array(
            $newStatus,
            [InstallStatus::Installed, InstallStatus::Trialing],
            true
        );

        // Set timestamps
        if ($newStatus === InstallStatus::Installed && !$installation->installed_at) {
            $updateData['installed_at'] = now();
        }

        if ($newStatus === InstallStatus::Uninstalled) {
            $updateData['uninstalled_at'] = now();
        }

        // Update installation
        $installation->update($updateData);

        Log::info('Installation status transitioned', [
            'installation_id' => $installation->id,
            'user_id' => $installation->user_id,
            'app_id' => $installation->app_id,
            'from' => $currentStatus->value,
            'to' => $newStatus->value,
        ]);

        return $installation->fresh();
    }

    /**
     * Get allowed transitions for current status
     *
     * @param InstallStatus $status
     * @return array<InstallStatus>
     */
    public function getAllowedTransitions(InstallStatus $status): array
    {
        $allowed = $this->allowedTransitions[$status->value] ?? [];

        return array_map(
            fn(string $value) => InstallStatus::from($value),
            $allowed
        );
    }
}

