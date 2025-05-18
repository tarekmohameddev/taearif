<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStep extends Model
{
    use HasFactory;

    protected $table = 'user_steps';

    protected $fillable = [
        'user_id',    // Foreign key to users table
        'menu_builder', ///content/menu
        'homepage_about_update', ///content/about
        'banner', ///content/banner
        'footer', ///content/footer
        'projects', //projects
        'properties', //properties
        'logo_uploaded',  // Name of the step
        'favicon_uploaded',  // Boolean for completion status
        'website_named',
        'homepage_updated',
        'contacts_social_info',
        'sub_pages_upper_image',
        'services',
        'user_contact',
        'user_hero_static',
        'user_skill',
        'user_portfolio',
        'user_testimonial',
        'user_counterInformation',
        'user_Brand',
        'user_social',
        'user_whychooseus',
    ];

    protected $casts = [
        'menu_builder' => 'boolean',
        'homepage_about_update' => 'boolean',
        'banner' => 'boolean',
        'footer' => 'boolean',
        'projects' => 'boolean',
        'properties' => 'boolean',
        'logo_uploaded' => 'boolean',
        'favicon_uploaded' => 'boolean',
        'website_named' => 'boolean',
        'homepage_updated' => 'boolean',
        'contacts_social_info' => 'boolean',
        'sub_pages_upper_image' => 'boolean',
        'services' => 'boolean',
        'user_contact' => 'boolean',
        'user_hero_static' => 'boolean',
        'user_skill' => 'boolean',
        'user_portfolio' => 'boolean',
        'user_testimonial' => 'boolean',
        'user_counterInformation' => 'boolean',
        'user_Brand' => 'boolean',
        'user_social' => 'boolean',
        'user_whychooseus' => 'boolean'
    ];
    // Define relationships, if needed
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
