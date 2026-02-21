<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\StoreWaTemplateRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateWaTemplateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends BaseApiController
{
    public function __construct(private readonly WhatsAppTemplateService $templateService) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->templateService->listForUser($userId, [
            'category' => $request->input('category'),
            'is_active' => $request->input('is_active'),
            'search' => $request->input('search'),
        ], $perPage);

        return $this->ok([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);

        if (! $template) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        return $this->ok(['data' => $template]);
    }

    public function store(StoreWaTemplateRequest $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validated();
        $template = $this->templateService->create($userId, $validated);

        return $this->created($template);
    }

    public function update(UpdateWaTemplateRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);

        if (! $template) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $validated = $request->validated();
        $template = $this->templateService->update($template, $validated);

        return $this->ok(['data' => $template]);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);

        if (! $template) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $this->templateService->delete($template);

        return $this->ok(['message' => 'Template deleted.'], 200);
    }
}
