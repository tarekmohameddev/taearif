<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasicExtended extends Model
{

    protected $table = 'basic_extendeds';

    public $timestamps = false;

    protected $fillable = [
        'language_id',
        'cookie_alert_status',
        'cookie_alert_text',
        'cookie_alert_button_text',
        'to_mail',
        'default_language_direction',
        'from_mail',
        'from_name',
        'is_smtp',
        'smtp_host',
        'smtp_port',
        'encryption',
        'smtp_username',
        'smtp_password',
        'base_currency_symbol',
        'base_currency_symbol_position',
        'base_currency_text',
        'base_currency_text_position',
        'base_currency_rate',
        'hero_section_title',
        'hero_section_text',
        'hero_section_button_text',
        'hero_section_button_url',
        'hero_section_video_url',
        'hero_img',
        'timezone',
        'contact_addresses',
        'contact_numbers',
        'contact_mails',
        'is_whatsapp',
        'whatsapp_number',
        'whatsapp_header_title',
        'whatsapp_popup_message',
        'whatsapp_popup',
        'domain_request_success_message',
        'cname_record_section_title',
        'cname_record_section_text',
        'package_features',
        'expiration_reminder',
        'custom_css',
        'custom_js',
        'hero_section_subtitle',
        'hero_section_secound_button_text',
        'hero_section_secound_button_url',
        'hero_img2',
        'hero_img3',
        'hero_img4',
        'hero_img5',
        'email_password_reset_template',
        'welcome_message_template',
        'subscription_expiration_template',
        'subscription_expired_template'
    ];

    public function language() {
        return $this->belongsTo('App\Models\Language');
    }
}
