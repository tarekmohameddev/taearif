<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectGalleryImg extends Model
{
    use HasFactory;

    public $table = "user_project_gallery_imgs";

    protected $fillable = [
        'user_id',
        'project_id',
        'image',
    ];

    public static function storeGalleryImage($userId, $projectId, $imageName)
    {
        return self::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'image' => $imageName
        ]);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function getImageAttribute($value)
    {
        return asset('storage/' . $value);
    }

}
