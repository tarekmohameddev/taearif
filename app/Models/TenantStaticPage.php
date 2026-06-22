<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Collection;

class TenantStaticPage extends Model
{
    use HasUuids;

    public const DASHBOARD_PAGE_IDS = ['privacy', 'terms', 'profile'];

    protected $fillable = [
        'id', 'user_id', 'page_id', 'components', 'url', 'published_data',
    ];

    protected $casts = [
        'components' => 'array',
        'published_data' => 'array',
    ];

    /**
     * Normalize payload from save API: either a list of components or { components, url? }.
     *
     * @return array{components: array, url: ?string, url_explicit: bool}
     */
    public static function normalizeIncomingPayload(mixed $payload): array
    {
        if (is_array($payload) && array_key_exists('components', $payload)) {
            return [
                'components' => Collection::make($payload['components'] ?? [])
                    ->sortBy('position')
                    ->values()
                    ->all(),
                'url' => array_key_exists('url', $payload) ? $payload['url'] : null,
                'url_explicit' => array_key_exists('url', $payload),
            ];
        }

        $arr = is_array($payload) ? $payload : [];

        return [
            'components' => Collection::make($arr)
                ->sortBy('position')
                ->values()
                ->all(),
            'url' => null,
            'url_explicit' => false,
        ];
    }

    public function publicComponents(): array
    {
        $published = $this->published_data;
        if (is_array($published) && $published !== []) {
            return $published;
        }

        return $this->components ?? [];
    }

    public function toPublicArray(): array
    {
        return [
            'page_id' => $this->page_id,
            'components' => $this->publicComponents(),
            'url' => $this->url,
        ];
    }

    public function hasPublicContent(): bool
    {
        return $this->publicComponents() !== [];
    }
}

