<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Calling\Models\CallSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileCallingEnabledTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'call_settings'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_get_user_returns_calling_enabled_false_when_no_call_settings(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create();
        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.calling_enabled', false);
    }

    public function test_get_user_returns_calling_enabled_true_when_tenant_calling_is_enabled(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create();
        CallSetting::create([
            'tenant_id' => $tenant->id,
            'enabled' => true,
            'record_by_default' => false,
            'max_channels' => 5,
        ]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $tenant->id)
            ->assertJsonPath('data.calling_enabled', true);
    }

    public function test_get_user_returns_calling_enabled_false_when_tenant_calling_is_disabled(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create();
        CallSetting::create([
            'tenant_id' => $tenant->id,
            'enabled' => false,
            'record_by_default' => false,
            'max_channels' => 5,
        ]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.calling_enabled', false);
    }

    public function test_employee_get_user_reflects_tenant_calling_enabled(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create();
        $employee = User::factory()->employee()->create([
            'tenant_id' => $tenant->id,
        ]);
        CallSetting::create([
            'tenant_id' => $tenant->id,
            'enabled' => true,
            'record_by_default' => false,
            'max_channels' => 5,
        ]);

        Sanctum::actingAs($employee);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.calling_enabled', true);
    }

    public function test_get_user_calling_enabled_updates_after_settings_change_and_cache_clear(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->tenant()->create();
        $settings = CallSetting::create([
            'tenant_id' => $tenant->id,
            'enabled' => false,
            'record_by_default' => false,
            'max_channels' => 5,
        ]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.calling_enabled', false);

        $settings->update(['enabled' => true]);
        Cache::forget("user:profile:v2:{$tenant->id}:{$tenant->id}");

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.calling_enabled', true);
    }
}
