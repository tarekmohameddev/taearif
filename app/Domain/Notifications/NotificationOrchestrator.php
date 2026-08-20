<?php

namespace App\Domain\Notifications;

use App\Jobs\SendPushNotificationJob;
use Illuminate\Support\Facades\Log;

class NotificationOrchestrator
{
    public const PROPERTY_REQUEST_CREATED = 'PROPERTY_REQUEST_CREATED';
    public const CONTACT_MESSAGE_CREATED = 'CONTACT_MESSAGE_CREATED';

    public function __construct(
        private NotificationInboxService $inbox,
        private NotificationPreferencesService $preferences,
        private DevicePushTokenService $tokens
    ) {
    }

    public function propertyRequestCreated(int $tenantId, object $propertyRequest): ?int
    {
        $name = trim((string) ($propertyRequest->full_name ?? ''));
        return $this->orchestrate([
            'tenant_user_id' => $tenantId,
            'type' => self::PROPERTY_REQUEST_CREATED,
            'category' => 'PROPERTY_REQUEST',
            'title' => 'New property request',
            'body' => $name !== '' ? "A new property request was submitted by {$name}." : 'A new property request was submitted.',
            'source_type' => 'property_request',
            'source_id' => (int) $propertyRequest->id,
            'request_id' => 'property_request_'.$propertyRequest->id,
            'customer_id' => $propertyRequest->customer_id ?? null,
            'payload' => ['source' => (string) ($propertyRequest->source ?? '')],
            'dedupe_key' => "pr_created:{$tenantId}:{$propertyRequest->id}",
            'occurred_at' => $propertyRequest->created_at ?? now(),
        ], 'customers_hub_requests.view');
    }

    public function contactMessageCreated(int $tenantId, object $contactMessage): ?int
    {
        $name = trim((string) ($contactMessage->customer_name ?? ''));
        return $this->orchestrate([
            'tenant_user_id' => $tenantId,
            'type' => self::CONTACT_MESSAGE_CREATED,
            'category' => 'CONTACT_MESSAGE',
            'title' => 'New contact message',
            'body' => $name !== '' ? "A new contact message was received from {$name}." : 'A new contact message was received.',
            'source_type' => 'contact_message',
            'source_id' => (int) $contactMessage->id,
            'request_id' => 'contact_message_'.$contactMessage->id,
            'customer_id' => $contactMessage->customer_id ?? null,
            'payload' => ['source' => (string) ($contactMessage->source ?? '')],
            'dedupe_key' => "cm_created:{$tenantId}:{$contactMessage->id}",
            'occurred_at' => $contactMessage->created_at ?? now(),
        ], 'contact_messages.view');
    }

    private function orchestrate(array $notification, string $permission): ?int
    {
        try {
            $result = $this->inbox->persist($notification, $permission);
            if ($result === null || ! $result['created']) {
                return $result['id'] ?? null;
            }

            $eligible = $this->preferences->eligibleUsers($result['recipients'], $notification['category']);
            $tokens = $this->tokens->activeForUsers($eligible);

            foreach ($tokens as $token) {
                SendPushNotificationJob::dispatch($result['id'], (int) $token->id);
            }

            return $result['id'];
        } catch (\Throwable $exception) {
            Log::error('Mobile notification fan-out failed without blocking domain write', [
                'type' => $notification['type'] ?? null,
                'source_id' => $notification['source_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
