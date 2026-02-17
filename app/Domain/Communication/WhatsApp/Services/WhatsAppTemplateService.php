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
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('content', 'like', '%' . $filters['search'] . '%');
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

    public function create(int $userId, array $data): WaTemplate
    {
        $data['user_id'] = $userId;

        return WaTemplate::create($data);
    }

    public function update(WaTemplate $template, array $data): WaTemplate
    {
        $template->update($data);

        return $template->refresh();
    }

    public function delete(WaTemplate $template): void
    {
        $template->delete();
    }

    public function renderContent(WaTemplate $template, array $variables = []): string
    {
        $content = $template->content;
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }
}
