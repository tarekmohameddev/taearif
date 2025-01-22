<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSpecification extends Model
{
    use HasFactory;
    public $table = "user_project_specifications";

    protected $fillable = [
        'user_id',
        'project_id',
        'language_id',
        'key',
        'label',
        'value',
    ];

    public static function storeSpecification($userID,  $requestData)
    {
        return self::create([
            'user_id' => $userID,
            'project_id' => $requestData['project_id'],
            'language_id' => $requestData['language_id'],
            'key' => $requestData['key'],
            'label' => $requestData['label'],
            'value' => $requestData['value'],
        ]);
    }
}
