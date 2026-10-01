<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\UserStatisticsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserStatisticsServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_all_requested_counters_for_each_user(): void
    {
        foreach (['conversations', 'messages', 'user_properties', 'property_matches'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }

        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $answeredConversation = $this->conversation($firstUser, 'answered');
        $this->message($answeredConversation, $firstUser, 'inbound', '2026-01-01 10:00:00');
        $this->message($answeredConversation, $firstUser, 'inbound', '2026-01-01 10:01:00');
        $this->message($answeredConversation, $firstUser, 'outbound', '2026-01-01 10:02:00');

        $abandonedConversation = $this->conversation($firstUser, 'abandoned');
        $this->message($abandonedConversation, $firstUser, 'inbound', '2026-01-02 10:00:00');

        $secondConversation = $this->conversation($secondUser, 'second-user');
        $this->message($secondConversation, $secondUser, 'inbound', '2026-01-03 10:00:00');

        $this->property($firstUser, 'rent', 'rent');
        $this->property($firstUser, 'sale', 'sale');
        $this->property($firstUser, null, 'rented');
        $this->property($secondUser, 'sale', 'sale');

        $this->match($firstUser, 1, 1);
        $this->match($firstUser, 2, 2);

        $statistics = app(UserStatisticsService::class)->forUsers([$firstUser, $secondUser]);

        $this->assertSame([
            'incoming_messages_count' => 3,
            'replied_messages_count' => 2,
            'abandoned_messages_count' => 1,
            'properties_count' => 3,
            'rental_properties_count' => 2,
            'sale_properties_count' => 1,
            'matches_count' => 2,
        ], $statistics[$firstUser->id]);

        $this->assertSame([
            'incoming_messages_count' => 1,
            'replied_messages_count' => 0,
            'abandoned_messages_count' => 1,
            'properties_count' => 1,
            'rental_properties_count' => 0,
            'sale_properties_count' => 1,
            'matches_count' => 0,
        ], $statistics[$secondUser->id]);
    }

    /** @test */
    public function it_returns_zeroes_for_a_user_without_activity(): void
    {
        foreach (['messages', 'user_properties', 'property_matches'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }

        $user = User::factory()->create();

        $this->assertSame([
            'incoming_messages_count' => 0,
            'replied_messages_count' => 0,
            'abandoned_messages_count' => 0,
            'properties_count' => 0,
            'rental_properties_count' => 0,
            'sale_properties_count' => 0,
            'matches_count' => 0,
        ], app(UserStatisticsService::class)->forUser($user));
    }

    private function conversation(User $user, string $identifier): Conversation
    {
        return Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => $identifier . '-' . $user->id,
            'last_message_at' => now(),
        ]);
    }

    private function message(Conversation $conversation, User $user, string $direction, string $createdAt): void
    {
        DB::table('messages')->insert([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $direction,
            'direction' => $direction,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function property(User $user, ?string $listingPurpose, string $legacyPurpose): void
    {
        DB::table('user_properties')->insert([
            'user_id' => $user->id,
            'purpose' => $legacyPurpose,
            'listing_purpose' => $listingPurpose,
            'type' => 'residential',
            'area' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function match(User $user, int $requestId, int $propertyId): void
    {
        DB::table('property_matches')->insert([
            'user_id' => $user->id,
            'customer_key' => 'customer-' . $requestId,
            'request_type' => 'web',
            'request_id' => $requestId,
            'property_id' => $propertyId,
            'match_score' => 80,
            'database_score' => 50,
            'ai_score' => 30,
            'is_reviewed' => false,
            'is_contacted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
