<?php

namespace App\Models\User;

use App\Models\Timezone;
use Illuminate\Database\Eloquent\Model;

class BasicSetting extends Model
{
    public $table = "user_basic_settings";

    protected $fillable = [
        'favicon',
        'breadcrumb',
        'timezone',
        'logo',
        'preloader',
        'base_color',
        'secondary_color',
        'accent_color',
        'theme',
        'email',
        'from_name',
        'is_quote',
        'user_id',
        'qr_image',
        'qr_color',
        'qr_size',
        'qr_style',
        'qr_eye_style',
        'qr_margin',
        'qr_text',
        'qr_text_color',
        'qr_text_size',
        'qr_text_x',
        'qr_text_y',
        'qr_inserted_image',
        'qr_inserted_image_size',
        'qr_inserted_image_x',
        'qr_inserted_image_y',
        'qr_type',
        'qr_url',
        'created_at',
        'updated_at',
        'whatsapp_status',
        'whatsapp_number',
        'whatsapp_header_title',
        'whatsapp_popup_status',
        'whatsapp_popup_message',
        'disqus_status',
        'disqus_short_name',
        'analytics_status',
        'measurement_id',
        'pixel_status',
        'pixel_id',
        'tawkto_status',
        'tawkto_direct_chat_link',
        'custom_css',
        'website_title',
        'base_currency_symbol',
        'base_currency_symbol_position',
        'base_currency_text',
        'base_currency_rate',
        'base_currency_text_position',
        'is_recaptcha',
        'google_recaptcha_site_key',
        'google_recaptcha_secret_key',
        'adsense_publisher_id',
        'features_section_image',
        'cv',
        'cv_original',
        'email_verification_status',
        'cookie_alert_status',
        'cookie_alert_text',
        'cookie_alert_button_text',
        'property_country_status',
        'property_state_status',
        'short_description',
        'industry_type',
        'company_name',
        'valLicense',
        'workingHours',
    ];


    public function language()
    {
        return $this->hasMany('App\Models\User\Language', 'user_id');
    }

    public function timezoneinfo()
    {
        // return $this->belongsTo(Timezone::class,'timezone');
        return $this->belongsTo(Timezone::class, 'timezone');
    }
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
