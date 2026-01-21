<?php

namespace App\Models\Api;

use App\Models\User;
use App\Models\Api\ApiApp;
use App\Enums\InstallStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\Api\ApiInstallationSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ApiInstallation extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'api_installations';
    protected $fillable = [
        'user_id',
        'app_id',
        'status',
        'installed', // This field is used to indicate if the app is installed
        'installed_at',
        'uninstalled_at',
        'activated_at',
        'trial_ends_at',
        'current_period_end',
        'payment_subscription_id', // Subscription ID for billing
        'invoice_id',
        'recurring_id',
    ];
    protected $casts = [
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
        'status' => InstallStatus::class, // Assuming InstallStatus is an enum for installation status
        'installed' => 'boolean', // Cast installed to boolean
        'activated_at'        => 'datetime',
        'trial_ends_at'       => 'datetime',
        'current_period_end'  => 'datetime',
        'trial_used_at' => 'immutable_datetime',

    ];

    /*──────── helper scopes ────────*/
    public function scopeForUser($q, int $userId)   { return $q->where('user_id', $userId); }
    public function scopeForApp ($q, int $appId)    { return $q->where('app_id',  $appId);  }


        /*──────── state helpers ────────*/
    /**
     * @deprecated This method is deprecated. Installations are now installed immediately.
     * Payment is tracked separately via AppPaymentTransaction.
     * Kept for backward compatibility only.
     */
    public function markPending(string $invoiceId): void
    {
        // Deprecated: No longer used in new flow
        // Just store invoice_id, installation should already be installed
        $this->update(['invoice_id' => $invoiceId]);
    }

    public function markInstalled(?string $recurringId = null): void
    {
        $this->update([
            'status' => InstallStatus::Installed,
            'installed' => true,
            'installed_at' => $this->installed_at ?? now(),
            'recurring_id' => $recurringId,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === InstallStatus::Installed
            || ($this->status === InstallStatus::Trialing && $this->trial_ends_at?->isFuture());
    }

    /**
     * Check if installation has a completed payment transaction
     *
     * @return bool
     */
    public function hasCompletedPayment(): bool
    {
        return $this->paymentTransactions()
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Check if installation has a valid subscription period
     *
     * @return bool
     */
    public function hasValidSubscription(): bool
    {
        if (!$this->current_period_end) {
            return false;
        }

        return $this->current_period_end->isFuture();
    }

    /*──────── relations ────────*/


    protected $attributes = [
        'status' => 'installed',
        'installed' => false, // Default to false, indicating the app is not installed
    ];
    public function app()
    {
        return $this->belongsTo(ApiApp::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function settings()
    {
        return $this->hasOne(ApiInstallationSetting::class, 'installation_id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(AppPaymentTransaction::class, 'installation_id');
    }
    public function scopeInstalled($query)
    {
        return $query->where('status', 'installed');
    }
    public function scopeUninstalled($query)
    {
        return $query->where('status', 'uninstalled');
    }
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    public function scopeByApp($query, $appId)
    {
        return $query->where('app_id', $appId);
    }
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
