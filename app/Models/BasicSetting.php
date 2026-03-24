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
        'adsense_publisher_id',
        'whatsapp_number',
        'whatsapp_message',
        'whatsapp_status',
        'whatsapp_notifications_enabled',
        'whatsapp_service',
        'meta_access_token',
        'meta_phone_number_id',
        'meta_business_account_id',
        'meta_template_name',
        'meta_template_language',
        'meta_test_template_name',
        'evolution_api_url',
        'evolution_api_key',
        'evolution_instance_name',
        'evolution_phone_number',
        'welcome_message_enabled',
        'welcome_message_text',
        'welcome_message_delay',
        'welcome_message_template',
        'welcome_message_api',
        'subscription_expiration_enabled',
        'subscription_expiration_text',
        'subscription_expiration_days_before',
        'subscription_expiration_template',
        'subscription_expiration_send_time',
        'subscription_expiration_api',
        'subscription_expired_enabled',
        'subscription_expired_text',
        'subscription_expired_template',
        'subscription_expired_send_time',
        'subscription_expired_api',
        'password_reset_enabled',
        'password_reset_text',
        'password_reset_template',
        'password_reset_api',
        'registration_otp_template',
        'otp_max_sends_per_hour',
        'email_password_reset_template'
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
