<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportDateFilter;
use App\Models\WaAiResponseLog;
use App\Models\WaAutomationRule;
use App\Models\WaCampaign;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class WhatsAppReportService
{
    public function summary(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        // Conversations (using wa_conversation_states)
        $convBase = DB::table('wa_conversation_states')->where('user_id', $userId);
        $conversations = [
            'total'    => (clone $convBase)->count(),
            'active'   => (clone $convBase)->where('status', 'active')->count(),
            'pending'  => (clone $convBase)->where('status', 'pending')->count(),
            'resolved' => (clone $convBase)->where('status', 'resolved')->count(),
        ];

        // AI automation rate (% of bot replies that did NOT hand off)
        $aiRow = DB::table('wa_ai_response_logs')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN handed_off = 0 THEN 1 ELSE 0 END) as automated, AVG(response_time_ms) as avg_ms')
            ->first();

        $aiTotal     = (int) ($aiRow->total ?? 0);
        $aiAutomated = (int) ($aiRow->automated ?? 0);
        $automationRate = $aiTotal > 0 ? round($aiAutomated / $aiTotal * 100, 2) : 0.0;
        $avgResponseMin = $aiRow->avg_ms ? round((float) $aiRow->avg_ms / 60000, 2) : null;

        // Campaigns in period
        $campaignBase = WaCampaign::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end]);

        $campaignStats = (clone $campaignBase)->selectRaw(
            'COUNT(*) as total, SUM(sent_count) as sent, SUM(delivered_count) as delivered, SUM(failed_count) as failed'
        )->first();

        $campaignTotal    = (int) ($campaignStats->total ?? 0);
        $campaignSent     = (int) ($campaignStats->sent ?? 0);
        $campaignDelivered = (int) ($campaignStats->delivered ?? 0);
        $campaignFailed   = (int) ($campaignStats->failed ?? 0);
        $deliveryRate     = $campaignSent > 0 ? round($campaignDelivered / $campaignSent * 100, 2) : 0.0;

        // Templates by status
        $templateBase = WaTemplate::where('user_id', $userId);
        $templateTotal = (clone $templateBase)->count();
        $templatesByStatus = (clone $templateBase)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // Automation rules
        $automationRules = DB::table('wa_automation_rules')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active, SUM(triggered_count) as triggered, SUM(success_count) as successes')
            ->first();

        $automationTriggered = (int) ($automationRules->triggered ?? 0);
        $automationSuccesses = (int) ($automationRules->successes ?? 0);
        $automationSuccessRate = $automationTriggered > 0
            ? round($automationSuccesses / $automationTriggered * 100, 2)
            : 0.0;

        // Credit balance (current month)
        $creditRow = DB::table('credit_transactions')
            ->where('user_id', $userId)
            ->where('transaction_type', 'usage')
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('SUM(ABS(credits_amount)) as used')
            ->first();

        $creditUsed = (int) ($creditRow->used ?? 0);
        $creditLimit = (int) DB::table('user_credits')->where('user_id', $userId)->value('monthly_limit') ?: 0;

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
            'active_automation_rules'     => (int) ($automationRules->active ?? 0),
            'automation_messages_triggered' => $automationTriggered,
            'automation_success_rate'       => $automationSuccessRate,
            'credit_used_this_month'        => $creditUsed,
            'credit_quota_limit'            => $creditLimit,
            'generated_at'                  => now()->toISOString(),
        ];
    }

    public function conversationVolume(int $userId, ReportDateFilter $filter): array
    {
        $start       = $filter->startDate;
        $end         = $filter->endDate;
        $granularity = $filter->granularity();

        $dateFormat = match ($granularity) {
            'month' => '%Y-%m',
            'week'  => '%x-W%v',
            default => '%Y-%m-%d',
        };

        $rows = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.user_id', $userId)
            ->where('c.channel', 'whatsapp')
            ->whereBetween('m.created_at', [$start, $end])
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

    public function hourlyDistribution(int $userId, ReportDateFilter $filter): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $rows = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.user_id', $userId)
            ->where('c.channel', 'whatsapp')
            ->whereBetween('m.created_at', [$start, $end])
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

    public function campaignDelivery(int $userId, ReportDateFilter $filter): array
    {
        $rows = WaCampaign::where('user_id', $userId)
            ->whereBetween('created_at', [$filter->startDate, $filter->endDate])
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

    public function automationTriggers(int $userId, ReportDateFilter $filter): array
    {
        $rows = WaAutomationRule::where('user_id', $userId)
            ->get(['name', 'trigger', 'triggered_count', 'success_count'])
            ->map(function ($r) {
                $successRate = $r->triggered_count > 0
                    ? round($r->success_count / $r->triggered_count * 100, 2)
                    : 0.0;

                return [
                    'rule_name'     => $r->name,
                    'trigger_type'  => $r->trigger,
                    'times_triggered' => (int) $r->triggered_count,
                    'success_rate'  => $successRate,
                ];
            })
            ->toArray();

        return ['data' => $rows, 'generated_at' => now()->toISOString()];
    }

    public function conversationStatus(int $userId): array
    {
        $rows = DB::table('wa_conversation_states')
            ->where('user_id', $userId)
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

    public function agentPerformance(int $userId, ReportDateFilter $filter, int $page, int $limit, ?int $actorId = null): array
    {
        $start = $filter->startDate;
        $end   = $filter->endDate;

        $query = DB::table('wa_conversation_states as wcs')
            ->join('users as u', 'u.id', '=', 'wcs.assigned_agent_id')
            ->where('wcs.user_id', $userId)
            ->whereNotNull('wcs.assigned_agent_id');

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
                'agent_name'           => trim((string) $r->agent_name),
                'conversations_handled' => (int) $r->conversations_handled,
                'avg_response_time_min' => null,
                'resolution_rate'      => null,
                'csat_score'           => null,
            ])
            ->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }

    public function numberPerformance(int $userId, ReportDateFilter $filter, int $page, int $limit): array
    {
        $numbers = WaNumber::where('user_id', $userId)
            ->with('aiConfig:id,wa_number_id,enabled')
            ->get();

        $total = $numbers->count();
        $paged = $numbers->forPage($page, $limit);

        $rows = $paged->map(function ($n) use ($userId) {
            $activeConvs = DB::table('wa_conversation_states')
                ->where('wa_number_id', $n->id)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->count();

            return [
                'name'               => $n->name ?: $n->phone_number,
                'phone_number'       => $n->phone_number,
                'quota_used'         => (int) $n->quota_used,
                'quota_limit'        => (int) $n->quota_limit,
                'bot_enabled'        => (bool) ($n->aiConfig?->enabled ?? false),
                'active_conversations' => $activeConvs,
            ];
        })->values()->toArray();

        return [
            'data'       => $rows,
            'pagination' => ['total' => $total, 'page' => $page, 'limit' => $limit],
            'generated_at' => now()->toISOString(),
        ];
    }
}
