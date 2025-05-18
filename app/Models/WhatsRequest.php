<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsRequest extends Model
{
    use HasFactory;
    protected $table = 'whats_requestes';
    protected $fillable = [
        'username',
        'phone_number',
        'status',
    ];
}
