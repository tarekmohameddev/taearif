<?php

namespace Modules\WhatsappAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForwardWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $method,
        public array $headers = [],
        public array $query = [],
        public ?string $body = null,
        public ?int $timeoutSeconds = null,
    ) {
    }

    public function handle(): void
    {
        $timeout = $this->timeoutSeconds ?? (int) config('whatsappai.webhook_forward_timeout', 5);

        try {
            $request = Http::timeout($timeout)
                ->withHeaders(array_merge($this->headers, [
                    'X-Taearif-Forwarded' => '1',
                ]))
                ->acceptJson();

            $method = strtolower($this->method);
            $url = $this->url;

            if (!empty($this->query)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($this->query);
            }

            if ($method === 'get') {
                $request->get($url);
                return;
            }

            // Mirror the raw JSON/body exactly (Meta signature validation may depend on it)
            $request->withBody($this->body ?? '', $this->headers['Content-Type'] ?? 'application/json')
                ->send(strtoupper($this->method), $url);

        } catch (\Throwable $e) {
            Log::warning('WhatsApp AI Webhook forward failed', [
                'url' => $this->url,
                'method' => $this->method,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

