<?php

namespace App\Http\Controllers\Api\V1\Email;

use App\Domain\Communication\Email\Services\EmailTemplateService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Email\StoreTemplateRequest;
use App\Http\Requests\Api\V1\Email\UpdateTemplateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends BaseApiController
{
    public function __construct(private readonly EmailTemplateService $templateService) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);

        $items = $this->templateService->listForUser($userId, [
            'is_active' => $request->input('is_active'),
        ], $perPage);

        return $this->ok($items);
    }

    public function show(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);

        if (!$template) {
            return response()->json(['status' => false, 'code' => 'TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        return $this->ok($template);
    }

    public function store(StoreTemplateRequest $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validated();
        $template = $this->templateService->create($userId, $validated);

        return $this->created($template);
    }

    public function update(UpdateTemplateRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);
        if (!$template) {
            return response()->json(['status' => false, 'code' => 'TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $validated = $request->validated();
        $updated = $this->templateService->update($template, $validated);

        return $this->ok($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);
        if (!$template) {
            return response()->json(['status' => false, 'code' => 'TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $this->templateService->delete($template);

        return $this->ok(['deleted' => true]);
    }
}
