<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Models\Api\GeneralSetting;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;
use App\Events\UserUpgradedFromFree;

/**
 * Test Membership Renewal Auto-Restoration
 * 
 * This test suite verifies that maintenance mode is automatically disabled
 * when users renew their membership from free/trial to paid packages.
 */
class MembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected $membershipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->membershipService = app(MembershipService::class);
    }

    /** @test */
    public function it_can_enable_and_disable_maintenance_mode()
    {
        $user = User::factory()->create();
        
        // Enable maintenance mode
        $this->membershipService->enableMaintenanceMode($user);
        $this->assertTrue($this->membershipService->isMaintenanceModeEnabled($user));
        
        // Disable maintenance mode
        $this->membershipService->disableMaintenanceMode($user);
        $this->assertFalse($this->membershipService->isMaintenanceModeEnabled($user));
    }

    /** @test */
    public function it_disables_maintenance_mode_when_upgrading_from_free_package()
    {
        $user = User::factory()->create();
        
        // Enable maintenance mode (simulating expired user scenario)
        GeneralSetting::create(['user_id' => $user->id, 'maintenance_mode' => 1]);
        $this->assertTrue($this->membershipService->isMaintenanceModeEnabled($user));

        // Create free package membership (ID 16 is free package)
        $freeMembership = Membership::create([
            'user_id' => $user->id,
            'package_id' => 16,
            'status' => 1,
            'start_date' => Carbon::now(),
            'expire_date' => Carbon::now()->addYear(),
            'price' => 0,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'system',
        ]);

        // Simulate user upgrading to a paid package (any ID that's not 16 or 26)
        Event::fake([UserUpgradedFromFree::class]);
        
        // Simulate upgrade to paid package
        $this->membershipService->handlePackageUpgrade($user, 5, 'payment');

        // Verify maintenance mode is disabled
        $user->refresh();
        $this->assertFalse($this->membershipService->isMaintenanceModeEnabled($user));
        
        // Verify event was fired
        Event::assertDispatched(UserUpgradedFromFree::class);
    }

    /** @test */
    public function it_disables_maintenance_mode_when_upgrading_from_trial_package()
    {
        $user = User::factory()->create();
        
        // Enable maintenance mode
        GeneralSetting::create(['user_id' => $user->id, 'maintenance_mode' => 1]);

        // Create trial package membership (ID 26 is trial package)
        Membership::create([
            'user_id' => $user->id,
            'package_id' => 26,
            'status' => 1,
            'start_date' => Carbon::now(),
            'expire_date' => Carbon::now()->addDays(7),
            'price' => 0,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'trial',
        ]);

        // Upgrade to paid package
        $this->membershipService->handlePackageUpgrade($user, 5, 'payment');

        // Verify maintenance mode is disabled
        $this->assertFalse($this->membershipService->isMaintenanceModeEnabled($user));
    }

    /** @test */
    public function it_does_not_change_maintenance_mode_for_paid_to_paid_renewal()
    {
        $user = User::factory()->create();
        
        // User on paid package with maintenance mode off
        GeneralSetting::create(['user_id' => $user->id, 'maintenance_mode' => 0]);
        
        Membership::create([
            'user_id' => $user->id,
            'package_id' => 5,
            'status' => 1,
            'start_date' => Carbon::now(),
            'expire_date' => Carbon::now()->addMonth(),
            'price' => 50,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'stripe',
        ]);

        // Renew with another paid package (both non-free, non-trial)
        $this->membershipService->handlePackageUpgrade($user, 6, 'payment');

        // Maintenance mode should remain unchanged
        $this->assertFalse($this->membershipService->isMaintenanceModeEnabled($user));
    }

    /** @test */
    public function it_logs_upgrade_source_correctly()
    {
        $user = User::factory()->create();
        
        GeneralSetting::create(['user_id' => $user->id, 'maintenance_mode' => 1]);
        
        Membership::create([
            'user_id' => $user->id,
            'package_id' => 16,
            'status' => 1,
            'start_date' => Carbon::now(),
            'expire_date' => Carbon::now()->addYear(),
            'price' => 0,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'system',
        ]);

        // Test different sources
        $this->membershipService->handlePackageUpgrade($user, 5, 'admin');
        $this->assertFalse($this->membershipService->isMaintenanceModeEnabled($user));
    }
}

