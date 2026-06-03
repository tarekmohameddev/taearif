<?php

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BuildingSlugTest extends TestCase
{
    use DatabaseTransactions;

    public function test_building_auto_generates_slug_on_create(): void
    {
        $user = User::factory()->create(['account_type' => 'tenant']);

        $building = Building::create([
            'user_id' => $user->id,
            'name' => 'برج الاختبار',
        ]);

        $this->assertNotNull($building->fresh()->slug);
        $this->assertStringContainsString('برج', $building->slug);
    }

    public function test_building_keeps_slug_when_name_is_updated(): void
    {
        $user = User::factory()->create(['account_type' => 'tenant']);

        $building = Building::create([
            'user_id' => $user->id,
            'name' => 'Original Name',
        ]);

        $originalSlug = $building->slug;
        $building->update(['name' => 'Updated Name']);

        $this->assertSame($originalSlug, $building->fresh()->slug);
    }
}
