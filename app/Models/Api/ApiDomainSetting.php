<?php

namespace App\Models\Api;

use App\Models\User;
use App\Domain\Domain\Models\CustomDomain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiDomainSetting extends Model
{
    use HasFactory;

    protected $table = 'api_domains_settings';

    protected $fillable = [
        'user_id',
        'custom_domain_id',
        'name',
        'custom_name',
        'status',
        'primary',
        'ssl',
        'added_date',
        'registrar',
        'expires_at',
        'auto_renewal',
        'dns_records', // Add this
    ];

    protected $casts = [
        'primary' => 'boolean',
        'ssl' => 'boolean',
        'added_date' => 'date',
        'expires_at' => 'date',
        'auto_renewal' => 'boolean',
        'dns_records' => 'array', // Add this
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customDomain()
    {
        return $this->belongsTo(CustomDomain::class, 'custom_domain_id');
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

