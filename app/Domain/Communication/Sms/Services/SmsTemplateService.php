<?php

namespace App\Domain\Communication\Sms\Services;

use App\Models\SmsTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SmsTemplateService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SmsTemplate::query()->where('user_id', $userId);

        if (isset($filters['category']) && $filters['category'] !== null && $filters['category'] !== '') {
            $query->where('category', (string) $filters['category']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $templateId): ?SmsTemplate
    {
        return SmsTemplate::query()
            ->where('user_id', $userId)
            ->find($templateId);
    }

    public function create(int $userId, array $data): SmsTemplate
    {
        $data['user_id'] = $userId;

        return SmsTemplate::create($data);
    }

    public function update(SmsTemplate $template, array $data): SmsTemplate
    {
        $template->update($data);

        return $template->refresh();
    }

    public function delete(SmsTemplate $template): void
    {
        $template->delete();
    }
}

