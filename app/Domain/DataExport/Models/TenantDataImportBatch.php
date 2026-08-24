<?php

namespace App\Domain\DataExport\Models;

use App\Domain\Admin\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDataImportBatch extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'owner_id',
        'admin_id',
        'file_path',
        'original_filename',
        'update_existing',
        'status',
        'result',
        'error',
    ];

    protected $casts = [
        'update_existing' => 'boolean',
        'result' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
