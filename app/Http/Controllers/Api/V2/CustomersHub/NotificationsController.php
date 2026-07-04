<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use App\Domain\CustomersHub\Services\CustomersHubNotificationService;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V2\CustomersHub\NotificationsIndexRequest;
use App\Http\Requests\Api\V2\CustomersHub\NotificationsMarkAllReadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customers Hub in-app notifications (polling-based, per-recipient read state).
 */
class NotificationsController extends ApiController
{
    public function __construct(
        private CustomersHubNotificationService $notificationService
    ) {
    }

    /**
     * GET /api/v2/customers-hub/notifications/unread
     */
    public function unread(NotificationsIndexRequest $request): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $result = $this->notificationService->listForViewer(
            $viewerId,
            $request->validated(),
            true
        );

        return $this->success($result);
    }

    /**
     * GET /api/v2/customers-hub/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $sourceType = $request->query('sourceType') ?? $request->query('source_type');

        return $this->success([
            'count' => $this->notificationService->unreadCountForViewer(
                $viewerId,
                is_string($sourceType) && $sourceType !== '' ? $sourceType : null
            ),
        ]);
    }

    /**
     * GET /api/v2/customers-hub/notifications
     */
    public function index(NotificationsIndexRequest $request): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $result = $this->notificationService->listForViewer(
            $viewerId,
            $request->validated(),
            false
        );

        return $this->success($result);
    }

    /**
     * PATCH /api/v2/customers-hub/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $viewerId = (int) $request->user()->id;

        if (!$this->notificationService->viewerCanAccessNotification($viewerId, $id)) {
            return $this->error('Notification not found', 404);
        }

        $this->notificationService->markRead($viewerId, $id);

        return $this->success([
            'message' => 'Notification marked as read',
            'notificationId' => $id,
        ]);
    }

    /**
     * PATCH /api/v2/customers-hub/notifications/read-all
     */
    public function markAllRead(NotificationsMarkAllReadRequest $request): JsonResponse
    {
        $viewerId = (int) $request->user()->id;
        $validated = $request->validated();
        $sourceType = $validated['sourceType'] ?? $validated['source_type'] ?? null;

        $updated = $this->notificationService->markAllRead(
            $viewerId,
            is_string($sourceType) && $sourceType !== '' ? $sourceType : null
        );

        return $this->success([
            'message' => 'All notifications marked as read',
            'updatedCount' => $updated,
        ]);
    }
}
