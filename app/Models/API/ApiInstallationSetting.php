<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiInstallationSetting extends Model
{
    use HasFactory;
    protected $table = 'api_installation_settings';

    protected $fillable = [
        'installation_id', 'settings',
    ];
    protected $casts = [
        'settings' => 'array',
    ];
    protected $attributes = [
        'settings' => '{}',
    ];
    public function installation()
    {
        return $this->belongsTo(ApiInstallation::class, 'installation_id');
    }
    public function scopeByInstallation($query, $installationId)
    {
        return $query->where('installation_id', $installationId);
    }
    public function app()
    {
        return $this->installation->app();
    }
    public function user()
    {
        return $this->installation->user();
    }
    public function scopeByUser($query, $userId)
    {
        return $query->whereHas('installation', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }
    public function scopeByApp($query, $appId)
    {
        return $query->whereHas('installation', function ($q) use ($appId) {
            $q->where('app_id', $appId);
        });
    }
    public function scopeByStatus($query, $status)
    {
        return $query->whereHas('installation', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

}
