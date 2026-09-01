<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Http\Helpers\MegaMailer;
use App\Models\Api\ApiDomainSetting;
use App\Models\BasicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CustomDomainStatusEmailTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('basic_settings')) {
            $this->markTestSkipped('Missing required DB tables.');
        }
    }

    private function signInWebAdmin(): Admin
    {
        $admin = Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);

        $this->app['auth']->guard('admin')->setUser($admin);

        return $admin;
    }

    private function seedDomain(User $user, string $status = 'pending'): ApiDomainSetting
    {
        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'status-email-' . uniqid('', false) . '.example.com',
            'status' => $status,
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);
    }

    private function ensureBasicSetting(): void
    {
        if (BasicSetting::query()->exists()) {
            return;
        }

        BasicSetting::create([
            'language_id' => 1,
            'website_title' => 'Taearif Test',
        ]);
    }

    /** @test */
    public function active_status_sends_connected_email_when_transitioning_from_pending(): void
    {
        $this->skipIfMissingSchema();
        $this->ensureBasicSetting();
        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
        ]);
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'status-active-' . uniqid('', true) . '@example.com',
        ]);
        $domain = $this->seedDomain($user, 'pending');

        $mailer = Mockery::mock('overload:' . MegaMailer::class);
        $mailer->shouldReceive('mailFromAdmin')
            ->once()
            ->with(Mockery::on(function (array $data) use ($user, $domain) {
                return $data['templateType'] === 'custom_domain_connected'
                    && $data['type'] === 'customDomainConnected'
                    && $data['toMail'] === $user->email
                    && $data['requested_domain'] === $domain->custom_name;
            }));

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.status'), [
                'domain_id' => $domain->id,
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('active', $domain->fresh()->status);
    }

    /** @test */
    public function failed_status_sends_rejected_email_when_transitioning_from_pending(): void
    {
        $this->skipIfMissingSchema();
        $this->ensureBasicSetting();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'status-failed-' . uniqid('', true) . '@example.com',
        ]);
        $domain = $this->seedDomain($user, 'pending');

        $mailer = Mockery::mock('overload:' . MegaMailer::class);
        $mailer->shouldReceive('mailFromAdmin')
            ->once()
            ->with(Mockery::on(function (array $data) use ($user, $domain) {
                return $data['templateType'] === 'custom_domain_rejected'
                    && $data['type'] === 'customDomainRejected'
                    && $data['toMail'] === $user->email
                    && $data['requested_domain'] === $domain->custom_name;
            }));

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.status'), [
                'domain_id' => $domain->id,
                'status' => 'failed',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('failed', $domain->fresh()->status);
    }

    /** @test */
    public function no_email_is_sent_when_status_is_unchanged(): void
    {
        $this->skipIfMissingSchema();
        $this->ensureBasicSetting();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'status-unchanged-' . uniqid('', true) . '@example.com',
        ]);
        $domain = $this->seedDomain($user, 'active');

        $mailer = Mockery::mock('overload:' . MegaMailer::class);
        $mailer->shouldReceive('mailFromAdmin')->never();

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.status'), [
                'domain_id' => $domain->id,
                'status' => 'active',
            ])
            ->assertRedirect();
    }

    /** @test */
    public function pending_status_does_not_send_connected_or_rejected_email(): void
    {
        $this->skipIfMissingSchema();
        $this->ensureBasicSetting();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'status-pending-' . uniqid('', true) . '@example.com',
        ]);
        $domain = $this->seedDomain($user, 'failed');

        $mailer = Mockery::mock('overload:' . MegaMailer::class);
        $mailer->shouldReceive('mailFromAdmin')->never();

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.status'), [
                'domain_id' => $domain->id,
                'status' => 'pending',
            ])
            ->assertRedirect();

        $this->assertSame('pending', $domain->fresh()->status);
    }
}
