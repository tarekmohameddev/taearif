<?php

namespace App\Models;

use App\Models\Timezone;
use Illuminate\Database\Eloquent\Model;

class BasicSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        "language_id",
        'intro_subtitle',
        'intro_title',
        'intro_text',
        'intro_main_image',
        'team_section_title',
        'team_section_subtitle',
        'feature_section',
        'process_section',
        'templates_section',
        'featured_users_section',
        'pricing_section',
        'partners_section',
        'intro_section',
        'testimonial_section',
        'news_section',
        'top_footer_section',
        'copyright_section',
        'footer_text',
        'copyright_text',
        'footer_logo',
        'maintainance_mode',
        'maintainance_text',
        'maintenance_img',
        'maintenance_status',
        'secret_path',
        'testimonial_image',
        'partners_section_title',
        'partners_section_subtitle',
        'vcard_section',
        'vcard_section_title',
        'vcard_section_subtitle',
        'intro_button_name',
        'intro_button_url',
        'adsense_publisher_id'
    ];

    public function language()
    {
        return $this->belongsTo('App\Models\Language');
    }
    public function timezoneinfo()
    {
        // return $this->belongsTo(Timezone::class,'timezone');
        return $this->belongsTo(Timezone::class, 'timezone_id');
    }

}
