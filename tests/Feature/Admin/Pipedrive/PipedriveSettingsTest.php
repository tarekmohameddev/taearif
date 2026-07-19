<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Pipedrive;

use App\Models\BasicSetting;
use Tests\Feature\Admin\AdminApiTestCase;

class PipedriveSettingsTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePipedriveColumns();
    }

    /** @test */
    public function admin_can_get_pipedrive_settings(): void
    {
        $this->ensureBasicSetting();
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.platform.settings.show', ['section' => 'pipedrive']));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'enabled',
                    'api_token',
                    'base_url',
                    'pipeline_id',
                    'stage_id',
                    'deal_title_prefix',
                ],
            ]);
    }

    /** @test */
    public function getting_pipedrive_settings_requires_authentication(): void
    {
        $this->getJson(route('admin.api.platform.settings.show', ['section' => 'pipedrive']))
            ->assertUnauthorized();
    }

    /** @test */
    public function api_token_is_masked_when_set(): void
    {
        $bs = $this->ensureBasicSetting();
        $bs->forceFill([
            'pipedrive_api_token' => 'real-secret-token',
            'pipedrive_base_url' => 'https://company.pipedrive.com',
        ])->save();

        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.platform.settings.show', ['section' => 'pipedrive']));

        $response->assertOk()
            ->assertJsonPath('data.api_token', '********');
    }

    /** @test */
    public function api_token_is_null_when_not_set(): void
    {
        $bs = $this->ensureBasicSetting();
        $bs->forceFill(['pipedrive_api_token' => null])->save();

        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.platform.settings.show', ['section' => 'pipedrive']));

        $response->assertOk()
            ->assertJsonPath('data.api_token', null);
    }

    /** @test */
    public function admin_can_update_pipedrive_settings(): void
    {
        $this->ensureBasicSetting();
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'pipedrive']),
            [
                'enabled' => false,
                'base_url' => 'https://taearif.pipedrive.com',
                'api_token' => 'my-real-token',
                'pipeline_id' => 2,
                'stage_id' => 8,
                'deal_title_prefix' => 'New Website Lead - ',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.base_url', 'https://taearif.pipedrive.com')
            ->assertJsonPath('data.pipeline_id', 2)
            ->assertJsonPath('data.stage_id', 8)
            ->assertJsonPath('data.deal_title_prefix', 'New Website Lead - ')
            ->assertJsonPath('data.api_token', '********');
    }

    /** @test */
    public function updating_with_masked_token_does_not_overwrite_existing_token(): void
    {
        $bs = $this->ensureBasicSetting();
        $bs->forceFill(['pipedrive_api_token' => 'original-token'])->save();

        $this->signInAdmin();

        $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'pipedrive']),
            ['api_token' => '********', 'base_url' => 'https://test.pipedrive.com']
        )->assertOk();

        $this->assertSame('original-token', BasicSetting::first()->pipedrive_api_token);
    }

    /** @test */
    public function enabling_sync_without_credentials_does_not_turn_it_on(): void
    {
        $bs = $this->ensureBasicSetting();
        $bs->forceFill([
            'pipedrive_sync_enabled' => false,
            'pipedrive_api_token' => null,
            'pipedrive_base_url' => null,
        ])->save();

        $this->signInAdmin();

        $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'pipedrive']),
            ['enabled' => true]
        )->assertOk();

        $this->assertFalse((bool) BasicSetting::first()->pipedrive_sync_enabled);
    }

    /** @test */
    public function base_url_validation_rejects_invalid_url(): void
    {
        $this->ensureBasicSetting();
        $this->signInAdmin();

        $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'pipedrive']),
            ['base_url' => 'not-a-url']
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['base_url']);
    }

    /** @test */
    public function updating_settings_requires_authentication(): void
    {
        $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'pipedrive']),
            ['enabled' => true]
        )->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ensureBasicSetting(): BasicSetting
    {
        return BasicSetting::query()->firstOr(function () {
            $model = new BasicSetting();
            $model->save();
            return $model;
        });
    }

    private function ensurePipedriveColumns(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('basic_settings', 'pipedrive_sync_enabled')) {
            $this->markTestSkipped('Pipedrive columns not yet migrated in test DB.');
        }
    }
}
