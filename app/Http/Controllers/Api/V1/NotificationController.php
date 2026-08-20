<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\NotificationInboxService;
use App\Domain\Notifications\NotificationPreferencesService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\UpdateNotificationPreferencesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationInboxService $service): JsonResponse
    {
        $filters = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0'],
            'unreadOnly' => ['sometimes', 'boolean'],
            'category' => ['sometimes', 'string', Rule::in([
                'PROPERTY_REQUEST', 'CONTACT_MESSAGE', 'REMINDER', 'RENTAL', 'SYSTEM',
            ])],
        ]);
        return response()->json(['data' => $service->listForViewer((int) $request->user()->id, $filters)]);
    }

    public function unreadCount(Request $request, NotificationInboxService $service): JsonResponse
    {
        return response()->json(['unreadCount' => $service->unreadCount((int) $request->user()->id)]);
    }

    public function markRead(Request $request, int $id, NotificationInboxService $service): JsonResponse
    {
        if (! $service->markRead((int) $request->user()->id, $id)) {
            abort(404);
        }
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request, NotificationInboxService $service): JsonResponse
    {
        return response()->json(['updated' => $service->markAllRead((int) $request->user()->id)]);
    }

    public function preferences(Request $request, NotificationPreferencesService $service): JsonResponse
    {
        return response()->json(['data' => $service->get((int) $request->user()->id)]);
    }

    public function updatePreferences(
        UpdateNotificationPreferencesRequest $request,
        NotificationPreferencesService $service
    ): JsonResponse {
        return response()->json([
            'data' => $service->put((int) $request->user()->id, $request->validated()),
        ]);
    }
}
