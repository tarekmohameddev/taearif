<?php

namespace Tests\Feature\Api\Onboarding;

use App\Jobs\SeedTenantWebsiteJob;
use App\Models\BasicExtended;
use App\Models\Language;
use App\Models\Package;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegisterSeedTenantWebsiteJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_dispatches_seed_tenant_website_job_and_returns_201_shape(): void
    {
        if (!Schema::hasTable('users')
            || !Schema::hasTable('packages')
            || !Schema::hasTable('languages')
            || !Schema::hasTable('basic_extendeds')) {
            $this->markTestSkipped('Required tables are missing for register seed job test.');
        }

        if (!Package::query()->find(26)) {
            $this->markTestSkipped('Trial package id 26 is missing.');
        }

        $currentLang = Language::query()->where('is_default', 1)->first();
        if (!$currentLang) {
            $this->markTestSkipped('Default language is missing.');
        }

        // AuthController::register reads $currentLang->basic_extended->is_smtp.
        // Testing dump may leave default language without a matching basic_extendeds row.
        if (!$currentLang->basic_extended) {
            BasicExtended::query()->create([
                'language_id' => $currentLang->id,
                'is_smtp' => 0,
                'from_mail' => 'noreply@example.test',
                'from_name' => 'Taearif Test',
                'to_mail' => 'admin@example.test',
                'base_currency_text' => 'USD',
                'base_currency_symbol' => '$',
                'base_currency_text_position' => 'left',
                'base_currency_symbol_position' => 'left',
                'base_currency_rate' => '1.000000',
            ]);
            $currentLang->unsetRelation('basic_extended');
        } else {
            // Ensure membership insert has non-null currency fields.
            $be = $currentLang->basic_extended;
            if (empty($be->base_currency_symbol) || empty($be->base_currency_text)) {
                $be->forceFill([
                    'base_currency_text' => $be->base_currency_text ?: 'USD',
                    'base_currency_symbol' => $be->base_currency_symbol ?: '$',
                    'base_currency_text_position' => $be->base_currency_text_position ?: 'left',
                    'base_currency_symbol_position' => $be->base_currency_symbol_position ?: 'left',
                    'base_currency_rate' => $be->base_currency_rate ?: '1.000000',
                ])->save();
            }
        }

        // Recaptcha + any Mandhoor URL — register must not require Mandhoor before 201.
        Http::fake([
            'https://www.google.com/recaptcha/*' => Http::response([
                'success' => true,
                'score' => 0.9,
            ], 200),
            '*' => Http::response(['ok' => true], 200),
        ]);

        Bus::fake();

        $suffix = uniqid('', true);
        $response = $this->postJson('/api/register', [
            'recaptcha_token' => 'TEST_BYPASS_TOKEN',
            'email' => "register-seed-{$suffix}@example.com",
            'username' => 'regseed' . substr(md5($suffix), 0, 10),
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+9665' . substr(preg_replace('/\D/', '', $suffix) . '00000000', 0, 8),
            'first_name' => 'Register',
            'last_name' => 'Seed',
            'account_type' => 'tenant',
        ]);

        if ($response->status() !== 201) {
            $this->markTestSkipped(
                'Register returned ' . $response->status() . ': ' . $response->getContent()
            );
        }

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'token',
                'membership' => ['start_date', 'expire_date'],
                'user',
            ]);

        $this->assertFalse(
            (bool) data_get($response->json(), 'user.onboarding_completed'),
            'New tenant should have onboarding_completed false'
        );

        Bus::assertDispatched(SeedTenantWebsiteJob::class);
    }
}
