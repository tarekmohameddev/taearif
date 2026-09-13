<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Events\TenantActivityOccurred;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Admin\AdminApiTestCase;

/**
 * Regression coverage for the admin custom-domain delete path.
 */
class AdminCustomDomainDeleteTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.expected_project_id' => 'prj_test',
            'services.vercel.expected_team_id' => 'team_test',
            'services.vercel.allow_shared_project_mutations' => true,
            'services.vercel.auto_attach_custom_domain' => true,
        ]);
    }

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('user_custom_domains')) {
            $this->fail('Required domain tables are missing.');
        }
    }

    private function signInWebAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $this->app['auth']->guard('admin')->setUser($admin);

        return $admin;
    }

    private function tenant(): User
    {
        $user = User::factory()->tenant()->create([
            'email' => 'admin-domain-' . uniqid('', true) . '@example.com',
        ]);

        ApiDomainSetting::where('user_id', $user->id)->delete();

        return $user;
    }

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

    /**
     * @return array{domain_id: int, confirm_domain: string}
     */
    private function deletePayload(ApiDomainSetting $domain): array
    {
        return [
            'domain_id' => $domain->id,
            'confirm_domain' => $domain->custom_name,
        ];
    }

    private function mockVercel(bool $shouldFail = false): void
    {
        $this->mock(VercelDomainClient::class, function ($mock) use ($shouldFail) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturnUsing(fn ($d) => strtolower(trim((string) $d)));
            $mock->shouldReceive('getProjectIdentity')->andReturn([
                'project_id' => 'prj_test',
                'team_id' => 'team_test',
            ]);

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
        $this->signInWebAdmin();
        $user = $this->tenant();

        $domain = $this->makeDomain($user, 'delete-me');
        $this->makeDecoyCustomDomain($domain->id, $user);

        Event::fake([TenantActivityOccurred::class]);

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($domain))
            ->assertRedirect();

        $this->assertDatabaseMissing('api_domains_settings', ['id' => $domain->id]);
        $this->assertDatabaseHas('user_custom_domains', ['id' => $domain->id]);
        Event::assertDispatched(TenantActivityOccurred::class, fn (TenantActivityOccurred $event) => $event->action === 'domain.deleted');
    }

    /** @test */
    public function delete_requires_typed_domain_confirmation(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $this->signInWebAdmin();
        $user = $this->tenant();
        $domain = $this->makeDomain($user, 'confirm-required');

        $this->post(route('admin.custom-domain.delete'), [
            'domain_id' => $domain->id,
        ])->assertSessionHasErrors('confirm_domain');

        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
    }

    /** @test */
    public function delete_rejects_mismatched_confirmation_value(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $this->signInWebAdmin();
        $user = $this->tenant();
        $domain = $this->makeDomain($user, 'confirm-mismatch');

        $this->post(route('admin.custom-domain.delete'), [
            'domain_id' => $domain->id,
            'confirm_domain' => 'wrong.example.com',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
    }

    /** @test */
    public function delete_is_blocked_when_shared_project_mutations_are_disabled_locally(): void
    {
        $this->skipIfMissingSchema();
        config(['services.vercel.allow_shared_project_mutations' => false]);
        $this->signInWebAdmin();
        $user = $this->tenant();
        $domain = $this->makeDomain($user, 'shared-blocked');

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.delete'), $this->deletePayload($domain))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
    }

    /** @test */
    public function delete_is_blocked_when_project_identity_does_not_match(): void
    {
        $this->skipIfMissingSchema();
        config(['services.vercel.expected_project_id' => 'prj_expected']);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test*' => Http::response([
                'id' => 'prj_actual',
                'accountId' => 'team_test',
            ], 200),
        ]);

        $this->signInWebAdmin();
        $user = $this->tenant();
        $domain = $this->makeDomain($user, 'identity-mismatch');

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($domain))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
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

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($primary))
            ->assertRedirect();

        $this->assertDatabaseMissing('api_domains_settings', ['id' => $primary->id]);
        $this->assertDatabaseHas('api_domains_settings', [
            'id' => $other->id,
            'primary' => 1,
        ]);
    }

    /** @test */
    public function delete_picks_the_preferred_active_domain_deterministically(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel();
        $this->signInWebAdmin();
        $user = $this->tenant();

        $primary = $this->makeDomain($user, 'primary', true);
        $older = $this->makeDomain($user, 'older', false, 'active');
        $newer = $this->makeDomain($user, 'newer', false, 'active');

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($primary))
            ->assertRedirect();

        $this->assertDatabaseHas('api_domains_settings', ['id' => $newer->id, 'primary' => 1]);
        $this->assertDatabaseHas('api_domains_settings', ['id' => $older->id, 'primary' => 0]);
    }

    /** @test */
    public function delete_keeps_the_row_when_vercel_removal_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->mockVercel(true);
        $this->signInWebAdmin();
        $user = $this->tenant();
        $domain = $this->makeDomain($user, 'vercel-fails');

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($domain))
            ->assertRedirect();

        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
    }

    /** @test */
    public function delete_keeps_primary_when_transport_failure_prevents_vercel_detach(): void
    {
        $this->skipIfMissingSchema();

        $this->mock(VercelDomainClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturnUsing(fn ($d) => strtolower(trim((string) $d)));
            $mock->shouldReceive('getProjectIdentity')->andReturn([
                'project_id' => 'prj_test',
                'team_id' => 'team_test',
            ]);
            $mock->shouldReceive('removeApexAndWww')->andThrow(new ConnectionException('timeout'));
        });

        $this->signInWebAdmin();
        $user = $this->tenant();
        $primary = $this->makeDomain($user, 'transport-primary', true);
        $this->makeDomain($user, 'transport-secondary', false, 'active');

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($primary))
            ->assertRedirect();

        $this->assertDatabaseHas('api_domains_settings', ['id' => $primary->id, 'primary' => 1]);
    }

    /** @test */
    public function delete_cascades_to_www_redirect_removal(): void
    {
        $this->skipIfMissingSchema();

        $removed = [];
        $this->mock(VercelDomainClient::class, function ($mock) use (&$removed) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('normalizeApex')->andReturnUsing(fn ($d) => strtolower(trim((string) $d)));
            $mock->shouldReceive('getProjectIdentity')->andReturn([
                'project_id' => 'prj_test',
                'team_id' => 'team_test',
            ]);
            $mock->shouldReceive('removeApexAndWww')->andReturnUsing(function (string $apex) use (&$removed) {
                $removed[] = $apex;
            });
        });

        $this->signInWebAdmin();
        $user = $this->tenant();
        $domain = $this->makeDomain($user, 'cascade-www');

        $this->post(route('admin.custom-domain.delete'), $this->deletePayload($domain))
            ->assertRedirect();

        $this->assertSame([$domain->custom_name], $removed);
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
            'confirm_domains' => [$first->custom_name, $second->custom_name],
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
            'confirm_domains' => [$first->custom_name, $second->custom_name, 'missing.example.com'],
        ]);

        $this->assertDatabaseHas('api_domains_settings', ['id' => $first->id]);
        $this->assertDatabaseHas('api_domains_settings', ['id' => $second->id]);
    }
}
