<?php

namespace App\Models\Api\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmRequest extends Model
{
	use HasFactory;

	protected $table = 'crm_requests';

	protected $fillable = [
		'user_id',
		'customer_id',
		'stage_id',
		'property_id',
		'property_specifications',
		'position',
	];

	protected $casts = [
		'property_specifications' => 'array',
	];

	/**
	 * Scope queries by tenant (user_id).
	 */
	public function scopeForUser(Builder $q, int $userId): Builder
	{
		return $q->where('user_id', $userId);
	}

	public function customer()
	{
		return $this->belongsTo(\App\Models\ApiCustomer::class, 'customer_id');
	}
}


