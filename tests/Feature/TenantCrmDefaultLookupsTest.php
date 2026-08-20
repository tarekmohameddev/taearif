<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerType;
use App\Models\User;
use App\Services\TenantCrmBootstrapService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantCrmDefaultLookupsTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach ([
            'users_api_customers_types',
            'users_api_customers_priorities',
            'users_api_customers_procedures',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
        ]);
    }

    /** @test */
    public function creating_a_tenant_seeds_types_priorities_and_procedures(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        $this->assertEqualsCanonicalizing(
            array_column(TenantCrmBootstrapService::defaultTypes(), 'value'),
            UserApiCustomerType::where('user_id', $tenant->id)->pluck('value')->all()
        );

        $this->assertEqualsCanonicalizing(
            array_map('strval', array_column(TenantCrmBootstrapService::defaultPriorities(), 'value')),
            UserApiCustomerPriority::where('user_id', $tenant->id)->pluck('value')->map('strval')->all()
        );

        $this->assertEqualsCanonicalizing(
            array_column(TenantCrmBootstrapService::defaultProcedures(), 'procedure_name'),
            UserApiCustomerProcedure::where('user_id', $tenant->id)->pluck('procedure_name')->all()
        );
    }

    /** @test */
    public function seeded_types_are_active_and_ordered_so_a_default_resolves(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        $first = UserApiCustomerType::where('user_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        $this->assertNotNull($first, 'A newly created tenant must have a resolvable default type.');
        $this->assertSame('Rent', $first->value);
    }

    /** @test */
    public function seeding_is_idempotent(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $bootstrap = app(TenantCrmBootstrapService::class);

        $bootstrap->ensureDefaultTypes((int) $tenant->id);
        $bootstrap->ensureDefaultTypes((int) $tenant->id);
        $bootstrap->ensureDefaultPriorities((int) $tenant->id);
        $bootstrap->ensureDefaultProcedures((int) $tenant->id);

        $this->assertCount(
            count(TenantCrmBootstrapService::defaultTypes()),
            UserApiCustomerType::where('user_id', $tenant->id)->get()
        );
        $this->assertCount(
            count(TenantCrmBootstrapService::defaultPriorities()),
            UserApiCustomerPriority::where('user_id', $tenant->id)->get()
        );
        $this->assertCount(
            count(TenantCrmBootstrapService::defaultProcedures()),
            UserApiCustomerProcedure::where('user_id', $tenant->id)->get()
        );
    }

    /** @test */
    public function seeding_backfills_only_the_missing_types_and_keeps_custom_ones(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        UserApiCustomerType::where('user_id', $tenant->id)->delete();

        UserApiCustomerType::create([
            'user_id' => $tenant->id,
            'name' => 'Bespoke',
            'value' => 'Bespoke',
            'color' => '#000',
            'icon' => 'star',
            'order' => 9,
            'is_active' => true,
        ]);

        app(TenantCrmBootstrapService::class)->ensureDefaultTypes((int) $tenant->id);

        $values = UserApiCustomerType::where('user_id', $tenant->id)->pluck('value')->all();

        $this->assertContains('Bespoke', $values);
        foreach (array_column(TenantCrmBootstrapService::defaultTypes(), 'value') as $expected) {
            $this->assertContains($expected, $values);
        }
    }

    /** @test */
    public function crm_index_bootstrap_does_not_re_enable_disabled_auto_create(): void
    {
        $this->requireTables();

        if (! Schema::hasTable('property_request_auto_customer_settings')) {
            $this->markTestSkipped('property_request_auto_customer_settings table required.');
        }

        $tenant = $this->createTenant();

        PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)
            ->update(['auto_create_customer' => false]);

        // The seeders the CRM board calls must not rewrite auto-customer settings.
        $bootstrap = app(TenantCrmBootstrapService::class);
        $bootstrap->ensureDefaultStages((int) $tenant->id);
        $bootstrap->ensureDefaultProcedures((int) $tenant->id);
        $bootstrap->ensureDefaultPriorities((int) $tenant->id);
        $bootstrap->ensureDefaultTypes((int) $tenant->id);

        $this->assertFalse(
            (bool) PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->value('auto_create_customer')
        );
    }

    /** @test */
    public function backfill_command_creates_missing_rows(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        UserApiCustomerType::where('user_id', $tenant->id)->delete();
        UserApiCustomerPriority::where('user_id', $tenant->id)->delete();
        UserApiCustomerProcedure::where('user_id', $tenant->id)->delete();

        $this->artisan('crm:backfill-tenant-defaults', ['--tenant' => $tenant->id, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, UserApiCustomerType::where('user_id', $tenant->id)->count(), 'Dry run must not write.');

        $this->artisan('crm:backfill-tenant-defaults', ['--tenant' => $tenant->id])
            ->assertExitCode(0);

        $this->assertCount(
            count(TenantCrmBootstrapService::defaultTypes()),
            UserApiCustomerType::where('user_id', $tenant->id)->get()
        );
        $this->assertCount(
            count(TenantCrmBootstrapService::defaultProcedures()),
            UserApiCustomerProcedure::where('user_id', $tenant->id)->get()
        );
    }
}
