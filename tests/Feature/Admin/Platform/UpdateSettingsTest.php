<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Platform;

use App\Models\BasicExtended;
use App\Models\BasicSetting;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateSettingsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_list_all_platform_settings(): void
    {
        $this->ensureBasicSetting();
        $this->ensureBasicExtended();

        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.platform.settings.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'general' => ['website_title'],
                ],
            ]);
    }

    /** @test */
    public function listing_platform_settings_requires_authentication(): void
    {
        $this->getJson(route('admin.api.platform.settings.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_specific_platform_setting_section(): void
    {
        $basicSetting = $this->ensureBasicSetting();
        $basicSetting->forceFill([
            'website_title' => 'Platform Title',
        ])->save();

        $this->signInAdmin();

        $response = $this->getJson(
            route('admin.api.platform.settings.show', ['section' => 'general'])
        );

        $response->assertOk()
            ->assertJsonPath('data.website_title', 'Platform Title');
    }

    /** @test */
    public function viewing_specific_settings_requires_authentication(): void
    {
        $this->ensureBasicSetting();

        $this->getJson(
            route('admin.api.platform.settings.show', ['section' => 'general'])
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_update_general_settings(): void
    {
        $basicSetting = $this->ensureBasicSetting();
        $basicExtended = $this->ensureBasicExtended();

        $basicSetting->forceFill([
            'website_title' => 'Original Title',
            'email_verification_status' => 0,
            'base_color' => 'ffffff',
        ])->save();

        $basicExtended->forceFill([
            'base_currency_symbol' => '$',
            'base_currency_symbol_position' => 'left',
            'base_currency_text' => 'USD',
            'base_currency_text_position' => 'right',
            'base_currency_rate' => 1.00,
        ])->save();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'general']),
            [
                'website_title' => 'Updated Platform Title',
                'email_verification_status' => true,
                'base_color' => '#123ABC',
                'currency' => [
                    'symbol' => 'SAR',
                    'symbol_position' => 'right',
                    'text' => 'SAR',
                    'text_position' => 'left',
                    'rate' => 3.75,
                ],
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.website_title', 'Updated Platform Title')
            ->assertJsonPath('data.email_verification_status', true)
            ->assertJsonPath('data.base_color', '123ABC')
            ->assertJsonPath('data.currency.symbol', 'SAR')
            ->assertJsonPath('data.currency.symbol_position', 'right')
            ->assertJsonPath('data.currency.text', 'SAR')
            ->assertJsonPath('data.currency.text_position', 'left')
            ->assertJsonPath('data.currency.rate', 3.75);

        $this->assertSame('Updated Platform Title', $basicSetting->fresh()->website_title);
        $this->assertSame(1, (int) $basicSetting->fresh()->email_verification_status);
        $this->assertSame('123ABC', $basicSetting->fresh()->base_color);

        $freshExtended = $basicExtended->fresh();
        $this->assertSame('SAR', $freshExtended->base_currency_symbol);
        $this->assertSame('right', $freshExtended->base_currency_symbol_position);
        $this->assertSame('SAR', $freshExtended->base_currency_text);
        $this->assertSame('left', $freshExtended->base_currency_text_position);
        $this->assertEquals(3.75, (float) $freshExtended->base_currency_rate);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_general_payload(): void
    {
        $this->ensureBasicSetting();
        $this->ensureBasicExtended();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'general']),
            [
                'currency' => 'USD',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['currency']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $response = $this->putJson(
            route('admin.api.platform.settings.update', ['section' => 'general']),
            [
                'website_title' => 'Should Fail',
            ]
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function invalid_section_requests_return_not_found(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            '/api/v1/admin/platform/settings/unsupported',
            []
        );

        $response->assertNotFound();
    }

    protected function ensureBasicSetting(): BasicSetting
    {
        return BasicSetting::query()->firstOr(function () {
            $model = new BasicSetting();
            $model->save();
            return $model;
        });
    }

    protected function ensureBasicExtended(): BasicExtended
    {
        return BasicExtended::query()->firstOr(function () {
            $model = new BasicExtended();
            $model->save();
            return $model;
        });
    }
}

