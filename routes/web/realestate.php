<?php

/*
|--------------------------------------------------------------------------
| Real Estate Management Routes
|--------------------------------------------------------------------------
| Routes for Real Estate Management and vCard features
|
*/

Route::group(['prefix' => 'user', 'middleware' => ['auth', 'userstatus', 'Demo']], function () {

    // Real Estate Management
    Route::prefix('realestate')->middleware('checkUserPermission:Real Estate Management')->group(function () {
        Route::prefix('property')->group(function () {
            // Property Categories
            Route::controller('User\RealestateManagement\ManageProperty\CategoryController')->group(function () {
                Route::get('/categories', 'index')->name('user.property_management.categories');
                Route::post('/store-category', 'store')->name('user.property_management.store_category');
                Route::post('/update-category', 'update')->name('user.property_management.update_category');
                Route::post('/update-category-featured', 'updateFeatured')->name('user.property_management.update_category_featured');
                Route::post('/delete-category', 'destroy')->name('user.property_management.delete_category');
                Route::post('/bulk-delete-category', 'bulkDestroy')->name('user.property_management.bulk_delete_category');
            });

            // Property Amenities
            Route::controller('User\RealestateManagement\ManageProperty\AmenityController')->group(function () {
                Route::get('/amenity', 'index')->name('user.property_management.amenities');
                Route::post('/store-amenity', 'store')->name('user.property_management.store_amenity');
                Route::post('/update-amenity', 'update')->name('user.property_management.update_amenity');
                Route::post('/delete-amenity', 'destroy')->name('user.property_management.delete_amenity');
                Route::post('/bulk-delete-amenity', 'bulkDestroy')->name('user.property_management.bulk_delete_amenity');
            });

            // Property Countries
            Route::controller('User\RealestateManagement\ManageProperty\CountryController')->group(function () {
                Route::get('/country', 'index')->name('user.property_management.countries');
                Route::get('/countries/{langId}', 'getCountries')->name('user.property_management.get_countries');
                Route::post('/store-country', 'store')->name('user.property_management.store_country');
                Route::post('/update-country', 'update')->name('user.property_management.update_country');
                Route::post('/delete-country', 'destroy')->name('user.property_management.delete_country');
                Route::post('/bulk-delete-country', 'bulkDestroy')->name('user.property_management.bulk_delete_country');
            });

            // Property States
            Route::controller('User\RealestateManagement\ManageProperty\StateController')->group(function () {
                Route::get('/states', 'index')->name('user.property_management.states');
                Route::get('/get-state/{country}', 'getState')->name('user.property_management.get_state');
                Route::get('/states/{langId}', 'langStates')->name('user.property_management.lang_states');
                Route::get('/get-states-cities/{country}', 'getStateCities')->name('user.property_management.get_state_cities');
                Route::post('/store-state', 'store')->name('user.property_management.store_state');
                Route::post('/update-state', 'update')->name('user.property_management.update_state');
                Route::post('/delete-state', 'destroy')->name('user.property_management.delete_state');
                Route::post('/bulk-delete-state', 'bulkDestroy')->name('user.property_management.bulk_delete_state');
            });

            // Property Cities
            Route::controller('User\RealestateManagement\ManageProperty\CityController')->group(function () {
                Route::get('/cities', 'index')->name('user.property_management.cities');
                Route::get('/get-cities', 'getCities')->name('user.property_management.get_cities');
                Route::post('/store-city', 'store')->name('user.property_management.store_city');
                Route::post('/update-city', 'update')->name('user.property_management.update_city');
                Route::post('/update-featured', 'updateFeatured')->name('user.property_management.update_city_featured');
                Route::post('/delete-city', 'destroy')->name('user.property_management.delete_city');
                Route::post('/bulk-delete-city', 'bulkDestroy')->name('user.property_management.bulk_delete_city');
            });

            // Region routes
            Route::get('/get-governorates/{region_id}', 'User\RegionController@getGovernorates')->name('get.governorates');

            // Properties
            Route::controller('User\RealestateManagement\ManageProperty\PropertyController')->group(function () {
                Route::get('/settings', 'settings')->name('user.property_management.settings');
                Route::post('/update-settings', 'update_settings')->name('user.property_management.update_settings');
                Route::get('/properties', 'index')->name('user.property_management.properties');
                Route::get('/type', 'type')->name('user.property_management.type');
                Route::get('/create', 'create')->name('user.property_management.create_property');
                Route::post('/store', 'store')->name('user.property_management.store_property');
                Route::post('/update_featured', 'updateFeatured')->name('user.property_management.update_featured');
                Route::post('update_status', 'updateStatus')->name('user.property_management.update_status');
                Route::get('edit-property/{id}', 'edit')->name('user.property_management.edit');
                Route::post('update/{id}', 'update')->name('user.property_management.update_property');
                Route::post('specification/delete', 'specificationDelete')->name('user.property_management.specification_delete');
                Route::post('/featured-payment', 'featuredPayment')->name('user.property_management.featured_payment');
                Route::post('/img-db-remove', 'imagedbrmv')->name('user.property.imgdbrmv');
                Route::post('delete', 'delete')->name('user.property_management.delete_property');
                Route::post('bulk-delete', 'bulkDelete')->name('user.property_management.bulk_delete_property');
            });

            // Property Messages
            Route::controller('User\RealestateManagement\ManageProperty\PropertyMessageController')->group(function () {
                Route::get('/messages', 'propertyMessages')->name('user.property_management.property_message');
                Route::post('/message-delete', 'destroy')->name('user.property_management.property_message.destroy');
            });
        });

        // Project Management
        Route::prefix('manage-project')->controller('User\RealestateManagement\ManageProject\ProjectController')->group(function () {
            Route::get('/projects', 'index')->name('user.project_management.projects');
            Route::get('/create', 'create')->name('user.project_management.create_project');
            Route::post('/store', 'store')->name('user.project_management.store_project');
            Route::post('/update_featured', 'updateFeatured')->name('user.project_management.update_featured');
            Route::post('update_status', 'updateStatus')->name('user.project_management.update_status');
            Route::get('edit-project/{id}', 'edit')->name('user.project_management.edit');
            Route::post('update/{id}', 'update')->name('user.project_management.update_project');
            Route::post('specification/delete', 'specificationDelete')->name('user.project_management.specification_delete');
            Route::post('/delete', 'destroy')->name('user.project_management.delete_project');
            Route::post('/bulk-delete', 'bulkDestroy')->name('user.project_management.bulk_delete_project');
            Route::post('/img-db-remove', 'galleryImageDbrmv')->name('user.project.gallery_imgdbrmv');
            Route::post('/floor-plan-img-db-remove', 'floorPlanImageDbrmv')->name('user.project.floor_plan_imgdbrmv');

            // Project Types
            Route::prefix('type')->controller('User\RealestateManagement\ManageProject\TypeController')->group(function () {
                Route::get('/{id}', 'index')->name('user.project_management.project_types');
                Route::post('/store', 'store')->name('user.project_management.project_type.store');
                Route::post('/update', 'update')->name('user.project_management.project_type.update');
                Route::post('/delete', 'delete')->name('user.project_management.delete_type');
                Route::post('/bulk-delete', 'bulkDelete')->name('user.project_management.bulk_delete_type');
            });
        });
    });

    // vCard Management
    Route::group(['middleware' => 'checkUserPermission:vCard'], function () {
        Route::get('/vcard', 'User\VcardController@vcard')->name('user.vcard');
        Route::get('/vcard/create', 'User\VcardController@create')->name('user.vcard.create');
        Route::post('/vcard/store', 'User\VcardController@store')->name('user.vcard.store');
        Route::get('/vcard/{id}/edit', 'User\VcardController@edit')->name('user.vcard.edit');
        Route::post('/vcard/update', 'User\VcardController@update')->name('user.vcard.update');
        Route::post('/vcard/delete', 'User\VcardController@delete')->name('user.vcard.delete');
        Route::post('/vcard/bulk/delete', 'User\VcardController@bulkDelete')->name('user.vcard.bulk.delete');
        Route::get('/vcard/{id}/information', 'User\VcardController@information')->name('user.vcard.information');

        // vCard Services
        Route::get('/vcard/{id}/services', 'User\VcardController@services')->name('user.vcard.services');
        Route::post('/vcard/service/store', 'User\VcardController@serviceStore')->name('user.vcard.serviceStore');
        Route::post('/vcard/service/update', 'User\VcardController@serviceUpdate')->name('user.vcard.serviceUpdate');
        Route::post('/vcard/service/delete', 'User\VcardController@serviceDelete')->name('user.vcard.serviceDelete');
        Route::post('/vcard/bulk/service/delete', 'User\VcardController@bulkServiceDelete')->name('user.vcard.bulkServiceDelete');

        // vCard Projects
        Route::get('/vcard/{id}/projects', 'User\VcardController@projects')->name('user.vcard.projects');
        Route::post('/vcard/project/store', 'User\VcardController@projectStore')->name('user.vcard.projectStore');
        Route::post('/vcard/project/update', 'User\VcardController@projectUpdate')->name('user.vcard.projectUpdate');
        Route::post('/vcard/project/delete', 'User\VcardController@projectDelete')->name('user.vcard.projectDelete');
        Route::post('/vcard/bulk/project/delete', 'User\VcardController@bulkProjectDelete')->name('user.vcard.bulkProjectDelete');

        // vCard Testimonials
        Route::get('/vcard/{id}/testimonials', 'User\VcardController@testimonials')->name('user.vcard.testimonials');
        Route::post('/vcard/testimonial/store', 'User\VcardController@testimonialStore')->name('user.vcard.testimonialStore');
        Route::post('/vcard/testimonial/update', 'User\VcardController@testimonialUpdate')->name('user.vcard.testimonialUpdate');
        Route::post('/vcard/testimonial/delete', 'User\VcardController@testimonialDelete')->name('user.vcard.testimonialDelete');
        Route::post('/vcard/bulk/testimonial/delete', 'User\VcardController@bulkTestimonialDelete')->name('user.vcard.bulkTestimonialDelete');

        // vCard About and Preferences
        Route::get('/vcard/{id}/about', 'User\VcardController@about')->name('user.vcard.about');
        Route::post('/vcard/aboutUpdate', 'User\VcardController@aboutUpdate')->name('user.vcard.aboutUpdate');
        Route::get('/vcard/{id}/preferences', 'User\VcardController@preferences')->name('user.vcard.preferences');
        Route::post('/vcard/{id}/prefUpdate', 'User\VcardController@prefUpdate')->name('user.vcard.prefUpdate');
        Route::get('/vcard/{id}/color', 'User\VcardController@color')->name('user.vcard.color');
        Route::post('/vcard/{id}/colorUpdate', 'User\VcardController@colorUpdate')->name('user.vcard.colorUpdate');
        Route::get('/vcard/{id}/keywords', 'User\VcardController@keywords')->name('user.vcard.keywords');
        Route::post('/vcard/{id}/keywordsUpdate', 'User\VcardController@keywordsUpdate')->name('user.vcard.keywordsUpdate');
    });

    // QR Builder
    Route::group(['middleware' => 'checkUserPermission:QR Builder'], function () {
        Route::get('/saved/qrs', 'User\QrController@index')->name('user.qrcode.index');
        Route::post('/saved/qr/delete', 'User\QrController@delete')->name('user.qrcode.delete');
        Route::post('/saved/qr/bulk-delete', 'User\QrController@bulkDelete')->name('user.qrcode.bulk.delete');
        Route::get('/qr-code', 'User\QrController@qrCode')->name('user.qrcode');
        Route::post('/qr-code/generate', 'User\QrController@generate')->withoutMiddleware('Demo')->name('user.qrcode.generate');
        Route::get('/qr-code/clear', 'User\QrController@clear')->name('user.qrcode.clear');
        Route::post('/qr-code/save', 'User\QrController@save')->name('user.qrcode.save');
    });

});
