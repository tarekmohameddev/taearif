<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiDomainSetting extends Model
{
    use HasFactory;

    protected $table = 'api_domains_settings';

    protected $fillable = [
        'user_id',
        'name',
        'custom_name',
        'primary',
        'ssl',
        'added_date',
    ];

    protected $casts = [
        'primary' => 'boolean',
        'ssl' => 'boolean',
        'added_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function getDnsRecords()
    {
        $user = Auth::user();
        return [
        [
            'type' => 'A',
            'name' => '@',
            'value' => '76.76.21.21',
            'ttl' => 3600,
        ],
        [
            'type' => 'CNAME',
            'name' => 'www',
            'value' => $this->user->id . '.taearif.com',
            'ttl' => 3600,
        ],
        ];
    }
}

