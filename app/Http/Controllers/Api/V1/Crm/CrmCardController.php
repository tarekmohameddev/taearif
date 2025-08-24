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
            ->forUser($user->id)
            ->when($request->filled('card_procedure'), fn($q) => $q->where('card_procedure', $request->card_procedure))
            ->when($request->filled('customer_id'), fn($q) => $q->where('card_customer_id', (int) $request->customer_id))
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

        $validated = $request->validate([
            'card_customer_id' => [
                'required','integer',
                Rule::exists('api_customers', 'id')->where(fn($q) => $q->where('user_id', $user->id)),
            ],
            'card_content'     => ['nullable','string'],
            'card_procedure'   => ['required', Rule::in(['reminder','note','interaction','appointment'])],
            'card_project'     => ['nullable','integer'],
            'card_property'    => ['nullable','integer'],
            'card_date'        => ['nullable','date'],
        ]);

        $card = new CrmCard($validated);
        $card->user_id = $user->id;
        $card->save();

        return response()->json(['status' => true, 'data' => $card], 201);
    }

    /**
     * GET /api/v1/crm/cards/{id}
     */
    public function show(Request $request, int $id)
    {
        $card = CrmCard::forUser($request->user()->id)->findOrFail($id);

        return $this->success([
            'card' => $card
        ]);
    }

    /**
     * PUT/PATCH /api/v1/crm/cards/{id}
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $card = CrmCard::forUser($user->id)->findOrFail($id);

        $validated = $request->validate([
            'card_customer_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('api_customers', 'id')->where(fn($q) => $q->where('user_id', $user->id)),
            ],
            'card_content'      => ['nullable', 'string'],
            'card_procedure'    => ['sometimes', 'required', Rule::in(['reminder','note','interaction','appointment'])],
            'card_project'      => ['nullable', 'integer'],
            'card_property'     => ['nullable', 'integer'],
            'card_date'         => ['nullable', 'date'],
        ]);

        $card->fill($validated)->save();

        return $this->success(['card' => $card]);
    }

    /**
     * DELETE /api/v1/crm/cards/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $card = CrmCard::forUser($user->id)->findOrFail($id);
        $card->delete();

        return $this->success(['message' => 'Deleted']);
    }
}
