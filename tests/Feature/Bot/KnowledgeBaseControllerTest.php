<?php

declare(strict_types=1);

namespace Tests\Feature\Bot;

use App\Domain\Ai\Knowledge\EmbeddingService;
use App\Models\AiKnowledgeSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KnowledgeBaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        // Mock EmbeddingService so tests don't call the real OpenAI API
        $this->mock(EmbeddingService::class, function ($mock) {
            $mock->shouldReceive('indexSource')
                ->andReturnUsing(function ($source, $content) {
                    // Simulate creating one chunk
                    \App\Models\AiKnowledgeChunk::create([
                        'source_id'      => $source->id,
                        'user_id'        => $source->user_id,
                        'content'        => substr($content, 0, 100),
                        'chunk_index'    => 0,
                        'content_hash'   => md5($content),
                        'embedding_json' => json_encode([0.1, 0.2, 0.3]),
                        'embedding_model' => 'test',
                        'embedding_dims'  => 3,
                    ]);
                    return 1;
                });
        });
    }

    public function test_it_lists_knowledge_sources(): void
    {
        AiKnowledgeSource::create([
            'user_id'  => $this->user->id,
            'type'     => 'text',
            'name'     => 'Test Source',
            'active'   => true,
        ]);

        $response = $this->getJson('/api/whatsapp/ai/knowledge');
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'name', 'type']]]);
    }

    public function test_it_creates_and_indexes_a_knowledge_source(): void
    {
        $response = $this->postJson('/api/whatsapp/ai/knowledge', [
            'name'    => 'FAQ Document',
            'type'    => 'faq',
            'content' => 'هذه وثيقة اختبار تحتوي على معلومات مفيدة حول العقارات.',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['id', 'name', 'chunk_count']);
        $this->assertDatabaseHas('ai_knowledge_sources', [
            'user_id' => $this->user->id,
            'name'    => 'FAQ Document',
        ]);
    }

    public function test_it_rejects_too_short_content(): void
    {
        $response = $this->postJson('/api/whatsapp/ai/knowledge', [
            'name'    => 'Short',
            'type'    => 'text',
            'content' => 'hi',
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_deletes_a_knowledge_source(): void
    {
        $source = AiKnowledgeSource::create([
            'user_id' => $this->user->id,
            'type'    => 'text',
            'name'    => 'To Delete',
            'active'  => true,
        ]);

        $response = $this->deleteJson('/api/whatsapp/ai/knowledge/' . $source->id);
        $response->assertOk();
        $this->assertDatabaseMissing('ai_knowledge_sources', ['id' => $source->id]);
    }

    public function test_it_cannot_access_other_tenants_source(): void
    {
        $other = \App\Models\User::factory()->create();
        $source = AiKnowledgeSource::create([
            'user_id' => $other->id,
            'type'    => 'text',
            'name'    => 'Other Source',
            'active'  => true,
        ]);

        $response = $this->deleteJson('/api/whatsapp/ai/knowledge/' . $source->id);
        $response->assertNotFound();
    }
}
