<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiUserCategory extends Model
{
    use HasFactory;

    protected $table = 'api_user_categories';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_active',
        'icon',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }

    public function properties()
    {
        return $this->hasMany(Property::class, 'category_id', 'id');
    }

    protected static function booted()
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public static function storeCategory(int $userId,array $data)
    {

        $existing = self::where('name', $data['name'])->first();
        if ($existing) {
            return $existing;
        }

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        return self::create($data);
    }


    public function updateCategory(array $data)
    {
        $existing = self::where('name', $data['name'])->where('id', '!=', $this->id)->first();

        if ($existing) {
            return $existing;
        }

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $this->update($data);
        return $this;
    }



}
