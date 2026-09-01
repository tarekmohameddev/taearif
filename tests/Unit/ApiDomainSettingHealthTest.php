<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Api\ApiDomainSetting;
use Carbon\Carbon;
use Tests\TestCase;

class ApiDomainSettingHealthTest extends TestCase
{
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
    public function health_is_linked_when_vercel_and_nameservers_are_ok(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => true,
        ]);

        $this->assertSame('linked', $domain->health()['code']);
        $this->assertSame('success', $domain->health()['class']);
    }

    /** @test */
    public function health_is_ns_mismatch_when_vercel_verified_but_nameservers_fail(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => true,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('ns_mismatch', $domain->health()['code']);
    }

    /** @test */
    public function health_is_not_on_vercel_when_vercel_attached_hint_is_false(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('not_on_vercel', $domain->health(false)['code']);
    }

    /** @test */
    public function health_is_unverified_when_not_verified_but_present_on_vercel(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->assertSame('unverified', $domain->health(true)['code']);
    }

    /** @test */
    public function health_prefers_stored_vercel_attached_false_over_external_true_hint(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
            'vercel_attached' => false,
        ]);

        $this->assertSame('not_on_vercel', $domain->health(true)['code']);
    }

    /** @test */
    public function health_prefers_stored_vercel_attached_true_over_external_false_hint(): void
    {
        $domain = $this->domainWithLastCheck([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'vercel_verified' => false,
            'nameservers_ok' => false,
            'vercel_attached' => true,
        ]);

        $this->assertSame('unverified', $domain->health(false)['code']);
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
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
