<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectType extends Model
{
    use HasFactory;

    public $table = "user_project_types";

    protected $fillable = [
        'user_id',
        'project_id',
        'language_id',
        'title',
        'min_area',
        'max_area',
        'min_price',
        'max_price',
        'unit',
    ];

    public static function storeProjectType($userId, $requestData)
    {
        return self::create([
            'user_id' => $userId,
            'project_id' => $requestData['project_id'],
            'language_id' => $requestData['language_id'],
            'title' => $requestData['title'],
            'min_area' => $requestData['min_area'],
            'max_area' => $requestData['max_area'],
            'min_price' => $requestData['min_price'],
            'max_price' => $requestData['max_price'],
            'unit' => $requestData['unit'],
        ]);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

}
