<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RmMaintenanceTicket extends Model
{
    use SoftDeletes;

    protected $table = 'rm_maintenance_tickets';

    protected $fillable = [
        'user_id', 'rental_id', 'category', 'priority', 'title', 'description',
        'estimated_cost', 'payer', 'payer_share_percent', 'status',
        'scheduled_date', 'assigned_to_vendor_id', 'attachments_count', 'notes'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }
}
