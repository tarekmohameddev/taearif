<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaAutomationRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WhatsAppAutomationRuleService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WaAutomationRule::query()
            ->with(['waNumber', 'template'])
            ->where('user_id', $userId);

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (isset($filters['trigger']) && $filters['trigger'] !== null && $filters['trigger'] !== '') {
            $query->where('trigger', (string) $filters['trigger']);
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $ruleId): ?WaAutomationRule
    {
        return WaAutomationRule::query()
            ->where('user_id', $userId)
            ->find($ruleId);
    }

    public function create(int $userId, array $data): WaAutomationRule
    {
        $data['user_id'] = $userId;

        return WaAutomationRule::create($data);
    }

    public function update(WaAutomationRule $rule, array $data): WaAutomationRule
    {
        $rule->update($data);

        return $rule->refresh();
    }

    public function delete(WaAutomationRule $rule): void
    {
        $rule->delete();
    }

    public function toggle(WaAutomationRule $rule): WaAutomationRule
    {
        $rule->update(['is_active' => ! $rule->is_active]);

        return $rule->refresh();
    }

    public function statsForUser(int $userId): array
    {
        $row = WaAutomationRule::query()
            ->where('user_id', $userId)
            ->selectRaw('count(*) as total, sum(case when is_active then 1 else 0 end) as active_count, sum(triggered_count) as triggered_total, sum(success_count) as success_total')
            ->first();

        if (! $row) {
            return ['total' => 0, 'active_count' => 0, 'triggered_total' => 0, 'success_total' => 0];
        }

        return [
            'total' => (int) ($row->total ?? 0),
            'active_count' => (int) ($row->active_count ?? 0),
            'triggered_total' => (int) ($row->triggered_total ?? 0),
            'success_total' => (int) ($row->success_total ?? 0),
        ];
    }
}
