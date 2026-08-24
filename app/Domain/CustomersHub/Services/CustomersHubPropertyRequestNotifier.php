<?php

namespace App\Domain\CustomersHub\Services;

use App\Domain\Notifications\MobileNotificationCopy;

/**
 * Builds and dispatches property-request notifications for Customers Hub events.
 */
class CustomersHubPropertyRequestNotifier
{
    public function __construct(
        private CustomersHubNotificationService $notifications
    ) {
    }

    public function notifyStageChanged(
        int $tenantUserId,
        int $propertyRequestId,
        ?string $oldStage,
        ?string $newStage,
        ?int $actorUserId = null
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $unassigned = MobileNotificationCopy::t('customers_hub.fallbacks.unassigned');
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_STAGE_CHANGED,
            MobileNotificationCopy::t('customers_hub.stage_updated.title'),
            MobileNotificationCopy::t('customers_hub.stage_updated.body', [
                'name' => $name,
                'from' => $oldStage ?? $unassigned,
                'to' => $newStage ?? $unassigned,
            ]),
            [
                'oldStage' => $oldStage,
                'newStage' => $newStage,
            ],
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyPriorityChanged(
        int $tenantUserId,
        int $propertyRequestId,
        ?string $oldPriority,
        ?string $newPriority,
        ?int $actorUserId = null
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $unknown = MobileNotificationCopy::t('customers_hub.fallbacks.unknown');
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_PRIORITY_CHANGED,
            MobileNotificationCopy::t('customers_hub.priority_updated.title'),
            MobileNotificationCopy::t('customers_hub.priority_updated.body', [
                'name' => $name,
                'from' => $oldPriority ?? $unknown,
                'to' => $newPriority ?? $unknown,
            ]),
            [
                'oldPriority' => $oldPriority,
                'newPriority' => $newPriority,
            ],
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyAssigned(
        int $tenantUserId,
        int $propertyRequestId,
        ?int $assignedTo,
        ?int $actorUserId = null
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_ASSIGNED,
            MobileNotificationCopy::t('customers_hub.assigned.title'),
            MobileNotificationCopy::t('customers_hub.assigned.body', [
                'name' => $name,
                'id' => $assignedTo ?? 0,
            ]),
            ['assignedTo' => $assignedTo],
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyStatusEvent(
        int $tenantUserId,
        int $propertyRequestId,
        string $type,
        string $title,
        string $body,
        array $payload = [],
        ?int $actorUserId = null
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            $type,
            $title,
            $body,
            $payload,
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyUpdated(
        int $tenantUserId,
        int $propertyRequestId,
        array $changedFields,
        ?int $actorUserId = null
    ): void {
        if ($changedFields === []) {
            return;
        }

        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_UPDATED,
            MobileNotificationCopy::t('customers_hub.updated.title'),
            MobileNotificationCopy::t('customers_hub.updated.body', ['name' => $name]),
            ['changedFields' => $changedFields],
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyAppointmentCreated(
        int $tenantUserId,
        int $propertyRequestId,
        int $appointmentId,
        string $appointmentTitle,
        ?string $datetime,
        ?int $actorUserId = null
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_APPOINTMENT_CREATED,
            MobileNotificationCopy::t('customers_hub.appointment_scheduled.title'),
            MobileNotificationCopy::t('customers_hub.appointment_scheduled.body', [
                'title' => $appointmentTitle,
                'when' => $datetime ?? '',
            ]),
            [
                'appointmentId' => $appointmentId,
                'datetime' => $datetime,
            ],
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyReminderCreated(
        int $tenantUserId,
        int $propertyRequestId,
        int $reminderId,
        string $reminderTitle,
        ?string $datetime,
        ?int $actorUserId = null
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_REMINDER_CREATED,
            MobileNotificationCopy::t('customers_hub.reminder_created.title'),
            MobileNotificationCopy::t('customers_hub.reminder_created.body', [
                'title' => $reminderTitle,
                'name' => $name,
            ]),
            [
                'reminderId' => $reminderId,
                'datetime' => $datetime,
            ],
            $actorUserId,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null
        );
    }

    public function notifyReminderDue(
        int $tenantUserId,
        int $propertyRequestId,
        int $reminderId,
        string $reminderTitle,
        string $datetime,
        bool $overdue = false
    ): void {
        $ctx = $this->notifications->getPropertyRequestContext($tenantUserId, $propertyRequestId);
        if (!$ctx) {
            return;
        }

        $type = $overdue
            ? CustomersHubNotificationService::TYPE_REMINDER_OVERDUE
            : CustomersHubNotificationService::TYPE_REMINDER_DUE;

        $dedupeKey = ($overdue ? 'reminder_overdue:' : 'reminder_due:')
            . $reminderId . ':' . substr($datetime, 0, 16);

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $keyPrefix = $overdue
            ? 'customers_hub.reminder_overdue'
            : 'customers_hub.reminder_due_soon';

        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            $type,
            MobileNotificationCopy::t($keyPrefix.'.title'),
            MobileNotificationCopy::t($keyPrefix.'.body', [
                'title' => $reminderTitle,
                'name' => $name,
            ]),
            [
                'reminderId' => $reminderId,
                'datetime' => $datetime,
                'overdue' => $overdue,
            ],
            null,
            $ctx->customer_id !== null ? (int) $ctx->customer_id : null,
            $dedupeKey
        );
    }

    /**
     * Parse property_request_{id} action id; returns null for non-property-request actions.
     */
    public static function parsePropertyRequestId(string $actionId): ?int
    {
        if (!str_starts_with($actionId, 'property_request_')) {
            return null;
        }

        $id = (int) substr($actionId, strlen('property_request_'));

        return $id > 0 ? $id : null;
    }
}
