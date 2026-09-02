<?php

namespace App\Models\Api;

use App\Contracts\Vercel\VercelDomainSourceOfTruth;
use App\Domain\Domain\Models\CustomDomain;
use App\Models\User;
use App\Support\DomainHealthMessages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiDomainSetting extends Model implements VercelDomainSourceOfTruth
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
        $autoAttach = (bool) ($lastCheck['auto_attach_custom_domain'] ?? true);
        $nsCheckEnabled = (bool) ($lastCheck['nameserver_check_enabled'] ?? true);

        if (! $autoAttach && ! $nsCheckEnabled) {
            return 'checks_disabled';
        }

        if (($lastCheck['reason'] ?? null) === 'expired') {
            return 'expired';
        }

        // Terminal, provider-confirmed rejection (e.g. Vercel says the name is not
        // a valid registrable domain — typically a subdomain). Checked before the
        // provider-error heuristic so it isn't mistaken for an unreachable provider.
        if (($lastCheck['reason'] ?? null) === 'invalid_domain') {
            return 'invalid_domain';
        }

        if (self::isProviderErrorState($lastCheck)) {
            return 'provider_error';
        }

        $attached = $apexAttached ?? (bool) ($lastCheck['apex_attached'] ?? $lastCheck['vercel_attached'] ?? false);
        $verified = (bool) ($lastCheck['apex_verified'] ?? $lastCheck['vercel_verified'] ?? false);
        $nameserversOk = (bool) ($lastCheck['nameservers_ok'] ?? false);
        $misconfigured = (bool) ($lastCheck['dns_misconfigured'] ?? false);
        $ownershipChallenge = $lastCheck['ownership_challenge'] ?? null;

        if (! $attached) {
            return 'not_on_vercel';
        }

        if (is_array($ownershipChallenge) && $ownershipChallenge !== [] && ! $verified) {
            return 'ownership_required';
        }

        $accountDomainPresent = (bool) ($lastCheck['account_domain_present'] ?? false);
        $zoneEnabled = (bool) ($lastCheck['zone_enabled'] ?? false);

        if ($accountDomainPresent && ! $zoneEnabled) {
            return 'zone_disabled';
        }

        if ($misconfigured) {
            return 'dns_misconfigured';
        }

        if ($nsCheckEnabled && ! $nameserversOk) {
            return 'ns_not_pointing';
        }

        if (! $verified) {
            return 'unverified';
        }

        $readiness = (string) ($lastCheck['certificate_readiness'] ?? '');

        if ($readiness === 'certificate_error') {
            return 'certificate_error';
        }

        if (($lastCheck['ssl_ready'] ?? false) !== true) {
            return 'certificate_pending';
        }

        $wwwPresent = (bool) ($lastCheck['www_present'] ?? false);
        $wwwRedirectCorrect = (bool) ($lastCheck['www_redirect_correct'] ?? false);

        if (! $wwwPresent || ! $wwwRedirectCorrect) {
            return 'apex_only';
        }

        return 'linked';
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
    private static function isProviderErrorState(array $lastCheck): bool
    {
        if (($lastCheck['reason'] ?? null) === 'provider_error') {
            return true;
        }

        if (array_key_exists('provider_reachable', $lastCheck) && $lastCheck['provider_reachable'] === false) {
            return true;
        }

        $message = (string) ($lastCheck['message'] ?? '');

        return str_contains($message, 'Could not reach the hosting provider')
            || str_contains($message, 'Unable to resolve domain nameservers');
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     * @return array{code: string, class: string, label: string, reason: string, checked_at: string|null}
     */
    private function healthState(string $code, array $lastCheck): array
    {
        $classes = [
            'linked' => 'success',
            'apex_only' => 'success',
            'ownership_required' => 'warning',
            'dns_misconfigured' => 'warning',
            'ns_mismatch' => 'warning',
            'ns_not_pointing' => 'warning',
            'not_on_vercel' => 'danger',
            'unverified' => 'warning',
            'zone_disabled' => 'warning',
            'certificate_pending' => 'warning',
            'certificate_error' => 'danger',
            'invalid_domain' => 'danger',
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
