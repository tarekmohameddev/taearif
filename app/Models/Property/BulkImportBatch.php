<?php

namespace App\Models\Property;

use App\Models\Building;
use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkImportBatch extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'building_id',
        'source',
        'status',
        'publish_status',
        'total',
        'succeeded',
        'failed',
        'preview_data',
        'report',
    ];

    protected $casts = [
        'preview_data' => 'array',
        'report' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
}
