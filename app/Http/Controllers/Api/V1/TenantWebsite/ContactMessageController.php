<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Events\ContactMessageReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\TenantWebsite\ContactMessage\StoreRequest;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    use ResolvesTenant;

    public function store(StoreRequest $request, string $tenantId): JsonResponse
    {
        $tenant = $this->resolveTenant($request, $tenantId);
        $validated = $request->validated();

        $phone = $validated['customer_phone'] ?? null;
        $email = $validated['customer_email'] ?? null;
        $messageText = $validated['message'];

        $duplicateQuery = ContactMessage::query()
            ->where('tenant_id', $tenant->id)
            ->where('message', $messageText)
            ->where('created_at', '>=', now()->subMinutes(5));

        $duplicateQuery->where(function ($q) use ($phone, $email) {
            if ($phone) {
                $q->orWhere('customer_phone', $phone);
            }
            if ($email) {
                $q->orWhere('customer_email', $email);
            }
        });

        if ($duplicateQuery->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate submission. Please wait before sending the same message again.',
            ], 429);
        }

        $metadata = array_merge($validated['metadata'] ?? [], [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $contactMessage = ContactMessage::create([
            'tenant_id' => $tenant->id,
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'message' => $messageText,
            'source' => $validated['source'],
            'is_read' => false,
            'status' => 'active',
            'metadata' => $metadata,
        ]);

        ContactMessageReceived::dispatch($contactMessage);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $contactMessage->id,
                'created_at' => $contactMessage->created_at?->toISOString(),
                'is_read' => false,
            ],
        ], 201);
    }
}
