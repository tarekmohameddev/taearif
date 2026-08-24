<?php

namespace App\Domain\DataExport\Models;

use App\Domain\Admin\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Data Export/Import Log
 *
 * Audit trail for tenant data export & import operations run from the admin
 * panel. One row per export download or import upload attempt.
 */
class DataExportImportLog extends Model
{
    protected $table = 'data_export_import_logs';

    /** Only created_at exists and is handled by the DB default. */
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'user_id',
        'affected_username',
        'operation',
        'status',
        'file_name',
        'imported_count',
        'updated_count',
        'skipped_count',
        'update_existing',
        'message',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'update_existing' => 'boolean',
        'imported_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public const OPERATION_EXPORT = 'export';
    public const OPERATION_IMPORT = 'import';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    /** The admin who performed the operation. */
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /** The tenant whose data was exported/imported. */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
