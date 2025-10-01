<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceMode extends Model
{
    /**
     * This is a virtual model used for policy authorization
     * It doesn't correspond to a database table
     */
    protected $table = null;
    
    /**
     * Disable mass assignment protection since this is a virtual model
     */
    protected $guarded = [];
    
    /**
     * Disable timestamps since this is a virtual model
     */
    public $timestamps = false;
}
