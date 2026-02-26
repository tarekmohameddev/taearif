<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'name',
        'description',
        'subject',
        'body_html',
        'body_text',
        'template_id',
        'status',
        'scheduled_at',
        'sent_at',
        'dispatch_reference',
        'recipient_count',
        'sent_count',
        'delivered_count',
        'failed_count',
        'reserved_credits',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @var list<string> */
    protected $appends = ['creator_display'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailCampaignTemplate::class, 'template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmailMessageLog::class, 'campaign_id');
    }

    /**
     * Creator display for API: employee => id, first_name, last_name; user (tenant) => id, company_name from user_basic_settings.
     *
     * @return array{id: int, type: 'employee'|'user', first_name?: string, last_name?: string, company_name?: string|null}
     */
    public function getCreatorDisplayAttribute(): array
    {
        $creator = $this->creator;
        if (! $creator) {
            $user = $this->user;
            if (! $user) {
                return ['id' => (int) $this->user_id, 'type' => 'user', 'company_name' => null];
            }
            $companyName = $user->basic_setting?->company_name ?? null;
            return [
                'id' => (int) $user->id,
                'type' => 'user',
                'company_name' => $companyName,
            ];
        }

        if ($creator->isEmployee()) {
            return [
                'id' => (int) $creator->id,
                'type' => 'employee',
                'first_name' => $creator->first_name ?? '',
                'last_name' => $creator->last_name ?? '',
            ];
        }

        $companyName = $creator->basic_setting?->company_name ?? null;

        return [
            'id' => (int) $creator->id,
            'type' => 'user',
            'company_name' => $companyName,
        ];
    }
}

