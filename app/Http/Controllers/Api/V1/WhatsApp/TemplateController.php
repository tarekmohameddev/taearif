<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('wa_templates', 'name')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'language' => 'nullable|string|max:10',
        ]);

        $template = $this->templateService->create($userId, $validated);

        return $this->created($template);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);

        if (! $template) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('wa_templates', 'name')->ignore($template->id)->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'content' => 'sometimes|string',
            'category' => 'nullable|string|max:50',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'language' => 'nullable|string|max:10',
        ]);

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
