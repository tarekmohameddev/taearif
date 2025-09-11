<?php

/*
|--------------------------------------------------------------------------
| User Dashboard Routes
|--------------------------------------------------------------------------
| Routes for authenticated user dashboard and management
|
*/

Route::group(['prefix' => 'user', 'middleware' => ['auth', 'userstatus', 'Demo']], function () {

    // Dashboard and Profile
    Route::get('/dashboard', 'User\UserController@index')->name('user-dashboard');
    Route::get('/profile', 'User\UserController@profile')->name('user-profile');
    Route::post('/profile', 'User\UserController@profileupdate')->name('user-profile-update');
    Route::get('/logout', 'User\Auth\LoginController@logout')->name('user-logout');
    Route::post('/change-status', 'User\UserController@status')->name('user-status');
    
    // Password Management
    Route::get('/change-password', 'User\UserController@changePass')->name('user.changePass');
    Route::post('/profile/updatePassword', 'User\UserController@updatePassword')->name('user.updatePassword');

    // Site Settings
    Route::get('/site-settings', 'User\UserController@sitesettings')->name('site-settings');
    Route::get('/home-page-settings', 'User\UserController@home_page_settings')->name('home-page-settings');
    Route::get('/webstie-settings', 'User\UserController@webstie_settings')->name('webstie.settings');
    Route::get('/show-steps', 'User\UserController@show_steps')->name('view-steps');
    Route::get('/reset', 'User\UserController@resetform')->name('user-reset');
    Route::post('/reset', 'User\UserController@reset')->name('user-reset-submit');
    Route::post('/update-home-setting', 'User\UserController@updateHomeSetting')->name('update.home_setting');

    // Theme and Language
    Route::get('/change-theme', 'User\UserController@changeTheme')->name('user.theme.change');
    Route::get('/rtlcheck/{langid}', 'User\LanguageController@rtlcheck')->name('user.rtlcheck');
    Route::get('/theme/version', 'User\BasicController@themeVersion')->name('user.theme.version');
    Route::post('/theme/update_version', 'User\BasicController@updateThemeVersion')->name('user.theme.update');

    // Payment Log
    Route::get('/payment-log', 'User\PaymentLogController@index')->name('user.payment-log.index');
    Route::get('/gateways-soon', 'User\PaymentLogController@gateways_soon')->name('user.gateways-soon');

    // User Domains & URLs
    Route::group(['middleware' => 'checkUserPermission:Custom Domain'], function () {
        Route::get('/domains', 'User\DomainController@domains')->name('user-domains');
        Route::post('/request/domain', 'User\DomainController@domainrequest')->name('user-domain-request');
    });

    // User Subdomains & URLs
    Route::get('/subdomain', 'User\SubdomainController@subdomain')->name('user-subdomain');

    // Follow and Following
    Route::group(['middleware' => 'checkUserPermission:Follow/Unfollow'], function () {
        Route::get('/follower-list', 'User\FollowerController@follower')->name('user.follower.list');
        Route::get('/following-list', 'User\FollowerController@following')->name('user.following.list');
        Route::get('/follow/{id}', 'User\FollowerController@follow')->name('user.follow');
        Route::get('/unfollow/{id}', 'User\FollowerController@unfollow')->name('user.unfollow');
    });

    // User Features
    Route::get('/features', 'User\FeatureController@index')->name('user.feature.index');
    Route::post('/feature/store', 'User\FeatureController@store')->name('user.feature.store');
    Route::get('/feature/{id}/edit', 'User\FeatureController@edit')->name('user.feature.edit');
    Route::post('/feature/update', 'User\FeatureController@update')->name('user.feature.update');
    Route::post('/feature/image/update', 'User\FeatureController@imageUpdate')->name('user.feature.image_update');
    Route::post('/feature/delete', 'User\FeatureController@delete')->name('user.feature.delete');

    // User Top Offer Banner
    Route::get('/offer-banner', 'User\OfferBannerController@index')->name('user.offerBanner.index');
    Route::post('/offer-banner/store', 'User\OfferBannerController@store')->name('user.offerBanner.store');
    Route::get('/offer-banner/{id}/edit', 'User\OfferBannerController@edit')->name('user.offerBanner.edit');
    Route::post('/offer-banner/update', 'User\OfferBannerController@update')->name('user.offerBanner.update');
    Route::post('/offer-banner/delete', 'User\OfferBannerController@delete')->name('user.offerBanner.delete');

    // Language Management
    Route::get('/languages', 'User\LanguageController@index')->name('user.language.index');
    Route::get('/language/{id}/edit', 'User\LanguageController@edit')->name('user.language.edit');
    Route::get('/language/{id}/edit/keyword', 'User\LanguageController@editKeyword')->name('user.language.editKeyword');
    Route::post('/language/{id}/add/keyword', 'Admin\LanguageController@addKeyword')->name('admin.language.addKeyword');
    Route::post('/language/{id}/update/keyword', 'User\LanguageController@updateKeyword')->name('user.language.updateKeyword');
    Route::post('/language/store', 'User\LanguageController@store')->name('user.language.store');
    Route::post('/language/upload', 'User\LanguageController@upload')->name('user.language.upload');
    Route::post('/language/{id}/uploadUpdate', 'User\LanguageController@uploadUpdate')->name('user.language.uploadUpdate');
    Route::post('/language/{id}/default', 'User\LanguageController@default')->name('user.language.default');
    Route::post('/language/{id}/delete', 'User\LanguageController@delete')->name('user.language.delete');
    Route::post('/language/update', 'User\LanguageController@update')->name('user.language.update');

    // Color and CSS
    Route::get('color', 'User\ColorController@index')->name('user.color.index');
    Route::post('color/update', 'User\ColorController@update')->name('user.color.update');
    Route::get('css', 'User\CssController@index')->name('user.css.index');
    Route::post('css/update', 'User\CssController@update')->name('user.css.update');

    // Favicon and Basic Settings
    Route::get('/favicon', 'User\BasicController@favicon')->name('user.favicon');
    Route::post('/favicon/post', 'User\BasicController@updatefav')->name('user.favicon.update');
    Route::get('/general-settings', 'User\BasicController@generalSettings')->name('user.basic_settings.general-settings');
    Route::post('general-settings/updateinfo', 'User\BasicController@updateInfo')->name('user.general_settings.update_info');
    Route::post('general-settings/update-all/{language}', 'User\BasicController@updateAllSettings')->name('user.general_settings.update_all');

    // Logo and Breadcrumb
    Route::get('/logo', 'User\BasicController@logo')->name('user.logo');
    Route::post('/logo/post', 'User\BasicController@updatelogo')->name('user.logo.update');
    Route::get('/breadcrumb', 'User\BasicController@breadcrumb')->name('user.breadcrumb');
    Route::post('/update_breadcrumb', 'User\BasicController@updateBreadcrumb')->name('user.update_breadcrumb');

    // Plugins
    Route::group(['middleware' => 'checkUserPermission:Plugins'], function () {
        Route::get('/plugins', 'User\BasicController@plugins')->name('user.plugins');
        Route::post('/update-analytics', 'User\BasicController@updateAnalytics')->name('user.update_analytics');
        Route::post('/basic-settings/update-recaptcha', 'User\BasicController@updateRecaptcha')->name('user.basic_settings.update_recaptcha');
        Route::post('/update-whatsapp', 'User\BasicController@updateWhatsApp')->name('user.update_whatsapp');
        Route::post('/update-disqus', 'User\BasicController@updateDisqus')->name('user.update_disqus');
        Route::post('/update-pixel', 'User\BasicController@updatePixel')->name('user.update_pixel');
        Route::post('/update-tawkto', 'User\BasicController@updateTawkto')->name('user.update_tawkto');
    });

    // Contact and Preloader
    Route::get('/contact', 'User\ContactController@index')->name('user.contact');
    Route::post('/contact/update/{language}', 'User\ContactController@update')->name('user.contact.update');
    Route::get('/preloader', 'User\BasicController@preloader')->name('user.preloader');
    Route::post('/preloader/post', 'User\BasicController@updatepreloader')->name('user.preloader.update');

    // SEO
    Route::get('/basic_settings/seo', 'User\BasicController@seo')->name('user.basic_settings.seo');
    Route::post('/basic_settings/update_seo_informations', 'User\BasicController@updateSEO')->name('user.basic_settings.update_seo_informations');

    // Cookie Alert
    Route::get('/cookie-alert', 'User\BasicController@cookieAlert')->name('user.cookie.alert');
    Route::post('/cookie-alert/{langid}/update', 'User\BasicController@updateCookie')->name('user.cookie.update');

    // Mail Templates
    Route::group(['middleware' => 'checkUserPermission:Ecommerce|Hotel Booking|Course Management|Donation Management'], function () {
        Route::get('/edit_mail_template/{id}', 'User\MailTemplateController@editMailTemplate')->name('user.basic_settings.edit_mail_template');
        Route::get('/mail_templates', 'User\MailTemplateController@mailTemplates')->name('user.basic_settings.mail_templates');
        Route::post('/update_mail_template/{id}', 'User\MailTemplateController@updateMailTemplate')->name('user.basic_settings.update_mail_template');
    });

    // Mail Information
    Route::get('/mail/information/subscriber', 'User\SubscriberController@getMailInformation')->name('user.mail.information');
    Route::post('/mail/information/subscriber', 'User\SubscriberController@storeMailInformation')->name('user.mail.subscriber');

    // Menu Builder
    Route::get('/menu-builder', 'User\MenuBuilderController@index')->name('user.menu_builder.index');
    Route::post('/menu-builder/update', 'User\MenuBuilderController@update')->name('user.menu_builder.update');

    // Home Page Management
    Route::get('/home-page-text/edit', 'User\BasicController@homePageTextEdit')->name('user.home.page.text.edit');
    Route::post('/home-page-text/update', 'User\BasicController@homePageTextUpdate')->name('user.home.page.text.update');
    Route::get('/home-page/about', 'User\BasicController@homePageAbout')->name('user.home.page.about');
    Route::post('/home-page/update_about', 'User\BasicController@homePageAboutUpdate')->name('user.home.page.update.about');
    Route::get('/home-page/video', 'User\BasicController@homePageVideo')->name('user.home.page.video');
    Route::post('/home-page/update_video', 'User\BasicController@homePageUpdateVideo')->name('user.home.page.update.video');

    // Call to Action Section
    Route::get('/action-section', 'User\ActionController@index')->name('user.home_page.action_section');
    Route::post('/update-action-section', 'User\ActionController@update')->name('user.home_page.update_action_section');

    // Brand Section
    Route::get('/home_page/brand_section', 'User\BrandSectionController@brandSection')->name('user.home_page.brand_section');
    Route::post('/home_page/brand_section/store_brand', 'User\BrandSectionController@storeBrand')->name('user.home_page.brand_section.store_brand');
    Route::post('/home_page/brand_section/update_brand', 'User\BrandSectionController@updateBrand')->name('user.home_page.brand_section.update_brand');
    Route::post('/home_page/brand_section/delete_brand', 'User\BrandSectionController@deleteBrand')->name('user.home_page.brand_section.delete_brand');

    // Hero Section - Static Version
    Route::get('/home_page/hero/static_version', 'User\HeroStaticController@staticVersion')->name('user.home_page.hero.static_version');
    Route::post('/home_page/hero/static_version/update_static_info/{language}', 'User\HeroStaticController@updateStaticInfo')->name('user.home_page.hero.update_static_info');

    // Hero Section - Slider Version
    Route::get('/home_page/hero/slider_version', 'User\HeroSliderController@sliderVersion')->name('user.home_page.hero.slider_version');
    Route::get('/home_page/hero/slider_version/create_slider', 'User\HeroSliderController@createSlider')->name('user.home_page.hero.create_slider');
    Route::post('/home_page/hero/slider_version/store_slider_info', 'User\HeroSliderController@storeSliderInfo')->name('user.home_page.hero.store_slider_info');
    Route::get('/home_page/hero/slider_version/edit_slider/{id}', 'User\HeroSliderController@editSlider')->name('user.home_page.hero.edit_slider');
    Route::post('/home_page/hero/slider_version/update_slider_info/{id}', 'User\HeroSliderController@updateSliderInfo')->name('user.home_page.hero.update_slider_info');
    Route::post('/home_page/hero/slider_version/delete_slider', 'User\HeroSliderController@deleteSlider')->name('user.home_page.hero.delete_slider');

    // Work Process Section
    Route::get('/home_page/work_process_section', 'User\BasicController@workProcessSection')->name('user.home_page.work_process_section');
    Route::post('/home_page/update_work_process_section/{language}', 'User\BasicController@updateWorkProcessSection')->name('user.home_page.update_work_process_section');
    Route::get('/home_page/work_process_section/create_work_process', 'User\WorkProcessController@create')->name('user.home_page.work_process_section.create_work_process');
    Route::post('/home_page/work_process_section/store_work_process', 'User\WorkProcessController@store')->name('user.home_page.work_process_section.store_work_process');
    Route::get('/home_page/work_process_section/edit_work_process/{id}', 'User\WorkProcessController@edit')->name('user.home_page.work_process_section.edit_work_process');
    Route::post('/home_page/work_process_section/update_work_process/{id}', 'User\WorkProcessController@update')->name('user.home_page.work_process_section.update_work_process');
    Route::post('/home_page/work_process_section/delete_work_process', 'User\WorkProcessController@delete')->name('user.home_page.work_process_section.delete_work_process');

    // Why Choose Us Section
    Route::get('/home_page/why-choose-us', 'User\BasicController@whyChooseUsSection')->name('user.home_page.why_choose_us_section');
    Route::post('/home_page/why-choose-us/item-add', 'User\BasicController@whyChooseUsItemStore')->name('user.home_page.why_choose_us_item_add');
    Route::post('/home_page/why-choose-us/item-update', 'User\BasicController@whyChooseUsItemUpdate')->name('user.home_page.why_choose_us_item_update');
    Route::post('/home_page/why-choose-us/item-delete', 'User\BasicController@whyChooseUsItemDelete')->name('user.home_page.why_choose_us_item_delete');
    Route::post('/home_page/update_why-choose-us/{language}', 'User\BasicController@updateWhyChooseUsSection')->name('user.home_page.update_why_choose_us_section');

    // Sections
    Route::get('/sections', 'User\BasicController@sections')->name('user.sections.index');
    Route::post('/sections/update', 'User\BasicController@updateSection')->name('user.sections.update');

    // Social Media
    Route::get('/social', 'User\SocialController@index')->name('user.social.index');
    Route::post('/social/store', 'User\SocialController@store')->name('user.social.store');
    Route::get('/social/{id}/edit', 'User\SocialController@edit')->name('user.social.edit');
    Route::post('/social/update', 'User\SocialController@update')->name('user.social.update');
    Route::post('/social/delete', 'User\SocialController@delete')->name('user.social.delete');

    // Team Management
    Route::group(['middleware' => 'checkUserPermission:Team'], function () {
        Route::get('team_section', 'User\BasicController@teamSection')->name('user.team_section');
        Route::post('update_team_section/{language}', 'User\BasicController@updateTeamSection')->name('user.update_team_section');
        Route::get('team_section/create_member', 'User\MemberController@createMember')->name('user.team_section.create_member');
        Route::post('team_section/store_member', 'User\MemberController@storeMember')->name('user.team_section.store_member');
        Route::get('team_section/edit_member/{id}', 'User\MemberController@editMember')->name('user.team_section.edit_member');
        Route::post('team_section/update_member/{id}', 'User\MemberController@updateMember')->name('user.team_section.update_member');
        Route::post('team_section/delete_member', 'User\MemberController@deleteMember')->name('user.team_section.delete_member');
        Route::post('team_section/member/featured', 'User\MemberController@featured')->name('user.team_section.member.feature');
    });

    // FAQ Management
    Route::get('/faq_management', 'User\FAQController@index')->name('user.faq_management');
    Route::post('/faq_management/store_faq', 'User\FAQController@store')->name('user.faq_management.store_faq');
    Route::post('/faq_management/update_faq', 'User\FAQController@update')->name('user.faq_management.update_faq');
    Route::post('/faq_management/delete_faq', 'User\FAQController@delete')->name('user.faq_management.delete_faq');
    Route::post('/faq_management/bulk_delete_faq', 'User\FAQController@bulkDelete')->name('user.faq_management.bulk_delete_faq');

    // Summernote image upload
    Route::post('/summernote/upload', 'Admin\SummernoteController@upload')->name('user.summernote.upload');

    // Blog Management
    Route::group(['middleware' => 'checkUserPermission:Blog'], function () {
        Route::get('/blog-categories', 'User\BlogCategoryController@index')->name('user.blog.category.index');
        Route::post('/blog-category/store', 'User\BlogCategoryController@store')->name('user.blog.category.store');
        Route::post('/blog-category/update', 'User\BlogCategoryController@update')->name('user.blog.category.update');
        Route::post('/blog-category/delete', 'User\BlogCategoryController@delete')->name('user.blog.category.delete');
        Route::post('/blog-category/bulk-delete', 'User\BlogCategoryController@bulkDelete')->name('user.blog.category.bulk.delete');

        Route::get('/blogs', 'User\BlogController@index')->name('user.blog.index');
        Route::post('/blog/upload', 'User\BlogController@upload')->name('user.blog.upload');
        Route::post('/blog/store', 'User\BlogController@store')->name('user.blog.store');
        Route::get('/blog/{id}/edit', 'User\BlogController@edit')->name('user.blog.edit');
        Route::post('/blog/update', 'User\BlogController@update')->name('user.blog.update');
        Route::post('/blog/{id}/uploadUpdate', 'User\BlogController@uploadUpdate')->name('user.blog.uploadUpdate');
        Route::post('/blog/delete', 'User\BlogController@delete')->name('user.blog.delete');
        Route::post('/blog/bulk-delete', 'User\BlogController@bulkDelete')->name('user.blog.bulk.delete');
        Route::get('/blog/{langid}/getcats', 'User\BlogController@getcats')->name('user.blog.getcats');
    });

    // Skills Management
    Route::group(['middleware' => 'checkUserPermission:Skill'], function () {
        Route::get('/skills', 'User\SkillController@index')->name('user.skill.index');
        Route::post('/skill/upload', 'User\SkillController@upload')->name('user.skill.upload');
        Route::post('/skill/store', 'User\SkillController@store')->name('user.skill.store');
        Route::get('/skill/{id}/edit', 'User\SkillController@edit')->name('user.skill.edit');
        Route::post('/skill/update', 'User\SkillController@update')->name('user.skill.update');
        Route::post('/skill/{id}/uploadUpdate', 'User\SkillController@uploadUpdate')->name('user.skill.uploadUpdate');
        Route::post('/skill/delete', 'User\SkillController@delete')->name('user.skill.delete');
        Route::post('/skill/bulk-delete', 'User\SkillController@bulkDelete')->name('user.skill.bulk.delete');
    });

    // Counter Information
    Route::group(['middleware' => 'checkUserPermission:Counter Information'], function () {
        Route::get('/counter-informations', 'User\CounterInformationController@index')->name('user.counter-information.index');
        Route::post('/counter-information/store', 'User\CounterInformationController@store')->name('user.counter-information.store');
        Route::get('/counter-information/{id}/edit', 'User\CounterInformationController@edit')->name('user.counter-information.edit');
        Route::post('/counter-information/update', 'User\CounterInformationController@update')->name('user.counter-information.update');
        Route::post('/counter-information/delete', 'User\CounterInformationController@delete')->name('user.counter-information.delete');
        Route::post('/counter-information/bulk-delete', 'User\CounterInformationController@bulkDelete')->name('user.counter-information.bulk.delete');
    });

    // Portfolio Management
    Route::group(['middleware' => 'checkUserPermission:Portfolio'], function () {
        Route::get('/portfolio-categories', 'User\PortfolioCategoryController@index')->name('user.portfolio.category.index');
        Route::post('/portfolio-category/store', 'User\PortfolioCategoryController@store')->name('user.portfolio.category.store');
        Route::post('/portfolio-category/update', 'User\PortfolioCategoryController@update')->name('user.portfolio.category.update');
        Route::post('/portfolio-category/delete', 'User\PortfolioCategoryController@delete')->name('user.portfolio.category.delete');
        Route::post('/portfolio-category/bulk-delete', 'User\PortfolioCategoryController@bulkDelete')->name('user.portfolio.category.bulk.delete');
        Route::post('/portfolio-category/featured', 'User\PortfolioCategoryController@makeFeatured')->name('user.portfolio.category.makeFeatured');

        Route::get('/portfolios', 'User\PortfolioController@index')->name('user.portfolio.index');
        Route::post('/portfolio/store', 'User\PortfolioController@store')->name('user.portfolio.store');
        Route::post('/portfolio/sliderstore', 'User\PortfolioController@sliderstore')->name('user.portfolio.sliderstore');
        Route::post('/portfolio/sliderupdate', 'User\PortfolioController@sliderupdate')->name('user.portfolio.sliderupdate');
        Route::post('/portfolio/sliderrmv', 'User\PortfolioController@sliderrmv')->name('user.portfolio.sliderrmv');
        Route::get('/portfolio/{id}/edit', 'User\PortfolioController@edit')->name('user.portfolio.edit');
        Route::get('/portfolio/{id}/images', 'User\PortfolioController@images')->name('user.portfolio.images');
        Route::post('/portfolio/update', 'User\PortfolioController@update')->name('user.portfolio.update');
        Route::post('/portfolio/delete', 'User\PortfolioController@delete')->name('user.portfolio.delete');
        Route::post('/portfolio/bulk-delete', 'User\PortfolioController@bulkDelete')->name('user.portfolio.bulk.delete');
        Route::post('/portfolio/featured', 'User\PortfolioController@featured')->name('user.portfolio.featured');
        Route::get('/portfolio/{langid}/getcats', 'User\PortfolioController@getcats')->name('user.portfolio.getcats');
    });

    // Services Management
    Route::group(['middleware' => 'checkUserPermission:Service'], function () {
        Route::get('/services', 'User\ServiceController@index')->name('user.services.index');
        Route::post('/service/store', 'User\ServiceController@store')->name('user.service.store');
        Route::get('/service/{id}/edit', 'User\ServiceController@edit')->name('user.service.edit');
        Route::post('/service/update', 'User\ServiceController@update')->name('user.service.update');
        Route::post('/service/delete', 'User\ServiceController@delete')->name('user.service.delete');
        Route::post('/service/bulk-delete', 'User\ServiceController@bulkDelete')->name('user.service.bulk.delete');
        Route::post('service/featured', 'User\ServiceController@featured')->name('user.service.feature');
    });

    // Testimonials Management
    Route::group(['middleware' => 'checkUserPermission:Testimonial'], function () {
        Route::get('/testimonials', 'User\TestimonialController@index')->name('user.testimonials.index');
        Route::post('/testimonial/store', 'User\TestimonialController@store')->name('user.testimonial.store');
        Route::get('/testimonial/{id}/edit', 'User\TestimonialController@edit')->name('user.testimonial.edit');
        Route::post('/testimonial/update', 'User\TestimonialController@update')->name('user.testimonial.update');
        Route::post('/testimonial/delete', 'User\TestimonialController@delete')->name('user.testimonial.delete');
        Route::post('/testimonial/bulk-delete', 'User\TestimonialController@bulkDelete')->name('user.testimonial.bulk.delete');
    });

    // Job Experience Management
    Route::get('/job-experiences', 'User\JobExperienceController@index')->name('user.job.experiences.index');
    Route::post('/job-experience/store', 'User\JobExperienceController@store')->name('user.job.experience.store');
    Route::get('/job-experience/{id}/edit', 'User\JobExperienceController@edit')->name('user.job.experience.edit');
    Route::post('/job-experience/update', 'User\JobExperienceController@update')->name('user.job.experience.update');
    Route::post('/job-experience/delete', 'User\JobExperienceController@delete')->name('user.job.experience.delete');
    Route::post('/job-experience/bulk-delete', 'User\JobExperienceController@bulkDelete')->name('user.job.experience.bulk.delete');

    // Educational Experience Management
    Route::get('/experiences', 'User\EducationController@index')->name('user.experience.index');
    Route::post('/experience/upload', 'User\EducationController@upload')->name('user.experience.upload');
    Route::post('/experience/store', 'User\EducationController@store')->name('user.experience.store');
    Route::get('/experience/{id}/edit', 'User\EducationController@edit')->name('user.experience.edit');
    Route::post('/experience/update', 'User\EducationController@update')->name('user.experience.update');
    Route::post('/experience/{id}/uploadUpdate', 'User\EducationController@uploadUpdate')->name('user.experience.uploadUpdate');
    Route::post('/experience/delete', 'User\EducationController@delete')->name('user.experience.delete');
    Route::post('/experience/bulk-delete', 'User\EducationController@bulkDelete')->name('user.experience.bulk.delete');

    // Package and Plan Management
    Route::get('/package-list', 'User\BuyPlanController@index')->name('user.plan.extend.index');
    Route::get('/package/checkout/{package_id}', 'User\BuyPlanController@checkout')->name('user.plan.extend.checkout');
    Route::post('/package/checkout', 'User\UserCheckoutController@checkout')->name('user.plan.checkout');

    // Footer Management
    Route::get('/footer/text', 'User\FooterController@footerText')->name('user.footer.text');
    Route::post('/footer/update_footer_info/{language}', 'User\FooterController@updateFooterInfo')->name('user.footer.update_footer_info');
    Route::post('/footer/update_footer_info_quicklink/{language}', 'User\FooterController@updateFooterInfo_QuickLink')->name('user.footer.update_footer_info_quicklink');
    Route::get('/footer/quick_links', 'User\FooterController@quickLinks')->name('user.footer.quick_links');
    Route::post('/footer/store_quick_link', 'User\FooterController@storeQuickLink')->name('user.footer.store_quick_link');
    Route::post('/footer/update_quick_link', 'User\FooterController@updateQuickLink')->name('user.footer.update_quick_link');
    Route::post('/footer/delete_quick_link', 'User\FooterController@deleteQuickLink')->name('user.footer.delete_quick_link');

    // Subscriber Management
    Route::get('/subscribers', 'User\SubscriberController@index')->name('user.subscriber.index');
    Route::get('/mailsubscriber', 'User\SubscriberController@mailsubscriber')->name('user.mailsubscriber');
    Route::post('/subscribers/sendmail', 'User\SubscriberController@subscsendmail')->name('user.subscribers.sendmail');
    Route::post('/subscriber/delete', 'User\SubscriberController@delete')->name('user.subscriber.delete');
    Route::post('/subscriber/bulk-delete', 'User\SubscriberController@bulkDelete')->name('user.subscriber.bulk.delete');

    // CV Upload
    Route::get('/cv-upload', 'User\BasicController@cvUpload')->name('user.cv.upload');
    Route::post('/cv-upload/update', 'User\BasicController@updateCV')->name('user.cv.upload.update');
    Route::post('/cv-upload/delete', 'User\BasicController@deleteCV')->name('user.cv.upload.delete');

});
