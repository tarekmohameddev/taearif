<?php

namespace Tests\Feature;

use App\Events\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantWebsiteContactMessageTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'contact_messages'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_can_create_contact_message(): void
    {
        $this->skipIfMissingSchema();
        Event::fake([ContactMessageReceived::class]);

        $tenant = User::factory()->create(['username' => 'tenant1']);

        $payload = [
            'source' => 'contact_form_section',
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'message' => 'Hello, I am interested.',
        ];

        $res = $this->postJson('/api/v1/tenant-website/tenant1/contact-messages', $payload);

        $res->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_read' => false,
                ],
            ]);

        $this->assertDatabaseHas('contact_messages', [
            'tenant_id' => $tenant->id,
            'customer_name' => 'John Doe',
            'source' => 'contact_form_section',
            'status' => 'active',
        ]);

        Event::assertDispatched(ContactMessageReceived::class);
    }

    public function test_requires_at_least_one_contact_field(): void
    {
        $this->skipIfMissingSchema();
        User::factory()->create(['username' => 'tenant1']);

        $res = $this->postJson('/api/v1/tenant-website/tenant1/contact-messages', [
            'source' => 'contact_form_section',
            'message' => 'Hello there',
        ]);

        $res->assertStatus(422);
    }

    public function test_rejects_invalid_source(): void
    {
        $this->skipIfMissingSchema();
        User::factory()->create(['username' => 'tenant1']);

        $res = $this->postJson('/api/v1/tenant-website/tenant1/contact-messages', [
            'source' => 'invalid_source',
            'customer_name' => 'John',
            'message' => 'Hello there',
        ]);

        $res->assertStatus(422);
    }

    public function test_rejects_duplicate_within_five_minutes(): void
    {
        $this->skipIfMissingSchema();
        Event::fake([ContactMessageReceived::class]);

        $tenant = User::factory()->create(['username' => 'tenant1']);

        $payload = [
            'source' => 'contact_us_home_page',
            'customer_email' => 'dup@example.com',
            'message' => 'Same message content',
        ];

        $this->postJson('/api/v1/tenant-website/tenant1/contact-messages', $payload)->assertStatus(201);

        $res = $this->postJson('/api/v1/tenant-website/tenant1/contact-messages', $payload);

        $res->assertStatus(429);
        $this->assertEquals(1, ContactMessage::where('tenant_id', $tenant->id)->count());
    }

    public function test_returns_404_for_unknown_tenant(): void
    {
        $this->skipIfMissingSchema();

        $res = $this->postJson('/api/v1/tenant-website/unknown-tenant/contact-messages', [
            'source' => 'contact_map_section',
            'customer_name' => 'Jane',
            'message' => 'Feedback message',
        ]);

        $res->assertStatus(404);
    }
}
