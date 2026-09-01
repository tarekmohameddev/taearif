<?php

namespace App\Models\Api;

use App\Domain\Domain\Models\CustomDomain;
use App\Models\User;
use App\Support\DomainHealthMessages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiDomainSetting extends Model
{
    use HasFactory;

    protected $table = 'api_domains_settings';

    protected $fillable = [
        'user_id',
        'custom_domain_id',
        'name',
        'custom_name',
        'status',
        'primary',
        'ssl',
        'added_date',
        'registrar',
        'expires_at',
        'auto_renewal',
        'dns_records',
    ];

    protected $casts = [
        'primary' => 'boolean',
        'ssl' => 'boolean',
        'added_date' => 'date',
        'expires_at' => 'date',
        'auto_renewal' => 'boolean',
        'dns_records' => 'array',
    ];

    protected ?bool $vercelAttachedHint = null;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customDomain()
    {
        return $this->belongsTo(CustomDomain::class, 'custom_domain_id');
    }

    public function scopePreferredActive($query)
    {
        return $query
            ->where('status', 'active')
            ->orderByDesc('primary')
            ->orderByDesc('id');
    }

    /**
     * DNS / nameserver setup instructions for tenants using Vercel NS.
     *
     * @return array{mode: string, nameservers: list<string>, steps: list<string>}
     */
    public static function nameserverInstructions(): array
    {
        $nameservers = array_values(config('services.vercel.nameservers', [
            'ns1.vercel-dns.com',
            'ns2.vercel-dns.com',
        ]));

        return [
            'mode' => 'nameservers',
            'nameservers' => $nameservers,
            'steps' => [
                'At your registrar (e.g. GoDaddy), set custom nameservers to the values above.',
                'Wait for propagation, then click Verify.',
            ],
        ];
    }

    /**
     * @deprecated Prefer nameserverInstructions(); kept for callers expecting a list shape.
     * @return array{mode: string, nameservers: list<string>, steps: list<string>}
     */
    public function getDnsRecords()
    {
        return self::nameserverInstructions();
    }

    public function setVercelAttachedHint(?bool $hint): self
    {
        $this->vercelAttachedHint = $hint;

        return $this;
    }

    /**
     * @return array{code: string, class: string, label: string, reason: string, checked_at: string|null}
     */
    public function getHealthAttribute(): array
    {
        return $this->health($this->vercelAttachedHint);
    }

    /**
     * @return array{code: string, class: string, label: string, reason: string, checked_at: string|null}
     */
    public function health(?bool $vercelAttached = null): array
    {
        $dnsRecords = is_array($this->dns_records) ? $this->dns_records : [];
        $lastCheck = $dnsRecords['last_check'] ?? null;

        if ($lastCheck === null || ! is_array($lastCheck)) {
            return $this->healthState('unchecked', []);
        }

        $autoAttach = (bool) ($lastCheck['auto_attach_custom_domain'] ?? true);
        $nsCheckEnabled = (bool) ($lastCheck['nameserver_check_enabled'] ?? true);

        if (! $autoAttach && ! $nsCheckEnabled) {
            return $this->healthState('checks_disabled', $lastCheck);
        }

        if (($lastCheck['reason'] ?? null) === 'expired' && $this->expires_at && $this->expires_at->isPast()) {
            return $this->healthState('expired', $lastCheck);
        }

        if ($this->isProviderError($lastCheck)) {
            return $this->healthState('provider_error', $lastCheck);
        }

        $vercelVerified = (bool) ($lastCheck['vercel_verified'] ?? false);
        $nameserversOk = (bool) ($lastCheck['nameservers_ok'] ?? false);

        if ($vercelVerified && $nameserversOk) {
            return $this->healthState('linked', $lastCheck);
        }

        $storedAttached = $lastCheck['vercel_attached'] ?? null;
        $attached = $storedAttached ?? $vercelAttached;
        if ($attached === false) {
            return $this->healthState('not_on_vercel', $lastCheck);
        }

        if ($nsCheckEnabled && ! $nameserversOk) {
            return $this->healthState('ns_not_pointing', $lastCheck);
        }

        return $this->healthState('unverified', $lastCheck);
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    private function isProviderError(array $lastCheck): bool
    {
        if (($lastCheck['reason'] ?? null) === 'provider_error') {
            return true;
        }

        $message = (string) ($lastCheck['message'] ?? '');

        return str_contains($message, 'Could not reach the hosting provider');
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     * @return array{code: string, class: string, label: string, reason: string, checked_at: string|null}
     */
    private function healthState(string $code, array $lastCheck): array
    {
        $classes = [
            'linked' => 'success',
            'ns_mismatch' => 'warning',
            'ns_not_pointing' => 'warning',
            'not_on_vercel' => 'danger',
            'unverified' => 'warning',
            'expired' => 'danger',
            'provider_error' => 'secondary',
            'checks_disabled' => 'secondary',
            'unchecked' => 'secondary',
        ];

        return [
            'code' => $code,
            'class' => $classes[$code] ?? 'secondary',
            'label' => __("domain_health.{$code}"),
            'reason' => DomainHealthMessages::translate((string) ($lastCheck['message'] ?? '')),
            'checked_at' => isset($lastCheck['last_check_at']) ? (string) $lastCheck['last_check_at'] : null,
        ];
    }
}
