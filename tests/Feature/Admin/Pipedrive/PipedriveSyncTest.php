<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Pipedrive;

use App\Models\BasicSetting;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Admin\AdminApiTestCase;
use Tests\Traits\MocksExternalServices;

class PipedriveSyncTest extends AdminApiTestCase
{
    use MocksExternalServices;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureRequiredColumns();
        $this->ensurePipedriveSyncLogsTable();
    }

    /** @test */
    public function admin_can_sync_single_tenant_to_pipedrive(): void
    {
        $this->configurePipedrive();
        $this->mockPipedriveClient(personId: 111, orgId: 222, dealId: 333);

        $user = $this->createTenantUser(['company_name' => 'Test Company']);
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.pipedrive.sync', ['user' => $user->id]),
            ['force' => false]
        );

        $response->assertOk()
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.status', 'success')
            ->assertJsonPath('data.person_id', 111)
            ->assertJsonPath('data.deal_id', 333);
    }

    /** @test */
    public function sync_single_user_requires_authentication(): void
    {
        $user = $this->createTenantUser();

        $this->postJson(
            route('admin.api.users.pipedrive.sync', ['user' => $user->id])
        )->assertUnauthorized();
    }

    /** @test */
    public function sync_skips_already_synced_user_without_force(): void
    {
        $this->configurePipedrive();
        $this->mockPipedriveClient();

        $user = $this->createTenantUser(['pipedrive_deal_id' => 9999]);
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.pipedrive.sync', ['user' => $user->id]),
            ['force' => false]
        );

        $response->assertOk()
            ->assertJsonPath('data.success', false)
            ->assertJsonPath('data.status', 'skipped');
    }

    /** @test */
    public function sync_re_syncs_user_when_force_is_true(): void
    {
        $this->configurePipedrive();
        $this->mockPipedriveClient(personId: 500, dealId: 600);

        $user = $this->createTenantUser(['pipedrive_deal_id' => 9999]);
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.pipedrive.sync', ['user' => $user->id]),
            ['force' => true]
        );

        $response->assertOk()
            ->assertJsonPath('data.success', true);
    }

    /** @test */
    public function sync_returns_422_when_pipedrive_not_configured(): void
    {
        // BasicSetting with no credentials
        BasicSetting::query()->delete();
        $model = new BasicSetting();
        $model->pipedrive_sync_enabled = false;
        $model->pipedrive_api_token = null;
        $model->pipedrive_base_url = null;
        $model->save();

        $user = $this->createTenantUser();
        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.pipedrive.sync', ['user' => $user->id]),
            ['force' => false]
        );

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'PIPEDRIVE_NOT_CONFIGURED');
    }

    /** @test */
    public function admin_can_bulk_sync_users_to_pipedrive(): void
    {
        $this->configurePipedrive();
        $this->mockPipedriveClient();

        $user1 = $this->createTenantUser();
        $user2 = $this->createTenantUser();

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.pipedrive.sync-bulk'),
            ['user_ids' => [$user1->id, $user2->id], 'force' => false]
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.synced', 2);
    }

    /** @test */
    public function bulk_sync_requires_authentication(): void
    {
        $this->postJson(route('admin.api.users.pipedrive.sync-bulk'), ['user_ids' => [1]])
            ->assertUnauthorized();
    }

    /** @test */
    public function bulk_sync_validates_max_50_users(): void
    {
        $this->signInAdmin();

        $ids = range(1, 51);

        $this->postJson(
            route('admin.api.users.pipedrive.sync-bulk'),
            ['user_ids' => $ids]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['user_ids']);
    }

    /** @test */
    public function bulk_sync_requires_user_ids(): void
    {
        $this->signInAdmin();

        $this->postJson(route('admin.api.users.pipedrive.sync-bulk'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_ids']);
    }

    /** @test */
    public function bulk_sync_skips_non_tenant_users(): void
    {
        $this->configurePipedrive();
        $this->mockPipedriveClient();

        $tenantUser = $this->createTenantUser();
        $nonTenantUser = $this->createTenantUser(['account_type' => 'employee']);

        $this->signInAdmin();

        $response = $this->postJson(
            route('admin.api.users.pipedrive.sync-bulk'),
            ['user_ids' => [$tenantUser->id, $nonTenantUser->id]]
        );

        $response->assertOk();

        $results = $response->json('data.results');

        $this->assertTrue($results[(string) $tenantUser->id]['success'] ?? false);
        $this->assertFalse($results[(string) $nonTenantUser->id]['success'] ?? true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTenantUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user' . uniqid() . '@example.com',
            'username' => 'user' . uniqid(),
            'password' => bcrypt('password'),
            'account_type' => 'tenant',
            'status' => 1,
        ], $attributes));
    }

    private function configurePipedrive(array $attributes = []): void
    {
        BasicSetting::query()->delete();
        $model = new BasicSetting();
        $model->pipedrive_sync_enabled = true;
        $model->pipedrive_api_token = 'test-token';
        $model->pipedrive_base_url = 'https://company.pipedrive.com';
        $model->pipedrive_pipeline_id = 2;
        $model->pipedrive_stage_id = 8;
        $model->pipedrive_deal_title_prefix = 'New Lead - ';

        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }

        $model->save();
    }

    private function ensureRequiredColumns(): void
    {
        if (!Schema::hasColumn('users', 'pipedrive_deal_id')
            || !Schema::hasColumn('basic_settings', 'pipedrive_sync_enabled')) {
            $this->markTestSkipped('Pipedrive columns not yet migrated in test DB.');
        }
    }

    private function ensurePipedriveSyncLogsTable(): void
    {
        if (Schema::hasTable('pipedrive_sync_logs')) {
            return;
        }

        Schema::create('pipedrive_sync_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'success', 'failed', 'skipped']);
            $table->enum('trigger', ['registration', 'manual', 'bulk']);
            $table->unsignedBigInteger('person_id')->nullable();
            $table->unsignedBigInteger('org_id')->nullable();
            $table->unsignedBigInteger('deal_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }
}
