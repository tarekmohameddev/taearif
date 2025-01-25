<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStep extends Model
{
    use HasFactory;

    protected $table = 'user_steps'; // Explicitly define the table name if it doesn't follow convention.

    protected $fillable = [
        'user_id',    // Foreign key to users table
        'logo_uploaded',  // Name of the step
        'favicon_uploaded',  // Boolean for completion status
        'website_named',
        'homepage_updated',
        'homepage_about_update',
        'contacts_social_info',
        'banner',
        'sub_pages_upper_image',
        'menu_builder',
        'services',
        'footer',
    ];

    // Define relationships, if needed
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
