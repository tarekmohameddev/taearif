<?php
namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FooterSetting extends Model
{
    use HasFactory;

    protected $table = 'api_footer_settings';

    protected $fillable = [
        'user_id',
        'general',
        'social',
        'columns',
        'newsletter',
        'style'
    ];

    protected $casts = [
        'general' => 'array',
        'social' => 'array',
        'columns' => 'array',
        'newsletter' => 'array',
        'style' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
