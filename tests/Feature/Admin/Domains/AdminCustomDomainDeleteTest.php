<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Admin\AdminApiTestCase;

/**
 * Regression coverage for the admin custom-domain delete path.
 *
 * The panel lists ApiDomainSetting rows but used to delete UserCustomDomain rows
 * by the same numeric id — destroying an unrelated record while leaving the
 * intended domain (and its Vercel entry) in place.
 */
class AdminCustomDomainDeleteTest extends AdminApiTestCase
{
    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('user_custom_domains')) {
            $this->markTestSkipped('Missing required DB tables.');
        }
    }

    private function signInWebAdmin(): Admin
    {
        $admin = Admin::factory()->create();

        // Authenticate on the admin guard WITHOUT making it the default guard.
        // actingAs() would call shouldUse('admin'), so $request->user() would
        // return an Admin — which never happens in production, where these web
        // routes run on the admin guard while the default stays 'web'.
        $this->app['auth']->guard('admin')->setUser($admin);

        return $admin;
    }

    private function tenant(): User
    {
        $user = User::factory()->tenant()->create([
            'email' => 'admin-domain-' . uniqid('', true) . '@example.com',
        ]);

        // The users table is reset per run but api_domains_settings is not, so a
        // recycled user id can inherit rows from an earlier run. reassignPrimary()
        // picks an arbitrary active domain (unordered first(), mirroring
        // DomainSettingsController::destroy()), so leftovers must not be candidates.
        ApiDomainSetting::where('user_id', $user->id)->delete();

        return $user;
    }

    /**
     * custom_name is uniquely indexed, so generate a fresh name per row rather
     * than depending on the test database being clean.
     */
    private function makeDomain(User $user, string $label, bool $primary = false, string $status = 'active'): ApiDomainSetting
    {
        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $label . '-' . uniqid('', false) . '.example.com',
            'status' => $status,
            'primary' => $primary,
            'ssl' => false,
            'added_date' => now(),
        ]);
    }

    /**
     * Insert a user_custom_domains row carrying a specific id, so we can prove
     * the delete no longer resolves against that table.
     */
    private function makeDecoyCustomDomain(int $id, User $user): void
    {
        DB::table('user_custom_domains')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'requested_domain' => 'decoy-' . $id . '.example.com',
            'current_domain' => 'decoy-' . $id . '.example.com',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mockVercel(bool $shouldFail = false): void
    {
        $this->mock(VercelDomainClient::class, function ($mock) use ($shouldFail) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturnUsing(fn ($d) => $d);

            if ($shouldFail) {
                $mock->shouldReceive('removeApexAndWww')
                    ->andThrow(new VercelDomainException('Failed to remove domain from Vercel', 500, ['error' => ['message' => 'boom']]));
            } else {
                $mock->shouldReceive('removeApexAndWww')->andReturnNull();
            }
        });
    }

    /** @test */
    public function delete_removes_the_api_domain_row_and_leaves_user_custom_domains_untouched(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $admin = $this->signInWebAdmin();
        $user = $this->tenant();

        $domain = $this->makeDomain($user, 'delete-me');
        // A decoy sharing the same numeric id is exactly what the old code destroyed.
        $this->makeDecoyCustomDomain($domain->id, $user);

        $response = $this->post(route('admin.custom-domain.delete'), [
            'domain_id' => $domain->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseMissing('api_domains_settings', ['id' => $domain->id]);
        $this->assertDatabaseHas('user_custom_domains', ['id' => $domain->id]);
    }

    /** @test */
    public function delete_promotes_another_active_domain_to_primary(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $this->signInWebAdmin();
        $user = $this->tenant();

        $primary = $this->makeDomain($user, 'primary', true);
        $other = $this->makeDomain($user, 'secondary', false, 'active');

        $this->post(route('admin.custom-domain.delete'), ['domain_id' => $primary->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('api_domains_settings', ['id' => $primary->id]);
        $this->assertDatabaseHas('api_domains_settings', [
            'id' => $other->id,
            'primary' => 1,
        ]);
    }

    /** @test */
    public function delete_keeps_the_row_when_vercel_removal_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel(true);
        $this->signInWebAdmin();
        $user = $this->tenant();

        $domain = $this->makeDomain($user, 'vercel-fails');

        $this->post(route('admin.custom-domain.delete'), ['domain_id' => $domain->id])
            ->assertRedirect();

        // Failing closed: never orphan the Vercel entry by deleting the row anyway.
        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
    }

    /** @test */
    public function bulk_delete_removes_every_selected_api_domain_row(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $this->signInWebAdmin();
        $user = $this->tenant();

        $first = $this->makeDomain($user, 'bulk-one');
        $second = $this->makeDomain($user, 'bulk-two');
        $this->makeDecoyCustomDomain($first->id, $user);

        $this->post(route('admin.custom-domain.bulk.delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertOk();

        $this->assertDatabaseMissing('api_domains_settings', ['id' => $first->id]);
        $this->assertDatabaseMissing('api_domains_settings', ['id' => $second->id]);
        $this->assertDatabaseHas('user_custom_domains', ['id' => $first->id]);
    }

    /** @test */
    public function bulk_delete_with_an_unknown_id_deletes_nothing(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $this->signInWebAdmin();
        $user = $this->tenant();

        $first = $this->makeDomain($user, 'keep-one');
        $second = $this->makeDomain($user, 'keep-two');
        $unknownId = ApiDomainSetting::max('id') + 5000;

        $this->post(route('admin.custom-domain.bulk.delete'), [
            'ids' => [$first->id, $second->id, $unknownId],
        ]);

        // Resolve-all-up-front: the batch aborts before anything is detached.
        $this->assertDatabaseHas('api_domains_settings', ['id' => $first->id]);
        $this->assertDatabaseHas('api_domains_settings', ['id' => $second->id]);
    }
}
