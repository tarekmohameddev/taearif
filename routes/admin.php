<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TokenLoginController;


Route::middleware(['web', 'guest:admin'])
// ->prefix('admin')
->group(function () {
    //
    Route::get('/', 'Admin\LoginController@login')->name('login');
    Route::post('/login', 'Admin\LoginController@authenticate')->name('auth');
    Route::get('/mail-form', 'Admin\ForgetController@mailForm')->name('forget.form');
    Route::post('/sendmail', 'Admin\ForgetController@sendmail')->name('forget.mail')->middleware('Demo');
    //

});

// Authenticated admin routes
Route::middleware(['web', 'auth:admin', 'checkstatus', 'Demo'])
// ->prefix('admin')
->group(function () {
    // RTL check
    Route::get('/rtlcheck/{langid}', 'Admin\LanguageController@rtlcheck')->name('rtlcheck');

    // admin redirect to dashboard route
    Route::get('/change-theme', 'Admin\DashboardController@changeTheme')->name('theme.change');

    // Summernote image upload
    Route::post('/summernote/upload', 'Admin\SummernoteController@upload')->name('summernote.upload');

    // Admin logout Route
    Route::get('/logout', 'Admin\LoginController@logout')->name('logout');

    Route::group(['middleware' => 'checkpermission:Dashboard'], function () {
        // Admin Dashboard Routes
        Route::get('/dashboard', 'Admin\DashboardController@dashboard')->name('dashboard');
    });

    // Admin Profile Routes
    Route::get('/changePassword', 'Admin\ProfileController@changePass')->name('changePass');
    Route::post('/profile/updatePassword', 'Admin\ProfileController@updatePassword')->name('updatePassword');
    Route::get('/profile/edit', 'Admin\ProfileController@editProfile')->name('editProfile');
    Route::post('/profile/update', 'Admin\ProfileController@updateProfile')->name('updateProfile');

    Route::group(['middleware' => 'checkpermission:Settings'], function () {

        // Admin Favicon Routes
        Route::get('/favicon', 'Admin\BasicController@favicon')->name('favicon');
        Route::post('/favicon/post', 'Admin\BasicController@updatefav')->name('favicon.update');

        // Admin Logo Routes
        Route::get('/logo', 'Admin\BasicController@logo')->name('logo');
        Route::post('/logo/post', 'Admin\BasicController@updatelogo')->name('logo.update');

        // Admin Preloader Routes
        Route::get('/preloader', 'Admin\BasicController@preloader')->name('preloader');
        Route::post('/preloader/post', 'Admin\BasicController@updatepreloader')->name('preloader.update');

        // Admin Basic Information Routes
        Route::get('/basicinfo', 'Admin\BasicController@basicinfo')->name('basicinfo');
        Route::post('/basicinfo/post', 'Admin\BasicController@updatebasicinfo')->name('basicinfo.update');

        // Admin Email Settings Routes
        Route::get('/mail-from-admin', 'Admin\EmailController@mailFromAdmin')->name('mailFromAdmin');
        Route::post('/mail-from-admin/update', 'Admin\EmailController@updateMailFromAdmin')->name('mailfromadmin.update');
        Route::get('/mail-to-admin', 'Admin\EmailController@mailToAdmin')->name('mailToAdmin');
        Route::post('/mail-to-admin/update', 'Admin\EmailController@updateMailToAdmin')->name('mailtoadmin.update');

        Route::get('/mail_templates', 'Admin\MailTemplateController@mailTemplates')->name('mail_templates');
        Route::get('/edit_mail_template/{id}', 'Admin\MailTemplateController@editMailTemplate')->name('edit_mail_template');
        Route::post('/update_mail_template/{id}', 'Admin\MailTemplateController@updateMailTemplate')->name('update_mail_template');

        // Admin Breadcrumb Routes
        // Route::get('/breadcrumb', 'Admin\BasicController@breadcrumb')->name('breadcrumb');
        // Route::post('/breadcrumb/update', 'Admin\BasicController@updatebreadcrumb')->name('breadcrumb.update');

        // Admin Scripts Routes
        Route::get('/script', 'Admin\BasicController@script')->name('script');
        Route::post('/script/update', 'Admin\BasicController@updatescript')->name('script.update');

        // Admin Social Routes
        Route::get('/social', 'Admin\SocialController@index')->name('social.index');
        Route::post('/social/store', 'Admin\SocialController@store')->name('social.store');
        Route::get('/social/{id}/edit', 'Admin\SocialController@edit')->name('social.edit');
        Route::post('/social/update', 'Admin\SocialController@update')->name('social.update');
        Route::post('/social/delete', 'Admin\SocialController@delete')->name('social.delete');

        // Admin Maintanance Mode Routes
        Route::get('/maintainance', 'Admin\BasicController@maintainance')->name('maintainance');
        Route::post('/maintainance/update', 'Admin\BasicController@updatemaintainance')->name('maintainance.update');

        // Admin Section Customization Routes
        Route::get('/sections', 'Admin\BasicController@sections')->name('sections.index');
        Route::post('/sections/update', 'Admin\BasicController@updatesections')->name('sections.update');

        // Admin Cookie Alert Routes
        Route::get('/cookie-alert', 'Admin\BasicController@cookiealert')->name('cookie.alert');
        Route::post('/cookie-alert/{langid}/update', 'Admin\BasicController@updatecookie')->name('cookie.update');

        // basic settings seo route
        Route::get('/seo', 'Admin\BasicController@seo')->name('seo');
        Route::post('/seo/update', 'Admin\BasicController@updateSEO')->name('seo.update');

        // admin custom css
        Route::get('css', 'Admin\BasicController@css')->name('css');
        Route::post('css/update', 'Admin\BasicController@updateCss')->name('css.update');

        // admin custom js
        Route::get('js', 'Admin\BasicController@js')->name('js');
        Route::post('js/update', 'Admin\BasicController@updateJs')->name('js.update');
    });

    Route::group(['middleware' => 'checkpermission:Subscribers'], function () {
        // Admin Subscriber Routes
        Route::get('/subscribers', 'Admin\SubscriberController@index')->name('subscriber.index');
        Route::get('/mailsubscriber', 'Admin\SubscriberController@mailsubscriber')->name('mailsubscriber');
        Route::post('/subscribers/sendmail', 'Admin\SubscriberController@subscsendmail')->name('subscribers.sendmail');
        Route::post('/subscriber/delete', 'Admin\SubscriberController@delete')->name('subscriber.delete');
        Route::post('/subscriber/bulk-delete', 'Admin\SubscriberController@bulkDelete')->name('subscriber.bulk.delete');
    });
    // MENU BUILDER
    Route::group(['middleware' => 'checkpermission:Menu Builder'], function () {
        Route::get('/menu-builder', 'Admin\MenuBuilderController@index')->name('menu_builder.index');
        Route::post('/menu-builder/update', 'Admin\MenuBuilderController@update')->name('menu_builder.update');
    });

    Route::group(['middleware' => 'checkpermission:Home Page'], function () {

        // Admin Hero Section Image & Text Routes
        Route::get('/herosection/imgtext', 'Admin\HerosectionController@imgtext')->name('herosection.imgtext');
        Route::post('/herosection/{langid}/update', 'Admin\HerosectionController@update')->name('herosection.update');

        // Admin Feature Routes
        Route::get('/features', 'Admin\FeatureController@index')->name('feature.index');
        Route::post('/feature/store', 'Admin\FeatureController@store')->name('feature.store');
        Route::get('/feature/{id}/edit', 'Admin\FeatureController@edit')->name('feature.edit');
        Route::post('/feature/update', 'Admin\FeatureController@update')->name('feature.update');
        Route::post('/feature/delete', 'Admin\FeatureController@delete')->name('feature.delete');

        // Admin Work Process Routes
        Route::get('/process', 'Admin\ProcessController@index')->name('process.index');
        Route::post('/process/store', 'Admin\ProcessController@store')->name('process.store');
        Route::get('/process/{id}/edit', 'Admin\ProcessController@edit')->name('process.edit');
        Route::post('/process/update', 'Admin\ProcessController@update')->name('process.update');
        Route::post('/process/delete', 'Admin\ProcessController@delete')->name('process.delete');

        // Admin Intro Section Routes
        Route::get('/introsection', 'Admin\IntrosectionController@index')->name('introsection.index');
        Route::post('/introsection/{langid}/update', 'Admin\IntrosectionController@update')->name('introsection.update');
        Route::post('/introsection/remove/image', 'Admin\IntrosectionController@removeImage')->name('introsection.img.rmv');

        // Admin Testimonial Routes
        Route::get('/testimonials', 'Admin\TestimonialController@index')->name('testimonial.index');
        Route::get('/testimonial/create', 'Admin\TestimonialController@create')->name('testimonial.create');
        Route::post('/testimonial/store', 'Admin\TestimonialController@store')->name('testimonial.store');
        Route::get('/testimonial/{id}/edit', 'Admin\TestimonialController@edit')->name('testimonial.edit');
        Route::post('/testimonial/update', 'Admin\TestimonialController@update')->name('testimonial.update');
        Route::post('/testimonial/update/image', 'Admin\TestimonialController@updateImage')->name('testimonial.update.image');
        Route::post('/testimonial/delete', 'Admin\TestimonialController@delete')->name('testimonial.delete');
        Route::post('/testimonialtext/{langid}/update', 'Admin\TestimonialController@textupdate')->name('testimonialtext.update');

        // Admin home page text routes
        Route::get('/home-page-text-section', 'Admin\HomePageTextController@index')->name('home.page.text.index');
        Route::post('/home-page-text-section/{langid}/update', 'Admin\HomePageTextController@update')->name('home.page.text.update');

        // Admin Partner Routes
        Route::get('/partners', 'Admin\PartnerController@index')->name('partner.index');
        Route::post('/partner/store', 'Admin\PartnerController@store')->name('partner.store');
        Route::post('/partner/upload', 'Admin\PartnerController@upload')->name('partner.upload');
        Route::get('/partner/{id}/edit', 'Admin\PartnerController@edit')->name('partner.edit');
        Route::post('/partner/update', 'Admin\PartnerController@update')->name('partner.update');
        Route::post('/partner/{id}/uploadUpdate', 'Admin\PartnerController@uploadUpdate')->name('partner.uploadUpdate');
        Route::post('/partner/delete', 'Admin\PartnerController@delete')->name('partner.delete');
    });

    Route::group(['middleware' => 'checkpermission:Pages'], function () {
        // Menu Manager Routes
        Route::get('/pages', 'Admin\PageController@index')->name('page.index');
        Route::get('/page/create', 'Admin\PageController@create')->name('page.create');
        Route::post('/page/store', 'Admin\PageController@store')->name('page.store');
        Route::get('/page/{menuID}/edit', 'Admin\PageController@edit')->name('page.edit');
        Route::post('/page/update', 'Admin\PageController@update')->name('page.update');
        Route::post('/page/delete', 'Admin\PageController@delete')->name('page.delete');
        Route::post('/page/bulk-delete', 'Admin\PageController@bulkDelete')->name('page.bulk.delete');
    });

    Route::group(['middleware' => 'checkpermission:Footer'], function () {
        // Admin Footer Logo Text Routes
        Route::get('/footers', 'Admin\FooterController@index')->name('footer.index');
        Route::post('/footer/{langid}/update', 'Admin\FooterController@update')->name('footer.update');
        Route::post('/footer/remove/image', 'Admin\FooterController@removeImage')->name('footer.rmvimg');

        // Admin Ulink Routes
        Route::get('/ulinks', 'Admin\UlinkController@index')->name('ulink.index');
        Route::get('/ulink/create', 'Admin\UlinkController@create')->name('ulink.create');
        Route::post('/ulink/store', 'Admin\UlinkController@store')->name('ulink.store');
        Route::get('/ulink/{id}/edit', 'Admin\UlinkController@edit')->name('ulink.edit');
        Route::post('/ulink/update', 'Admin\UlinkController@update')->name('ulink.update');
        Route::post('/ulink/delete', 'Admin\UlinkController@delete')->name('ulink.delete');
    });

    // Announcement Popup Routes
    Route::group(['middleware' => 'checkpermission:Announcement Popup'], function () {
        Route::get('popups', 'Admin\PopupController@index')->name('popup.index');
        Route::get('popup/types', 'Admin\PopupController@types')->name('popup.types');
        Route::get('popup/{id}/edit', 'Admin\PopupController@edit')->name('popup.edit');
        Route::get('popup/create', 'Admin\PopupController@create')->name('popup.create');
        Route::post('popup/store', 'Admin\PopupController@store')->name('popup.store');;
        Route::post('popup/delete', 'Admin\PopupController@delete')->name('popup.delete');
        Route::post('popup/bulk-delete', 'Admin\PopupController@bulkDelete')->name('popup.bulk.delete');
        Route::post('popup/status', 'Admin\PopupController@status')->name('popup.status');
        Route::post('popup/update', 'Admin\PopupController@update')->name('popup.update');;
    });

    //advertisement

    Route::prefix('advertisement')->group(function () {
        Route::get('settings', 'Admin\AdvertisementController@index')->name('advertisement.settings');
        Route::post('settings/update', 'Admin\AdvertisementController@update')->name('advertisement.update');
    });

    Route::group(['middleware' => 'checkpermission:Registered Users'], function () {
        // Register User start
        Route::get('register/users', 'Admin\RegisterUserController@index')->name('register.user');
        Route::post('register/user/store', 'Admin\RegisterUserController@store')->name('register.user.store');
        Route::post('register/users/ban', 'Admin\RegisterUserController@userban')->name('register.user.ban');
        Route::post('register/users/featured', 'Admin\RegisterUserController@userFeatured')->name('register.user.featured');
        Route::post('register/users/template', 'Admin\RegisterUserController@userTemplate')->name('register.user.template');
        Route::post('register/users/template/update', 'Admin\RegisterUserController@userUpdateTemplate')->name('register.user.updateTemplate');
        Route::post('register/users/email', 'Admin\RegisterUserController@emailStatus')->name('register.user.email');
        Route::get('register/user/details/{id}', 'Admin\RegisterUserController@view')->name('register.user.view');
        Route::post('/user/current-package/remove', 'Admin\RegisterUserController@removeCurrPackage')->name('user.currPackage.remove');
        Route::post('/user/current-package/change', 'Admin\RegisterUserController@changeCurrPackage')->name('user.currPackage.change');
        Route::post('/user/current-package/add', 'Admin\RegisterUserController@addCurrPackage')->name('user.currPackage.add');
        Route::post('/user/next-package/remove', 'Admin\RegisterUserController@removeNextPackage')->name('user.nextPackage.remove');
        Route::post('/user/next-package/change', 'Admin\RegisterUserController@changeNextPackage')->name('user.nextPackage.change');
        Route::post('/user/next-package/add', 'Admin\RegisterUserController@addNextPackage')->name('user.nextPackage.add');
        Route::post('register/user/delete', 'Admin\RegisterUserController@delete')->name('register.user.delete');
        Route::post('register/user/bulk-delete', 'Admin\RegisterUserController@bulkDelete')->name('register.user.bulk.delete');
        Route::get('register/user/{id}/changePassword', 'Admin\RegisterUserController@changePass')->name('register.user.changePass');
        Route::post('register/user/updatePassword', 'Admin\RegisterUserController@updatePassword')->name('register.user.updatePassword');
        //Register User end
        // users vcards route start
        Route::get('register/user/vcard', 'Admin\UsersVcardsController@index')->name('register.user.vcards');
        Route::post('register/users/vcard/change-status', 'Admin\UsersVcardsController@changeStatus')->name('register.user.vcard.status');
        Route::post('register/users/vcard/template', 'Admin\UsersVcardsController@vcardTemplate')->name('register.user.vcard.template');
        Route::post('register/users/vcard/template/update', 'Admin\UsersVcardsController@vcardUpdateTemplate')->name('register.user.vcard.updateTemplate');
        Route::post('register/users/vcard/template', 'Admin\UsersVcardsController@vcardTemplate')->name('register.user.vcard.template');
        Route::post('register/users/vcard/delete', 'Admin\UsersVcardsController@destroy')->name('register.user.vcard.delete');

    });

    Route::group(['middleware' => 'checkpermission:FAQ Management'], function () {
        // Admin FAQ Routes
        Route::get('/faqs', 'Admin\FaqController@index')->name('faq.index');
        Route::get('/faq/create', 'Admin\FaqController@create')->name('faq.create');
        Route::post('/faq/store', 'Admin\FaqController@store')->name('faq.store');
        Route::post('/faq/update', 'Admin\FaqController@update')->name('faq.update');
        Route::post('/faq/delete', 'Admin\FaqController@delete')->name('faq.delete');
        Route::post('/faq/bulk-delete', 'Admin\FaqController@bulkDelete')->name('faq.bulk.delete');
    });

    Route::group(['middleware' => 'checkpermission:Blogs'], function () {
        // Admin Blog Category Routes
        Route::get('/bcategorys', 'Admin\BcategoryController@index')->name('bcategory.index');
        Route::post('/bcategory/store', 'Admin\BcategoryController@store')->name('bcategory.store');
        Route::post('/bcategory/update', 'Admin\BcategoryController@update')->name('bcategory.update');
        Route::post('/bcategory/delete', 'Admin\BcategoryController@delete')->name('bcategory.delete');
        Route::post('/bcategory/bulk-delete', 'Admin\BcategoryController@bulkDelete')->name('bcategory.bulk.delete');


        // Admin Blog Routes
        Route::get('/blogs', 'Admin\BlogController@index')->name('blog.index');
        Route::post('/blog/upload', 'Admin\BlogController@upload')->name('blog.upload');
        Route::post('/blog/store', 'Admin\BlogController@store')->name('blog.store');
        Route::get('/blog/{id}/edit', 'Admin\BlogController@edit')->name('blog.edit');
        Route::post('/blog/update', 'Admin\BlogController@update')->name('blog.update');
        Route::post('/blog/{id}/uploadUpdate', 'Admin\BlogController@uploadUpdate')->name('blog.uploadUpdate');
        Route::post('/blog/delete', 'Admin\BlogController@delete')->name('blog.delete');
        Route::post('/blog/bulk-delete', 'Admin\BlogController@bulkDelete')->name('blog.bulk.delete');
        Route::get('/blog/{langid}/getcats', 'Admin\BlogController@getcats')->name('blog.getcats');
    });

    Route::group(['middleware' => 'checkpermission:Sitemap'], function () {
        Route::get('/sitemap', 'Admin\SitemapController@index')->name('sitemap.index');
        Route::post('/sitemap/store', 'Admin\SitemapController@store')->name('sitemap.store');
        Route::get('/sitemap/{id}/update', 'Admin\SitemapController@update')->name('sitemap.update');
        Route::post('/sitemap/{id}/delete', 'Admin\SitemapController@delete')->name('sitemap.delete');
        Route::post('/sitemap/download', 'Admin\SitemapController@download')->name('sitemap.download');
    });

    Route::group(['middleware' => 'checkpermission:Contact Page'], function () {
        // Admin Contact Routes
        Route::get('/contact', 'Admin\ContactController@index')->name('contact.index');
        Route::post('/contact/{langid}/post', 'Admin\ContactController@update')->name('contact.update');
    });

    Route::group(['middleware' => 'checkpermission:Communication'], function () {
        // Admin Communication Routes
        Route::get('/communication/whatsapp', 'Admin\CommunicationController@whatsapp')->name('communication.whatsapp');
        Route::post('/communication/whatsapp/update', 'Admin\CommunicationController@updateWhatsapp')->name('communication.whatsapp.update');
        Route::post('/communication/service/update', 'Admin\CommunicationController@updateServiceSelection')->name('communication.service.update');

        // Meta Cloud API Routes
        Route::get('/communication/meta-cloud', 'Admin\CommunicationController@metaCloud')->name('communication.meta-cloud');
        Route::post('/communication/meta-cloud/update', 'Admin\CommunicationController@updateMetaCloud')->name('communication.meta-cloud.update');

        // Evolution API Routes
        Route::get('/communication/evolution-api', 'Admin\CommunicationController@evolutionApi')->name('communication.evolution-api');
        Route::post('/communication/evolution-api/update', 'Admin\CommunicationController@updateEvolutionApi')->name('communication.evolution-api.update');

        // Test Routes
        Route::post('/communication/test-whatsapp', 'Admin\CommunicationController@testWhatsAppService')->name('communication.test-whatsapp');
        Route::post('/communication/check-config', 'Admin\CommunicationController@checkConfiguration')->name('communication.check-config');

        // Welcome Message Routes
        Route::post('/communication/welcome-message/update', 'Admin\CommunicationController@updateWelcomeMessage')->name('communication.welcome-message.update');
        Route::post('/communication/welcome-message/test', 'Admin\CommunicationController@testWelcomeMessage')->name('communication.welcome-message.test');

        // Subscription Expiration Routes
        Route::post('/communication/subscription-expiration/update', 'Admin\CommunicationController@updateSubscriptionExpiration')->name('communication.subscription-expiration.update');
        Route::post('/communication/subscription-expiration/test', 'Admin\CommunicationController@testSubscriptionExpiration')->name('communication.subscription-expiration.test');

        // On Expiration Notification Routes
        Route::post('/communication/subscription-expired/update', 'Admin\CommunicationController@updateSubscriptionExpired')->name('communication.subscription-expired.update');
        Route::post('/communication/subscription-expired/test', 'Admin\CommunicationController@testSubscriptionExpired')->name('communication.subscription-expired.test');

        // Password Reset Routes
        Route::post('/communication/password-reset/update', 'Admin\CommunicationController@updatePasswordReset')->name('communication.password-reset.update');
        Route::post('/communication/password-reset/test', 'Admin\CommunicationController@testPasswordReset')->name('communication.password-reset.test');

        // Email Communication Routes
        Route::get('/communication/email', 'Admin\EmailCommunicationController@index')->name('communication.email');
        Route::post('/communication/email/smtp/update', 'Admin\EmailCommunicationController@updateSmtpSettings')->name('email-communication.smtp.update');
        Route::post('/communication/email/templates/update', 'Admin\EmailCommunicationController@updateEmailTemplates')->name('email-communication.templates.update');
        Route::post('/communication/email/test', 'Admin\EmailCommunicationController@testEmailConfiguration')->name('email-communication.test');

        // Meta Templates
        Route::get('/communication/fetch-meta-templates', 'Admin\CommunicationController@fetchMetaTemplates')->name('communication.fetch-meta-templates');
        Route::post('/communication/save-selected-template', 'Admin\CommunicationController@saveSelectedTemplate')->name('communication.save-selected-template');
        Route::post('/communication/test-selected-template', 'Admin\CommunicationController@testSelectedTemplate')->name('communication.test-selected-template');

        // Master Toggle Routes
        Route::post('/communication/master-toggle/whatsapp', 'Admin\CommunicationController@updateMasterWhatsAppToggle')->name('communication.master-toggle.whatsapp');
        Route::post('/communication/master-toggle/email', 'Admin\EmailCommunicationController@updateMasterEmailToggle')->name('communication.master-toggle.email');

        // WhatsApp Templates Routes (Dedicated)
        Route::resource('/communication/whatsapp-templates', 'Admin\WhatsAppTemplateController')->names('whatsapp-templates');
        Route::post('/communication/whatsapp-templates/{whatsappTemplate}/toggle-status', 'Admin\WhatsAppTemplateController@toggleStatus')->name('whatsapp-templates.toggle-status');
        Route::get('/communication/whatsapp-templates/{whatsappTemplate}/duplicate', 'Admin\WhatsAppTemplateController@duplicate')->name('whatsapp-templates.duplicate');
        Route::get('/communication/whatsapp-templates/{whatsappTemplate}/preview', 'Admin\WhatsAppTemplateController@preview')->name('whatsapp-templates.preview');

        // Email Templates Routes
        Route::resource('/communication/email-templates', 'Admin\EmailTemplateController')->names('email-templates');
        Route::post('/communication/email-templates/{emailTemplate}/toggle-status', 'Admin\EmailTemplateController@toggleStatus')->name('email-templates.toggle-status');
        Route::get('/communication/email-templates/{emailTemplate}/duplicate', 'Admin\EmailTemplateController@duplicate')->name('email-templates.duplicate');
        Route::get('/communication/email-templates/{emailTemplate}/preview', 'Admin\EmailTemplateController@preview')->name('email-templates.preview');
    });

    Route::group(['middleware' => 'checkpermission:Payment Gateways'], function () {
        // Admin Online Gateways Routes
        Route::get('/gateways', 'Admin\GatewayController@index')->name('gateway.index');
        Route::post('/stripe/update', 'Admin\GatewayController@stripeUpdate')->name('stripe.update');
        Route::post('/anet/update', 'Admin\GatewayController@anetUpdate')->name('anet.update');
        Route::post('/paypal/update', 'Admin\GatewayController@paypalUpdate')->name('paypal.update');
        Route::post('/paystack/update', 'Admin\GatewayController@paystackUpdate')->name('paystack.update');
        Route::post('/paytm/update', 'Admin\GatewayController@paytmUpdate')->name('paytm.update');
        Route::post('/flutterwave/update', 'Admin\GatewayController@flutterwaveUpdate')->name('flutterwave.update');
        Route::post('/instamojo/update', 'Admin\GatewayController@instamojoUpdate')->name('instamojo.update');
        Route::post('/mollie/update', 'Admin\GatewayController@mollieUpdate')->name('mollie.update');
        Route::post('/razorpay/update', 'Admin\GatewayController@razorpayUpdate')->name('razorpay.update');
        Route::post('/mercadopago/update', 'Admin\GatewayController@mercadopagoUpdate')->name('mercadopago.update');
        Route::post('/phonepe/update', 'Admin\GatewayController@phonepeUpdate')->name('phonepe.update');

        Route::post('/perfect-money/update', 'Admin\GatewayController@perfect_moneyUpdate')->name('perfect_money.update');
        Route::post('/xendit/update', 'Admin\GatewayController@xenditUpdate')->name('xendit.update');

        Route::post('/myfatoorah/update', 'Admin\GatewayController@myfatoorahUpdate')->name('myfatoorah.update');
        Route::post('/arb/update', 'Admin\GatewayController@arbUpdate')->name('arb.update');
        Route::post('/yoco/update', 'Admin\GatewayController@yocoUpdate')->name('yoco.update');
        Route::post('/toyyibpay/update', 'Admin\GatewayController@toyyibpayUpdate')->name('toyyibpay.update');
        Route::post('/paytabs/update', 'Admin\GatewayController@paytabsUpdate')->name('paytabs.update');
        Route::post('/iyzico/update', 'Admin\GatewayController@iyzicoUpdate')->name('iyzico.update');
        Route::post('/midtrans/update', 'Admin\GatewayController@midtransUpdate')->name('midtrans.update');

        // Admin Offline Gateway Routes
        Route::get('/offline/gateways', 'Admin\GatewayController@offline')->name('gateway.offline');
        Route::post('/offline/gateway/store', 'Admin\GatewayController@store')->name('gateway.offline.store');
        Route::post('/offline/gateway/update', 'Admin\GatewayController@update')->name('gateway.offline.update');
        Route::post('/offline/status', 'Admin\GatewayController@status')->name('offline.status');
        Route::post('/offline/gateway/delete', 'Admin\GatewayController@delete')->name('offline.gateway.delete');
    });

    Route::group(['middleware' => 'checkpermission:Role Management'], function () {
        // Admin Roles Routes
        Route::get('/roles', 'Admin\RoleController@index')->name('role.index');
        Route::post('/role/store', 'Admin\RoleController@store')->name('role.store');
        Route::post('/role/update', 'Admin\RoleController@update')->name('role.update');
        Route::post('/role/delete', 'Admin\RoleController@delete')->name('role.delete');
        Route::get('role/{id}/permissions/manage', 'Admin\RoleController@managePermissions')->name('role.permissions.manage');
        Route::post('role/permissions/update', 'Admin\RoleController@updatePermissions')->name('role.permissions.update');
    });

    Route::group(['middleware' => 'checkpermission:Admins Management'], function () {
        // Admin Users Routes
        Route::get('/users', 'Admin\UserController@index')->name('user.index');
        Route::post('/user/upload', 'Admin\UserController@upload')->name('user.upload');
        Route::post('/user/store', 'Admin\UserController@store')->name('user.store');
        Route::get('/user/{id}/edit', 'Admin\UserController@edit')->name('user.edit');
        Route::post('/user/update', 'Admin\UserController@update')->name('user.update');
        Route::post('/user/{id}/uploadUpdate', 'Admin\UserController@uploadUpdate')->name('user.uploadUpdate');
        Route::post('/user/delete', 'Admin\UserController@delete')->name('user.delete');
    });

    Route::group(['middleware' => 'checkpermission:Language Management'], function () {
        // Admin Language Routes
        Route::get('/languages', 'Admin\LanguageController@index')->name('language.index');
        Route::get('/language/{id}/edit', 'Admin\LanguageController@edit')->name('language.edit');
        Route::get('/language/{id}/edit/keyword', 'Admin\LanguageController@editKeyword')->name('language.editKeyword');
        Route::post('/language/store', 'Admin\LanguageController@store')->name('language.store');
        Route::post('/language/upload', 'Admin\LanguageController@upload')->name('language.upload');
        Route::post('/language/{id}/uploadUpdate', 'Admin\LanguageController@uploadUpdate')->name('language.uploadUpdate');
        Route::post('/language/{id}/default', 'Admin\LanguageController@default')->name('language.default');
        Route::post('/language/{id}/delete', 'Admin\LanguageController@delete')->name('language.delete');
        Route::post('/language/update', 'Admin\LanguageController@update')->name('language.update');
        Route::post('/language/{id}/update/keyword', 'Admin\LanguageController@updateKeyword')->name('language.updateKeyword');

        //tenant Language Routes
        Route::get('/tenant/default/language', 'Admin\TenantLanguageController@defaultLanguage')->name('tenant_language.default');
        Route::get('/tenant/default/language/edit', 'Admin\TenantLanguageController@defaultLanguageEdit')->name('tenant.default_language.edit');
        Route::post('/tenant/default/language/update', 'Admin\TenantLanguageController@defaultLanguageUpdate')->name('tenant.default_language.update');
        Route::get('/tenant/language/edit', 'Admin\TenantLanguageController@editKeyword')->name('tenant_language.edit');
        Route::post('tenant/language/{id}/update/keyword', 'Admin\TenantLanguageController@updateKeyword')->name('tenant_language.updateKeyword');
        //tenant Language Routes

        Route::post('tenant/language/{id}/add/keyword', 'Admin\TenantLanguageController@addKeyword')->name('tenant_language.addKeyword');
    });

    // Admin Cache Clear Routes
    Route::get('/cache-clear', 'Admin\CacheController@clear')->name('cache.clear');

    Route::group(['middleware' => 'checkpermission:Packages'], function () {
        // Package Settings routes
        Route::get('/package/settings', 'Admin\PackageController@settings')->name('package.settings');
        Route::post('/package/settings', 'Admin\PackageController@updateSettings')->name('package.settings.update');
        // Package Settings routes
        Route::get('/package/features', 'Admin\PackageController@features')->name('package.features');
        Route::post('/package/features', 'Admin\PackageController@updateFeatures')->name('package.features.update');
        // Package routes
        Route::get('packages', 'Admin\PackageController@index')->name('package.index');
        Route::post('package/upload', 'Admin\PackageController@upload')->name('package.upload');
        Route::post('package/store', 'Admin\PackageController@store')->name('package.store');
        Route::get('package/{id}/edit', 'Admin\PackageController@edit')->name('package.edit');
        Route::post('package/update', 'Admin\PackageController@update')->name('package.update');
        Route::post('package/{id}/uploadUpdate', 'Admin\PackageController@uploadUpdate')->name('package.uploadUpdate');
        Route::post('package/delete', 'Admin\PackageController@delete')->name('package.delete');
        Route::post('package/bulk-delete', 'Admin\PackageController@bulkDelete')->name('package.bulk.delete');

        // Admin Coupon Routes
        Route::get('/coupon', 'Admin\CouponController@index')->name('coupon.index');
        Route::post('/coupon/store', 'Admin\CouponController@store')->name('coupon.store');
        Route::get('/coupon/{id}/edit', 'Admin\CouponController@edit')->name('coupon.edit');
        Route::post('/coupon/update', 'Admin\CouponController@update')->name('coupon.update');
        Route::post('/coupon/delete', 'Admin\CouponController@delete')->name('coupon.delete');
        // Admin Coupon Routes End
    });

    Route::group(['middleware' => 'checkpermission:Payment Log'], function () {
        // Payment Log
        Route::get('/payment-log', 'Admin\PaymentLogController@index')->name('payment-log.index');
        Route::post('/payment-log/update', 'Admin\PaymentLogController@update')->name('payment-log.update');
    });

    // Custom Domains
    Route::group(['middleware' => 'checkpermission:Custom Domains'], function () {
        Route::get('/domains', 'Admin\CustomDomainController@index')->name('custom-domain.index');
        Route::get('/domain/texts', 'Admin\CustomDomainController@texts')->name('custom-domain.texts');
        Route::post('/domain/texts', 'Admin\CustomDomainController@updateTexts')->name('custom-domain.texts.update');
        Route::post('/domain/status', 'Admin\CustomDomainController@status')->name('custom-domain.status');
        Route::post('/domain/mail', 'Admin\CustomDomainController@mail')->name('custom-domain.mail');
        Route::post('/domain/delete', 'Admin\CustomDomainController@delete')->name('custom-domain.delete');
        Route::post('/domain/bulk-delete', 'Admin\CustomDomainController@bulkDelete')->name('custom-domain.bulk.delete');
        Route::post('/domain/ssl-status', 'Admin\CustomDomainController@updateSslStatus')->name('custom-domain.ssl-status');
    });

    // Subdomains
    Route::group(['middleware' => 'checkpermission:Subdomains'], function () {
        Route::get('/subdomains', 'Admin\SubdomainController@index')->name('subdomain.index');
        Route::post('/subdomain/status', 'Admin\SubdomainController@status')->name('subdomain.status');
        Route::post('/subdomain/mail', 'Admin\SubdomainController@mail')->name('subdomain.mail');
    });

    //AppInstallationController
    Route::group(['middleware' => 'checkpermission:App Request'], function () {
        Route::get('/app-request', 'Admin\AppInstallationController@index')->name('app.request.index');
        Route::patch('/app-request/{id}/status', 'Admin\AppInstallationController@updateStatus')->name('app-request.updateStatus');
        Route::post('/app-request/delete', 'Admin\AppInstallationController@delete')->name('app.request.delete');
    });

    //MarketplaceAppsController
    Route::group(['middleware' => 'checkpermission:App Request'], function () {
        Route::get('/marketplace-apps', 'Admin\MarketplaceAppController@index')->name('marketplace-apps.index');
        Route::get('/marketplace-apps/create', 'Admin\MarketplaceAppController@create')->name('marketplace-apps.create');
        Route::post('/marketplace-apps/store', 'Admin\MarketplaceAppController@store')->name('marketplace-apps.store');
        Route::get('/marketplace-apps/{id}/edit', 'Admin\MarketplaceAppController@edit')->name('marketplace-apps.edit');
        Route::post('/marketplace-apps/update', 'Admin\MarketplaceAppController@update')->name('marketplace-apps.update');
        Route::post('/marketplace-apps/delete', 'Admin\MarketplaceAppController@delete')->name('marketplace-apps.delete');
        Route::post('/marketplace-apps/bulk-delete', 'Admin\MarketplaceAppController@bulkDelete')->name('marketplace-apps.bulk-delete');
        Route::post('/marketplace-apps/toggle-status', 'Admin\MarketplaceAppController@toggleStatus')->name('marketplace-apps.toggle-status');
    });

    // Themes Management
    Route::group(['middleware' => 'checkpermission:Packages'], function () {
        Route::get('/themes', 'Admin\ThemeController@index')->name('themes.index');
        Route::get('/themes/create', 'Admin\ThemeController@create')->name('themes.create');
        Route::post('/themes/store', 'Admin\ThemeController@store')->name('themes.store');
        Route::get('/themes/{themeId}/edit', 'Admin\ThemeController@edit')->name('themes.edit');
        Route::post('/themes/update', 'Admin\ThemeController@update')->name('themes.update');
        Route::post('/themes/delete', 'Admin\ThemeController@delete')->name('themes.delete');
        Route::post('/themes/toggle-enabled', 'Admin\ThemeController@toggleEnabled')->name('themes.toggle-enabled');
    });

    //AdminCreditController
    Route::group(['middleware' => 'checkpermission:Credit Management'], function () {
        Route::get('/credit-transactions', 'Admin\AdminCreditController@index')->name('credit.transactions.index');
        Route::get('/credit-transactions/{id}', 'Admin\AdminCreditController@show')->name('credit.transactions.show');
        Route::patch('/credit-transactions/{id}/status', 'Admin\AdminCreditController@updateStatus')->name('credit.transactions.updateStatus');
        Route::post('/credit-transactions/{id}/refund', 'Admin\AdminCreditController@refund')->name('credit.transactions.refund');
        Route::delete('/credit-transactions/{id}', 'Admin\AdminCreditController@destroy')->name('credit.transactions.destroy');

        Route::get('/credit-statistics', 'Admin\AdminCreditController@statistics')->name('credit.statistics');
        Route::get('/user-credits', 'Admin\AdminCreditController@userCredits')->name('credit.userCredits');
        Route::post('/add-credits', 'Admin\AdminCreditController@addCredits')->name('credit.addCredits');
        Route::post('/remove-credits', 'Admin\AdminCreditController@removeCredits')->name('credit.removeCredits');

        // Unified Credit Management Dashboard
        Route::get('/credit-management', 'Admin\CreditManagementController@index')->name('credit-management.index');
        Route::post('/credit-management/packages/quick-create', 'Admin\CreditManagementController@quickCreatePackage')->name('credit-management.packages.quick-create');
        Route::post('/credit-management/pricing/quick-create', 'Admin\CreditManagementController@quickCreatePricing')->name('credit-management.pricing.quick-create');
        Route::get('/credit-management/packages/{id}/edit', 'Admin\CreditManagementController@editPackage')->name('credit-management.packages.edit');
        Route::get('/credit-management/pricing/{id}/edit', 'Admin\CreditManagementController@editPricing')->name('credit-management.pricing.edit');
        Route::put('/credit-management/packages/{id}', 'Admin\CreditManagementController@updatePackage')->name('credit-management.packages.update');
        Route::put('/credit-management/pricing/{id}', 'Admin\CreditManagementController@updatePricing')->name('credit-management.pricing.update');
        Route::put('/credit-management/packages/{id}/quick-update', 'Admin\CreditManagementController@quickUpdatePackage')->name('credit-management.packages.quick-update');
        Route::put('/credit-management/pricing/{id}/quick-update', 'Admin\CreditManagementController@quickUpdatePricing')->name('credit-management.pricing.quick-update');
        Route::post('/credit-management/packages/{id}/toggle-status', 'Admin\CreditManagementController@togglePackageStatus')->name('credit-management.packages.toggle-status');
        Route::post('/credit-management/pricing/{id}/toggle-status', 'Admin\CreditManagementController@togglePricingStatus')->name('credit-management.pricing.toggle-status');
        Route::delete('/credit-management/packages/{id}', 'Admin\CreditManagementController@deletePackage')->name('credit-management.packages.delete');
        Route::delete('/credit-management/pricing/{id}', 'Admin\CreditManagementController@deletePricing')->name('credit-management.pricing.delete');
        Route::get('/credit-management/packages/{id}/estimates', 'Admin\CreditManagementController@getPackageEstimates')->name('credit-management.packages.estimates');
        Route::post('/credit-management/sync-pricing', 'Admin\CreditManagementController@syncPricingFromPackages')->name('credit-management.sync-pricing');
        
        // WhatsApp Add-ons Management
        Route::get('/whatsapp-addons', 'Admin\WhatsappAddonController@index')->name('whatsapp-addons.index');
        Route::get('/whatsapp-addons/{id}', 'Admin\WhatsappAddonController@show')->name('whatsapp-addons.show');
        Route::post('/whatsapp-addons/{id}/approve', 'Admin\WhatsappAddonController@approve')->name('whatsapp-addons.approve');
        Route::post('/whatsapp-addons/{id}/reject', 'Admin\WhatsappAddonController@reject')->name('whatsapp-addons.reject');
        Route::delete('/whatsapp-addons/{id}', 'Admin\WhatsappAddonController@destroy')->name('whatsapp-addons.destroy');
        
        // WhatsApp Numbers Management
        Route::get('/whatsapp-numbers/{id}/edit', 'Admin\WhatsappNumberController@edit')->name('whatsapp-numbers.edit');
        Route::put('/whatsapp-numbers/{id}', 'Admin\WhatsappNumberController@update')->name('whatsapp-numbers.update');
        Route::post('/whatsapp-numbers/{id}/toggle-status', 'Admin\WhatsappNumberController@toggleStatus')->name('whatsapp-numbers.toggle-status');
        Route::delete('/whatsapp-numbers/{id}', 'Admin\WhatsappNumberController@destroy')->name('whatsapp-numbers.destroy');
        
        // WhatsApp Addon Plans Management
        Route::get('/whatsapp-addon-plans', 'Admin\WhatsappAddonPlanController@index')->name('whatsapp-addon-plans.index');
        Route::post('/whatsapp-addon-plans', 'Admin\WhatsappAddonPlanController@store')->name('whatsapp-addon-plans.store');
        Route::put('/whatsapp-addon-plans/{id}', 'Admin\WhatsappAddonPlanController@update')->name('whatsapp-addon-plans.update');
        Route::delete('/whatsapp-addon-plans/{id}', 'Admin\WhatsappAddonPlanController@destroy')->name('whatsapp-addon-plans.destroy');
        Route::post('/whatsapp-addon-plans/{id}/toggle-status', 'Admin\WhatsappAddonPlanController@toggleStatus')->name('whatsapp-addon-plans.toggle-status');
        
        // Employee Addon Plans Management
        Route::get('/employee-addon-plans', 'Admin\EmployeeAddonPlanController@index')->name('employee-addon-plans.index');
        Route::post('/employee-addon-plans', 'Admin\EmployeeAddonPlanController@store')->name('employee-addon-plans.store');
        Route::put('/employee-addon-plans/{id}', 'Admin\EmployeeAddonPlanController@update')->name('employee-addon-plans.update');
        Route::delete('/employee-addon-plans/{id}', 'Admin\EmployeeAddonPlanController@destroy')->name('employee-addon-plans.destroy');
        Route::post('/employee-addon-plans/{id}/toggle-status', 'Admin\EmployeeAddonPlanController@toggleStatus')->name('employee-addon-plans.toggle-status');
    });

    //IstharaController
    Route::group(['middleware' => 'checkpermission:Isthara'], function () {
        Route::get('/isthara', 'Admin\AdminIstharaController@index')->name('isthara.index');
        Route::get('/isthara/{id}/show', 'Admin\AdminIstharaController@show')->name('isthara.show');
        Route::post('/isthara/update', 'Admin\AdminIstharaController@markAsRead')->name('isthara.update');
    });

    // affiliate
    Route::group(['middleware' => 'checkpermission:Affiliate'], function () {
        Route::get('/affiliates', 'Admin\AffiliateController@index')->name('affiliates.index');
        // Status management
        Route::post('/affiliates/status/{id}', 'Admin\AffiliateController@updateStatus')->name('affiliates.updateStatus');
        // Payment and history
        Route::get('/affiliates/payment-history/{id}', 'Admin\AffiliateController@paymentHistory')->name('affiliates.paymentHistory');
        // AJAX endpoints
        Route::get('/affiliates/{id}/balance-summary', 'Admin\AffiliateController@getBalanceSummary')->name('affiliates.balanceSummary');
        // approve all pending commissions
        Route::patch('/affiliates/{affiliate}/approve-all','Admin\AffiliateController@approveAllPending')->name('affiliates.approveAll');
         // updateCommission
        Route::post('/affiliates/update-commission', 'Admin\AffiliateController@updateCommission')->name('affiliates.commission.update');
        Route::post('/affiliates/update-date', 'Admin\AffiliateController@updateDate')->name('affiliates.date.update');
        Route::post('/affiliates/commission', 'Admin\AffiliateController@updateCommission')->name('user.commission.update');

     });


});


