<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AiAlias extends Model
{
    protected $table = 'ai_aliases';

    protected $fillable = [
        'alias_type',
        'alias',
        'canonical',
        'occurrence_count',
    ];

    protected $casts = [
        'occurrence_count' => 'integer',
    ];

    /**
     * Upsert an alias, incrementing occurrence_count when already known.
     */
    public static function upsertAlias(string $type, string $alias, string $canonical): self
    {
        /** @var self|null $existing */
        $existing = self::where('alias_type', $type)->where('alias', $alias)->first();

        if ($existing !== null) {
            $existing->increment('occurrence_count');
            if ($existing->canonical !== $canonical) {
                $existing->update(['canonical' => $canonical]);
            }
            return $existing;
        }

        return self::create([
            'alias_type'       => $type,
            'alias'            => $alias,
            'canonical'        => $canonical,
            'occurrence_count' => 1,
        ]);
    }
}
