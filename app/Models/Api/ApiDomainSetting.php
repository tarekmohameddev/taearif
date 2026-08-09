<?php

namespace App\Models\Api;

use App\Domain\Domain\Models\CustomDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'dns_records',
    ];

    protected $casts = [
        'primary' => 'boolean',
        'ssl' => 'boolean',
        'added_date' => 'date',
        'expires_at' => 'date',
        'auto_renewal' => 'boolean',
        'dns_records' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customDomain()
    {
        return $this->belongsTo(CustomDomain::class, 'custom_domain_id');
    }

    public function scopePreferredActive($query)
    {
        return $query
            ->where('status', 'active')
            ->orderByDesc('primary')
            ->orderByDesc('id');
    }

    /**
     * DNS / nameserver setup instructions for tenants using Vercel NS.
     *
     * @return array{mode: string, nameservers: list<string>, steps: list<string>}
     */
    public static function nameserverInstructions(): array
    {
        $nameservers = array_values(config('services.vercel.nameservers', [
            'ns1.vercel-dns.com',
            'ns2.vercel-dns.com',
        ]));

        return [
            'mode' => 'nameservers',
            'nameservers' => $nameservers,
            'steps' => [
                'At your registrar (e.g. GoDaddy), set custom nameservers to the values above.',
                'Wait for propagation, then click Verify.',
            ],
        ];
    }

    /**
     * @deprecated Prefer nameserverInstructions(); kept for callers expecting a list shape.
     * @return array{mode: string, nameservers: list<string>, steps: list<string>}
     */
    public function getDnsRecords()
    {
        return self::nameserverInstructions();
    }
}
