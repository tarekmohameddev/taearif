<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WhatsAppNumberService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WaNumber::query()->where('user_id', $userId);

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (string) $filters['status']);
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $id): ?WaNumber
    {
        return WaNumber::query()
            ->where('user_id', $userId)
            ->find($id);
    }

    public function create(int $userId, array $data): WaNumber
    {
        $data['user_id'] = $userId;

        return WaNumber::create($data);
    }

    public function update(WaNumber $waNumber, array $data): WaNumber
    {
        $waNumber->update($data);

        return $waNumber->refresh();
    }
}
