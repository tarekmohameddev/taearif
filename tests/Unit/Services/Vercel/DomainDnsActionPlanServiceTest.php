<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Vercel;

use App\Services\Vercel\DomainDnsActionPlanService;
use PHPUnit\Framework\TestCase;

class DomainDnsActionPlanServiceTest extends TestCase
{
    /** @test */
    public function it_keeps_an_accepted_fallback_group_and_only_deletes_conflicting_apex_records(): void
    {
        $plan = (new DomainDnsActionPlanService())->build([
            'custom_name' => 'rawashinsa.com',
            'dns_mode' => 'external_dns',
            'observed_nameservers' => ['ns26.domaincontrol.com', 'ns25.domaincontrol.com'],
            'recommended_ipv4_groups' => [
                ['rank' => 1, 'values' => ['216.198.79.1', '64.29.17.1']],
                ['rank' => 2, 'values' => ['76.76.21.21']],
            ],
            'recommended_cname_groups' => [
                ['rank' => 1, 'values' => ['b358343036126ac5.vercel-dns-017.com']],
                ['rank' => 2, 'values' => ['cname.vercel-dns.com']],
            ],
            'apex_records' => [
                ['type' => 'A', 'value' => '76.223.105.230'],
                ['type' => 'A', 'value' => '13.248.243.5'],
                ['type' => 'A', 'value' => '76.76.21.21'],
            ],
            // The A values are resolver results behind the CNAME and must not be
            // presented as editable www records.
            'www_records' => [
                ['type' => 'A', 'value' => '76.76.21.98'],
                ['type' => 'A', 'value' => '66.33.60.67'],
                ['type' => 'CNAME', 'value' => 'cname.vercel-dns.com'],
            ],
            'apex_lookup_known' => true,
            'www_lookup_known' => true,
        ]);

        $this->assertSame('keep', $plan['nameserver_action']);
        $this->assertSame(2, $plan['selected_ipv4_rank']);
        $this->assertSame(2, $plan['selected_cname_rank']);
        $this->assertTrue($plan['kept_existing_ipv4_group']);
        $this->assertTrue($plan['kept_existing_cname_group']);

        $this->assertContains($this->row('delete', 'A', '@', '76.223.105.230'), $plan['rows']);
        $this->assertContains($this->row('delete', 'A', '@', '13.248.243.5'), $plan['rows']);
        $this->assertContains($this->row('keep', 'A', '@', '76.76.21.21'), $plan['rows']);
        $this->assertContains($this->row('keep', 'CNAME', 'www', 'cname.vercel-dns.com'), $plan['rows']);
        $this->assertNotContains($this->row('delete', 'A', 'www', '76.76.21.98'), $plan['rows']);
        $this->assertNotContains($this->row('add', 'A', '@', '216.198.79.1'), $plan['rows']);
    }

    /** @test */
    public function it_uses_the_complete_preferred_group_for_a_new_external_dns_setup(): void
    {
        $plan = (new DomainDnsActionPlanService())->build([
            'custom_name' => 'example.com',
            'dns_mode' => 'external_dns',
            'recommended_ipv4_groups' => [
                ['rank' => 1, 'values' => ['216.198.79.1', '64.29.17.1']],
                ['rank' => 2, 'values' => ['76.76.21.21']],
            ],
            'recommended_cname_groups' => [
                ['rank' => 1, 'values' => ['project.vercel-dns-017.com']],
                ['rank' => 2, 'values' => ['cname.vercel-dns.com']],
            ],
            'apex_records' => [],
            'www_records' => [],
            'apex_lookup_known' => true,
            'www_lookup_known' => true,
        ]);

        $this->assertContains($this->row('add', 'A', '@', '216.198.79.1'), $plan['rows']);
        $this->assertContains($this->row('add', 'A', '@', '64.29.17.1'), $plan['rows']);
        $this->assertContains($this->row('add', 'CNAME', 'www', 'project.vercel-dns-017.com'), $plan['rows']);
        $this->assertNotContains($this->row('add', 'A', '@', '76.76.21.21'), $plan['rows']);
    }

    /** @test */
    public function it_returns_nameserver_instructions_only_for_vercel_nameserver_mode(): void
    {
        $plan = (new DomainDnsActionPlanService())->build([
            'dns_mode' => 'vercel_ns',
            'expected_nameservers' => ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'],
            'observed_nameservers' => ['ns25.domaincontrol.com', 'ns26.domaincontrol.com'],
        ]);

        $this->assertSame('replace', $plan['nameserver_action']);
        $this->assertSame(['ns1.vercel-dns.com', 'ns2.vercel-dns.com'], $plan['nameservers']);
        $this->assertSame([], $plan['rows']);
    }

    /** @return array{action: string, type: string, host: string, value: string, ttl: string} */
    private function row(string $action, string $type, string $host, string $value): array
    {
        return compact('action', 'type', 'host', 'value') + ['ttl' => 'auto'];
    }
}
