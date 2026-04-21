<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsappAI;

use App\Domain\CustomersHub\Services\IgnoredCustomersService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsappAI\Jobs\ProcessConversation;
use Tests\TestCase;

/**
 * Local stub that bypasses the real OpenAI call.
 */
class FakeProcessConversationForIgnoreTest extends ProcessConversation
{
    private array $fakeExtraction;

    public function __construct(int $conversationId, array $fakeExtraction)
    {
        parent::__construct($conversationId);
        $this->fakeExtraction = $fakeExtraction;
    }

    protected function analyzeWithAI(string $transcript): array
    {
        return $this->fakeExtraction;
    }
}

/**
 * Tests that the WhatsApp-AI ProcessConversation job respects the ignore list.
 */
class ProcessConversationIgnoreListTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        $required = [
            'whatsapp_conversations',
            'whatsapp_messages',
            'users_property_requests',
            'api_customer_inquiry',
            'customers_hub_ignored_customers',
        ];
        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createUserId(): int
    {
        return (int) DB::table('users')->insertGetId([
            'first_name'   => 'Ignore',
            'last_name'    => 'Test',
            'email'        => 'ignore_test_' . uniqid() . '@example.com',
            'username'     => 'ignore_test_' . uniqid(),
            'password'     => bcrypt('password'),
            'account_type' => 'tenant',
            'status'       => 1,
            'active'       => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function createConversationWithMessage(int $userId, string $phone, string $messageContent): int
    {
        $whatsappUserId = DB::table('whatsapp_users')->where('user_id', $userId)->value('id');
        if (!$whatsappUserId) {
            $whatsappUserId = DB::table('whatsapp_users')->insertGetId([
                'user_id'    => $userId,
                'phone_id'   => 'phone_' . uniqid(),
                'number'     => '+966500000000',
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $conversationId = DB::table('whatsapp_conversations')->insertGetId([
            'user_id'          => $userId,
            'whatsapp_user_id' => $whatsappUserId,
            'customer_phone'   => $phone,
            'customer_name'    => 'Ignored Test Customer',
            'status'           => 'collecting',
            'last_message_at'  => now()->subMinutes(10),
            'message_count'    => 1,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('whatsapp_messages')->insert([
            'conversation_id'     => $conversationId,
            'whatsapp_message_id' => 'msg_' . uniqid(),
            'message_type'        => 'text',
            'content'             => $messageContent,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return $conversationId;
    }

    /** @test */
    public function process_conversation_skips_inquiry_and_property_request_for_ignored_phone(): void
    {
        $this->requireTables();

        $userId = $this->createUserId();

        // Add the phone to the ignore list
        app(IgnoredCustomersService::class)->add($userId, '0501234567', null, null, null);

        $conversationId = $this->createConversationWithMessage(
            $userId,
            '966501234567',
            'أريد شراء شقة في الرياض بميزانية 500,000'
        );

        $fakeExtraction = [
            'is_real_estate_inquiry' => true,
            'inquiry_type'           => 'buy',
            'property_type'          => 'apartment',
            'budget_min'             => null,
            'budget_max'             => 500000,
            'currency'               => 'SAR',
            'bedrooms'               => null,
            'bathrooms'              => null,
            'city'                   => 'الرياض',
            'district'               => null,
            'urgency'                => null,
            'furnished'              => null,
            'summary'                => 'يريد شراء شقة في الرياض',
        ];

        $inquiryCountBefore  = DB::table('api_customer_inquiry')->where('user_id', $userId)->count();
        $requestCountBefore  = DB::table('users_property_requests')->where('user_id', $userId)->count();

        $job = new FakeProcessConversationForIgnoreTest($conversationId, $fakeExtraction);
        $job->handle();

        // Nothing should have been created
        $this->assertEquals(
            $inquiryCountBefore,
            DB::table('api_customer_inquiry')->where('user_id', $userId)->count(),
            'No api_customer_inquiry should be created for an ignored phone'
        );
        $this->assertEquals(
            $requestCountBefore,
            DB::table('users_property_requests')->where('user_id', $userId)->count(),
            'No users_property_requests should be created for an ignored phone'
        );

        // Conversation should be archived (not left as collecting)
        $status = DB::table('whatsapp_conversations')->where('id', $conversationId)->value('status');
        $this->assertEquals('archived', $status);
    }

    /** @test */
    public function process_conversation_proceeds_normally_when_phone_is_not_ignored(): void
    {
        $this->requireTables();

        $userId = $this->createUserId();

        // Do NOT add the phone to the ignore list
        $conversationId = $this->createConversationWithMessage(
            $userId,
            '966509999999',
            'أريد استئجار فيلا في جدة'
        );

        $fakeExtraction = [
            'is_real_estate_inquiry' => true,
            'inquiry_type'           => 'rent',
            'property_type'          => 'villa',
            'budget_min'             => null,
            'budget_max'             => null,
            'currency'               => 'SAR',
            'bedrooms'               => null,
            'bathrooms'              => null,
            'city'                   => 'جدة',
            'district'               => null,
            'urgency'                => null,
            'furnished'              => null,
            'summary'                => 'يريد استئجار فيلا في جدة',
        ];

        $requestCountBefore = DB::table('users_property_requests')->where('user_id', $userId)->count();

        $job = new FakeProcessConversationForIgnoreTest($conversationId, $fakeExtraction);
        $job->handle();

        // A property request should have been created
        $this->assertGreaterThan(
            $requestCountBefore,
            DB::table('users_property_requests')->where('user_id', $userId)->count(),
            'A users_property_requests row should be created for a non-ignored phone'
        );
    }
}
