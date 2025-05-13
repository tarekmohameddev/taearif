<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Embedding extends Model
{
    protected $fillable = ['text', 'embedding'];
    protected $casts = ['embedding' => 'array'];
}
