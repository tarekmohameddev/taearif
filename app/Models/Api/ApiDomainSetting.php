<?php

namespace App\Models\Api;

use App\Contracts\Vercel\VercelDomainSourceOfTruth;
use App\Domain\Domain\Models\CustomDomain;
use App\Models\User;
use App\Services\Vercel\DomainHealthPolicy;
use App\Support\DomainHealthMessages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiDomainSetting extends Model implements VercelDomainSourceOfTruth
{
    use HasFactory;

    public const DNS_MODE_VERCEL_NS = 'vercel_ns';

    public const DNS_MODE_EXTERNAL_DNS = 'external_dns';

    protected $table = 'api_domains_settings';

    protected $fillable = [
        'user_id',
        'custom_domain_id',
        'name',
        'custom_name',
        'dns_mode',
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
        'dns_mode' => 'string',
    ];

    protected ?bool $vercelAttachedHint = null;

    protected bool $hasWwwStateHint = false;

    protected ?bool $wwwPresentHint = null;

    protected ?bool $wwwRedirectCorrectHint = null;

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
            ->servable()
            ->orderByDesc('primary')
            ->orderByDesc('id');
    }

    public function scopeServable($query)
    {
        return $query->where('status', 'active');
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
                __('domain_dns.nameserver_step_registrar'),
                __('domain_dns.nameserver_step_verify'),
            ],
            'ownership_txt_instruction' => __('domain_dns.ownership_txt_instruction'),
            'recommended_a_label' => __('domain_dns.recommended_a_label'),
            'recommended_cname_label' => __('domain_dns.recommended_cname_label'),
            'ownership_txt_label' => __('domain_dns.ownership_txt_label'),
            'record_type_label' => __('domain_dns.record_type'),
            'record_name_label' => __('domain_dns.record_name'),
            'record_value_label' => __('domain_dns.record_value'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function dnsModeOptions(): array
    {
        return [
            self::DNS_MODE_VERCEL_NS => __('domain_dns.mode_vercel_ns'),
            self::DNS_MODE_EXTERNAL_DNS => __('domain_dns.mode_external_dns'),
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
     * Live Vercel inventory www state used to classify linked vs apex_only
     * without waiting for a full status sync to rewrite last_check.
     */
    public function setWwwStateHint(bool $present, bool $redirectCorrect): self
    {
        $this->hasWwwStateHint = true;
        $this->wwwPresentHint = $present;
        $this->wwwRedirectCorrectHint = $redirectCorrect;

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

        if ($this->hasWwwStateHint) {
            $lastCheck['www_present'] = (bool) $this->wwwPresentHint;
            $lastCheck['www_redirect_correct'] = (bool) $this->wwwRedirectCorrectHint;
        }

        $lastCheck['dns_mode'] = $lastCheck['dns_mode'] ?? $this->dns_mode ?? self::DNS_MODE_VERCEL_NS;

        if (isset($lastCheck['health_code']) && is_string($lastCheck['health_code']) && $lastCheck['health_code'] !== '') {
            $code = $lastCheck['health_code'];

            if ($code === 'expired' && (! $this->expires_at || ! $this->expires_at->isPast())) {
                $code = self::resolveHealthCode($lastCheck, $this->resolveAttachment($lastCheck, $vercelAttached));
            } elseif ($vercelAttached !== null && in_array($code, [
                'not_on_vercel',
                'unverified',
                'linked',
                'apex_only',
                'zone_disabled',
                'certificate_pending',
                'certificate_error',
            ], true)) {
                $code = self::resolveHealthCode($lastCheck, $vercelAttached);
            } elseif ($this->hasWwwStateHint && in_array($code, ['linked', 'apex_only'], true)) {
                $code = self::resolveHealthCode(
                    $lastCheck,
                    $this->resolveAttachment($lastCheck, $vercelAttached)
                );
            }

            return $this->healthState($code, $lastCheck);
        }

        $attached = $this->resolveAttachment($lastCheck, $vercelAttached);
        $code = self::resolveHealthCode($lastCheck, $attached);

        return $this->healthState($code, $lastCheck);
    }

    /**
     * Health re-derived strictly from the persisted diagnostic fields, ignoring any
     * stored `health_code`. Use this where the fields themselves are displayed (e.g.
     * the diagnostics drawer) so the badge can never contradict the rows — a stale
     * record whose stored code predates the zone/SSL fields resolves honestly here.
     *
     * @return array{code: string, class: string, label: string, reason: string, checked_at: string|null}
     */
    public function resolvedHealth(): array
    {
        $dnsRecords = is_array($this->dns_records) ? $this->dns_records : [];
        $lastCheck = $dnsRecords['last_check'] ?? null;

        if (! is_array($lastCheck) || $lastCheck === []) {
            return $this->healthState('unchecked', []);
        }

        $attached = $this->resolveAttachment($lastCheck, null);
        $code = self::resolveHealthCode($lastCheck, $attached);

        return $this->healthState($code, $lastCheck);
    }

    /**
     * Deterministic, mutually exclusive health code from diagnostic fields.
     *
     * @param  array<string, mixed>  $lastCheck
     */
    public static function resolveHealthCode(array $lastCheck, ?bool $apexAttached = null): string
    {
        $lastCheck['dns_mode'] = $lastCheck['dns_mode'] ?? self::DNS_MODE_VERCEL_NS;

        if ($apexAttached !== null) {
            $lastCheck['apex_attached'] = $apexAttached;
            $lastCheck['vercel_attached'] = $apexAttached;
        }

        return app(DomainHealthPolicy::class)->resolveHealthCode($lastCheck);
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    private function resolveAttachment(array $lastCheck, ?bool $freshHint): ?bool
    {
        if ($freshHint !== null) {
            return $freshHint;
        }

        if (array_key_exists('apex_attached', $lastCheck)) {
            return (bool) $lastCheck['apex_attached'];
        }

        if (array_key_exists('vercel_attached', $lastCheck)) {
            return (bool) $lastCheck['vercel_attached'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    /**
     * @param  array<string, mixed>  $lastCheck
     * @return array{code: string, class: string, label: string, reason: string, checked_at: string|null}
     */
    private function healthState(string $code, array $lastCheck): array
    {
        $policy = app(DomainHealthPolicy::class);
        $severity = method_exists($policy, 'severityForCode')
            ? $policy->severityForCode($code)
            : 'secondary';

        return [
            'code' => $code,
            'class' => $severity,
            'label' => __("domain_health.{$code}"),
            'reason' => DomainHealthMessages::translate((string) ($lastCheck['message'] ?? '')),
            'checked_at' => isset($lastCheck['last_check_at']) ? (string) $lastCheck['last_check_at'] : null,
        ];
    }
}
