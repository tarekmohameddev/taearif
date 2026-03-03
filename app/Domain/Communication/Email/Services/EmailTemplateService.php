<?php

namespace App\Domain\Communication\Email\Services;

use App\Models\EmailCampaignTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmailTemplateService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = EmailCampaignTemplate::query()->where('user_id', $userId);

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $templateId): ?EmailCampaignTemplate
    {
        return EmailCampaignTemplate::query()
            ->where('user_id', $userId)
            ->find($templateId);
    }

    public function create(int $userId, array $data): EmailCampaignTemplate
    {
        $data['user_id'] = $userId;

        return EmailCampaignTemplate::create($data);
    }

    public function update(EmailCampaignTemplate $template, array $data): EmailCampaignTemplate
    {
        $template->update($data);

        return $template->refresh();
    }

    public function delete(EmailCampaignTemplate $template): void
    {
        $template->delete();
    }
}
