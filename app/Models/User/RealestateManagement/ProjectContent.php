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
    protected $primaryKey = 'id';
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

    public static function storeProjectContent($userId,  $requestData)
    {
        return self::create([
            'user_id' => $userId,
            'project_id' => $requestData['project_id'],
            'language_id' => $requestData['language_id'],
            'title' => $requestData['title'],
            'slug' => make_slug($requestData['title']),
            'address' => $requestData['address'],
            'description' => $requestData['description'],
            'meta_keyword' => $requestData['meta_keyword'],
            'meta_description' => $requestData['meta_description'],
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
