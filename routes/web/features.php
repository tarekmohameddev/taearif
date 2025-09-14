<?php

/*
|--------------------------------------------------------------------------
| Feature-Specific Routes
|--------------------------------------------------------------------------
| Routes for specific features like Hotel Booking, Course Management, etc.
|
*/

Route::group(['prefix' => 'user', 'middleware' => ['auth', 'userstatus', 'Demo']], function () {

    // Hotel Booking Management
    Route::group(['middleware' => 'checkUserPermission:Hotel Booking'], function () {
        Route::get('/rooms_management/settings', 'User\HotelBooking\RoomManagementController@settings')->name('user.rooms_management.settings');
        Route::post('/rooms_management/update_settings', 'User\HotelBooking\RoomManagementController@updateSettings')->name('user.rooms_management.update_settings');
        Route::get('/rooms_management/coupons', 'User\HotelBooking\RoomManagementController@coupons')->name('user.rooms_management.coupons');
        Route::post('/rooms_management/store-coupon', 'User\HotelBooking\RoomManagementController@storeCoupon')->name('user.rooms_management.store_coupon');
        Route::post('/rooms_management/update-coupon', 'User\HotelBooking\RoomManagementController@updateCoupon')->name('user.rooms_management.update_coupon');
        Route::post('/rooms_management/delete-coupon/{id}', 'User\HotelBooking\RoomManagementController@destroyCoupon')->name('user.rooms_management.delete_coupon');
        Route::get('/rooms_management/amenities', 'User\HotelBooking\RoomManagementController@amenities')->name('user.rooms_management.amenities');
        Route::post('/rooms_management/store_amenity/{language}', 'User\HotelBooking\RoomManagementController@storeAmenity')->name('user.rooms_management.store_amenity');
        Route::post('/rooms_management/update_amenity', 'User\HotelBooking\RoomManagementController@updateAmenity')->name('user.rooms_management.update_amenity');
        Route::post('/rooms_management/delete_amenity', 'User\HotelBooking\RoomManagementController@deleteAmenity')->name('user.rooms_management.delete_amenity');
        Route::post('/rooms_management/bulk_delete_amenity', 'User\HotelBooking\RoomManagementController@bulkDeleteAmenity')->name('user.rooms_management.bulk_delete_amenity');
        Route::get('/rooms_management/categories', 'User\HotelBooking\RoomManagementController@categories')->name('user.rooms_management.categories');
        Route::post('/rooms_management/store_category/{language}', 'User\HotelBooking\RoomManagementController@storeCategory')->name('user.rooms_management.store_category');
        Route::post('/rooms_management/update_category', 'User\HotelBooking\RoomManagementController@updateCategory')->name('user.rooms_management.update_category');
        Route::post('/rooms_management/delete_category', 'User\HotelBooking\RoomManagementController@deleteCategory')->name('user.rooms_management.delete_category');
        Route::post('/rooms_management/bulk_delete_category', 'User\HotelBooking\RoomManagementController@bulkDeleteCategory')->name('user.rooms_management.bulk_delete_category');
        Route::get('/rooms_management/rooms', 'User\HotelBooking\RoomManagementController@rooms')->name('user.rooms_management.rooms');
        Route::post('/rooms_management/upload-slider-image', 'User\HotelBooking\RoomManagementController@uploadSliderImage')->name('user.rooms_management.upload_slider_image');
        Route::post('/rooms_management/remove-slider-image', 'User\HotelBooking\RoomManagementController@removeSliderImage')->name('user.rooms_management.remove_slider_image');
        Route::post('/rooms_management/detach-slider-image', 'User\HotelBooking\RoomManagementController@detachImage')->name('user.rooms_management.detach_slider_image');
        Route::get('/rooms_management/create_room', 'User\HotelBooking\RoomManagementController@createRoom')->name('user.rooms_management.create_room');
        Route::post('/rooms_management/store_room', 'User\HotelBooking\RoomManagementController@storeRoom')->name('user.rooms_management.store_room');
        Route::post('/rooms_management/update_featured_room', 'User\HotelBooking\RoomManagementController@updateFeaturedRoom')->name('user.rooms_management.update_featured_room');
        Route::get('/rooms_management/edit_room/{id}', 'User\HotelBooking\RoomManagementController@editRoom')->name('user.rooms_management.edit_room');
        Route::get('/rooms_management/slider_images/{id}', 'User\HotelBooking\RoomManagementController@getSliderImages');
        Route::post('/rooms_management/update_room/{id}', 'User\HotelBooking\RoomManagementController@updateRoom')->name('user.rooms_management.update_room');
        Route::post('/rooms_management/delete_room', 'User\HotelBooking\RoomManagementController@deleteRoom')->name('user.rooms_management.delete_room');
        Route::post('/rooms_management/bulk_delete_room', 'User\HotelBooking\RoomManagementController@bulkDeleteRoom')->name('user.rooms_management.bulk_delete_room');

        // Room Bookings
        Route::get('/room_bookings/all_bookings', 'User\HotelBooking\RoomManagementController@bookings')->name('user.room_bookings.all_bookings');
        Route::get('/room_bookings/paid_bookings', 'User\HotelBooking\RoomManagementController@bookings')->name('user.room_bookings.paid_bookings');
        Route::get('/room_bookings/unpaid_bookings', 'User\HotelBooking\RoomManagementController@bookings')->name('user.room_bookings.unpaid_bookings');
        Route::post('/room_bookings/update_payment_status', 'User\HotelBooking\RoomManagementController@updatePaymentStatus')->name('user.room_bookings.update_payment_status');
        Route::get('/room_bookings/booking_details_and_edit/{id}', 'User\HotelBooking\RoomManagementController@editBookingDetails')->name('user.room_bookings.booking_details_and_edit');
        Route::post('/room_bookings/update_booking', 'User\HotelBooking\RoomManagementController@updateBooking')->name('user.room_bookings.update_booking');
        Route::post('/room_bookings/send_mail', 'User\HotelBooking\RoomManagementController@sendMail')->name('user.room_bookings.send_mail');
        Route::post('/room_bookings/delete_booking/{id}', 'User\HotelBooking\RoomManagementController@deleteBooking')->name('user.room_bookings.delete_booking');
        Route::post('/room_bookings/bulk_delete_booking', 'User\HotelBooking\RoomManagementController@bulkDeleteBooking')->name('user.room_bookings.bulk_delete_booking');
        Route::get('/room_bookings/get_booked_dates', 'User\HotelBooking\RoomManagementController@bookedDates')->name('user.room_bookings.get_booked_dates');
        Route::get('/room_bookings/booking_form', 'User\HotelBooking\RoomManagementController@bookingForm')->name('user.room_bookings.booking_form');
        Route::post('/room_bookings/make_booking', 'User\HotelBooking\RoomManagementController@makeBooking')->name('user.room_bookings.make_booking');
    });

    // Course Management
    Route::middleware('checkUserPermission:Course Management')->prefix('/course-management')->group(function () {
        // Instructor Management
        Route::get('/instructors', 'User\CourseManagement\Instructor\InstructorController@index')->name('user.instructors');
        Route::get('/create-instructor', 'User\CourseManagement\Instructor\InstructorController@create')->name('user.create_instructor');
        Route::post('/store-instructor', 'User\CourseManagement\Instructor\InstructorController@store')->name('user.store_instructor');
        Route::post('/instructor/{id}/update-featured', 'User\CourseManagement\Instructor\InstructorController@updateFeatured')->name('user.instructor.update_featured');
        Route::get('/edit-instructor/{id}', 'User\CourseManagement\Instructor\InstructorController@edit')->name('user.edit_instructor');
        Route::post('/update-instructor/{id}', 'User\CourseManagement\Instructor\InstructorController@update')->name('user.update_instructor');
        Route::post('/delete-instructor/{id}', 'User\CourseManagement\Instructor\InstructorController@destroy')->name('user.delete_instructor');
        Route::post('/bulk-delete-instructor', 'User\CourseManagement\Instructor\InstructorController@bulkDestroy')->name('user.bulk_delete_instructor');

        // Instructor Social Links
        Route::prefix('/instructor')->group(function () {
            Route::get('/{id}/social-links', 'User\CourseManagement\Instructor\SocialLinkController@links')->name('user.instructor.social_links');
            Route::post('/{id}/store-social-link', 'User\CourseManagement\Instructor\SocialLinkController@storeLink')->name('user.instructor.store_social_link');
            Route::get('/{instructor_id}/edit-social-link/{id}', 'User\CourseManagement\Instructor\SocialLinkController@editLink')->name('user.instructor.edit_social_link');
            Route::post('/update-social-link/{id}', 'User\CourseManagement\Instructor\SocialLinkController@updateLink')->name('user.instructor.update_social_link');
            Route::post('/delete-social-link/{id}', 'User\CourseManagement\Instructor\SocialLinkController@destroyLink')->name('user.instructor.delete_social_link');
        });

        // Category Management
        Route::get('/categories', 'User\CourseManagement\CategoryController@index')->name('user.course_management.categories');
        Route::post('/store-category', 'User\CourseManagement\CategoryController@store')->name('user.course_management.store_category');
        Route::post('/category/{id}/update-featured', 'User\CourseManagement\CategoryController@updateFeatured')->name('user.course_management.category.update_featured');
        Route::put('/update-category', 'User\CourseManagement\CategoryController@update')->name('user.course_management.update_category');
        Route::post('/delete-category/{id}', 'User\CourseManagement\CategoryController@destroy')->name('user.course_management.delete_category');
        Route::post('/bulk-delete-category', 'User\CourseManagement\CategoryController@bulkDestroy')->name('user.course_management.bulk_delete_category');

        // Course Management
        Route::get('/courses', 'User\CourseManagement\CourseController@index')->name('user.course_management.courses');
        Route::get('/create-course', 'User\CourseManagement\CourseController@create')->name('user.course_management.create_course');
        Route::post('/store-course', 'User\CourseManagement\CourseController@store')->name('user.course_management.store_course');
        Route::post('/course/{id}/update-status', 'User\CourseManagement\CourseController@updateStatus')->name('user.course_management.course.update_status');
        Route::post('/course/{id}/update-featured', 'User\CourseManagement\CourseController@updateFeatured')->name('user.course_management.course.update_featured');
        Route::get('/edit-course/{id}', 'User\CourseManagement\CourseController@edit')->name('user.course_management.edit_course');
        Route::post('/update-course/{id}', 'User\CourseManagement\CourseController@update')->name('user.course_management.update_course');
        Route::post('/delete-course/{id}', 'User\CourseManagement\CourseController@destroy')->name('user.course_management.delete_course');
        Route::post('/bulk-delete-course', 'User\CourseManagement\CourseController@bulkDestroy')->name('user.course_management.bulk_delete_course');

        // Course Modules
        Route::prefix('/course')->group(function () {
            Route::get('/{id}/modules', 'User\CourseManagement\ModuleController@index')->name('user.course_management.course.modules');
            Route::post('/{id}/store-module', 'User\CourseManagement\ModuleController@store')->name('user.course_management.course.store_module');
            Route::post('/update-module', 'User\CourseManagement\ModuleController@update')->name('user.course_management.course.update_module');
            Route::post('/delete-module/{id}', 'User\CourseManagement\ModuleController@destroy')->name('user.course_management.course.delete_module');
            Route::post('/bulk-delete-module', 'User\CourseManagement\ModuleController@bulkDestroy')->name('user.course_management.course.bulk_delete_module');
        });

        // Module Lessons
        Route::prefix('/module')->group(function () {
            Route::post('/{id}/store-lesson', 'User\CourseManagement\LessonController@store')->name('user.course_management.module.store_lesson');
            Route::post('/update-lesson', 'User\CourseManagement\LessonController@update')->name('user.course_management.module.update_lesson');
        });

        // Lesson Contents
        Route::prefix('/lesson')->group(function () {
            Route::get('/{id}/contents', 'User\CourseManagement\LessonContentController@contents')->name('user.course_management.lesson.contents');
            Route::post('/upload-video', 'User\CourseManagement\LessonContentController@uploadVideo')->name('user.course_management.lesson.upload_video');
            Route::post('/video-preview', 'User\CourseManagement\LessonContentController@videoPreview')->name('user.course_management.lesson.video_preview');
            Route::post('/remove-video', 'User\CourseManagement\LessonContentController@removeVideo')->name('user.course_management.lesson.remove_video');
            Route::post('/{id}/store-video', 'User\CourseManagement\LessonContentController@storeVideo')->name('user.course_management.lesson.store_video');
            Route::post('/upload-file', 'User\CourseManagement\LessonContentController@uploadFile')->name('user.course_management.lesson.upload_file');
            Route::post('/remove-file', 'User\CourseManagement\LessonContentController@removeFile')->name('user.course_management.lesson.remove_file');
            Route::post('/{id}/store-file', 'User\CourseManagement\LessonContentController@storeFile')->name('user.course_management.lesson.store_file');
            Route::get('/download-file/{id}', 'User\CourseManagement\LessonContentController@downloadFile')->name('user.course_management.lesson.download_file');
            Route::post('/{id}/store-text', 'User\CourseManagement\LessonContentController@storeText')->name('user.course_management.lesson.store_text');
            Route::post('/update-text', 'User\CourseManagement\LessonContentController@updateText')->name('user.course_management.lesson.update_text');
            Route::post('/{id}/store-code', 'User\CourseManagement\LessonContentController@storeCode')->name('user.course_management.lesson.store_code');
            Route::post('/update-code', 'User\CourseManagement\LessonContentController@updateCode')->name('user.course_management.lesson.update_code');
            Route::post('/delete-content/{id}', 'User\CourseManagement\LessonContentController@destroyContent')->name('user.course_management.lesson.delete_content');
            Route::get('/{id}/create-quiz', 'User\CourseManagement\LessonQuizController@create')->name('user.course_management.lesson.create_quiz');
            Route::post('/{id}/store-quiz', 'User\CourseManagement\LessonQuizController@store')->name('user.course_management.lesson.store_quiz');
            Route::get('/{id}/manage-quiz', 'User\CourseManagement\LessonQuizController@index')->name('user.course_management.lesson.manage_quiz');
            Route::get('/{lessonId}/edit-quiz/{quizId}', 'User\CourseManagement\LessonQuizController@edit')->name('user.course_management.lesson.edit_quiz');
            Route::get('/get-ans/{id}', 'User\CourseManagement\LessonQuizController@getAns')->name('user.course_management.lesson.get_ans');
            Route::post('/update-quiz/{id}', 'User\CourseManagement\LessonQuizController@update')->name('user.course_management.lesson.update_quiz');
            Route::post('/delete-quiz/{id}', 'User\CourseManagement\LessonQuizController@destroy')->name('user.course_management.lesson.delete_quiz');
            Route::post('/sort-contents', 'User\CourseManagement\LessonContentController@sort')->name('user.course_management.lesson.sort_contents');
        });

        Route::post('/module/delete-lesson/{id}', 'User\CourseManagement\LessonController@destroy')->name('user.course_management.module.delete_lesson');

        // Course FAQs
        Route::prefix('/course')->group(function () {
            Route::get('/{id}/faqs', 'User\CourseManagement\CourseFaqController@index')->name('user.course_management.course.faqs');
            Route::post('/{id}/store-faq', 'User\CourseManagement\CourseFaqController@store')->name('user.course_management.course.store_faq');
            Route::post('/update-faq', 'User\CourseManagement\CourseFaqController@update')->name('user.course_management.course.update_faq');
            Route::post('/delete-faq/{id}', 'User\CourseManagement\CourseFaqController@destroy')->name('user.course_management.course.delete_faq');
            Route::post('/bulk-delete-faq', 'User\CourseManagement\CourseFaqController@bulkDestroy')->name('user.course_management.course.bulk_delete_faq');
            Route::get('/{id}/thanks-page', 'User\CourseManagement\CourseController@thanksPage')->name('user.course_management.course.thanks_page');
            Route::post('/{id}/update-thanks-page', 'User\CourseManagement\CourseController@updateThanksPage')->name('user.course_management.course.update_thanks_page');
            Route::get('/{id}/certificate-settings', 'User\CourseManagement\CourseController@certificateSettings')->name('user.course_management.course.certificate_settings');
            Route::post('/{id}/update-certificate-settings', 'User\CourseManagement\CourseController@updateCertificateSettings')->name('user.course_management.course.update_certificate_settings');
        });

        // Coupons
        Route::get('/coupons', 'User\CourseManagement\CouponController@index')->name('user.course_management.coupons');
        Route::post('/store-coupon', 'User\CourseManagement\CouponController@store')->name('user.course_management.store_coupon');
        Route::post('/update-coupon', 'User\CourseManagement\CouponController@update')->name('user.course_management.update_coupon');
        Route::post('/delete-coupon/{id}', 'User\CourseManagement\CouponController@destroy')->name('user.course_management.delete_coupon');

        // Course Enrolments
        Route::get('/course-enrolments', 'User\CourseManagement\EnrolmentController@index')->name('user.course_enrolments');
        Route::prefix('/course-enrolment')->group(function () {
            Route::post('/{id}/update-payment-status', 'User\CourseManagement\EnrolmentController@updatePaymentStatus')->name('user.course_enrolment.update_payment_status');
            Route::get('/{id}/details', 'User\CourseManagement\EnrolmentController@show')->name('user.course_enrolment.details');
            Route::post('/{id}/delete', 'User\CourseManagement\EnrolmentController@destroy')->name('user.course_enrolment.delete');
        });
        Route::get('/course-enrolments/report', 'User\CourseManagement\EnrolmentController@report')->name('user.course_enrolments.report');
        Route::get('/course-enrolments/export', 'User\CourseManagement\EnrolmentController@export')->name('user.course_enrolments.export');
        Route::post('/course-enrolments/bulk-delete', 'User\CourseManagement\EnrolmentController@bulkDestroy')->name('user.course_enrolments.bulk_delete');
    });

    // Donation Management
    Route::group(['middleware' => 'checkUserPermission:Donation Management'], function () {
        Route::get('/donations', 'User\DonationManagement\DonationController@index')->name('user.donation.index');
        Route::get('/donation/catgories', 'User\DonationManagement\DonationCategoryController@index')->name('user.donation.categories');
        Route::post('/donation/catgories/store/{language}', 'User\DonationManagement\DonationCategoryController@store')->name('user.donation.category.store');
        Route::post('/donation/catgories/update', 'User\DonationManagement\DonationCategoryController@update')->name('user.donation.category.update');
        Route::post('/donation/catgories/delete', 'User\DonationManagement\DonationCategoryController@destroy')->name('user.donation.category.destroy');
        Route::post('/donation/catgories/bulk-delete', 'User\DonationManagement\DonationCategoryController@bulkDestroy')->name('user.donation.category.bulkDestroy');
        Route::get('/donation/create', 'User\DonationManagement\DonationController@create')->name('user.donation.create');
        Route::get('/donation/settings', 'User\DonationManagement\DonationController@settings')->name('user.donation.settings');
        Route::post('/donation/settings', 'User\DonationManagement\DonationController@updateSettings')->name('user.donation.settings');
        Route::post('/donation/store', 'User\DonationManagement\DonationController@store')->name('user.donation.store');
        Route::get('/donation/{id}/edit', 'User\DonationManagement\DonationController@edit')->name('user.donation.edit');
        Route::post('/donation/{id}/update', 'User\DonationManagement\DonationController@update')->name('user.donation.update');
        Route::post('/donation/{id}/uploadUpdate', 'User\DonationManagement\DonationController@uploadUpdate')->name('user.donation.uploadUpdate');
        Route::post('/donation/delete', 'User\DonationManagement\DonationController@delete')->name('user.donation.delete');
        Route::post('/donation/bulk-delete', 'User\DonationManagement\DonationController@bulkDelete')->name('user.donation.bulk.delete');
        Route::get('/donations/payment-log', 'User\DonationManagement\DonationController@paymentLog')->name('user.donation.payment.log');
        Route::post('/donations/payment/delete', 'User\DonationManagement\DonationController@paymentDelete')->name('user.donation.payment.delete');
        Route::post('/donations/bulk/delete', 'User\DonationManagement\DonationController@bulkPaymentDelete')->name('user.donation.payment.bulk.delete');
        Route::post('/donations/payment-log-update', 'User\DonationManagement\DonationController@paymentLogUpdate')->name('user.donation.payment.log.update');
        Route::get('/donation/report', 'User\DonationManagement\DonationController@report')->name('user.donation.report');
        Route::get('/donation/export', 'User\DonationManagement\DonationController@exportReport')->name('user.donation.export');
    });

});
