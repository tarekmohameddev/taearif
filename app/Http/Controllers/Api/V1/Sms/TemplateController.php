<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Domain\Communication\Sms\Services\SmsTemplateService;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateController extends BaseApiController
{
    public function __construct(private readonly SmsTemplateService $templateService) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);

        $items = $this->templateService->listForUser($userId, [
            'category' => $request->input('category'),
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

    public function store(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sms_templates', 'name')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'content' => 'required|string',
            'category' => 'required|in:promotional,transactional,reminder,notification,follow_up',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template = $this->templateService->create($userId, $validated);

        return $this->created($template);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($userId, $id);
        if (!$template) {
            return response()->json(['status' => false, 'code' => 'TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('sms_templates', 'name')
                    ->ignore($template->id)
                    ->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'content' => 'sometimes|string',
            'category' => 'sometimes|in:promotional,transactional,reminder,notification,follow_up',
            'variables' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

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

