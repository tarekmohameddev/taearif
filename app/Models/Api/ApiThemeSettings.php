<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserTheme;

class ApiThemeSettings extends Model
{
    use HasFactory;
    protected $table = 'api_themes_settings';

    protected $fillable = [
        'theme_id',
        'name',
        'description',
        'thumbnail',
        'category',
        'active',
        'popular',
        'is_free',
        'is_enabled',
        'price',
        'currency',
    ];

    protected $casts = [
        'active' => 'boolean',
        'popular' => 'boolean',
        'is_free' => 'boolean',
        'is_enabled' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Get users who have purchased this theme
     */
    public function userThemes()
    {
        return $this->hasMany(UserTheme::class, 'theme_id', 'theme_id');
    }

    /**
     * Check if theme is free
     */
    public function isFree(): bool
    {
        return $this->is_free === true;
    }

    /**
     * Check if theme is enabled
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === true;
    }

    /**
     * Check if user has access to this theme
     */
    public function userHasAccess($userId): bool
    {
        // Free themes are accessible to everyone
        if ($this->isFree()) {
            return true;
        }

        // Check if user purchased this theme
        return UserTheme::where('user_id', $userId)
            ->where('theme_id', $this->theme_id)
            ->where('status', UserTheme::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Scope to get only enabled themes
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to get only free themes
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope to get only paid themes
     */
    public function scopePaid($query)
    {
        return $query->where('is_free', false)->whereNotNull('price');
    }
}

