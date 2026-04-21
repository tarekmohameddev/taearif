<?php

declare(strict_types=1);

namespace App\Models\CustomersHub;

use App\Models\ApiCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IgnoredCustomer extends Model
{
    use HasFactory;

    protected $table = 'customers_hub_ignored_customers';

    protected $fillable = [
        'tenant_user_id',
        'phone_normalized',
        'customer_id',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'tenant_user_id' => 'integer',
        'customer_id'    => 'integer',
        'created_by'     => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }
}
