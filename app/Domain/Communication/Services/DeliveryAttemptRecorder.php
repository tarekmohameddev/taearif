<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\DTOs\ProviderDispatchResult;
use App\Models\CommunicationDeliveryAttempt;
use App\Models\Message;
use App\Models\SmsMessageLog;
use Illuminate\Support\Facades\DB;

class DeliveryAttemptRecorder
{
    public function __construct(
        private readonly RetryPolicyHelper $retryPolicyHelper
    ) {}

    /**
     * Record one delivery attempt for a Message (WhatsApp).
     * Call only when config('communication.reliability.enabled') is true.
     * retry_eligible: false for sync API paths (paths 1-4).
     */
    public function recordMessageAttempt(
        Message $message,
        string $channel,
        string $provider,
        ProviderDispatchResult $result,
        bool $retryEligible,
        ?int $waNumberId = null,
        ?array $requestPayload = null,
        ?array $providerResponse = null
    ): CommunicationDeliveryAttempt {
        return DB::transaction(function () use ($message, $channel, $provider, $result, $retryEligible, $waNumberId, $requestPayload, $providerResponse) {
            $attemptNo = $this->nextAttemptNo(CommunicationDeliveryAttempt::SUBJECT_TYPE_MESSAGE, $message->id);
            $now = now();
            $attemptStatus = $result->success ? CommunicationDeliveryAttempt::STATUS_SENT : CommunicationDeliveryAttempt::STATUS_FAILED;
            $nextRetryAt = null;
            if (!$result->success && $result->is_transient_failure && $retryEligible) {
                $attemptStatus = CommunicationDeliveryAttempt::STATUS_RETRY_SCHEDULED;
                $nextRetryAt = $this->retryPolicyHelper->nextRetryAt($attemptNo - 1);
            }

            $attempt = CommunicationDeliveryAttempt::create([
                'user_id' => $message->user_id,
                'channel' => $channel,
                'provider' => $provider,
                'subject_type' => CommunicationDeliveryAttempt::SUBJECT_TYPE_MESSAGE,
                'subject_id' => $message->id,
                'wa_number_id' => $waNumberId,
                'attempt_no' => $attemptNo,
                'attempt_status' => $attemptStatus,
                'retry_eligible' => $retryEligible,
                'provider_message_id' => $result->success ? $result->provider_message_id : null,
                'is_transient_failure' => $result->is_transient_failure,
                'error_code' => $result->error_code,
                'error_message' => $result->error_message,
                'next_retry_at' => $nextRetryAt,
                'dispatched_at' => $now,
                'completed_at' => $now,
                'request_payload' => $requestPayload,
                'provider_response' => $result->raw_response ?: $providerResponse,
            ]);

            return $attempt;
        });
    }

    /**
     * Record one delivery attempt for SmsMessageLog.
     * retry_eligible: true only for campaign dispatch path (path 5).
     */
    public function recordSmsLogAttempt(
        SmsMessageLog $log,
        string $provider,
        bool $success,
        ?string $gatewayMessageId,
        bool $isTransientFailure,
        bool $retryEligible,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?array $requestPayload = null,
        ?array $providerResponse = null
    ): CommunicationDeliveryAttempt {
        return DB::transaction(function () use ($log, $provider, $success, $gatewayMessageId, $isTransientFailure, $retryEligible, $errorCode, $errorMessage, $requestPayload, $providerResponse) {
            $attemptNo = $this->nextAttemptNo(CommunicationDeliveryAttempt::SUBJECT_TYPE_SMS_MESSAGE_LOG, $log->id);
            $now = now();
            $attemptStatus = $success ? CommunicationDeliveryAttempt::STATUS_SENT : CommunicationDeliveryAttempt::STATUS_FAILED;
            $nextRetryAt = null;
            if (!$success && $isTransientFailure && $retryEligible) {
                $attemptStatus = CommunicationDeliveryAttempt::STATUS_RETRY_SCHEDULED;
                $nextRetryAt = $this->retryPolicyHelper->nextRetryAt($attemptNo - 1);
            }

            return CommunicationDeliveryAttempt::create([
                'user_id' => $log->user_id,
                'channel' => 'sms',
                'provider' => $provider,
                'subject_type' => CommunicationDeliveryAttempt::SUBJECT_TYPE_SMS_MESSAGE_LOG,
                'subject_id' => $log->id,
                'wa_number_id' => null,
                'attempt_no' => $attemptNo,
                'attempt_status' => $attemptStatus,
                'retry_eligible' => $retryEligible,
                'provider_message_id' => $gatewayMessageId,
                'is_transient_failure' => $isTransientFailure,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'next_retry_at' => $nextRetryAt,
                'dispatched_at' => $now,
                'completed_at' => $now,
                'request_payload' => $requestPayload,
                'provider_response' => $providerResponse,
            ]);
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
