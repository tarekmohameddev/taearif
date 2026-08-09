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

    /**
     * AI response stats for the authenticated tenant.
     *
     * @param array{wa_number_id?: int|null, period?: string|null} $filters
     *   period values: 'this_month' (default), 'all_time', '7d', '30d'
     */
    public function aiStatsForUser(int $userId, array $filters = []): array
    {
        $query = WaAiResponseLog::query()->where('user_id', $userId);

        if (! empty($filters['wa_number_id'])) {
            $query->where('wa_number_id', (int) $filters['wa_number_id']);
        }

        $period = $filters['period'] ?? 'this_month';

        switch ($period) {
            case 'all_time':
                break;
            case '7d':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case '30d':
                $query->where('created_at', '>=', now()->subDays(30));
                break;
            case 'this_month':
            default:
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
        }

        $row = $query
            ->selectRaw('count(*) as total, sum(case when handed_off then 1 else 0 end) as handed_off_count, avg(response_time_ms) as avg_response_time_ms')
            ->first();

        return [
            'total_responses'      => (int) ($row->total ?? 0),
            'handed_off_count'     => (int) ($row->handed_off_count ?? 0),
            'avg_response_time_ms' => $row && $row->avg_response_time_ms !== null ? (float) $row->avg_response_time_ms : null,
            'period'               => $period,
            'wa_number_id'         => ! empty($filters['wa_number_id']) ? (int) $filters['wa_number_id'] : null,
        ];
    }
}
