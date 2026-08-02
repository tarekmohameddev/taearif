<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\WhatsappAI\Entities\WhatsappConversation;

final class ExportBotGoldenCorpus extends Command
{
    protected $signature = 'ai:export-golden-corpus {--limit=500} {--output=storage/app/ai/golden-corpus-raw.json}';

    protected $description = 'Export real WhatsApp conversations from whatsapp_conversations + whatsapp_messages for golden corpus curation.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $output = (string) $this->option('output');

        $conversations = WhatsappConversation::with('messages')
            ->has('messages', '>=', 3)
            ->orderByDesc('message_count')
            ->limit($limit)
            ->get();

        $corpus = [];

        foreach ($conversations as $conversation) {
            $turns = [];

            foreach ($conversation->messages as $msg) {
                $content = (string) ($msg->content ?? '');
                $type = str_starts_with($content, '[صوتي:') ? 'voice_transcript' : 'text';

                $turns[] = [
                    'role'    => 'customer',
                    'content' => $content,
                    'type'    => $type,
                    'at'      => $msg->created_at?->toIso8601String(),
                ];
            }

            $rawPhone = (string) ($conversation->customer_phone ?? '');
            $masked = $this->maskPhone($rawPhone);

            $corpus[] = [
                'conversation_id' => $conversation->id,
                'user_id'         => $conversation->user_id,
                'customer_phone'  => $masked,
                'turns'           => $turns,
                'extracted_data'  => $conversation->extracted_data ?? null,
                'message_count'   => $conversation->message_count ?? count($turns),
                'ideal_reply'     => null,
            ];
        }

        $outputPath = base_path($output);
        $dir = dirname($outputPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, json_encode($corpus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Exported ' . count($corpus) . ' conversations to ' . $outputPath);

        return self::SUCCESS;
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return $phone;
        }

        $last4 = substr($phone, -4);
        $stars = str_repeat('*', strlen($phone) - 4);

        return $stars . $last4;
    }
}
