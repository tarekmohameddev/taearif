<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\MetaTemplateService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\WaTemplateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TemplateController extends BaseApiController
{
    public function __construct(
        private readonly WhatsAppTemplateService $templateService,
        private readonly MetaTemplateService $metaTemplateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);

        $items = $this->templateService->listForUser($userId, [
            'category' => $request->input('category'),
            'status'   => $request->input('status'),
            'search'   => $request->input('search'),
        ], $perPage);

        return $this->ok([
            'data' => WaTemplateResource::collection($items->items()),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'last_page'    => $items->lastPage(),
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

        return $this->ok(['data' => new WaTemplateResource($template)]);
    }

    /**
     * Sync templates from the tenant's Meta WhatsApp Business Account.
     */
    public function sync(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        try {
            $result = $this->metaTemplateService->syncTemplatesForUser($userId);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'WA_NO_WHATSAPP_CONNECTION') {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'WA_NO_WHATSAPP_CONNECTION',
                    'message' => 'No WhatsApp Business Account connected. Please complete the WhatsApp setup first.',
                ], 422);
            }

            if (str_starts_with($e->getMessage(), 'WA_META_API_ERROR:')) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'WA_META_API_ERROR',
                    'message' => 'Failed to fetch templates from Meta: ' . substr($e->getMessage(), strlen('WA_META_API_ERROR: ')),
                ], 502);
            }

            throw $e;
        }

        return $this->ok([
            'message' => 'Templates synced successfully.',
            'data'    => $result,
        ]);
    }
}
