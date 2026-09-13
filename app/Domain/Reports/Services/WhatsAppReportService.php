<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Support\WhatsAppNumberFilter;
use App\Models\WaAutomationRule;
use App\Models\WaCampaign;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WhatsAppReportService
{
    private const UNLIMITED_CREDIT_SENTINEL = 2147483647;

    public function summary(int $userId, ReportFilters $filter): array
    {
        $start = $filter->date->startDate;
        $end   = $filter->date->endDate;
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);

        if ($waNumberId === 0) {
            return $this->emptySummary();
        }

        $convBase = DB::table('wa_conversation_states')->where('user_id', $userId);
        $this->applyNumber($convBase, $waNumberId);
        $conversations = [
            'total'    => (clone $convBase)->count(),
            'active'   => (clone $convBase)->where('status', 'active')->count(),
            'pending'  => (clone $convBase)->where('status', 'pending')->count(),
            'resolved' => (clone $convBase)->where('status', 'resolved')->count(),
        ];

        $aiQuery = DB::table('wa_ai_response_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end]);
        $this->applyNumber($aiQuery, $waNumberId);
        $aiRow = $aiQuery
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN handed_off = 0 THEN 1 ELSE 0 END) as automated, AVG(response_time_ms) as avg_ms')
            ->first();

        $aiTotal     = (int) ($aiRow->total ?? 0);
        $aiAutomated = (int) ($aiRow->automated ?? 0);
        $automationRate = $aiTotal > 0 ? round($aiAutomated / $aiTotal * 100, 2) : 0.0;
        $avgResponseMin = $aiRow->avg_ms ? round((float) $aiRow->avg_ms / 60000, 2) : null;

        $campaignQuery = WaCampaign::where('user_id', $userId);
        $this->applyCampaignDate($campaignQuery, $start, $end);
        if ($waNumberId !== null) {
            $campaignQuery->where('wa_number_id', $waNumberId);
        }
        $campaignStats = (clone $campaignQuery)->selectRaw(
            'COUNT(*) as total, SUM(sent_count) as sent, SUM(delivered_count) as delivered, SUM(failed_count) as failed'
        )->first();

        $campaignTotal     = (int) ($campaignStats->total ?? 0);
        $campaignSent      = (int) ($campaignStats->sent ?? 0);
        $campaignDelivered = (int) ($campaignStats->delivered ?? 0);
        $campaignFailed    = (int) ($campaignStats->failed ?? 0);
        $deliveryRate      = $campaignSent > 0 ? round($campaignDelivered / $campaignSent * 100, 2) : 0.0;

        $templateBase = WaTemplate::where('user_id', $userId);
        $templateTotal = (clone $templateBase)->count();
        $templatesByStatus = [];
        foreach ((clone $templateBase)->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->get() as $row) {
            $key = strtolower((string) ($row->status ?? ''));
            if ($key === '') {
                continue;
            }
            $templatesByStatus[$key] = ($templatesByStatus[$key] ?? 0) + (int) $row->cnt;
        }

        $rulesQuery = DB::table('wa_automation_rules')->where('user_id', $userId);
        $this->applyNumber($rulesQuery, $waNumberId);
        $automationRules = $rulesQuery
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active, SUM(triggered_count) as triggered, SUM(success_count) as successes')
            ->first();

        $automationTriggered = (int) ($automationRules->triggered ?? 0);
        $automationSuccesses = (int) ($automationRules->successes ?? 0);
        $automationSuccessRate = $automationTriggered > 0
            ? round($automationSuccesses / $automationTriggered * 100, 2)
            : 0.0;

        $creditRow = DB::table('credit_transactions')
            ->where('user_id', $userId)
            ->where('transaction_type', 'usage')
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('SUM(ABS(credits_amount)) as used')
            ->first();

        $creditUsed = (int) ($creditRow->used ?? 0);
        $rawLimit = Schema::hasTable('user_credits')
            ? DB::table('user_credits')->where('user_id', $userId)->value('monthly_limit')
            : null;
        $creditLimit = $this->normalizeCreditLimit($rawLimit);

        return [
            'conversations'          => $conversations,
            'ai_automation_rate'     => $automationRate,
            'avg_response_time_min'  => $avgResponseMin,
            'campaigns_total'        => $campaignTotal,
            'campaign_delivery_rate' => $deliveryRate,
            'campaign_failed_count'  => $campaignFailed,
            'templates_total'        => $templateTotal,
            'templates_by_status'    => [
                'approved' => (int) ($templatesByStatus['approved'] ?? 0),
                'pending'  => (int) ($templatesByStatus['pending'] ?? 0),
                'rejected' => (int) ($templatesByStatus['rejected'] ?? 0),
            ],
            'active_automation_rules'       => (int) ($automationRules->active ?? 0),
            'automation_messages_triggered' => $automationTriggered,
            'automation_success_rate'       => $automationSuccessRate,
            'credit_used_this_month'        => $creditUsed,
            'credit_quota_limit'            => $creditLimit,
            'generated_at'                  => now()->toISOString(),
        ];
    }

    public function conversationVolume(int $userId, ReportFilters $filter): array
    {
        $start       = $filter->date->startDate;
        $end         = $filter->date->endDate;
        $granularity = $filter->date->granularity();
        $waNumberId  = WhatsAppNumberFilter::resolveId($userId, $filter->number);

        if ($waNumberId === 0) {
            return ['granularity' => $granularity, 'data' => [], 'generated_at' => now()->toISOString()];
        }

        $dateFormat = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $query = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.user_id', $userId)
            ->where('c.channel', 'whatsapp')
            ->whereBetween('m.created_at', [$start, $end]);

        if ($waNumberId !== null) {
            $query->join('wa_conversation_states as wcs', 'wcs.conversation_id', '=', 'c.id')
                ->where('wcs.wa_number_id', $waNumberId);
        }

        $rows = $query
            ->selectRaw(
                "DATE_FORMAT(m.created_at, '{$dateFormat}') as date_label,
                 SUM(CASE WHEN m.direction = 'outbound' THEN 1 ELSE 0 END) as ai_messages,
                 SUM(CASE WHEN m.direction = 'inbound' THEN 1 ELSE 0 END) as human_messages"
            )
            ->groupByRaw("DATE_FORMAT(m.created_at, '{$dateFormat}')")
            ->orderBy('date_label')
            ->get()
            ->map(fn ($r) => [
                'date'           => $r->date_label,
                'human_messages' => (int) $r->human_messages,
                'ai_messages'    => (int) $r->ai_messages,
            ])
            ->toArray();

        return ['granularity' => $granularity, 'data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function hourlyDistribution(int $userId, ReportFilters $filter): array
    {
        $start      = $filter->date->startDate;
        $end        = $filter->date->endDate;
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);

        if ($waNumberId === 0) {
            return ['data' => $this->emptyHourBuckets(), 'generated_at' => now()->toISOString()];
        }

        $query = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.user_id', $userId)
            ->where('c.channel', 'whatsapp')
            ->whereBetween('m.created_at', [$start, $end]);

        if ($waNumberId !== null) {
            $query->join('wa_conversation_states as wcs', 'wcs.conversation_id', '=', 'c.id')
                ->where('wcs.wa_number_id', $waNumberId);
        }

        $rows = $query
            ->selectRaw('HOUR(m.created_at) as hour, COUNT(*) as message_count')
            ->groupByRaw('HOUR(m.created_at)')
            ->orderBy('hour')
            ->pluck('message_count', 'hour')
            ->toArray();

        $buckets = [];
        for ($h = 0; $h < 24; $h++) {
            $buckets[] = ['hour' => $h, 'message_count' => (int) ($rows[$h] ?? 0)];
        }

        return ['data' => $buckets, 'generated_at' => now()->toISOString()];
    }

    public function campaignDelivery(int $userId, ReportFilters $filter): array
    {
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);
        if ($waNumberId === 0) {
            return ['data' => [], 'generated_at' => now()->toISOString()];
        }

        $query = WaCampaign::where('user_id', $userId);
        $this->applyCampaignDate($query, $filter->date->startDate, $filter->date->endDate);
        if ($waNumberId !== null) {
            $query->where('wa_number_id', $waNumberId);
        }

        $rows = $query
            ->get(['name', 'sent_count', 'delivered_count', 'failed_count'])
            ->map(function ($c) {
                $deliveryRate = $c->sent_count > 0
                    ? round($c->delivered_count / $c->sent_count * 100, 2)
                    : 0.0;

                return [
                    'name'          => $c->name,
                    'sent'          => (int) $c->sent_count,
                    'delivered'     => (int) $c->delivered_count,
                    'failed'        => (int) $c->failed_count,
                    'delivery_rate' => $deliveryRate,
                ];
            })
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function automationTriggers(int $userId, ReportFilters $filter): array
    {
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);
        if ($waNumberId === 0) {
            return ['data' => [], 'generated_at' => now()->toISOString()];
        }

        $query = WaAutomationRule::where('user_id', $userId);
        if ($waNumberId !== null) {
            $query->where('wa_number_id', $waNumberId);
        }

        $rows = $query
            ->get(['name', 'trigger', 'triggered_count', 'success_count'])
            ->map(function ($r) {
                $successRate = $r->triggered_count > 0
                    ? round($r->success_count / $r->triggered_count * 100, 2)
                    : 0.0;

                return [
                    'rule_name'       => $r->name,
                    'trigger_type'    => $r->trigger,
                    'times_triggered' => (int) $r->triggered_count,
                    'success_rate'    => $successRate,
                ];
            })
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function conversationStatus(int $userId, ReportFilters $filter): array
    {
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);
        if ($waNumberId === 0) {
            return [
                'data' => ['active' => 0, 'pending' => 0, 'resolved' => 0],
                'generated_at' => now()->toISOString(),
            ];
        }

        $query = DB::table('wa_conversation_states')->where('user_id', $userId);
        $this->applyNumber($query, $waNumberId);
        $rows = $query
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'data' => [
                'active'   => (int) ($rows['active'] ?? 0),
                'pending'  => (int) ($rows['pending'] ?? 0),
                'resolved' => (int) ($rows['resolved'] ?? 0),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function agentPerformance(int $userId, ReportFilters $filter, int $page, int $limit, ?int $actorId = null): array
    {
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);
        if ($waNumberId === 0) {
            return [
                'data'       => [],
                'pagination' => ['total' => 0, 'page' => $page, 'limit' => $limit],
                'generated_at' => now()->toISOString(),
            ];
        }

        $query = DB::table('wa_conversation_states as wcs')
            ->join('users as u', 'u.id', '=', 'wcs.assigned_agent_id')
            ->where('wcs.user_id', $userId)
            ->whereNotNull('wcs.assigned_agent_id');
        if ($waNumberId !== null) {
            $query->where('wcs.wa_number_id', $waNumberId);
        }

        if ($actorId !== null) {
            $query->where('wcs.assigned_agent_id', $actorId);
        }

        $total = (clone $query)->distinct('wcs.assigned_agent_id')->count();

        $rows = (clone $query)
            ->selectRaw(
                "u.id,
                 CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as agent_name,
                 COUNT(DISTINCT wcs.id) as conversations_handled,
                 AVG(wcs.unread_count) as avg_unread"
            )
            ->groupBy('u.id', 'u.first_name', 'u.last_name')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'agent_name'            => trim((string) $r->agent_name),
                'conversations_handled' => (int) $r->conversations_handled,
                'avg_response_time_min' => null,
                'resolution_rate'       => null,
                'csat_score'            => null,
            ])
            ->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function numberPerformance(int $userId, ReportFilters $filter, int $page, int $limit): array
    {
        $waNumberId = WhatsAppNumberFilter::resolveId($userId, $filter->number);
        if ($waNumberId === 0) {
            return [
                'data'       => [],
                'pagination' => ['total' => 0, 'page' => $page, 'limit' => $limit],
                'generated_at' => now()->toISOString(),
            ];
        }

        $numbersQuery = WaNumber::where('user_id', $userId)->with('aiConfig:id,wa_number_id,enabled');
        if ($waNumberId !== null) {
            $numbersQuery->where('id', $waNumberId);
        }

        $numbers = $numbersQuery->get();
        $total = $numbers->count();
        $paged = $numbers->forPage($page, $limit);

        $rows = $paged->map(function ($n) use ($userId) {
            $activeConvs = DB::table('wa_conversation_states')
                ->where('wa_number_id', $n->id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->count();

            return [
                'name'                 => $n->name ?: $n->phone_number,
                'phone_number'         => $n->phone_number,
                'quota_used'           => (int) $n->quota_used,
                'quota_limit'          => (int) $n->quota_limit,
                'bot_enabled'          => (bool) ($n->aiConfig?->enabled ?? false),
                'active_conversations' => $activeConvs,
            ];
        })->values()->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function applyNumber(Builder|\Illuminate\Database\Eloquent\Builder $query, ?int $waNumberId): void
    {
        if ($waNumberId !== null) {
            $query->where('wa_number_id', $waNumberId);
        }
    }

    private function applyCampaignDate($query, $start, $end): void
    {
        $query->where(function ($q) use ($start, $end): void {
            $q->whereBetween('sent_at', [$start, $end])
                ->orWhere(function ($inner) use ($start, $end): void {
                    $inner->whereNull('sent_at')->whereBetween('created_at', [$start, $end]);
                });
        });
    }

    private function normalizeCreditLimit(mixed $rawLimit): ?int
    {
        if ($rawLimit === null) {
            return null;
        }

        $limit = (int) $rawLimit;
        if ($limit <= 0 || $limit >= self::UNLIMITED_CREDIT_SENTINEL) {
            return null;
        }

        return $limit;
    }

    private function emptySummary(): array
    {
        return [
            'conversations'                 => ['total' => 0, 'active' => 0, 'pending' => 0, 'resolved' => 0],
            'ai_automation_rate'            => 0.0,
            'avg_response_time_min'         => null,
            'campaigns_total'               => 0,
            'campaign_delivery_rate'        => 0.0,
            'campaign_failed_count'         => 0,
            'templates_total'               => 0,
            'templates_by_status'           => ['approved' => 0, 'pending' => 0, 'rejected' => 0],
            'active_automation_rules'       => 0,
            'automation_messages_triggered' => 0,
            'automation_success_rate'       => 0.0,
            'credit_used_this_month'        => 0,
            'credit_quota_limit'            => null,
            'generated_at'                  => now()->toISOString(),
        ];
    }

    private function emptyHourBuckets(): array
    {
        $buckets = [];
        for ($h = 0; $h < 24; $h++) {
            $buckets[] = ['hour' => $h, 'message_count' => 0];
        }

        return $buckets;
    }
}
