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

    /**
     * Bulk-insert specifications for a project (skips Eloquent events).
     *
     * @param  list<array<string, mixed>>  $specifications
     */
    public static function insertManyForProject(int $userId, int $projectId, int $languageId, array $specifications): void
    {
        if ($specifications === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($specifications as $spec) {
            $rows[] = [
                'user_id' => $userId,
                'project_id' => $projectId,
                'language_id' => $languageId,
                'key' => $spec['key'],
                'label' => $spec['label'],
                'value' => $spec['value'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        self::insert($rows);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

}
