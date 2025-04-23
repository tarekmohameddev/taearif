<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    public $table = "user_property_categories";
    protected $guarded = [];

    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }

    public static function storeCategory($userId, $request, $image)
    {
        return self::create([
            'user_id' => $userId,
            'language_id' => $request['language'],
            'name' => $request['name'],
            'slug' => $request['name'],
            'type' => $request['type'],
            'image' => $image,
            'status' => $request['status'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public function updateCategory($request, $image)
    {
        $this->update([
            // 'language_id' => $request['language'],
            'name' => $request['name'],
            'slug' => $request['name'],
            // 'type' => $request['type'],
            'image' => $image,
            'status' => $request['status'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public function properties()
    {
        return $this->hasMany(PropertyContent::class, 'category_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function projects()
    {
        return $this->belongsToMany(
            Project::class,
            'user_property_categories',
            'category_id',
            'project_id'
        );
    }

}
