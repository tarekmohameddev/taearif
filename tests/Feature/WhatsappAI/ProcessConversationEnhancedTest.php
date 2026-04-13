<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsappAI;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Modules\WhatsappAI\Jobs\ProcessConversation;
use Tests\TestCase;

/**
 * Testable subclass that bypasses the real OpenAI call.
 */
class FakeProcessConversation extends ProcessConversation
{
    private array $fakeExtraction;

    public function __construct(int $conversationId, array $fakeExtraction)
    {
        parent::__construct($conversationId);
        $this->fakeExtraction = $fakeExtraction;
    }

    protected function analyzeWithAI(string $transcript): array
    {
        // Store the transcript so tests can inspect it
        static::$lastTranscript = $transcript;
        return $this->fakeExtraction;
    }

    public static ?string $lastTranscript = null;
}

class ProcessConversationEnhancedTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        FakeProcessConversation::$lastTranscript = null;
    }

    private function requireTables(): void
    {
        $tables = ['whatsapp_conversations', 'whatsapp_messages', 'users_property_requests'];
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createUser(): int
    {
        return (int) DB::table('users')->insertGetId([
            'first_name'   => 'Test',
            'last_name'    => 'User',
            'email'        => 'test_' . uniqid() . '@example.com',
            'username'     => 'test_' . uniqid(),
            'password'     => bcrypt('password'),
            'account_type' => 'tenant',
            'status'       => 1,
            'active'       => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function createConversation(int $userId, array $overrides = []): WhatsappConversation
    {
        // Find or create a WhatsappUser for this tenant
        $whatsappUserId = DB::table('whatsapp_users')->where('user_id', $userId)->value('id');
        if (!$whatsappUserId) {
            $whatsappUserId = DB::table('whatsapp_users')->insertGetId([
                'user_id'         => $userId,
                'phone_id'        => 'phone_' . uniqid(),
                'number'          => '+966501234567',
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return WhatsappConversation::create(array_merge([
            'user_id'          => $userId,
            'whatsapp_user_id' => $whatsappUserId,
            'customer_phone'   => '+966509' . rand(1000000, 9999999),
            'customer_name'    => 'Test Customer',
            'status'           => 'collecting',
            'message_count'    => 1,
            'last_message_at'  => now()->subMinutes(10),
        ], $overrides));
    }

    private function addMessage(int $conversationId, string $type, string $content): void
    {
        WhatsappMessage::create([
            'conversation_id'     => $conversationId,
            'whatsapp_message_id' => 'msg_' . uniqid(),
            'message_type'        => $type,
            'content'             => $content,
        ]);
    }

    private function baseExtraction(array $overrides = []): array
    {
        return array_merge([
            'is_real_estate_inquiry' => true,
            'inquiry_type'           => 'rent',
            'property_type'          => 'residential',
            'budget_min'             => null,
            'budget_max'             => 3000,
            'currency'               => 'SAR',
            'bedrooms'               => 2,
            'bathrooms'              => 1,
            'area_min'               => null,
            'area_max'               => null,
            'city'                   => 'الرياض',
            'district'               => null,
            'latitude'               => null,
            'longitude'              => null,
            'urgency'                => 'soon',
            'furnished'              => false,
            'summary'                => 'يريد شقة للإيجار.',
        ], $overrides);
    }

    /** @test */
    public function area_is_extracted_and_saved_to_user_property_request(): void
    {
        $this->requireTables();

        if (!Schema::hasColumn('users_property_requests', 'area_from')) {
            $this->markTestSkipped('area_from column not present.');
        }

        $userId = $this->createUser();
        $extraction = $this->baseExtraction(['area_min' => 100, 'area_max' => 150]);

        $conversation = $this->createConversation($userId);
        $this->addMessage($conversation->id, 'text', 'أبي شقة للإيجار في الرياض مساحة 100 إلى 150 متر');

        (new FakeProcessConversation($conversation->id, $extraction))->handle();

        $propertyRequest = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($propertyRequest, 'UserPropertyRequest should be created.');
        $this->assertEquals(100, (int) $propertyRequest->area_from, 'area_from should be 100.');
        $this->assertEquals(150, (int) $propertyRequest->area_to, 'area_to should be 150.');
    }

    /** @test */
    public function region_is_not_hardcoded_to_riyadh(): void
    {
        $this->requireTables();

        $userId = $this->createUser();
        $extraction = $this->baseExtraction([
            'city'         => 'جدة',
            'inquiry_type' => 'buy',
            'property_type'=> 'residential',
        ]);

        $conversation = $this->createConversation($userId);
        $this->addMessage($conversation->id, 'text', 'أبي فيلا في جدة للبيع');

        (new FakeProcessConversation($conversation->id, $extraction))->handle();

        $propertyRequest = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($propertyRequest, 'UserPropertyRequest should be created.');
        $this->assertNotEquals('الرياض', $propertyRequest->region,
            'Region must not be hardcoded to الرياض when city is جدة.');
    }

    /** @test */
    public function location_message_coordinates_are_stored(): void
    {
        $this->requireTables();

        if (!Schema::hasColumn('users_property_requests', 'latitude')) {
            $this->markTestSkipped('latitude column not present.');
        }

        $userId = $this->createUser();
        $extraction = $this->baseExtraction([
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ]);

        $conversation = $this->createConversation($userId);
        $this->addMessage($conversation->id, 'text', 'أبي شقة في هذه المنطقة');
        $this->addMessage($conversation->id, 'location', '[Location: 24.7136, 46.6753]');

        (new FakeProcessConversation($conversation->id, $extraction))->handle();

        $propertyRequest = DB::table('users_property_requests')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($propertyRequest, 'UserPropertyRequest should be created.');
        $this->assertNotNull($propertyRequest->latitude, 'Latitude should be stored.');
        $this->assertEqualsWithDelta(24.7136, (float) $propertyRequest->latitude, 0.001);
        $this->assertEqualsWithDelta(46.6753, (float) $propertyRequest->longitude, 0.001);
    }

    /** @test */
    public function non_text_messages_are_included_in_transcript(): void
    {
        $this->requireTables();

        $userId = $this->createUser();
        // Return non-real-estate so no inquiry is created
        $extraction = ['is_real_estate_inquiry' => false];

        $conversation = $this->createConversation($userId);
        $this->addMessage($conversation->id, 'text', 'هل هذا الحي مناسب؟');
        $this->addMessage($conversation->id, 'image', 'صورة شقة بالنزهة');
        $this->addMessage($conversation->id, 'location', '[Location: 24.7136, 46.6753]');

        (new FakeProcessConversation($conversation->id, $extraction))->handle();

        // The transcript captured by FakeProcessConversation should include all message types
        $transcript = FakeProcessConversation::$lastTranscript;
        $this->assertNotNull($transcript, 'Transcript should have been captured.');
        $this->assertStringContainsString('هل هذا الحي مناسب؟', $transcript);
        $this->assertStringContainsString('صورة شقة بالنزهة', $transcript);
        $this->assertStringContainsString('[Location: 24.7136, 46.6753]', $transcript);
    }

    /** @test */
    public function non_real_estate_conversation_does_not_create_request(): void
    {
        $this->requireTables();

        $userId = $this->createUser();
        $extraction = ['is_real_estate_inquiry' => false];

        $conversation = $this->createConversation($userId);
        $this->addMessage($conversation->id, 'text', 'مرحباً، كيف حالك؟');

        $countBefore = DB::table('users_property_requests')->where('user_id', $userId)->count();

        (new FakeProcessConversation($conversation->id, $extraction))->handle();

        $countAfter = DB::table('users_property_requests')->where('user_id', $userId)->count();
        $this->assertEquals($countBefore, $countAfter, 'No property request should be created for non-real-estate conversation.');
    }
}
