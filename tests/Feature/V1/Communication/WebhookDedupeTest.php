<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\CommunicationWebhookEvent;
use App\Domain\Communication\Services\WebhookEventJournal;
use App\Domain\Communication\Services\WebhookEventNormalizer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookDedupeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function journal_duplicate_by_provider_event_id_returns_null(): void
    {
        if (! Schema::hasTable('communication_webhook_events')) {
            $this->markTestSkipped('communication_webhook_events table required.');
        }

        $journal = app(WebhookEventJournal::class);
        $payload = ['id' => 'evt-1', 'status' => 'delivered'];

        $first = $journal->journal('whatsapp', 'meta', 'status', 'evt-1', 'wamid.1', $payload, true, true, 1);
        $this->assertNotNull($first);

        $second = $journal->journal('whatsapp', 'meta', 'status', 'evt-1', 'wamid.1', $payload, true, true, 1);
        $this->assertNull($second);

        $this->assertSame(1, CommunicationWebhookEvent::where('provider_event_id', 'evt-1')->count());
    }

    /** @test */
    public function journal_duplicate_by_event_hash_returns_null_when_no_provider_event_id(): void
    {
        if (! Schema::hasTable('communication_webhook_events')) {
            $this->markTestSkipped('communication_webhook_events table required.');
        }

        $journal = app(WebhookEventJournal::class);
        $payload = ['id' => 'msg-1', 'status' => 'sent'];

        $first = $journal->journal('whatsapp', 'meta', 'status', null, 'wamid.1', $payload, true, true, 1);
        $this->assertNotNull($first);

        $second = $journal->journal('whatsapp', 'meta', 'status', null, 'wamid.1', $payload, true, true, 1);
        $this->assertNull($second);
    }
}
