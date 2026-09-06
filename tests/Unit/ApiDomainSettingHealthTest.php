<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DomainStatusSyncService;
use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\VercelDomainClient;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiDomainSettingHealthTest extends TestCase
{
    use DatabaseTransactions;
    /** @test */
    public function health_is_unchecked_when_last_check_is_missing(): void
    {
        $domain = new ApiDomainSetting([
            'custom_name' => 'unchecked.example.com',
            'dns_records' => [],
        ]);

        $this->assertSame('unchecked', $domain->health()['code']);
        $this->assertSame('secondary', $domain->health()['class']);
    }

    /** @test */
    public function health_is_checks_disabled_when_both_checks_are_off(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => false,
            'nameserver_check_enabled' => false,
            'vercel_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('checks_disabled', $domain->health()['code']);
    }

    /** @test */
    public function health_is_expired_when_reason_is_expired_and_expires_at_is_past(): void
    {
        $domain = $this->domainWithLastCheck(
            [
                'health_code' => 'expired',
                'reason' => 'expired',
                'auto_attach_custom_domain' => true,
                'nameserver_check_enabled' => true,
                'vercel_verified' => false,
                'nameservers_ok' => false,
            ],
            Carbon::yesterday()
        );

        $this->assertSame('expired', $domain->health()['code']);
    }

    /** @test */
    public function health_is_provider_error_when_reason_is_provider_error(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'provider_error',
            'reason' => 'provider_error',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('provider_error', $domain->health()['code']);
    }

    /** @test */
    public function health_is_provider_error_when_message_indicates_unreachable_provider(): void
    {
        $domain = $this->domainWithLastCheck([
            'message' => 'Could not reach the hosting provider to check this domain.',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('provider_error', $domain->health()['code']);
    }

    /** @test */
    public function health_is_linked_when_apex_www_and_dns_are_ok(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'linked',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => true,
            'www_redirect_correct' => true,
        ]);

        $this->assertSame('linked', $domain->health()['code']);
        $this->assertSame('success', $domain->health()['class']);
    }

    /** @test */
    public function health_is_apex_only_when_www_redirect_is_missing(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'apex_only',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => false,
            'www_redirect_correct' => false,
        ]);

        $this->assertSame('apex_only', $domain->health()['code']);
        $this->assertSame('success', $domain->health()['class']);
    }

    /** @test */
    public function health_is_dns_misconfigured_when_vercel_reports_misconfiguration(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'dns_misconfigured',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => true,
        ]);

        $this->assertSame('dns_misconfigured', $domain->health()['code']);
    }

    /** @test */
    public function health_is_ownership_required_when_txt_challenge_is_present(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'ownership_required',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
            'ownership_challenge' => [
                'type' => 'txt',
                'domain' => '_vercel.example.com',
                'value' => 'vc-domain-verify=abc',
            ],
        ]);

        $this->assertSame('ownership_required', $domain->health()['code']);
    }

    /** @test */
    public function health_is_ns_not_pointing_when_nameserver_check_fails(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'nameservers_ok' => false,
            'message' => 'Nameservers are not pointing to Vercel yet.',
        ]);

        $health = $domain->health();

        $this->assertSame('ns_not_pointing', $health['code']);
        $this->assertSame('warning', $health['class']);
        $this->assertStringContainsString('Nameservers are not pointing', $health['reason']);
    }

    /** @test */
    public function health_is_ns_not_pointing_when_on_vercel_but_nameservers_fail(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('ns_not_pointing', $domain->health(true)['code']);
    }

    /** @test */
    public function health_is_unverified_when_on_vercel_with_correct_nameservers_but_not_verified(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
        ]);

        $this->assertSame('unverified', $domain->health(true)['code']);
    }

    /** @test */
    public function health_is_not_on_vercel_when_fresh_hint_is_false(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('not_on_vercel', $domain->health(false)['code']);
    }

    /** @test */
    public function health_fresh_hint_overrides_stored_attachment_when_hint_is_false(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'unverified',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
        ]);

        $this->assertSame('not_on_vercel', $domain->health(false)['code']);
    }

    /** @test */
    public function health_fresh_hint_overrides_stored_detachment_when_hint_is_true(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'not_on_vercel',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => false,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('ns_not_pointing', $domain->health(true)['code']);
    }

    /** @test */
    public function health_prefers_not_on_vercel_over_ns_not_pointing_when_not_attached(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => false,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('not_on_vercel', $domain->health(false)['code']);
    }

    /** @test */
    public function resolve_health_code_prefers_dns_misconfigured_over_verified(): void
    {
        $code = ApiDomainSetting::resolveHealthCode([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => true,
            'www_present' => true,
            'www_redirect_correct' => true,
        ], true);

        $this->assertSame('dns_misconfigured', $code);
    }

    /** @test */
    public function health_is_not_on_vercel_when_apex_not_attached(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => false,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('not_on_vercel', $domain->health()['code']);
    }

    /** @test */
    public function health_is_unverified_when_attached_but_not_verified(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
        ]);

        $this->assertSame('unverified', $domain->health()['code']);
    }

    /** @test */
    public function health_provider_unreachable_maps_to_provider_error(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'provider_reachable' => false,
            'reason' => 'provider_error',
        ]);

        $this->assertSame('provider_error', $domain->health()['code']);
    }

    /** @test */
    public function health_expired_code_is_ignored_when_expires_at_is_still_future(): void
    {
        $domain = $this->domainWithLastCheck(
            [
                'health_code' => 'expired',
                'auto_attach_custom_domain' => true,
                'nameserver_check_enabled' => true,
                'apex_attached' => true,
                'apex_verified' => false,
                'nameservers_ok' => true,
            ],
            Carbon::tomorrow()
        );

        $this->assertSame('unverified', $domain->health()['code']);
    }

    /** @test */
    public function sync_preserves_active_status_when_provider_is_unknown(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
            $this->fail('Required domain tables are missing.');
        }

        $domain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create()->id,
            'custom_name' => 'sync-provider.example.com',
            'status' => 'active',
            'ssl' => true,
            'primary' => true,
            'added_date' => now(),
        ]);

        $this->mock(VercelDomainClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturnUsing(fn ($d) => strtolower(trim($d)));
            $mock->shouldReceive('verifyDomain')->andThrow(new \App\Services\Vercel\VercelDomainException(
                'rate limited',
                429,
                internalCode: \App\Services\Vercel\VercelDomainException::CODE_RATE_LIMITED
            ));
        });

        $this->mock(DnsNameserverChecker::class, function ($mock) {
            $mock->shouldReceive('hasExpectedNameservers')->andReturn(true);
            $mock->shouldReceive('getObservedNameservers')->andReturn(['ns1.vercel-dns.com']);
        });

        config([
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
            'services.vercel.health_failure_threshold' => 3,
        ]);

        $inventory = [
            'domains' => [
                ['name' => 'sync-provider.example.com', 'verified' => true],
            ],
        ];

        $result = app(DomainStatusSyncService::class)->sync(
            $domain,
            attemptVerify: true,
            applyFailureThreshold: true,
            projectInventory: $inventory
        );

        $this->assertSame('active', $result['new_status']);
        $this->assertSame('provider_error', $result['health_code']);
    }

    /** @test */
    public function sync_resets_failure_counters_after_successful_linked_check(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
            $this->fail('Required domain tables are missing.');
        }

        $domain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create()->id,
            'custom_name' => 'sync-reset.example.com',
            'status' => 'active',
            'ssl' => true,
            'primary' => true,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'consecutive_failures' => 2,
                    'first_failure_at' => now()->subHours(6)->toIso8601String(),
                ],
            ],
        ]);

        $this->mock(VercelDomainClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturn('sync-reset.example.com');
            $mock->shouldReceive('getDomainVerification')->andReturn([]);
            $mock->shouldReceive('getDomainConfig')->andReturn(['misconfigured' => false]);
            $mock->shouldReceive('getAccountDomain')->andReturn([
                'name' => 'sync-reset.example.com',
                'zone' => true,
                'verified' => true,
                'nameservers' => [],
                'intendedNameservers' => [],
            ]);
            $issuedCert = [
                'id' => 'cert_sync_reset',
                'cns' => ['sync-reset.example.com'],
                'expiresAt' => time() + 86400 * 90,
                'readiness' => 'issued',
            ];
            $mock->shouldReceive('listCertificates')->andReturn([
                'certificates' => [$issuedCert],
                'is_lower_bound' => false,
            ]);
            $mock->shouldReceive('findCoveringCertificate')->andReturn($issuedCert);
            $mock->shouldReceive('isCertificateReady')->andReturn(true);
        });

        $this->mock(DnsNameserverChecker::class, function ($mock) {
            $mock->shouldReceive('hasExpectedNameservers')->andReturn(true);
            $mock->shouldReceive('getObservedNameservers')->andReturn(['ns1.vercel-dns.com', 'ns2.vercel-dns.com']);
        });

        config([
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
            'services.vercel.health_failure_threshold' => 3,
        ]);

        $inventory = [
            'domains' => [
                ['name' => 'sync-reset.example.com', 'verified' => true],
                ['name' => 'www.sync-reset.example.com', 'verified' => true, 'redirect' => 'sync-reset.example.com', 'redirectStatusCode' => 301],
            ],
        ];

        app(DomainStatusSyncService::class)->sync(
            $domain,
            attemptVerify: false,
            applyFailureThreshold: true,
            projectInventory: $inventory
        );

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? [];
        $this->assertSame(0, $lastCheck['consecutive_failures'] ?? -1);
        $this->assertSame('linked', $lastCheck['health_code'] ?? null);
    }

    /** @test */
    public function health_is_zone_disabled_when_account_domain_zone_is_off(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => false,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
        ]);

        $this->assertSame('zone_disabled', $domain->health()['code']);
        $this->assertSame('warning', $domain->health()['class']);
    }

    /** @test */
    public function health_is_certificate_pending_when_ssl_is_not_ready(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => false,
            'certificate_readiness' => 'pending',
        ]);

        $this->assertSame('certificate_pending', $domain->health()['code']);
        $this->assertSame('warning', $domain->health()['class']);
    }

    /** @test */
    public function health_is_certificate_error_when_certificate_readiness_is_terminal(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => false,
            'certificate_readiness' => 'certificate_error',
        ]);

        $this->assertSame('certificate_error', $domain->health()['code']);
        $this->assertSame('danger', $domain->health()['class']);
    }

    /** @test */
    public function sync_keeps_domain_inactive_when_verified_but_zone_disabled(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
            $this->fail('Required domain tables are missing.');
        }

        $domain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create()->id,
            'custom_name' => 'zone-off.example.com',
            'status' => 'pending',
            'ssl' => false,
            'primary' => true,
            'added_date' => now(),
        ]);

        $this->mock(VercelDomainClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturn('zone-off.example.com');
            $mock->shouldReceive('getDomainVerification')->andReturn([]);
            $mock->shouldReceive('getDomainConfig')->andReturn(['misconfigured' => false]);
            $mock->shouldReceive('getAccountDomain')->andReturn([
                'name' => 'zone-off.example.com',
                'zone' => false,
                'verified' => true,
                'nameservers' => [],
                'intendedNameservers' => [],
            ]);
            $mock->shouldReceive('listCertificates')->andReturn(['certificates' => [], 'is_lower_bound' => false]);
            $mock->shouldReceive('findCoveringCertificate')->andReturn(null);
        });

        $this->mock(DnsNameserverChecker::class, function ($mock) {
            $mock->shouldReceive('hasExpectedNameservers')->andReturn(true);
            $mock->shouldReceive('getObservedNameservers')->andReturn(['ns1.vercel-dns.com', 'ns2.vercel-dns.com']);
        });

        config([
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
            'services.vercel.health_failure_threshold' => 3,
        ]);

        $inventory = [
            'domains' => [
                ['name' => 'zone-off.example.com', 'verified' => true],
            ],
        ];

        $result = app(DomainStatusSyncService::class)->sync(
            $domain,
            attemptVerify: false,
            applyFailureThreshold: true,
            projectInventory: $inventory
        );

        $domain->refresh();

        $this->assertSame('pending', $result['new_status']);
        $this->assertSame('zone_disabled', $result['health_code']);
        $this->assertFalse($result['ssl']);
        $this->assertSame('zone_disabled', $domain->dns_records['last_check']['health_code'] ?? null);
        $this->assertFalse($domain->dns_records['last_check']['zone_enabled'] ?? true);
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    /** @test */
    public function live_www_inventory_hint_overrides_stale_last_check_for_linked_vs_apex_only(): void
    {
        $domain = $this->domainWithLastCheck([
            'health_code' => 'apex_only',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => false,
            'www_redirect_correct' => false,
        ]);

        $this->assertSame('apex_only', $domain->health()['code']);

        $domain->setWwwStateHint(true, true);

        $this->assertSame('linked', $domain->health()['code']);
    }

    /** @test */
    public function resolved_health_re_derives_from_fields_ignoring_stale_stored_code(): void
    {
        // A record written by older logic: stored code says apex_only, but the
        // zone/SSL fields (defaulting false) say otherwise. health() trusts the
        // stored code; resolvedHealth() must re-derive from the fields.
        $domain = $this->domainWithLastCheck([
            'health_code' => 'apex_only',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => false,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => false,
        ]);

        $this->assertSame('apex_only', $domain->health()['code']);
        $this->assertSame('zone_disabled', $domain->resolvedHealth()['code']);
        $this->assertSame('warning', $domain->resolvedHealth()['class']);
    }

    /** @test */
    public function resolved_health_matches_stored_code_for_a_consistent_record(): void
    {
        // When the stored code already agrees with the fields, re-deriving is a no-op.
        $domain = $this->domainWithLastCheck([
            'health_code' => 'apex_only',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => false,
            'www_redirect_correct' => false,
        ]);

        $this->assertSame('apex_only', $domain->resolvedHealth()['code']);
    }

    /** @test */
    public function resolved_health_is_unchecked_without_last_check(): void
    {
        $domain = new ApiDomainSetting([
            'custom_name' => 'no-check.example.com',
            'dns_records' => [],
        ]);

        $this->assertSame('unchecked', $domain->resolvedHealth()['code']);
    }

    /** @test */
    public function health_is_invalid_domain_marker_beats_provider_error_heuristic(): void
    {
        // A provider-confirmed "invalid domain name" rejection must classify as the
        // terminal invalid_domain state, not the generic provider_error, even though
        // the failure record sets provider_reachable.
        $domain = $this->domainWithLastCheck([
            'health_code' => 'invalid_domain',
            'reason' => 'invalid_domain',
            'provider_reachable' => true,
            'message' => 'The `name` field contains an invalid domain name',
        ]);

        $this->assertSame('invalid_domain', $domain->health()['code']);
        $this->assertSame('invalid_domain', $domain->resolvedHealth()['code']);
        $this->assertSame('danger', $domain->resolvedHealth()['class']);
    }

    private function domainWithLastCheck(array $lastCheck, ?Carbon $expiresAt = null): ApiDomainSetting
    {
        $domain = new ApiDomainSetting([
            'custom_name' => 'health-' . uniqid('', false) . '.example.com',
            'dns_records' => ['last_check' => $lastCheck],
        ]);

        if ($expiresAt !== null) {
            $domain->expires_at = $expiresAt;
        }

        return $domain;
    }
}
