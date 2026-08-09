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

    /**
     * Bulk-insert types for a project (skips Eloquent events).
     *
     * @param  list<array<string, mixed>>  $types
     */
    public static function insertManyForProject(int $userId, int $projectId, int $defaultLanguageId, array $types): void
    {
        if ($types === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($types as $type) {
            $rows[] = [
                'user_id' => $userId,
                'project_id' => $projectId,
                'language_id' => $type['language_id'] ?? $defaultLanguageId,
                'title' => $type['title'] ?? null,
                'min_area' => $type['min_area'] ?? null,
                'max_area' => $type['max_area'] ?? null,
                'min_price' => $type['min_price'] ?? null,
                'max_price' => $type['max_price'] ?? null,
                'unit' => $type['unit'] ?? null,
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
