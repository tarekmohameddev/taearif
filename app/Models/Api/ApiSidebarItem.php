<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSidebarItem extends Model
{
    use HasFactory;

    protected $table = 'api_sidebar_items';

    protected $fillable = [
        'title',
        'description',
        'icon',
        'path',
        'permission',
        'condition_type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope a query to only include active items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order items by order field.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
