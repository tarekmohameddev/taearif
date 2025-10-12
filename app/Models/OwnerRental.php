<?php

namespace App\Models;

use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OwnerRental extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'id_number',
        'address',
        'city',
        'password',
        'is_active',
        'last_login_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the main user who created this owner rental.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the properties assigned to this owner rental.
     */
    public function properties()
    {
        return $this->belongsToMany(Property::class, 'owner_rental_property', 'owner_rental_id', 'property_id')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active owner rentals.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if owner rental has access to a specific property.
     */
    public function hasAccessToProperty($propertyId)
    {
        return $this->properties()->where('property_id', $propertyId)->exists();
    }

    /**
     * Get the guard name for this model.
     */
    public function guardName()
    {
        return 'owner-rental';
    }
}

