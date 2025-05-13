<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectFloorplanImg extends Model
{
    use HasFactory;

    public $table = "user_project_floorplan_imgs";

    protected $fillable = [
        'user_id',
        'project_id',
        'image',
    ];

    public static function storeFloorplanImage($userId, $projectId, $imageName)
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

    // public function getImageAttribute($value)
    // {
    //     return asset('storage/' . $value);
    // }



}
