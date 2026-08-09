<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaTemplate;
use App\Models\WhatsappUser;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class MetaTemplateService
{
    private const GRAPH_API_VERSION = 'v20.0';
    private const GRAPH_API_BASE = 'https://graph.facebook.com';
    private const TEMPLATE_FIELDS = 'id,name,status,category,language,components,namespace';
    private const PER_PAGE = 100;

    /**
     * Sync all Meta message templates for the given tenant into wa_templates.
     * Returns a summary of the sync operation.
     *
     * @return array{synced: int, created: int, updated: int}
     * @throws RuntimeException if the tenant has no WhatsApp connection
     */
    public function syncTemplatesForUser(int $userId): array
    {
        $waUser = WhatsappUser::where('user_id', $userId)
            ->whereNotNull('waba_id')
            ->whereNotNull('access_token')
            ->first();

        if (! $waUser) {
            throw new RuntimeException('WA_NO_WHATSAPP_CONNECTION');
        }

        $metaTemplates = $this->fetchAllFromMeta($waUser);

        $created = 0;
        $updated = 0;

        foreach ($metaTemplates as $metaTemplate) {
            $parsed = $this->parseMetaTemplate($metaTemplate);
            $parsed['user_id'] = $userId;

            $existing = WaTemplate::where('user_id', $userId)
                ->where('meta_template_id', $parsed['meta_template_id'])
                ->first();

            if ($existing) {
                $existing->update($parsed);
                $updated++;
            } else {
                WaTemplate::create($parsed);
                $created++;
            }
        }

        $metaIds = array_column($metaTemplates, 'id');
        if (! empty($metaIds)) {
            WaTemplate::where('user_id', $userId)
                ->whereNotNull('meta_template_id')
                ->whereNotIn('meta_template_id', $metaIds)
                ->delete();
        }

        Log::info('MetaTemplateService: sync completed', [
            'user_id' => $userId,
            'waba_id' => $waUser->waba_id,
            'synced' => count($metaTemplates),
            'created' => $created,
            'updated' => $updated,
        ]);

        return [
            'synced' => count($metaTemplates),
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Fetch all templates from Meta Graph API, handling pagination.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllFromMeta(WhatsappUser $waUser): array
    {
        $templates = [];
        $cursor = null;

        do {
            $params = [
                'fields' => self::TEMPLATE_FIELDS,
                'limit' => self::PER_PAGE,
                'access_token' => $waUser->access_token,
            ];

            if ($cursor !== null) {
                $params['after'] = $cursor;
            }

            $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . '/' . $waUser->waba_id . '/message_templates';

            $response = Http::get($url, $params);

            if (! $response->successful()) {
                $body = $response->json();
                $errorMsg = $body['error']['message'] ?? $response->body();
                Log::error('MetaTemplateService: failed to fetch templates', [
                    'waba_id' => $waUser->waba_id,
                    'status' => $response->status(),
                    'error' => $errorMsg,
                ]);
                throw new RuntimeException('WA_META_API_ERROR: ' . $errorMsg);
            }

            $body = $response->json();
            $data = $body['data'] ?? [];
            $templates = array_merge($templates, $data);

            $cursor = $body['paging']['cursors']['after'] ?? null;
            $hasNext = isset($body['paging']['next']);
        } while ($hasNext && $cursor !== null);

        return $templates;
    }

    /**
     * Transform a single Meta template API response item into wa_templates row data.
     *
     * @param array<string, mixed> $metaData
     * @return array<string, mixed>
     */
    private function parseMetaTemplate(array $metaData): array
    {
        $components = $metaData['components'] ?? [];
        $variables = $this->extractVariables($components);

        return [
            'meta_template_id' => (string) ($metaData['id'] ?? ''),
            'name' => (string) ($metaData['name'] ?? ''),
            'status' => (string) ($metaData['status'] ?? 'PENDING'),
            'category' => (string) ($metaData['category'] ?? ''),
            'language' => (string) ($metaData['language'] ?? ''),
            'components' => $components,
            'variables' => $variables,
            'namespace' => isset($metaData['namespace']) ? (string) $metaData['namespace'] : null,
            'synced_at' => now(),
        ];
    }

    /**
     * Extract variable placeholders (e.g. {{1}}, {{2}}) from template components.
     * Returns an array of variable indices found across all text components.
     *
     * @param array<int, array<string, mixed>> $components
     * @return array<int, int>
     */
    private function extractVariables(array $components): array
    {
        $variables = [];

        foreach ($components as $component) {
            $text = $component['text'] ?? '';
            if (! is_string($text) || $text === '') {
                continue;
            }

            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
            foreach ($matches[1] as $varIndex) {
                $variables[] = (int) $varIndex;
            }
        }

        return array_values(array_unique($variables));
    }
}
