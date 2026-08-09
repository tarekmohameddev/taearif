<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WhatsAppTemplateService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WaTemplate::query()->where('user_id', $userId);

        if (isset($filters['category']) && $filters['category'] !== null && $filters['category'] !== '') {
            $query->where('category', (string) $filters['category']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', strtoupper((string) $filters['status']));
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $templateId): ?WaTemplate
    {
        return WaTemplate::query()
            ->where('user_id', $userId)
            ->find($templateId);
    }
}
