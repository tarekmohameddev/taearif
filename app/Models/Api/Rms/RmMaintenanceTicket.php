<?php

namespace App\Models\Api\Rms;

use App\Models\Building;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RmMaintenanceTicket extends Model
{
    use SoftDeletes;

    protected $table = 'rm_maintenance_tickets';

    protected $fillable = [
        'user_id', 'rental_id', 'unit_id', 'building_id', 'category', 'priority',
        'title', 'description', 'estimated_cost', 'payer', 'payer_share_percent',
        'status', 'scheduled_date', 'assigned_to_vendor_id', 'attachments_count', 'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    /**
     * The unit (property) this ticket is for — unit-level maintenance (AC, plumbing).
     */
    public function unit()
    {
        return $this->belongsTo(Property::class, 'unit_id');
    }

    /**
     * The building this ticket is for — common-area maintenance (elevator, entrance).
     */
    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }
}
