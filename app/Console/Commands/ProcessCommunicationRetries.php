<?php

namespace App\Console\Commands;

use App\Models\CommunicationDeliveryAttempt;
use App\Models\Message;
use App\Models\SmsMessageLog;
use App\Domain\Communication\Services\CommunicationRetrySender;
use App\Domain\Communication\Services\DeliveryAttemptRecorder;
use App\Domain\Communication\Services\RetryPolicyHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCommunicationRetries extends Command
{
    protected $signature = 'communication:process-retries';

    protected $description = 'Process communication delivery retries (transient failures, retry_eligible only).';

    public function __construct(
        private readonly CommunicationRetrySender $retrySender,
        private readonly DeliveryAttemptRecorder $attemptRecorder,
        private readonly RetryPolicyHelper $retryPolicy
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('communication.reliability.enabled', false)) {
            return self::SUCCESS;
        }

        $lookbackDays = (int) config('communication.reliability.reconcile.lookback_days', 30);
        $cutoff = now()->subDays($lookbackDays);
        $batchSize = 100;

        $attempts = CommunicationDeliveryAttempt::query()
            ->where('attempt_status', CommunicationDeliveryAttempt::STATUS_RETRY_SCHEDULED)
            ->where('retry_eligible', true)
            ->where('next_retry_at', '<=', now())
            ->where('created_at', '>=', $cutoff)
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        $processed = 0;
        foreach ($attempts as $attempt) {
            try {
                if ($this->processOne($attempt)) {
                    $processed++;
                }
            } catch (\Throwable $e) {
                Log::channel('stack')->warning('communication.reliability.retry.exception', [
                    'attempt_id' => $attempt->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($processed > 0) {
            $this->info("Processed {$processed} retries.");
        }

        return self::SUCCESS;
    }

    private function processOne(CommunicationDeliveryAttempt $attempt): bool
    {
        $subject = $attempt->subject;
        if (! $subject) {
            $attempt->update(['attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED]);
            return false;
        }

        if ($attempt->subject_type === CommunicationDeliveryAttempt::SUBJECT_TYPE_MESSAGE) {
            /** @var Message $message */
            $message = $subject;
            $current = $message->status ?? 'queued';
            if (in_array($current, ['sent', 'delivered', 'read'], true)) {
                $attempt->update(['attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED]);
                return false;
            }
            return $this->retryMessageAttempt($attempt, $message);
        }

        if ($attempt->subject_type === CommunicationDeliveryAttempt::SUBJECT_TYPE_SMS_MESSAGE_LOG) {
            /** @var SmsMessageLog $log */
            $log = $subject;
            $current = $log->status ?? 'pending';
            if (in_array($current, ['sent', 'delivered'], true)) {
                $attempt->update(['attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED]);
                return false;
            }
            return $this->retrySmsLogAttempt($attempt, $log);
        }

        return false;
    }

    private function retryMessageAttempt(CommunicationDeliveryAttempt $attempt, Message $message): bool
    {
        $result = $this->retrySender->retryMessage($message);
        $maxAttempts = $this->retryPolicy->maxAttempts();

        return DB::transaction(function () use ($attempt, $message, $result, $maxAttempts) {
            $nextAttemptNo = $this->nextAttemptNo(CommunicationDeliveryAttempt::SUBJECT_TYPE_MESSAGE, $message->id);
            $attempt->update(['attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED]);

            if ($result->success) {
                Message::where('id', $message->id)->update([
                    'status' => 'sent',
                    'provider_message_id' => $result->provider_message_id,
                ]);
                CommunicationDeliveryAttempt::create([
                    'user_id' => $attempt->user_id,
                    'channel' => $attempt->channel,
                    'provider' => $attempt->provider,
                    'subject_type' => $attempt->subject_type,
                    'subject_id' => $attempt->subject_id,
                    'wa_number_id' => $attempt->wa_number_id,
                    'attempt_no' => $nextAttemptNo,
                    'attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED,
                    'retry_eligible' => true,
                    'provider_message_id' => $result->provider_message_id,
                    'is_transient_failure' => false,
                    'dispatched_at' => now(),
                    'completed_at' => now(),
                    'provider_response' => $result->raw_response,
                ]);
                return true;
            }

            $isTransient = $result->is_transient_failure;
            $nextRetryAt = null;
            $newStatus = CommunicationDeliveryAttempt::STATUS_FAILED;
            if ($isTransient && $nextAttemptNo <= $maxAttempts) {
                $newStatus = CommunicationDeliveryAttempt::STATUS_RETRY_SCHEDULED;
                $nextRetryAt = $this->retryPolicy->nextRetryAt($nextAttemptNo - 1);
            }

            CommunicationDeliveryAttempt::create([
                'user_id' => $attempt->user_id,
                'channel' => $attempt->channel,
                'provider' => $attempt->provider,
                'subject_type' => $attempt->subject_type,
                'subject_id' => $attempt->subject_id,
                'wa_number_id' => $attempt->wa_number_id,
                'attempt_no' => $nextAttemptNo,
                'attempt_status' => $newStatus,
                'retry_eligible' => true,
                'provider_message_id' => null,
                'is_transient_failure' => $isTransient,
                'error_code' => $result->error_code,
                'error_message' => $result->error_message,
                'next_retry_at' => $nextRetryAt,
                'dispatched_at' => now(),
                'completed_at' => now(),
                'provider_response' => $result->raw_response,
            ]);

            if (! $isTransient || $nextAttemptNo > $maxAttempts) {
                Message::where('id', $message->id)->update(['status' => 'failed']);
            }
            return true;
        });
    }

    private function retrySmsLogAttempt(CommunicationDeliveryAttempt $attempt, SmsMessageLog $log): bool
    {
        $result = $this->retrySender->retrySmsLog($log);
        $maxAttempts = $this->retryPolicy->maxAttempts();

        return DB::transaction(function () use ($attempt, $log, $result, $maxAttempts) {
            $nextAttemptNo = $this->nextAttemptNo(CommunicationDeliveryAttempt::SUBJECT_TYPE_SMS_MESSAGE_LOG, $log->id);
            $attempt->update(['attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED]);

            if ($result->success) {
                SmsMessageLog::where('id', $log->id)->update([
                    'status' => 'sent',
                    'gateway_message_id' => $result->provider_message_id,
                    'provider' => $attempt->provider,
                    'error_message' => null,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
                CommunicationDeliveryAttempt::create([
                    'user_id' => $attempt->user_id,
                    'channel' => 'sms',
                    'provider' => $attempt->provider,
                    'subject_type' => CommunicationDeliveryAttempt::SUBJECT_TYPE_SMS_MESSAGE_LOG,
                    'subject_id' => $attempt->subject_id,
                    'wa_number_id' => null,
                    'attempt_no' => $nextAttemptNo,
                    'attempt_status' => CommunicationDeliveryAttempt::STATUS_RECONCILED,
                    'retry_eligible' => true,
                    'provider_message_id' => $result->provider_message_id,
                    'is_transient_failure' => false,
                    'dispatched_at' => now(),
                    'completed_at' => now(),
                    'provider_response' => $result->raw_response,
                ]);
                return true;
            }

            $isTransient = $result->is_transient_failure;
            $newStatus = CommunicationDeliveryAttempt::STATUS_FAILED;
            $nextRetryAt = null;
            if ($isTransient && $nextAttemptNo <= $maxAttempts) {
                $newStatus = CommunicationDeliveryAttempt::STATUS_RETRY_SCHEDULED;
                $nextRetryAt = $this->retryPolicy->nextRetryAt($nextAttemptNo - 1);
            }

            CommunicationDeliveryAttempt::create([
                'user_id' => $attempt->user_id,
                'channel' => 'sms',
                'provider' => $attempt->provider,
                'subject_type' => $attempt->subject_type,
                'subject_id' => $attempt->subject_id,
                'wa_number_id' => null,
                'attempt_no' => $nextAttemptNo,
                'attempt_status' => $newStatus,
                'retry_eligible' => true,
                'provider_message_id' => null,
                'is_transient_failure' => $isTransient,
                'error_message' => $result->error_message,
                'next_retry_at' => $nextRetryAt,
                'dispatched_at' => now(),
                'completed_at' => now(),
                'provider_response' => $result->raw_response,
            ]);

            if (! $isTransient || $nextAttemptNo > $maxAttempts) {
                SmsMessageLog::where('id', $log->id)->update([
                    'status' => 'failed',
                    'error_message' => $result->error_message,
                    'updated_at' => now(),
                ]);
            }
            return true;
        });
    }

    private function nextAttemptNo(string $subjectType, int $subjectId): int
    {
        $max = CommunicationDeliveryAttempt::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->lockForUpdate()
            ->max('attempt_no');
        return ($max ?? 0) + 1;
    }
}
