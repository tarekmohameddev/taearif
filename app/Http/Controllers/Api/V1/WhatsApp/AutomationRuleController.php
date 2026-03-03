<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppAutomationRuleService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\StoreAutomationRuleRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateAutomationRuleRequest;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationRuleController extends BaseApiController
{
    public function __construct(private readonly WhatsAppAutomationRuleService $ruleService) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->ruleService->listForUser($userId, [
            'is_active' => $request->input('is_active'),
            'trigger' => $request->input('trigger'),
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
        $rule = $this->ruleService->findForUser($userId, $id);

        if (! $rule) {
            return response()->json(['status' => 'error', 'code' => 'WA_RULE_NOT_FOUND', 'message' => 'Automation rule not found.'], 404);
        }

        return $this->ok(['data' => $rule]);
    }

    public function store(StoreAutomationRuleRequest $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validated();
        if (isset($validated['wa_number_id']) && ! WaNumber::where('id', $validated['wa_number_id'])->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }
        if (isset($validated['template_id']) && ! WaTemplate::where('id', $validated['template_id'])->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $rule = $this->ruleService->create($userId, $validated);

        return $this->created($rule);
    }

    public function update(UpdateAutomationRuleRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $rule = $this->ruleService->findForUser($userId, $id);

        if (! $rule) {
            return response()->json(['status' => 'error', 'code' => 'WA_RULE_NOT_FOUND', 'message' => 'Automation rule not found.'], 404);
        }

        $validated = $request->validated();
        if (array_key_exists('wa_number_id', $validated) && $validated['wa_number_id'] !== null && ! WaNumber::where('id', $validated['wa_number_id'])->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }
        if (array_key_exists('template_id', $validated) && $validated['template_id'] !== null && ! WaTemplate::where('id', $validated['template_id'])->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $rule = $this->ruleService->update($rule, $validated);

        return $this->ok(['data' => $rule]);
    }

    public function toggle(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $rule = $this->ruleService->findForUser($userId, $id);

        if (! $rule) {
            return response()->json(['status' => 'error', 'code' => 'WA_RULE_NOT_FOUND', 'message' => 'Automation rule not found.'], 404);
        }

        $rule = $this->ruleService->toggle($rule);

        return $this->ok(['data' => $rule]);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $rule = $this->ruleService->findForUser($userId, $id);

        if (! $rule) {
            return response()->json(['status' => 'error', 'code' => 'WA_RULE_NOT_FOUND', 'message' => 'Automation rule not found.'], 404);
        }

        $this->ruleService->delete($rule);

        return $this->ok(['message' => 'Rule deleted.'], 200);
    }

    public function stats(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $stats = $this->ruleService->statsForUser($userId);

        return $this->ok(['data' => $stats]);
    }
}
