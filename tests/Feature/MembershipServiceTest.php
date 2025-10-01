<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Package;
use App\Services\MembershipService;
use App\Models\Api\GeneralSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $membershipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->membershipService = app(MembershipService::class);
    }

    /** @test */
    public function it_can_determine_if_user_has_free_package()
    {
        // Create a user with free package
        $user = User::factory()->create();
        
        // Mock the UserPermissionHelper to return free package
        $freePackage = new \stdClass();
        $freePackage->id = MembershipService::FREE_PACKAGE_ID;
        
        $this->mock(\App\Http\Helpers\UserPermissionHelper::class)
            ->shouldReceive('userPackage')
            ->with($user->id)
            ->andReturn($freePackage);

        $this->assertTrue($this->membershipService->hasFreePackage($user));
    }

    /** @test */
    public function it_can_determine_if_user_can_control_maintenance_mode()
    {
        // Create a user with paid package
        $user = User::factory()->create();
        
        // Mock the UserPermissionHelper to return paid package
        $paidPackage = new \stdClass();
        $paidPackage->id = 24; // Some paid package ID
        
        $this->mock(\App\Http\Helpers\UserPermissionHelper::class)
            ->shouldReceive('userPackage')
            ->with($user->id)
            ->andReturn($paidPackage);

        $this->assertTrue($this->membershipService->canControlMaintenanceMode($user));
    }

    /** @test */
    public function free_package_users_cannot_control_maintenance_mode()
    {
        // Create a user with free package
        $user = User::factory()->create();
        
        // Mock the UserPermissionHelper to return free package
        $freePackage = new \stdClass();
        $freePackage->id = MembershipService::FREE_PACKAGE_ID;
        
        $this->mock(\App\Http\Helpers\UserPermissionHelper::class)
            ->shouldReceive('userPackage')
            ->with($user->id)
            ->andReturn($freePackage);

        $this->assertFalse($this->membershipService->canControlMaintenanceMode($user));
    }

    /** @test */
    public function it_can_enable_maintenance_mode()
    {
        $user = User::factory()->create();
        
        $this->membershipService->enableMaintenanceMode($user);
        
        $setting = GeneralSetting::where('user_id', $user->id)->first();
        $this->assertNotNull($setting);
        $this->assertEquals(1, $setting->maintenance_mode);
    }

    /** @test */
    public function it_can_disable_maintenance_mode()
    {
        $user = User::factory()->create();
        
        // First enable maintenance mode
        $this->membershipService->enableMaintenanceMode($user);
        
        // Then disable it
        $this->membershipService->disableMaintenanceMode($user);
        
        $setting = GeneralSetting::where('user_id', $user->id)->first();
        $this->assertNotNull($setting);
        $this->assertEquals(0, $setting->maintenance_mode);
    }
}
