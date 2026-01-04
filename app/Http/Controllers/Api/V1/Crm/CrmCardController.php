<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Models\Api\Crm\CrmCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Api\ApiController;

class CrmCardController extends ApiController
{

    /**
     * GET /api/v1/crm/cards
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CrmCard::query()
            ->forUser($request->user()->tenantOwnerId())
            ->when($request->filled('card_procedure'), fn($q) => $q->where('card_procedure', $request->card_procedure))
            ->when($request->filled('card_request_id'), fn($q) => $q->where('card_request_id', (int) $request->card_request_id))
            ->when($request->filled('date_from'), fn($q) => $q->where('card_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->where('card_date', '<=', $request->date_to))
            ->orderByDesc('card_date')
            ->orderByDesc('id');

        return $this->paginateQuery(
            query: $query,
            request: $request,
            resourceKey: 'cards',
            transform: null,
            extraData: []
        );
    }

    /**
     * POST /api/v1/crm/cards
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $tenantId = $request->user()->tenantOwnerId();

        $validated = $request->validate([
            'card_request_id' => [
                'required','integer',
                Rule::exists('crm_requests', 'id')->where(fn($q) => $q->where('user_id', $tenantId)),
            ],
            'card_content'     => ['nullable','string'],
            'card_procedure'   => ['required', Rule::in(['reminder','note','interaction','appointment'])],
            'card_project'     => ['nullable','integer'],
            'card_property'    => ['nullable','integer'],
            'card_date'        => ['nullable','date'],
        ]);

        $card = new CrmCard($validated);
        $card->user_id = $tenantId;
        $card->save();

        return response()->json(['status' => true, 'data' => $card], 201);
    }

    /**
     * GET /api/v1/crm/cards/{id}
     */
    public function show(Request $request, int $id)
    {
        try {
            $card = CrmCard::forUser($request->user()->tenantOwnerId())->findOrFail($id);

            return $this->success([
                'card' => $card
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Card not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT/PATCH /api/v1/crm/cards/{id}
     */
    public function update(Request $request, int $id)
    {
        try {
            $user = $request->user();
            $tenantId = $request->user()->tenantOwnerId();
            $card = CrmCard::forUser($tenantId)->findOrFail($id);

            $validated = $request->validate([
                'card_request_id' => [
                    'sometimes', 'required', 'integer',
                    Rule::exists('crm_requests', 'id')->where(fn($q) => $q->where('user_id', $tenantId)),
                ],
                'card_content'      => ['nullable', 'string'],
                'card_procedure'    => ['sometimes', 'required', Rule::in(['reminder','note','interaction','appointment'])],
                'card_project'      => ['nullable', 'integer'],
                'card_property'     => ['nullable', 'integer'],
                'card_date'         => ['nullable', 'date'],
            ]);

            $card->fill($validated)->save();

            return $this->success(['card' => $card]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Card not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * DELETE /api/v1/crm/cards/{id}
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $user = $request->user();
            $card = CrmCard::forUser($request->user()->tenantOwnerId())->findOrFail($id);
            $card->delete();

            return $this->success(['message' => 'Deleted']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Card not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
