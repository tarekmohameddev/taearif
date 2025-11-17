<?php

namespace App\Models;

use App\Domain\Domain\Models\CustomDomain;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DomainRenewalPricing extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'domain_renewal_pricings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'custom_domain_id',
        'registrar',
        'period_key',
        'label',
        'years',
        'price',
        'currency',
        'active',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'years' => 'integer',
        'price' => 'decimal:2',
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the custom domain that owns this pricing rule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customDomain()
    {
        return $this->belongsTo(CustomDomain::class, 'custom_domain_id', 'id');
    }

    /**
     * Scope a query to only include active pricing rules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope a query to filter by domain ID.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $domainId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDomain(Builder $query, int $domainId): Builder
    {
        return $query->where('custom_domain_id', $domainId);
    }

    /**
     * Scope a query to filter by registrar.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $registrar
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRegistrar(Builder $query, string $registrar): Builder
    {
        return $query->where('registrar', $registrar);
    }

    /**
     * Scope a query to filter by period key.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $periodKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod(Builder $query, string $periodKey): Builder
    {
        return $query->where('period_key', $periodKey);
    }

    /**
     * Scope a query to only include pricing rules valid for the current date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValidDateRange(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now());
        });
    }
}
