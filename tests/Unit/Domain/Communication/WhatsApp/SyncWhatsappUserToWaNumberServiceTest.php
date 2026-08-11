<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\SyncWhatsappUserToWaNumberService;
use App\Models\User;
use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SyncWhatsappUserToWaNumberServiceTest extends TestCase
{
    use DatabaseTransactions;

    private SyncWhatsappUserToWaNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'whatsapp_users', 'wa_numbers'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }

        $this->service = app(SyncWhatsappUserToWaNumberService::class);
    }

    public function test_it_creates_meta_wa_number_from_active_whatsapp_user(): void
    {
        $user = User::factory()->create();
        $wu = WhatsappUser::create([
            'user_id'      => $user->id,
            'number'       => '966501112233',
            'name'         => 'Office Line',
            'status'       => 'active',
            'phone_id'     => 'meta-phone-123',
            'access_token' => 'tok_abc',
            'token'        => 'tok_abc',
            'waba_id'      => 'waba-9',
        ]);

        $waNumber = $this->service->sync($wu);

        $this->assertNotNull($waNumber);
        $this->assertSame('meta', $waNumber->provider);
        $this->assertSame('+966501112233', $waNumber->phone_number);
        $this->assertSame('meta-phone-123', $waNumber->phone_number_id);
        $this->assertSame('active', $waNumber->status);
        $this->assertSame('tok_abc', $waNumber->meta['access_token'] ?? null);
        $this->assertDatabaseHas('wa_numbers', [
            'user_id'         => $user->id,
            'phone_number_id' => 'meta-phone-123',
            'provider'        => 'meta',
        ]);
    }

    public function test_it_updates_existing_meta_wa_number_on_reauth(): void
    {
        $user = User::factory()->create();

        $existing = WaNumber::create([
            'user_id'         => $user->id,
            'provider'        => 'meta',
            'phone_number'    => '+966501112233',
            'phone_number_id' => 'meta-phone-123',
            'name'            => 'Old Name',
            'status'          => 'active',
            'meta'            => ['access_token' => 'old_token'],
        ]);

        $wu = WhatsappUser::create([
            'user_id'      => $user->id,
            'number'       => '+966501112233',
            'name'         => 'New Name',
            'status'       => 'active',
            'phone_id'     => 'meta-phone-123',
            'access_token' => 'new_token',
            'token'        => 'new_token',
        ]);

        $waNumber = $this->service->sync($wu);

        $this->assertNotNull($waNumber);
        $this->assertSame($existing->id, $waNumber->id);
        $this->assertSame('New Name', $waNumber->name);
        $this->assertSame('new_token', $waNumber->meta['access_token'] ?? null);
        $this->assertSame(1, WaNumber::where('user_id', $user->id)->where('phone_number_id', 'meta-phone-123')->count());
    }

    public function test_it_creates_evolution_wa_number_without_meta_tokens(): void
    {
        $user = User::factory()->create();
        $wu = WhatsappUser::create([
            'user_id'  => $user->id,
            'number'   => '+966509998877',
            'name'     => 'Evo Line',
            'status'   => 'active',
            'phone_id' => 'evo-instance-1',
        ]);

        $waNumber = $this->service->sync($wu);

        $this->assertNotNull($waNumber);
        $this->assertSame('evolution', $waNumber->provider);
        $this->assertSame('evo-instance-1', $waNumber->provider_account_id);
        $this->assertSame('active', $waNumber->status);
    }

    public function test_it_deactivates_wa_number_when_whatsapp_user_unlinked(): void
    {
        $user = User::factory()->create();

        WaNumber::create([
            'user_id'         => $user->id,
            'provider'        => 'meta',
            'phone_number'    => '+966501112233',
            'phone_number_id' => 'meta-phone-123',
            'status'          => 'active',
        ]);

        $wu = WhatsappUser::create([
            'user_id'      => $user->id,
            'number'       => '+966501112233',
            'status'       => 'not_linked',
            'phone_id'     => 'meta-phone-123',
            'access_token' => 'tok',
        ]);

        $waNumber = $this->service->sync($wu);

        $this->assertNotNull($waNumber);
        $this->assertSame('inactive', $waNumber->status);
    }

    public function test_it_skips_when_phone_id_missing(): void
    {
        $user = User::factory()->create();
        $wu = WhatsappUser::create([
            'user_id' => $user->id,
            'number'  => '+966501112233',
            'status'  => 'active',
        ]);

        $this->assertNull($this->service->sync($wu));
        $this->assertSame(0, WaNumber::where('user_id', $user->id)->count());
    }
}
