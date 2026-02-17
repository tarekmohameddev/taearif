<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\Sms;

use App\Domain\Communication\Sms\Services\SmsRecipientResolverService;
use App\Models\ApiCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class SmsRecipientResolverServiceTest extends TestCase
{
    use DatabaseTransactions;

    private SmsRecipientResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(SmsRecipientResolverService::class);
    }

    private function requireApiCustomersTable(): void
    {
        if (!Schema::hasTable('api_customers')) {
            $this->markTestSkipped('api_customers table required.');
        }
    }

    /** @test */
    public function resolve_returns_customers_by_ids_tenant_scoped(): void
    {
        $this->requireApiCustomersTable();
        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $c1 = ApiCustomer::create([
            'user_id' => $user->id,
            'name' => 'Customer One',
            'phone_number' => '+966501234567',
            'email' => 'c1@test.com',
            'password' => bcrypt('password'),
        ]);
        $c2 = ApiCustomer::create([
            'user_id' => $user->id,
            'name' => 'Customer Two',
            'phone_number' => '966509876543',
            'email' => 'c2@test.com',
            'password' => bcrypt('password'),
        ]);

        $resolved = $this->resolver->resolve($user->id, [$c1->id, $c2->id], []);

        $this->assertCount(2, $resolved);
        $phones = array_column($resolved, 'phone');
        $this->assertContains('+966501234567', $phones);
        $this->assertContains('966509876543', $phones);
        $names = array_column($resolved, 'name');
        $this->assertContains('Customer One', $names);
        $this->assertContains('Customer Two', $names);
    }

    /** @test */
    public function resolve_merges_manual_phones_and_deduplicates_by_normalized_phone(): void
    {
        $this->requireApiCustomersTable();
        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $resolved = $this->resolver->resolve($user->id, [], ['+966500000001', '+966 500 000 001', '500000002']);

        $this->assertCount(2, $resolved);
        $phones = array_column($resolved, 'phone');
        $this->assertContains('+966500000001', $phones);
        $this->assertContains('500000002', $phones);
    }

    /** @test */
    public function resolve_ignores_other_tenant_customers(): void
    {
        $this->requireApiCustomersTable();
        $userA = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $userB = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $cB = ApiCustomer::create([
            'user_id' => $userB->id,
            'name' => 'Other',
            'phone_number' => '+966501111111',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
        ]);

        $resolved = $this->resolver->resolve($userA->id, [$cB->id], []);

        $this->assertCount(0, $resolved);
    }

    /** @test */
    public function resolve_throws_when_manual_phones_exceed_max(): void
    {
        Config::set('communication.sms.max_manual_recipients', 2);
        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many manual recipients');

        $this->resolver->resolve($user->id, [], ['+966500000001', '+966500000002', '+966500000003']);
    }
}
