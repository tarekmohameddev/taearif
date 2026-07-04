<?php

namespace App\Domain\CustomersHub\Services;

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
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_STAGE_CHANGED,
            'Property request stage updated',
            sprintf('Request for %s moved from %s to %s', $name, $oldStage ?? 'Unassigned', $newStage ?? 'Unassigned'),
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
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_PRIORITY_CHANGED,
            'Property request priority updated',
            sprintf('Priority for %s changed from %s to %s', $name, $oldPriority ?? 'Unknown', $newPriority ?? 'Unknown'),
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
            'Property request assigned',
            sprintf('Request for %s was assigned to employee #%d', $name, $assignedTo ?? 0),
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
            'Property request updated',
            sprintf('Request for %s was updated', $name),
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

        $name = $ctx->full_name ?? ('#' . $propertyRequestId);
        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            CustomersHubNotificationService::TYPE_APPOINTMENT_CREATED,
            'Appointment scheduled',
            sprintf('Appointment "%s" scheduled for %s', $appointmentTitle, $name),
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
            'Reminder created',
            sprintf('Reminder "%s" created for %s', $reminderTitle, $name),
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
        $title = $overdue ? 'Reminder overdue' : 'Reminder due soon';
        $body = $overdue
            ? sprintf('Reminder "%s" for %s is overdue', $reminderTitle, $name)
            : sprintf('Reminder "%s" for %s is due soon', $reminderTitle, $name);

        $this->notifications->notifyPropertyRequestEvent(
            $tenantUserId,
            $propertyRequestId,
            $type,
            $title,
            $body,
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
