<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CRM\Pipedrive;

use App\Domain\CRM\Pipedrive\Services\PipedriveSettingsService;
use App\Models\BasicSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests PipedriveSettingsService by mocking Eloquent's BasicSetting
 * so no real DB connection is required.
 */
class PipedriveSettingsServiceTest extends TestCase
{
    use DatabaseTransactions;
    // -------------------------------------------------------------------------
    // Token masking / credentials DTO — all logic-only, no DB
    // -------------------------------------------------------------------------

    /** @test */
    public function it_masks_api_token_when_set(): void
    {
        $bs = $this->fakeBasicSetting([
            'pipedrive_api_token' => 'super-secret-real-token',
            'pipedrive_base_url' => 'https://company.pipedrive.com',
        ]);

        $service = $this->makeServiceWith($bs);

        $settings = $service->getSettingsForApi();

        $this->assertSame('********', $settings['api_token']);
    }

    /** @test */
    public function it_returns_null_for_api_token_when_not_set(): void
    {
        $bs = $this->fakeBasicSetting(['pipedrive_api_token' => null]);

        $service = $this->makeServiceWith($bs);

        $settings = $service->getSettingsForApi();

        $this->assertNull($settings['api_token']);
    }

    /** @test */
    public function it_exposes_raw_token_via_get_credentials(): void
    {
        $bs = $this->fakeBasicSetting(['pipedrive_api_token' => 'real-token']);

        $service = $this->makeServiceWith($bs);

        $this->assertSame('real-token', $service->getCredentials()->apiToken);
    }

    /** @test */
    public function is_configured_returns_false_when_credentials_missing(): void
    {
        $bs = $this->fakeBasicSetting(['pipedrive_api_token' => null, 'pipedrive_base_url' => null]);

        $service = $this->makeServiceWith($bs);

        $this->assertFalse($service->getCredentials()->isConfigured());
    }

    /** @test */
    public function is_configured_returns_true_when_both_token_and_url_set(): void
    {
        $bs = $this->fakeBasicSetting([
            'pipedrive_api_token' => 'tok',
            'pipedrive_base_url' => 'https://example.pipedrive.com',
        ]);

        $service = $this->makeServiceWith($bs);

        $this->assertTrue($service->getCredentials()->isConfigured());
    }

    /** @test */
    public function can_auto_sync_requires_both_enabled_and_configured(): void
    {
        $bs = $this->fakeBasicSetting([
            'pipedrive_sync_enabled' => true,
            'pipedrive_api_token' => 'tok',
            'pipedrive_base_url' => 'https://example.pipedrive.com',
        ]);

        $this->assertTrue($this->makeServiceWith($bs)->getCredentials()->canAutoSync());
    }

    /** @test */
    public function can_auto_sync_returns_false_when_disabled(): void
    {
        $bs = $this->fakeBasicSetting([
            'pipedrive_sync_enabled' => false,
            'pipedrive_api_token' => 'tok',
            'pipedrive_base_url' => 'https://example.pipedrive.com',
        ]);

        $this->assertFalse($this->makeServiceWith($bs)->getCredentials()->canAutoSync());
    }

    /** @test */
    public function it_strips_trailing_slash_from_base_url(): void
    {
        $bs = $this->fakeBasicSetting(['pipedrive_base_url' => 'https://company.pipedrive.com/']);

        $service = $this->makeServiceWith($bs);

        $this->assertSame('https://company.pipedrive.com', $service->getCredentials()->baseUrl);
    }

    // -------------------------------------------------------------------------
    // updateSettings() — DB write tests; skipped if DB unavailable
    // -------------------------------------------------------------------------

    /** @test */
    public function it_does_not_update_token_when_mask_is_passed(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $this->seedBasicSetting(['pipedrive_api_token' => 'original-token']);
        $service = new PipedriveSettingsService();
        $service->updateSettings(['api_token' => '********']);

        $this->assertSame('original-token', $service->getCredentials()->apiToken);
    }

    /** @test */
    public function it_updates_token_when_new_value_is_passed(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $this->seedBasicSetting(['pipedrive_api_token' => 'old-token']);
        $service = new PipedriveSettingsService();
        $service->updateSettings(['api_token' => 'new-token']);

        $this->assertSame('new-token', $service->getCredentials()->apiToken);
    }

    /** @test */
    public function enabling_sync_without_credentials_does_not_enable(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $this->seedBasicSetting([
            'pipedrive_sync_enabled' => false,
            'pipedrive_api_token' => null,
            'pipedrive_base_url' => null,
        ]);

        $service = new PipedriveSettingsService();
        $service->updateSettings(['enabled' => true]);

        $this->assertFalse($service->getCredentials()->enabled);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a PipedriveSettingsService backed by a fake in-memory BasicSetting
     * (no DB hit — just attribute access via the model's dynamic properties).
     */
    private function makeServiceWith(BasicSetting $model): PipedriveSettingsService
    {
        return new class($model) extends PipedriveSettingsService {
            public function __construct(private BasicSetting $fakeModel) {}

            protected function loadBasicSetting(): ?BasicSetting
            {
                return $this->fakeModel;
            }
        };
    }

    private function fakeBasicSetting(array $attributes): BasicSetting
    {
        $model = new BasicSetting();
        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }
        return $model;
    }

    private function seedBasicSetting(array $attributes): void
    {
        BasicSetting::query()->delete();
        $model = new BasicSetting();
        foreach ($attributes as $key => $value) {
            $model->$key = $value;
        }
        $model->save();
    }

    private function dbAvailable(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return \Illuminate\Support\Facades\Schema::hasTable('basic_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
