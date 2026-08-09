<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\ApiDomainSetting;
use App\Models\Api\FooterSetting;
use App\Models\Api\marketing\MarketingChannel;
use App\Models\User;
use App\Models\User\BasicSetting;
use App\Models\User\RealestateManagement\Property;
use App\Models\UserStep;
use App\Models\WhatsappUser;
use App\Services\SiteSetupProgressService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StepsProgressApiTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach ([
            'users',
            'user_steps',
            'user_basic_settings',
            'api_footer_settings',
            'user_properties',
            'whatsapp_users',
            'marketing_channels',
            'api_domains_settings',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    private function createTenant(array $overrides = []): User
    {
        return User::factory()->tenant()->create(array_merge([
            'email' => 'steps-' . uniqid('', true) . '@example.com',
            'username' => 'steps' . substr(md5(uniqid('', true)), 0, 10),
            'onboarding_completed' => false,
        ], $overrides));
    }

    private function createEmployeeFor(User $tenant, array $overrides = []): User
    {
        return User::factory()->employee()->create(array_merge([
            'tenant_id' => $tenant->id,
            'email' => 'emp-' . uniqid('', true) . '@example.com',
            'username' => 'emp' . substr(md5(uniqid('', true)), 0, 10),
        ], $overrides));
    }

    private function seedPlaceholderContact(User $owner): void
    {
        FooterSetting::create([
            'user_id' => $owner->id,
            'general' => [
                'phone' => '+966 5XXXXXXXX',
                'email' => 'info@example.com',
            ],
            'social' => [],
            'columns' => [],
            'newsletter' => [],
            'style' => [],
            'status' => true,
        ]);

        BasicSetting::create([
            'user_id' => $owner->id,
            'email' => 'info@example.com',
        ]);
    }

    private function seedRealContact(User $owner): void
    {
        FooterSetting::updateOrCreate(
            ['user_id' => $owner->id],
            [
                'general' => [
                    'phone' => '+966 512345678',
                    'email' => 'office@realestate.test',
                ],
                'social' => [],
                'columns' => [],
                'newsletter' => [],
                'style' => [],
                'status' => true,
            ]
        );

        BasicSetting::updateOrCreate(
            ['user_id' => $owner->id],
            ['email' => 'office@realestate.test']
        );
    }

    private function assertProgressShape(array $json): void
    {
        $this->assertArrayHasKey('progress', $json);
        $this->assertArrayHasKey('done', $json);
        $this->assertArrayHasKey('total', $json);
        $this->assertArrayHasKey('headline_key', $json);
        $this->assertArrayHasKey('dismissed', $json);
        $this->assertArrayHasKey('steps', $json);
        $this->assertFalse($json['dismissed']);
        $this->assertSame(5, $json['total']);
        $this->assertIsFloat((float) $json['progress']);
        $this->assertSame(
            (float) $json['done'] / 5,
            (float) $json['progress']
        );

        $ids = array_column($json['steps'], 'id');
        $this->assertSame(
            ['site_identity', 'contact_info', 'first_property', 'integrated_link', 'connect_site'],
            $ids
        );

        foreach ($json['steps'] as $index => $step) {
            $this->assertArrayHasKey('locked', $step);
            $this->assertFalse($step['locked']);
            $this->assertArrayHasKey('href', $step);
            $this->assertArrayHasKey('status', $step);
            $this->assertArrayHasKey('label_ar', $step);
            $this->assertSame($index + 1, $step['order']);

            if ($step['status'] === true) {
                $this->assertNull($step['href']);
            } elseif ($step['id'] !== 'site_identity') {
                $this->assertNotNull($step['href']);
            }
        }

        $this->assertSame(4, $json['steps'][3]['order']);
        $this->assertSame('integrated_link', $json['steps'][3]['id']);
        $this->assertSame(5, $json['steps'][4]['order']);
        $this->assertSame('connect_site', $json['steps'][4]['id']);
    }

    private function stepStatus(array $json, string $id): bool
    {
        foreach ($json['steps'] as $step) {
            if ($step['id'] === $id) {
                return (bool) $step['status'];
            }
        }

        $this->fail("Step {$id} missing from response");
    }

    public function test_get_progress_returns_fe_shape_and_start_headline(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/steps/progress');

        $response->assertOk();
        $json = $response->json();
        $this->assertProgressShape($json);
        $this->assertSame(0, $json['done']);
        $this->assertSame('start', $json['headline_key']);
        $this->assertFalse($this->stepStatus($json, 'site_identity'));
        $this->assertFalse($this->stepStatus($json, 'contact_info'));
    }

    public function test_placeholders_keep_contact_info_incomplete(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant(['onboarding_completed' => false]);
        $this->seedPlaceholderContact($tenant);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertFalse($this->stepStatus($json, 'contact_info'));
        $this->assertFalse($this->stepStatus($json, 'site_identity'));
    }

    public function test_site_identity_from_onboarding_completed(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant(['onboarding_completed' => true]);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'site_identity'));
        $this->assertSame(1, $json['done']);
        $this->assertSame('early', $json['headline_key']);
    }

    public function test_contact_info_from_real_company_phone_and_email(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->seedRealContact($tenant);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'contact_info'));
        $contact = collect($json['steps'])->firstWhere('id', 'contact_info');
        $this->assertNull($contact['href']);
    }

    public function test_first_property_from_draft_property_count(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        Property::create([
            'user_id' => $tenant->id,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'draft',
            'status' => 0,
            'featured_image' => 'test.jpg',
            'property_type' => 'apartment',
        ]);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'first_property'));
    }

    public function test_integrated_link_from_whatsapp_users_active(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        WhatsappUser::create([
            'user_id' => $tenant->id,
            'number' => '966500000001',
            'name' => 'WA',
            'status' => 'active',
            'request_status' => 'active',
        ]);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'integrated_link'));
    }

    public function test_integrated_link_from_marketing_channel_connected(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        MarketingChannel::create([
            'user_id' => $tenant->id,
            'name' => 'WhatsApp',
            'type' => MarketingChannel::TYPE_WHATSAPP,
            'number' => '966500000002',
            'is_connected' => true,
        ]);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'integrated_link'));
    }

    public function test_connect_site_from_active_domain(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'active-' . uniqid('', true) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);
        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'pending-' . uniqid('', true) . '.example.com',
            'status' => 'pending',
            'primary' => false,
            'ssl' => false,
            'added_date' => now(),
        ]);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'connect_site'));
    }

    public function test_pending_domain_does_not_complete_connect_site(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'only-pending-' . uniqid('', true) . '.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);
        Sanctum::actingAs($tenant);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertFalse($this->stepStatus($json, 'connect_site'));
    }

    public function test_headline_key_buckets(): void
    {
        $this->skipIfMissingSchema();

        $service = app(SiteSetupProgressService::class);
        $tenant = $this->createTenant(['onboarding_completed' => true]);
        $this->seedRealContact($tenant);

        $progress = $service->getProgress($tenant);
        $this->assertSame(2, $progress['done']);
        $this->assertSame('mid', $progress['headline_key']);

        Property::create([
            'user_id' => $tenant->id,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'draft',
            'status' => 0,
            'featured_image' => 't.jpg',
            'property_type' => 'apartment',
        ]);
        WhatsappUser::create([
            'user_id' => $tenant->id,
            'number' => '966500000099',
            'name' => 'WA',
            'status' => 'active',
            'request_status' => 'active',
        ]);

        $progress = $service->getProgress($tenant->fresh());
        $this->assertSame(4, $progress['done']);
        $this->assertSame('almost', $progress['headline_key']);

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'done-' . uniqid('', true) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $progress = $service->getProgress($tenant->fresh());
        $this->assertSame(5, $progress['done']);
        $this->assertSame('done', $progress['headline_key']);
        $this->assertSame(1.0, (float) $progress['progress']);
    }

    public function test_employee_sees_owner_progress(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant(['onboarding_completed' => true]);
        $this->seedRealContact($tenant);
        $employee = $this->createEmployeeFor($tenant);

        Sanctum::actingAs($employee);
        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertTrue($this->stepStatus($json, 'site_identity'));
        $this->assertTrue($this->stepStatus($json, 'contact_info'));
        $this->assertSame(2, $json['done']);
    }

    public function test_post_first_property_writes_owner_properties_flag(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/steps/complete', ['step' => 'first_property']);

        $response->assertOk()
            ->assertJsonPath('message', 'Step marked as completed.')
            ->assertJsonStructure(['progress', 'done', 'total', 'headline_key', 'dismissed', 'steps']);

        $this->assertTrue(
            (bool) UserStep::where('user_id', $tenant->id)->value('properties')
        );
        $this->assertProgressShape($response->json());
    }

    public function test_post_legacy_properties_maps_to_first_property_flag(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson('/api/steps/complete', ['step' => 'properties'])
            ->assertOk();

        $this->assertTrue(
            (bool) UserStep::where('user_id', $tenant->id)->value('properties')
        );
    }

    public function test_post_contact_info_writes_contacts_social_info(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson('/api/steps/complete', ['step' => 'contact_info'])
            ->assertOk();

        $this->assertTrue(
            (bool) UserStep::where('user_id', $tenant->id)->value('contacts_social_info')
        );
    }

    public function test_post_noop_steps_do_not_write_user_steps_columns(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        foreach (['site_identity', 'integrated_link', 'connect_site'] as $step) {
            $response = $this->postJson('/api/steps/complete', ['step' => $step]);
            $response->assertOk();
            $this->assertProgressShape($response->json());
        }

        $row = UserStep::where('user_id', $tenant->id)->first();
        if ($row) {
            $this->assertFalse((bool) $row->properties);
            $this->assertFalse((bool) $row->contacts_social_info);
        }
    }

    public function test_post_legacy_steps_return_422(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        foreach (['banner', 'footer', 'homepage_about_update', 'menu_builder', 'projects'] as $step) {
            $this->postJson('/api/steps/complete', ['step' => $step])
                ->assertStatus(422);
        }
    }

    public function test_employee_post_writes_owner_user_steps_row(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $employee = $this->createEmployeeFor($tenant);
        Sanctum::actingAs($employee);

        $this->postJson('/api/steps/complete', ['step' => 'first_property'])
            ->assertOk();

        $this->assertTrue(
            (bool) UserStep::where('user_id', $tenant->id)->value('properties')
        );
        $this->assertFalse(
            UserStep::where('user_id', $employee->id)->where('properties', true)->exists()
        );
    }

    public function test_property_create_syncs_owner_properties_flag_only(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $employee = $this->createEmployeeFor($tenant);

        Property::create([
            'user_id' => $tenant->id,
            'created_by' => $employee->id,
            'price' => 50000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'draft',
            'status' => 0,
            'featured_image' => 'obs.jpg',
            'property_type' => 'apartment',
        ]);

        $this->assertTrue(
            (bool) UserStep::where('user_id', $tenant->id)->value('properties')
        );
        $this->assertFalse(
            UserStep::where('user_id', $employee->id)->where('properties', true)->exists()
        );

        // Early-return path: already true should not throw.
        app(SiteSetupProgressService::class)->syncPropertiesFlag($tenant);
        $this->assertTrue(
            (bool) UserStep::where('user_id', $tenant->id)->value('properties')
        );
    }

    public function test_owner_null_employee_fails_closed_with_403(): void
    {
        $this->skipIfMissingSchema();

        $employee = User::factory()->employee()->create([
            'tenant_id' => 0,
            'email' => 'orphan-' . uniqid('', true) . '@example.com',
            'username' => 'orphan' . substr(md5(uniqid('', true)), 0, 8),
        ]);

        Sanctum::actingAs($employee);

        $this->getJson('/api/steps/progress')->assertStatus(403);
        $this->postJson('/api/steps/complete', ['step' => 'contact_info'])->assertStatus(403);
    }

    public function test_tenant_scoping_does_not_leak_other_owner_progress(): void
    {
        $this->skipIfMissingSchema();

        $ownerA = $this->createTenant(['onboarding_completed' => true]);
        $this->seedRealContact($ownerA);
        Property::create([
            'user_id' => $ownerA->id,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'draft',
            'status' => 0,
            'featured_image' => 'a.jpg',
            'property_type' => 'apartment',
        ]);

        $ownerB = $this->createTenant(['onboarding_completed' => false]);
        Sanctum::actingAs($ownerB);

        $json = $this->getJson('/api/steps/progress')->assertOk()->json();

        $this->assertFalse($this->stepStatus($json, 'site_identity'));
        $this->assertFalse($this->stepStatus($json, 'contact_info'));
        $this->assertFalse($this->stepStatus($json, 'first_property'));
        $this->assertSame(0, $json['done']);
    }

    public function test_complete_onboarding_step_request_enums_match_openapi(): void
    {
        $request = new \App\Http\Requests\Api\Onboarding\CompleteOnboardingStepRequest();
        $rule = $request->rules()['step'];
        $this->assertIsString($rule);
        $this->assertStringContainsString('site_identity', $rule);
        $this->assertStringContainsString('contact_info', $rule);
        $this->assertStringContainsString('first_property', $rule);
        $this->assertStringContainsString('integrated_link', $rule);
        $this->assertStringContainsString('connect_site', $rule);
        $this->assertStringContainsString('properties', $rule);
        $this->assertStringNotContainsString('banner', $rule);
    }
}
