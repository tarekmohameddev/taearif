<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Facades\Purifier;

class ProjectContent extends Model
{
    use HasFactory;

    public $table = "user_project_contents";
    protected $fillable = [
        'user_id',
        'project_id',
        'language_id',
        'title',
        'slug',
        'address',
        'description',
        'meta_keyword',
        'meta_description',
    ];

    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }

    public function description(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Purifier::clean($value, 'youtube'),
        );
    }

    public static function storeProjectContent($userId,  $requstData)
    {
        return self::create([
            'user_id' => $userId,
            'project_id' => $requstData['project_id'],
            'language_id' => $requstData['language_id'],
            'title' => $requstData['title'],
            'slug' => $requstData['title'],
            'address' => $requstData['address'],
            'description' => $requstData['description'],
            'meta_keyword' => $requstData['meta_keyword'],
            'meta_description' => $requstData['meta_description'],
        ]);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

}
