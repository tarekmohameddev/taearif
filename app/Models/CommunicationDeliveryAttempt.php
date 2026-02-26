<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunicationDeliveryAttempt extends Model
{
    protected $table = 'communication_delivery_attempts';

    protected $fillable = [
        'user_id',
        'channel',
        'provider',
        'subject_type',
        'subject_id',
        'wa_number_id',
        'attempt_no',
        'attempt_status',
        'retry_eligible',
        'provider_message_id',
        'is_transient_failure',
        'error_code',
        'error_message',
        'next_retry_at',
        'dispatched_at',
        'completed_at',
        'request_payload',
        'provider_response',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'retry_eligible' => 'boolean',
        'is_transient_failure' => 'boolean',
        'request_payload' => 'array',
        'provider_response' => 'array',
        'next_retry_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRY_SCHEDULED = 'retry_scheduled';
    public const STATUS_RECONCILED = 'reconciled';

    public const SUBJECT_TYPE_MESSAGE = 'message';
    public const SUBJECT_TYPE_SMS_MESSAGE_LOG = 'sms_message_log';
    public const SUBJECT_TYPE_EMAIL_MESSAGE_LOG = 'email_message_log';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function waNumber(): BelongsTo
    {
        return $this->belongsTo(WaNumber::class, 'wa_number_id');
    }

    /**
     * Resolve subject to Message or SmsMessageLog or EmailMessageLog.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }
}
