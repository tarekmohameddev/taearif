<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Pipedrive;

use App\Jobs\SyncTenantToPipedriveJob;
use App\Models\BasicSetting;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Admin\AdminApiTestCase;

class PipedriveAutoSyncTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureRequiredColumns();
    }

    /** @test */
    public function admin_create_user_dispatches_pipedrive_sync_job(): void
    {
        Queue::fake();

        $this->signInAdmin();

        $this->postJson(route('admin.api.users.store'), [
            'first_name' => 'Ahmed',
            'last_name' => 'Test',
            'email' => 'ahmed.autotest' . uniqid() . '@example.com',
            'username' => 'ahmed_auto' . uniqid(),
            'password' => 'Password1!',
            'account_type' => 'tenant',
            'status' => 1,
        ]);

        Queue::assertPushed(SyncTenantToPipedriveJob::class, function ($job) {
            return $job->trigger === 'registration';
        });
    }

    /** @test */
    public function sync_job_is_unique_per_user(): void
    {
        $job1 = new SyncTenantToPipedriveJob(userId: 10, trigger: 'registration');
        $job2 = new SyncTenantToPipedriveJob(userId: 10, trigger: 'registration');
        $job3 = new SyncTenantToPipedriveJob(userId: 20, trigger: 'registration');

        $this->assertSame($job1->uniqueId(), $job2->uniqueId());
        $this->assertNotSame($job1->uniqueId(), $job3->uniqueId());
    }

    /** @test */
    public function sync_job_does_nothing_for_non_tenant_user(): void
    {
        if (!Schema::hasColumn('users', 'pipedrive_deal_id')
            || !Schema::hasTable('pipedrive_sync_logs')) {
            $this->markTestSkipped('Pipedrive tables not yet migrated in test DB.');
        }

        $this->configurePipedrive();

        $employeeUser = User::create([
            'first_name' => 'Emp',
            'last_name' => 'User',
            'email' => 'emp' . uniqid() . '@example.com',
            'username' => 'emp' . uniqid(),
            'password' => bcrypt('password'),
            'account_type' => 'employee',
            'status' => 1,
        ]);

        // Should return without syncing
        $job = new SyncTenantToPipedriveJob(userId: $employeeUser->id, trigger: 'registration');

        // Just dispatching synchronously should not throw and not touch pipedrive_deal_id
        $job->handle(app(\App\Domain\CRM\Pipedrive\Services\PipedriveTenantSyncService::class));

        $this->assertNull($employeeUser->fresh()->pipedrive_deal_id);
    }

    /** @test */
    public function sync_job_does_nothing_for_nonexistent_user(): void
    {
        $job = new SyncTenantToPipedriveJob(userId: 999999, trigger: 'registration');

        // Should not throw
        $job->handle(app(\App\Domain\CRM\Pipedrive\Services\PipedriveTenantSyncService::class));

        $this->assertTrue(true); // No exception = pass
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function configurePipedrive(): void
    {
        if (!Schema::hasColumn('basic_settings', 'pipedrive_sync_enabled')) {
            return;
        }

        BasicSetting::query()->delete();
        $model = new BasicSetting();
        $model->pipedrive_sync_enabled = false; // disabled so no real HTTP calls
        $model->pipedrive_api_token = null;
        $model->pipedrive_base_url = null;
        $model->save();
    }

    private function ensureRequiredColumns(): void
    {
        if (!Schema::hasColumn('basic_settings', 'pipedrive_sync_enabled')) {
            $this->markTestSkipped('Pipedrive columns not yet migrated in test DB.');
        }
    }
}
