<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppPaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'app_payment_transactions';

    protected $fillable = [
        'user_id',
        'installation_id',
        'app_id',
        'payment_transaction_id',
        'gateway',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'verified_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function installation()
    {
        return $this->belongsTo(ApiInstallation::class, 'installation_id');
    }

    public function app()
    {
        return $this->belongsTo(ApiApp::class, 'app_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForInstallation($query, int $installationId)
    {
        return $query->where('installation_id', $installationId);
    }

    public function scopeForApp($query, int $appId)
    {
        return $query->where('app_id', $appId);
    }

    public function scopeOlderThan($query, int $hours)
    {
        return $query->where('created_at', '<', now()->subHours($hours));
    }

    /**
     * Helper Methods
     */
    public function markCompleted(array $gatewayResponse = [], array $metadata = []): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_response' => $gatewayResponse ?: $this->gateway_response,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
            'verified_at' => now(),
        ]);
    }

    public function markFailed(array $gatewayResponse = [], array $metadata = []): void
    {
        $this->update([
            'status' => 'failed',
            'gateway_response' => $gatewayResponse ?: $this->gateway_response,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    public function isProcessed(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'refunded']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
