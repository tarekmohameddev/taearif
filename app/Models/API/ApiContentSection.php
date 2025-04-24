<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class ApiContentSection extends Model
{
    use HasFactory;
    protected $table = 'api_content_sections';

    protected $fillable = [
        'section_id',
        'title',
        'description',
        'icon',
        'path',
        'status',
        'info',
        'badge',
        'lastUpdate',
        'lastUpdateFormatted',
        'count',
        'created_at',
        'updated_at'
    ];
    protected $casts = [
        'status' => 'boolean', // true/false
    ];
    public function getLastUpdateFormattedAttribute()
    {
        return 'آخر تحديث ' . Carbon::parse($this->updated_at)->diffForHumans([
            'parts' => 1,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
            'locale' => 'ar',
        ]);
    }

    public function getUpdatedAgoAttribute()
    {
        return 'آخر تحديث ' . Carbon::parse($this->updated_at)->diffForHumans([
            'parts' => 1,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
            'locale' => 'ar',
        ]);
    }

    public function getCreatedAgoAttribute()
    {
        return 'تم الإنشاء ' . Carbon::parse($this->created_at)->diffForHumans([
            'parts' => 1,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
            'locale' => 'ar',
        ]);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
