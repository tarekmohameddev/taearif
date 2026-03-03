<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaAiResponseLog;
use App\Models\WaAutomationRule;

class WhatsAppStatsService
{
    public function automationStatsForUser(int $userId): array
    {
        $rules = WaAutomationRule::query()->where('user_id', $userId)->get();

        return [
            'total_rules' => $rules->count(),
            'active_rules' => $rules->where('is_active', true)->count(),
            'triggered_total' => $rules->sum('triggered_count'),
            'success_total' => $rules->sum('success_count'),
        ];
    }

    public function aiStatsForUser(int $userId): array
    {
        $row = WaAiResponseLog::query()
            ->where('user_id', $userId)
            ->selectRaw('count(*) as total, sum(case when handed_off then 1 else 0 end) as handed_off_count, avg(response_time_ms) as avg_response_time_ms')
            ->first();

        return [
            'total_responses' => (int) ($row->total ?? 0),
            'handed_off_count' => (int) ($row->handed_off_count ?? 0),
            'avg_response_time_ms' => $row && $row->avg_response_time_ms !== null ? (float) $row->avg_response_time_ms : null,
        ];
    }
}
