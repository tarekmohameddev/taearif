<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class WhatsAppTemplateService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WaTemplate::query()->where('user_id', $userId);

        if (isset($filters['category']) && $filters['category'] !== null && $filters['category'] !== '') {
            $query->where('category', (string) $filters['category']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', strtoupper((string) $filters['status']));
        }
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $templateId): ?WaTemplate
    {
        return WaTemplate::query()
            ->where('user_id', $userId)
            ->find($templateId);
    }

    public function findApprovedForUser(int $userId, int $templateId): ?WaTemplate
    {
        $template = $this->findForUser($userId, $templateId);
        if (! $template || ! $template->is_active || strtoupper((string) $template->status) !== 'APPROVED') {
            return null;
        }

        return $template;
    }

    /** @return array<int, int> */
    public function placeholderIndices(WaTemplate $template): array
    {
        $indices = [];
        $hasBody = false;
        foreach ((array) $template->components as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            if ($type === 'BODY' && is_string($component['text'] ?? null) && trim($component['text']) !== '') $hasBody = true;
            if ($type === 'HEADER' && strtoupper((string) ($component['format'] ?? 'TEXT')) !== 'TEXT') {
                throw new InvalidArgumentException('Media headers require template media parameters and are not supported for automated reminders.');
            }
            if ($type === 'BUTTONS') {
                foreach ((array) ($component['buttons'] ?? []) as $button) {
                    if (preg_match('/\{\{|\}\}/', (string) ($button['url'] ?? '') . (string) ($button['text'] ?? ''))) {
                        throw new InvalidArgumentException('Parameterized buttons are not supported for automated reminders.');
                    }
                }
            }
            $text = $component['text'] ?? '';
            if (! is_string($text) || $text === '') continue;
            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
            if (! $matches[1]) continue;
            if (! in_array($type, ['BODY', 'HEADER'], true)) {
                throw new InvalidArgumentException('Template placeholders in buttons or unsupported components cannot be used for automated reminders.');
            }
            foreach ($matches[1] as $index) $indices[] = (int) $index;
        }

        if (! $hasBody) throw new InvalidArgumentException('Approved template must include a text body for reminder history and preview.');

        return array_values(array_unique($indices));
    }

    /** @param array<string|int, mixed> $variables @return array<int, array<string, mixed>> */
    public function buildTemplateComponentParameters(WaTemplate $template, array $variables): array
    {
        $required = $this->placeholderIndices($template);
        $components = [];
        foreach ((array) $template->components as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            if (! in_array($type, ['BODY', 'HEADER'], true)) continue;
            $text = $component['text'] ?? '';
            if (! is_string($text) || $text === '') continue;
            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
            if (! $matches[1]) continue;
            $parameters = [];
            foreach ($matches[1] as $index) {
                $value = $variables[(string) $index] ?? $variables[(int) $index] ?? $variables[(int) $index - 1] ?? null;
                if (! is_scalar($value) || (string) $value === '') {
                    throw new InvalidArgumentException("Template placeholder {{$index}} is missing a value.");
                }
                $parameters[] = ['type' => 'text', 'text' => (string) $value];
            }
            $components[] = ['type' => strtolower($type), 'parameters' => $parameters];
        }
        if ($required && ! $components) throw new InvalidArgumentException('Approved template has placeholders that cannot be mapped.');
        return $components;
    }

    /** Render the body for API history and preview; provider dispatch still uses the approved template payload. */
    public function renderContent(WaTemplate $template, array $variables = []): string
    {
        foreach ((array) $template->components as $component) {
            if (strtoupper((string) ($component['type'] ?? '')) !== 'BODY' || ! is_string($component['text'] ?? null)) continue;
            return preg_replace_callback('/\{\{(\d+)\}\}/', function ($matches) use ($variables): string {
                $value = $variables[$matches[1]] ?? $variables[(int) $matches[1]] ?? $variables[(int) $matches[1] - 1] ?? null;
                if (! is_scalar($value) || (string) $value === '') throw new InvalidArgumentException("Template placeholder {{$matches[1]}} is missing a value.");
                return (string) $value;
            }, $component['text']);
        }

        throw new InvalidArgumentException('Approved template does not have a text body.');
    }
}
