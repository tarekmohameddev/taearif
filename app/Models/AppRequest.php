<?php

namespace App\Models;

use App\Models\User;
use App\Models\Api\ApiApp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppRequest extends Model
{
    use HasFactory;
    protected $table = 'app_requests';
    protected $fillable = ['user_id', 'app_id', 'phone_number', 'token', 'status'];
    protected $casts = [
        'status' => 'string',
        'phone_number' => 'string',
        'token' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    public function app()
    {
        return $this->belongsTo(ApiApp::class, 'app_id');
    }

    public function getStatusAttribute($value)
    {
        return $value === 'pending' ? 'Pending' : ($value === 'approved' ? 'Approved' : 'Rejected');
    }
    public function getStatusColorAttribute()
    {
        return $this->status === 'pending' ? 'warning' : ($this->status === 'approved' ? 'success' : 'danger');
    }
    public function getStatusLabelAttribute()
    {
        return '<span class="badge badge-' . $this->status_color . '">' . $this->status . '</span>';
    }

}
