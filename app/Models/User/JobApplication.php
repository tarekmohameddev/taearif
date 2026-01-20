<?php

namespace App\Models\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $table = 'job_applications';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'description',
        'pdf_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
