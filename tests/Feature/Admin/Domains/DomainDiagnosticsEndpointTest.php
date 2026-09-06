<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Http\Controllers\Admin\CustomDomainController;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\Feature\Admin\AdminApiTestCase;

/**
 * Guards the diagnostics endpoint's content-negotiation fix: the modal loads the
 * drawer over XHR with a wildcard Accept header, which makes expectsJson() true —
 * so the controller must key off wantsJson() and return the HTML view, returning
 * JSON only when the caller explicitly asks for it.
 */
class DomainDiagnosticsEndpointTest extends AdminApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function xhr_request_with_wildcard_accept_returns_the_html_drawer_not_json(): void
    {
        $domain = $this->makeDomain();

        $request = Request::create("/admin/domain/{$domain->id}/diagnostics", 'GET');
        // Exactly what jQuery.get sends by default.
        $request->headers->set('Accept', '*/*');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(CustomDomainController::class)->diagnostics($request, $domain->id);

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('admin.domains.partials.diagnostics-drawer', $response->name());
    }

    /** @test */
    public function request_that_explicitly_wants_json_still_receives_json(): void
    {
        $domain = $this->makeDomain();

        $request = Request::create("/admin/domain/{$domain->id}/diagnostics", 'GET');
        $request->headers->set('Accept', 'application/json');

        $response = app(CustomDomainController::class)->diagnostics($request, $domain->id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $payload = $response->getData(true);
        $this->assertSame($domain->id, $payload['domain_id'] ?? null);
        $this->assertArrayHasKey('health_code', $payload);
    }

    private function makeDomain(): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'diag-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'diag-' . uniqid('', true) . '.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'health_code' => 'zone_disabled',
                    'last_check_at' => now()->toIso8601String(),
                    'apex_attached' => true,
                    'apex_verified' => true,
                    'account_domain_present' => true,
                    'zone_enabled' => false,
                    'nameservers_ok' => true,
                    'nameserver_check_enabled' => true,
                    'ssl_ready' => false,
                ],
            ],
        ]);
    }
}
