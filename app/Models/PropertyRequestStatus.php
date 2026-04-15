<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PropertyRequestStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name_ar',
        'name_en',
        'slug',
        'display_order',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Per-tenant workflow statuses (not system defaults). Labels match the former global rows
     * from migration 2026_03_17_000001_update_property_request_statuses_to_new_labels.
     */
    public static function ensureWorkflowStatusesForTenant(int $userId): void
    {
        // Some deployments use a global property_request_statuses table (no user_id column).
        // In that case, per-tenant workflow rows are not supported.
        if (!Schema::hasColumn('property_request_statuses', 'user_id')) {
            return;
        }

        $templates = [
            [
                'slug' => 'in_progress',
                'name_ar' => 'جاري العمل',
                'name_en' => 'In Progress',
                'display_order' => 2,
            ],
            [
                'slug' => 'waiting',
                'name_ar' => 'جاري الانتظار',
                'name_en' => 'Waiting',
                'display_order' => 3,
            ],
        ];

        foreach ($templates as $t) {
            static::query()->firstOrCreate(
                [
                    'user_id' => $userId,
                    'slug' => $t['slug'],
                ],
                [
                    'name_ar' => $t['name_ar'],
                    'name_en' => $t['name_en'],
                    'display_order' => $t['display_order'],
                    'is_active' => true,
                    'is_system' => false,
                ]
            );
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Global defaults (user_id null) plus rows owned by the given tenant.
     */
    public function scopeForTenant($query, int $userId)
    {
        if (!Schema::hasColumn('property_request_statuses', 'user_id')) {
            return $query;
        }

        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }
}

