<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'user_home_sections';

    protected $fillable = [
        'user_id',
        "intro_section",
        "featured_services_section",
        "video_section",
        "portfolio_section",
        "why_choose_us_section",
        "counter_info_section",
        "team_members_section",
        "skills_section",
        "testimonials_section",
        "brand_section",
        "blogs_section",
        "faq_section",
        "contact_section",
        "top_footer_section",
        "copyright_section",
        "work_process_section",
        "newsletter_section",
        "featured_section",
        "offer_banner_section",
        "category_section",
        "slider_section",
        "left_offer_banner_section",
        "bottom_offer_banner_section",
        "featured_item_section",
        "new_item_section",
        "toprated_item_section",
        "bestseller_item_section",
        "special_item_section",
        "flashsale_item_section",
        "rooms_section",
        "call_to_action_section_status",
        "featured_courses_section_status",
        "causes_section",
        "job_education_section",
        "featured_properties_section",
        "property_section",
        "project_section",
        "cities_section",
    ];

}
