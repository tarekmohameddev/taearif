<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CRM\Pipedrive;

use App\Domain\CRM\Pipedrive\DTOs\PipedriveCredentialsDto;
use App\Domain\CRM\Pipedrive\Exceptions\PipedriveApiException;
use App\Domain\CRM\Pipedrive\Services\PipedriveClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PipedriveClientTest extends TestCase
{
    private PipedriveCredentialsDto $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = new PipedriveCredentialsDto(
            enabled: true,
            apiToken: 'test-api-token-123',
            baseUrl: 'https://company.pipedrive.com',
            pipelineId: 2,
            stageId: 8,
            dealTitlePrefix: 'New Lead - ',
        );
    }

    /** @test */
    public function it_sends_x_api_token_header_not_bearer(): void
    {
        Http::fake([
            '*/api/v2/persons' => Http::response(['data' => ['id' => 42]], 200),
        ]);

        $client = new PipedriveClient($this->credentials);
        $client->createPerson(['name' => 'Ahmed Mohamed']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-token', 'test-api-token-123')
                && !$request->hasHeader('Authorization');
        });
    }

    /** @test */
    public function it_creates_person_successfully(): void
    {
        Http::fake([
            '*/api/v2/persons' => Http::response(['data' => ['id' => 100]], 200),
        ]);

        $client = new PipedriveClient($this->credentials);
        $result = $client->createPerson([
            'name' => 'Ahmed Mohamed',
            'emails' => [['value' => 'ahmed@example.com', 'primary' => true, 'label' => 'work']],
        ]);

        $this->assertSame(100, $result['data']['id']);
    }

    /** @test */
    public function it_creates_organization_successfully(): void
    {
        Http::fake([
            '*/api/v2/organizations' => Http::response(['data' => ['id' => 55]], 200),
        ]);

        $client = new PipedriveClient($this->credentials);
        $result = $client->createOrganization(['name' => 'Acme Corp']);

        $this->assertSame(55, $result['data']['id']);
    }

    /** @test */
    public function it_creates_deal_successfully(): void
    {
        Http::fake([
            '*/api/v2/deals' => Http::response(['data' => ['id' => 200]], 200),
        ]);

        $client = new PipedriveClient($this->credentials);
        $result = $client->createDeal([
            'title' => 'New Lead - Ahmed Mohamed',
            'person_id' => 100,
            'pipeline_id' => 2,
            'stage_id' => 8,
        ]);

        $this->assertSame(200, $result['data']['id']);
    }

    /** @test */
    public function it_throws_pipedrive_api_exception_on_4xx_error(): void
    {
        Http::fake([
            '*/api/v2/persons' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(PipedriveApiException::class);

        $client = new PipedriveClient($this->credentials);
        $client->createPerson(['name' => 'Test']);
    }

    /** @test */
    public function it_exposes_http_status_code_in_exception(): void
    {
        Http::fake([
            '*/api/v2/persons' => Http::response(['error' => 'Not found'], 404),
        ]);

        try {
            $client = new PipedriveClient($this->credentials);
            $client->createPerson(['name' => 'Test']);
            $this->fail('Expected PipedriveApiException was not thrown');
        } catch (PipedriveApiException $e) {
            $this->assertSame(404, $e->getHttpStatusCode());
            $this->assertTrue($e->isClientError());
            $this->assertFalse($e->isAuthError());
        }
    }

    /** @test */
    public function it_identifies_auth_error_correctly(): void
    {
        Http::fake([
            '*/api/v2/persons' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        try {
            $client = new PipedriveClient($this->credentials);
            $client->createPerson(['name' => 'Test']);
        } catch (PipedriveApiException $e) {
            $this->assertTrue($e->isAuthError());
        }
    }

    /** @test */
    public function it_strips_trailing_slash_from_base_url(): void
    {
        Http::fake([
            '*/api/v2/persons' => Http::response(['data' => ['id' => 1]], 200),
        ]);

        $credentialsWithSlash = new PipedriveCredentialsDto(
            enabled: true,
            apiToken: 'token',
            baseUrl: 'https://company.pipedrive.com/',
            pipelineId: 2,
            stageId: 8,
            dealTitlePrefix: null,
        );

        $client = new PipedriveClient($credentialsWithSlash);
        $client->createPerson(['name' => 'Test']);

        Http::assertSent(function ($request) {
            // URL should not have double-slash
            return str_contains($request->url(), 'pipedrive.com/api/v2/persons');
        });
    }

    /** @test */
    public function test_connection_returns_true_on_success(): void
    {
        Http::fake([
            '*/api/v1/users/me' => Http::response(['data' => ['id' => 1]], 200),
        ]);

        $client = new PipedriveClient($this->credentials);
        $this->assertTrue($client->testConnection());
    }

    /** @test */
    public function test_connection_returns_false_on_failure(): void
    {
        Http::fake([
            '*/api/v1/users/me' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $client = new PipedriveClient($this->credentials);
        $this->assertFalse($client->testConnection());
    }
}
