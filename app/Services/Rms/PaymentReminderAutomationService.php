<?php

namespace App\Services\Rms;

use App\Domain\Communication\Sms\Services\SmsSingleMessageService;
use App\Domain\Communication\Sms\Contracts\SmsGatewayReadiness;
use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\DTOs\SendMessageDto;
use App\Domain\Communication\WhatsApp\Services\WhatsAppConversationService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService;
use App\Domain\Communication\Support\CommunicationEndpoints;
use App\Jobs\DispatchRmsPaymentReminderJob;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmsPaymentReminderAutomation;
use App\Models\Api\Rms\RmsPaymentReminderLog;
use App\Models\Message;
use App\Models\WaNumber;
use App\Models\User\Language as UserLanguage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentReminderAutomationService
{
    public function __construct(private readonly WhatsAppTemplateService $templates, private readonly SmsGatewayReadiness $smsReadiness) {}

    public function save(int $ownerId, array $data): RmsPaymentReminderAutomation
    {
        if ($data['enabled']) {
            $this->assertChannelAvailable($ownerId, $data);
        }
        if ($data['channel_type'] === 'whatsapp') {
            $channel = WaNumber::where('user_id', $ownerId)->where('id', $data['channel_id'])->first();
            $data['channel_name'] = $channel?->name;
            $data['sender_id'] = null;
        }
        $automation = RmsPaymentReminderAutomation::firstOrNew(['user_id' => $ownerId]);
        $automation->fill($data);
        $automation->save();
        if (! $automation->enabled) {
            RmsPaymentReminderLog::where('user_id', $ownerId)->where('status', 'pending')->update([
                'status' => 'skipped', 'skipped_reason' => 'automation_disabled', 'updated_at' => now(),
            ]);
        }
        return $automation->fresh();
    }

    public function configuration(int $ownerId): array
    {
        $automation = RmsPaymentReminderAutomation::firstWhere('user_id', $ownerId);
        return $automation ? $automation->toArray() : [
            'user_id' => $ownerId, 'enabled' => false, 'channel_type' => 'whatsapp', 'channel_id' => null,
            'channel_name' => null, 'sender_id' => null, 'sending_time' => '09:00:00', 'timezone' => 'Asia/Riyadh',
            'rules' => self::defaultRules(),
        ];
    }

    public static function defaultRules(): array
    {
        return [
            ['key' => 'before_due', 'stage' => 'before_due', 'days_offset' => -10, 'enabled' => true, 'template' => 'Hello {tenant_name}, {balance} is due on {due_date} for {rental_reference} ({unit_name}).'],
            ['key' => 'due_today', 'stage' => 'due_today', 'days_offset' => 0, 'enabled' => true, 'template' => 'Hello {tenant_name}, {balance} is due today for {rental_reference} ({unit_name}).'],
            ['key' => 'overdue', 'stage' => 'overdue', 'days_offset' => 1, 'enabled' => true, 'template' => 'Hello {tenant_name}, {balance} remains unpaid since {due_date} for {rental_reference} ({unit_name}).'],
            ['key' => 'overdue_daily', 'stage' => 'overdue_recurring', 'days_offset' => 2, 'interval_days' => 1, 'recurring' => true, 'enabled' => false, 'template' => 'Hello {tenant_name}, {balance} remains unpaid since {due_date} for {rental_reference} ({unit_name}).'],
        ];
    }

    private function assertChannelAvailable(int $ownerId, array $data): void
    {
        if (config('queue.default') === 'sync') {
            throw ValidationException::withMessages(['enabled' => 'Automated reminders require an asynchronous queue connection; the sync queue is not supported.']);
        }
        if ($data['channel_type'] === 'whatsapp') {
            $query = WaNumber::where('user_id', $ownerId)->where('status', 'active');
            if (! empty($data['channel_id'])) $query->where('id', $data['channel_id']);
            $wa = $query->first();
            if (! $wa) {
                throw ValidationException::withMessages(['channel_id' => 'An active WhatsApp channel owned by this account is required.']);
            }
            if (! config('communication.enabled', false) || ! config('communication.whatsapp.enabled', false) || ! $this->whatsAppCredentialsAvailable($wa)) {
                throw ValidationException::withMessages(['channel_id' => 'The selected WhatsApp channel is missing active messaging configuration or provider credentials.']);
            }
            if (strtolower((string) $wa->provider) === 'meta') {
                foreach ((array) ($data['rules'] ?? []) as $index => $rule) {
                    if (empty($rule['enabled'])) continue;
                    $template = $this->templates->findApprovedForUser($ownerId, (int) ($rule['wa_template_id'] ?? 0));
                    if (! $template) throw ValidationException::withMessages(["rules.$index.wa_template_id" => 'An approved active Meta template owned by this account is required.']);
                    try {
                        $indices = $this->templates->placeholderIndices($template);
                    } catch (\InvalidArgumentException $e) {
                        throw ValidationException::withMessages(["rules.$index.wa_template_id" => $e->getMessage()]);
                    }
                    $map = $rule['meta_variable_map'] ?? [];
                    foreach ($indices as $placeholder) {
                        $field = $map[(string) $placeholder] ?? $map[$placeholder] ?? null;
                        if (! in_array($field, ['tenant_name', 'due_date', 'rental_reference', 'unit_name', 'balance'], true)) {
                            throw ValidationException::withMessages(["rules.$index.meta_variable_map" => "Map template placeholder {{$placeholder}} to a supported reminder value."]);
                        }
                    }
                    if (count($map) !== count($indices)) throw ValidationException::withMessages(["rules.$index.meta_variable_map" => 'Map every approved template placeholder exactly once.']);
                }
            }
            return;
        }
        if (! $this->smsReadiness->isReady()) {
            throw ValidationException::withMessages(['channel_type' => 'SMS automation requires an enabled, registered provider adapter with valid deployment credentials.']);
        }
    }

    private function whatsAppCredentialsAvailable(WaNumber $wa): bool
    {
        $provider = strtolower((string) $wa->provider);
        $meta = is_array($wa->meta) ? $wa->meta : [];
        if ($provider === 'meta') {
            return ! empty($meta['access_token'] ?? $meta['meta_access_token'] ?? null)
                && ! empty($wa->phone_number_id ?? $meta['phone_number_id'] ?? $meta['meta_phone_number_id'] ?? null);
        }
        if ($provider === 'evolution') {
            return ! empty(config('communication.whatsapp.evolution.base_url'))
                && ! empty(config('communication.whatsapp.evolution.api_key'))
                && ! empty($wa->provider_account_id ?? $meta['instance'] ?? $meta['evolution_instance'] ?? null);
        }
        return false;
    }

    /** Scan due installments and insert one idempotent log per installment/channel/local date. */
    public function scheduleDue(Carbon $now): int
    {
        $created = 0;
        RmsPaymentReminderAutomation::where('enabled', true)->orderBy('id')->chunkById(100, function ($automations) use ($now, &$created): void {
            foreach ($automations as $automation) {
                try { $zone = $automation->timezone ?: 'Asia/Riyadh'; $localNow = $now->copy()->setTimezone($zone); }
                catch (\Throwable $e) { Log::warning('Invalid RMS reminder timezone', ['user_id' => $automation->user_id]); continue; }
                if ($localNow->format('H:i') < substr((string) $automation->sending_time, 0, 5)) continue;
                $rules = collect($automation->rules ?: self::defaultRules())->filter(fn ($r) => !empty($r['enabled']))->sortBy(fn ($r) => (int) ($r['priority'] ?? 100));
                if ($rules->isEmpty()) continue;
                $languageId = $this->defaultLanguageId((int) $automation->user_id);
                RmPaymentInstallment::query()->where('user_id', $automation->user_id)
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->whereHas('rental', fn ($q) => $q->where('user_id', $automation->user_id)->where('status', 'active')->whereNull('deleted_at'))
                    ->whereHas('contract', fn ($q) => $q->where('user_id', $automation->user_id)->where('status', 'active'))
                    ->with(['rental.property.contents', 'contract'])
                    ->where('amount', '>', 0)->whereRaw('amount > COALESCE(paid_amount, 0)')
                    ->chunkById(200, function ($installments) use ($automation, $rules, $localNow, $zone, $languageId, &$created): void {
                        foreach ($installments as $installment) {
                            foreach ($rules as $rule) {
                                if (! $this->ruleDue($rule, $installment->due_date, $localNow)) continue;
                                $created += $this->createScheduledLog($automation, $installment, $rule, $localNow, $zone, $languageId);
                                break; // Rule priority prevents same-day collisions.
                            }
                        }
                    });
            }
        });
        return $created;
    }

    private function ruleDue(array $rule, $dueDate, Carbon $now): bool
    {
        $offset = (int) ($rule['days_offset'] ?? 0);
        $target = $this->dueDateCarbon($dueDate, (string) $now->timezone)->addDays($offset);
        if ($target->gt($now->copy()->startOfDay())) return false;
        if (! empty($rule['recurring'])) {
            $interval = max(1, (int) ($rule['interval_days'] ?? 1));
            return $target->diffInDays($now->copy()->startOfDay()) % $interval === 0;
        }
        return $target->isSameDay($now);
    }

    private function createScheduledLog(RmsPaymentReminderAutomation $automation, RmPaymentInstallment $i, array $rule, Carbon $localNow, string $zone, int $languageId): int
    {
        $rental = $i->rental;
        if (! $rental) return 0;
        $due = $this->dueDateCarbon($i->due_date, $zone);
        $remaining = max(0, (float) $i->amount - min(max(0, (float) $i->paid_amount), (float) $i->amount));
        try {
            $content = $this->ruleContent($automation, $rule, $i, $rental, $due, $remaining, $languageId);
        } catch (\Throwable $e) {
            Log::warning('RMS reminder template could not be rendered', ['user_id' => $automation->user_id, 'installment_id' => $i->id, 'error' => $e->getMessage()]);
            try {
                RmsPaymentReminderLog::create([
                    'user_id' => $automation->user_id, 'rental_id' => $i->rental_id, 'installment_id' => $i->id,
                    'rule_key' => $rule['key'] ?? $rule['stage'], 'stage' => $rule['stage'], 'channel_type' => $automation->channel_type,
                    'channel_id' => $automation->channel_id, 'channel_name' => $automation->channel_name,
                    'recipient_phone' => $this->normalizePhone((string) $rental->tenant_phone),
                    'sender_phone' => $automation->channel_type === 'whatsapp' ? WaNumber::where('id', $automation->channel_id)->where('user_id', $automation->user_id)->first()?->phone_number : null,
                    'sender_id' => $automation->sender_id, 'message_content' => '',
                    'scheduled_at' => $localNow->copy()->setTimeFromTimeString($automation->sending_time)->setTimezone('UTC'),
                    'status' => 'failed', 'failure_reason' => 'template_unavailable_or_invalid: ' . $e->getMessage(),
                    'outstanding_balance' => $remaining, 'local_day' => $localNow->toDateString(),
                ]);
                return 1;
            } catch (QueryException $duplicate) {
                if (in_array((string) $duplicate->getCode(), ['23000', '23505'], true) || str_contains(strtolower($duplicate->getMessage()), 'unique constraint')) return 0;
                throw $duplicate;
            }
        }
        try {
            $log = RmsPaymentReminderLog::create([
                'user_id' => $automation->user_id, 'rental_id' => $i->rental_id, 'installment_id' => $i->id,
                'rule_key' => $rule['key'] ?? $rule['stage'], 'stage' => $rule['stage'], 'channel_type' => $automation->channel_type,
                'channel_id' => $automation->channel_id, 'channel_name' => $automation->channel_name,
                'recipient_phone' => $this->normalizePhone((string) $rental->tenant_phone), 'sender_phone' => $automation->channel_type === 'whatsapp' ? WaNumber::where('id', $automation->channel_id)->where('user_id', $automation->user_id)->first()?->phone_number : null,
                'sender_id' => $automation->sender_id, 'scheduled_at' => $localNow->copy()->setTimeFromTimeString($automation->sending_time)->setTimezone('UTC'),
                'status' => 'pending', 'message_content' => $content, 'outstanding_balance' => $remaining,
                'local_day' => $localNow->toDateString(),
            ]);
        } catch (QueryException $e) {
            if (in_array((string) $e->getCode(), ['23000', '23505'], true) || str_contains(strtolower($e->getMessage()), 'unique constraint')) return 0;
            throw $e;
        }
        DispatchRmsPaymentReminderJob::dispatch($log->id)->onQueue('communication');
        return 1;
    }

    public function dispatch(int $logId, CommunicationService $communication, WhatsAppConversationService $conversations, SmsSingleMessageService $sms): void
    {
        $context = null;
        DB::transaction(function () use ($logId, &$log, &$context): void {
            $log = RmsPaymentReminderLog::whereKey($logId)->lockForUpdate()->first();
            if (! $log || $log->status !== 'pending') return;
            $automation = RmsPaymentReminderAutomation::where('user_id', $log->user_id)->first();
            if (! $automation || ! $automation->enabled) { $log->update(['status' => 'skipped', 'skipped_reason' => 'automation_disabled']); return; }
            $i = RmPaymentInstallment::where('id', $log->installment_id)->where('user_id', $log->user_id)->first();
            $rental = $i ? RmRental::whereKey($i->rental_id)->where('user_id', $log->user_id)->where('status', 'active')->with('property.contents')->first() : null;
            $contractIsActive = $i && RmContract::where('id', $i->contract_id)->where('user_id', $log->user_id)->where('status', 'active')->whereNull('deleted_at')->exists();
            $balance = $i ? max(0, (float) $i->amount - min(max(0, (float) $i->paid_amount), (float) $i->amount)) : 0;
            if (! $i || ! $rental || ! $contractIsActive || in_array((string) $i->status, ['void', 'cancelled'], true)
                || in_array((string) $rental->status, ['ended', 'cancelled', 'terminated'], true)
                || $balance <= 0) {
                $log->update(['status' => 'skipped', 'skipped_reason' => $balance <= 0 ? 'installment_paid' : 'rental_or_installment_unavailable', 'outstanding_balance' => $balance]);
                return;
            }
            if ((int) $automation->channel_id !== (int) $log->channel_id || $automation->channel_type !== $log->channel_type) {
                $log->update(['status' => 'skipped', 'skipped_reason' => 'channel_configuration_changed', 'outstanding_balance' => $balance]);
                return;
            }
            $normalizedPhone = $this->normalizePhone((string) $rental->tenant_phone);
            if (! $normalizedPhone) {
                $log->update(['status' => 'skipped', 'skipped_reason' => 'invalid_recipient_phone', 'outstanding_balance' => $balance]);
                return;
            }
            $rule = collect($automation->rules ?: self::defaultRules())->first(fn ($r) => ($r['key'] ?? $r['stage'] ?? null) === $log->rule_key);
            if (! $rule || empty($rule['enabled'])) {
                $log->update(['status' => 'skipped', 'skipped_reason' => 'reminder_rule_disabled', 'outstanding_balance' => $balance]);
                return;
            }
            try {
                $content = $this->ruleContent($automation, $rule, $i, $rental, $this->dueDateCarbon($i->due_date, $automation->timezone), $balance, $this->defaultLanguageId((int) $automation->user_id));
            } catch (\Throwable $e) {
                $log->update(['status' => 'failed', 'failure_reason' => $e->getMessage(), 'outstanding_balance' => $balance]);
                return;
            }
            $log->update(['status' => 'sending', 'attempted_at' => now(), 'outstanding_balance' => $balance, 'recipient_phone' => $normalizedPhone, 'message_content' => $content]);
            $context = compact('automation', 'i', 'rental', 'rule', 'balance');
        });
        if (! isset($log) || $log->status !== 'sending' || ! $log->attempted_at || ! $context) return;
        extract($context, EXTR_SKIP);
        try {
            if ($log->channel_type === 'whatsapp') {
                $wa = WaNumber::where('id', $log->channel_id)->where('user_id', $log->user_id)->where('status', 'active')->first();
                if (! $wa) throw new \RuntimeException('Configured WhatsApp channel is unavailable.');
                $isMeta = strtolower((string) $wa->provider) === 'meta';
                $conversation = $conversations->createOrReturnConversation((int) $log->user_id, (string) $log->recipient_phone, (int) $wa->id);
                $dto = new SendMessageDto(
                    userId: (int) $log->user_id, conversationId: (int) $conversation->id,
                    content: (string) $log->message_content, channel: 'whatsapp', waNumberId: (int) $wa->id,
                    endpointSignature: $isMeta ? CommunicationEndpoints::WHATSAPP_SEND_TEMPLATE : null,
                    templateId: $isMeta ? (int) ($rule['wa_template_id'] ?? 0) : null,
                    variables: $isMeta ? $this->metaVariables($rule, $i, $rental, $balance, $automation) : null,
                );
                $message = $communication->sendMessage($dto, 'rms-reminder-' . $log->id);
                $log->update(['status' => 'sent', 'sent_at' => now(), 'provider_message_id' => $message->provider_message_id, 'communication_message_id' => $message->id, 'sender_phone' => $wa->phone_number]);
            } else {
                if (! $this->smsReadiness->isReady()) throw new \RuntimeException('Configured SMS provider adapter is no longer available.');
                $result = $sms->send((int) $log->user_id, 'rms-reminder-' . $log->id, (string) $log->recipient_phone, (string) $log->message_content, $automation->sender_id ?: null);
                if (($result['status'] ?? null) !== 'sent' || empty($result['gateway_message_id']) || empty($result['provider'])) {
                    throw new \RuntimeException('SMS provider did not confirm acceptance.');
                }
                $log->update(['status' => 'sent', 'sent_at' => $result['sent_at'] ?? now(), 'provider_message_id' => $result['gateway_message_id'], 'provider' => $result['provider'], 'sender_id' => $result['sender_id'] ?? null]);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            Log::warning('RMS payment reminder dispatch failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
        }
    }

    private function renderTemplate(string $template, RmRental $rental, Carbon $due, float $balance, int $languageId): string
    {
        $property = $rental->property;
        if ($property && (int) $property->user_id === (int) $rental->user_id) {
            $contents = $property->relationLoaded('contents') ? $property->contents : $property->contents()->orderBy('id')->get();
            $content = $contents->firstWhere('language_id', $languageId) ?? $contents->sortBy('id')->first();
            $unitName = $content?->title ?: ($property->property_code ?: 'Property #' . $property->id);
        } else {
            $unitName = 'Property #' . ($rental->unit_id ?: 'unavailable');
        }
        return strtr($template, [
            '{tenant_name}' => (string) $rental->tenant_full_name, '{due_date}' => $due->toDateString(),
            '{rental_reference}' => (string) ($rental->contract_number ?: $rental->id),
            '{unit_name}' => (string) $unitName,
            '{balance}' => number_format($balance, 2) . ' ' . ($rental->currency ?: 'SAR'),
        ]);
    }

    private function metaVariables(array $rule, RmPaymentInstallment $i, RmRental $rental, float $balance, RmsPaymentReminderAutomation $automation): array
    {
        $values = $this->reminderValues($rental, $this->dueDateCarbon($i->due_date, $automation->timezone), $balance, $this->defaultLanguageId((int) $automation->user_id));
        $out = [];
        foreach ((array) ($rule['meta_variable_map'] ?? []) as $index => $field) $out[(string) $index] = $values[$field] ?? null;
        return $out;
    }

    private function ruleContent(RmsPaymentReminderAutomation $automation, array $rule, RmPaymentInstallment $installment, RmRental $rental, Carbon $due, float $balance, int $languageId): string
    {
        $wa = $automation->channel_type === 'whatsapp' ? WaNumber::where('user_id', $automation->user_id)->where('id', $automation->channel_id)->first() : null;
        if ($wa && strtolower((string) $wa->provider) === 'meta') {
            $template = $this->templates->findApprovedForUser((int) $automation->user_id, (int) ($rule['wa_template_id'] ?? 0));
            if (! $template) throw new \RuntimeException('Configured approved WhatsApp template is no longer available.');
            return $this->templates->renderContent($template, $this->metaVariables($rule, $installment, $rental, $balance, $automation));
        }
        return $this->renderTemplate((string) ($rule['template'] ?? ''), $rental, $due, $balance, $languageId);
    }

    private function reminderValues(RmRental $rental, Carbon $due, float $balance, int $languageId): array
    {
        $property = $rental->property;
        $ownsProperty = $property && (int) $property->user_id === (int) $rental->user_id;
        $contents = $ownsProperty ? ($property->relationLoaded('contents') ? $property->contents : $property->contents()->orderBy('id')->get()) : collect();
        $content = $contents->firstWhere('language_id', $languageId) ?? $contents->sortBy('id')->first();
        $unit = $ownsProperty ? ($content?->title ?: ($property->property_code ?: 'Property #' . $property->id)) : 'Property #' . ($rental->unit_id ?: 'unavailable');
        return ['tenant_name' => (string) $rental->tenant_full_name, 'due_date' => $due->toDateString(), 'rental_reference' => (string) ($rental->contract_number ?: $rental->id), 'unit_name' => (string) $unit, 'balance' => number_format($balance, 2) . ' ' . ($rental->currency ?: 'SAR')];
    }

    private function dueDateCarbon($value, string $timezone): Carbon
    {
        $date = $value instanceof DateTimeInterface ? $value->format('Y-m-d') : substr((string) $value, 0, 10);
        return Carbon::createFromFormat('!Y-m-d', $date, $timezone);
    }

    private function defaultLanguageId(int $ownerId): int
    {
        return (int) (UserLanguage::where('user_id', $ownerId)->where('is_default', 1)->orderBy('id')->value('id') ?? 1);
    }

    public function logs(int $ownerId, array $filters): array
    {
        $q = RmsPaymentReminderLog::where('user_id', $ownerId);
        if (!empty($filters['rental_id'])) $q->where('rental_id', $filters['rental_id']);
        if (!empty($filters['installment_id'])) $q->where('installment_id', $filters['installment_id']);
        if (!empty($filters['channel_type'])) $q->where('channel_type', $filters['channel_type']);
        if (!empty($filters['stage'])) $q->where('stage', $filters['stage']);
        if (!empty($filters['phone_number'])) {
            $normalizedFilter = $this->normalizePhone((string) $filters['phone_number']);
            $searchDigits = preg_replace('/\D+/', '', $normalizedFilter ?: (string) $filters['phone_number']);
            $q->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(recipient_phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', '') LIKE ?", ['%' . $searchDigits . '%']);
        }
        if (!empty($filters['from_date'])) $q->whereDate('local_day', '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $q->whereDate('local_day', '<=', $filters['to_date']);
        $recipientBaseQuery = clone $q;
        $recipientSummaryQuery = (clone $recipientBaseQuery)->select('recipient_phone', 'channel_type')
            ->selectRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent_count")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count")
            ->selectRaw('MAX(sent_at) AS most_recent_message_at')->groupBy('recipient_phone', 'channel_type');
        if (isset($filters['messages_sent']) || isset($filters['messages_sent_min']) || isset($filters['messages_sent_max'])) {
            $min = (int) ($filters['messages_sent_min'] ?? $filters['messages_sent'] ?? 0);
            $max = $filters['messages_sent_max'] ?? $filters['messages_sent'] ?? null;
            $recipientSummaryQuery->havingRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) >= ?", [$min]);
            if ($max !== null) $recipientSummaryQuery->havingRaw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) <= ?", [(int) $max]);
            $matchingRecipients = (clone $recipientSummaryQuery)->reorder();
            $matchingLogIds = DB::table('rms_payment_reminder_logs as matched_logs')
                ->joinSub($matchingRecipients, 'matching_recipients', function ($join): void {
                    $join->on('matching_recipients.recipient_phone', '=', 'matched_logs.recipient_phone')
                        ->on('matching_recipients.channel_type', '=', 'matched_logs.channel_type');
                })->select('matched_logs.id');
            $q->whereIn('id', $matchingLogIds);
        }
        if (($filters['status'] ?? null) === 'delivered') {
            $q->whereExists(fn ($message) => $message->selectRaw('1')->from('messages as reminder_messages')
                ->whereColumn('reminder_messages.id', 'rms_payment_reminder_logs.communication_message_id')->where('reminder_messages.status', 'delivered'));
        } elseif (!empty($filters['status'])) $q->where('status', $filters['status']);
        $countQuery = clone $q;
        $page = max(1, (int) ($filters['page'] ?? 1)); $per = min(100, max(1, (int) ($filters['per_page'] ?? 25)));
        $items = $q->orderByDesc('scheduled_at')->orderByDesc('id')->paginate($per, ['*'], 'page', $page);
        $messageStatuses = Message::whereIn('id', collect($items->items())->pluck('communication_message_id')->filter()->all())
            ->get(['id', 'status'])->keyBy('id');
        foreach ($items->items() as $item) {
            $item->setAttribute('delivery_status', $messageStatuses->get($item->communication_message_id)?->status ?? $item->status);
        }
        $summaryQuery = clone $countQuery;
        $recipientRows = (clone $recipientSummaryQuery)->orderByDesc('most_recent_message_at')->limit(101)->get();
        $recipientSummaries = $recipientRows->take(100)->map(fn ($row) => [
            'recipient_phone' => $row->recipient_phone, 'channel_type' => $row->channel_type,
            'messages_sent' => (int) $row->sent_count, 'failed_attempts' => (int) $row->failed_count,
            'most_recent_message_at' => $row->most_recent_message_at,
        ])->values();
        return [
            'items' => $items->items(), 'pagination' => ['page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total(), 'has_more' => $items->hasMorePages()],
            'summary' => ['total' => $summaryQuery->count(), 'sent' => (clone $summaryQuery)->where('status', 'sent')->count(), 'delivered' => (clone $summaryQuery)->whereExists(fn ($message) => $message->selectRaw('1')->from('messages as reminder_messages')->whereColumn('reminder_messages.id', 'rms_payment_reminder_logs.communication_message_id')->where('reminder_messages.status', 'delivered'))->count(), 'failed' => (clone $summaryQuery)->where('status', 'failed')->count(), 'skipped' => (clone $summaryQuery)->where('status', 'skipped')->count(), 'recipients' => $recipientSummaries],
            'recipient_summary_limit' => 100,
            'recipient_summary_truncated' => $recipientRows->count() > 100,
            'date_filter_field' => 'local_day (inclusive; account timezone)',
        ];
    }

    /** Normalize to E.164-like digits using configured country code for local numbers. */
    private function normalizePhone(string $phone): ?string
    {
        $raw = trim($phone);
        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits || strlen($digits) < 8 || strlen($digits) > 16) return null;
        if (str_starts_with($raw, '+')) return '+' . $digits;
        if (str_starts_with($digits, '00')) return '+' . substr($digits, 2);
        $countryCode = preg_replace('/\D+/', '', (string) config('communication.sms.default_country_code', '966'));
        if ($countryCode !== '' && str_starts_with($digits, $countryCode)) return '+' . $digits;
        if (str_starts_with($digits, '0')) $digits = substr($digits, 1);
        if (strlen($digits) <= 10 && $countryCode !== '') $digits = $countryCode . $digits;
        return '+' . $digits;
    }
}
