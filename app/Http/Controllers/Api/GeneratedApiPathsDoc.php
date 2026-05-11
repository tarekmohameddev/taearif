<?php

namespace App\Http\Controllers\Api;

/**
 * Auto-generated OpenAPI path items for Main API (routes/api.php).
 * Do not edit by hand. Regenerate with: php artisan swagger:generate-api-paths
 *
 *  * @OA\PathItem(
 *
 *     path="/affiliate",
 *
 *     @OA\Get(
 *         operationId="get_affiliate_0",
 *         tags={"Affiliate"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/affiliate/register",
 *
 *     @OA\Post(
 *         operationId="post_affiliate_register_0",
 *         tags={"Affiliate"},
 *         summary="Register", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"fullname","bank_name","bank_account_number","iban"},
 *             @OA\Property(property="fullname", type="string", maxLength=255),
 *             @OA\Property(property="bank_name", type="string", maxLength=255),
 *             @OA\Property(property="bank_account_number", type="string", maxLength=30),
 *             @OA\Property(property="iban", type="string", maxLength=34),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/analytics/live-test",
 *
 *     @OA\Get(
 *         operationId="get_analytics_live_test_0",
 *         tags={"Analytics"},
 *         summary="Live Test", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/analytics/page-locations",
 *
 *     @OA\Get(
 *         operationId="get_analytics_page_locations_0",
 *         tags={"Analytics"},
 *         summary="Get Page Locations", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/analytics/realtime",
 *
 *     @OA\Get(
 *         operationId="get_analytics_realtime_0",
 *         tags={"Analytics"},
 *         summary="Get Realtime", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/analytics/search",
 *
 *     @OA\Get(
 *         operationId="get_analytics_search_0",
 *         tags={"Analytics"},
 *         summary="Search Analytics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/analytics/tenants",
 *
 *     @OA\Get(
 *         operationId="get_analytics_tenants_0",
 *         tags={"Analytics"},
 *         summary="Get Tenants List", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/analytics/today",
 *
 *     @OA\Get(
 *         operationId="get_analytics_today_0",
 *         tags={"Analytics"},
 *         summary="Get Today", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps",
 *
 *     @OA\Get(
 *         operationId="get_apps_0",
 *         tags={"Apps"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/install",
 *
 *     @OA\Post(
 *         operationId="post_apps_install_0",
 *         tags={"Apps"},
 *         summary="Install", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"app_id"},
 *             @OA\Property(property="app_id", type="integer"),
 *             @OA\Property(property="settings", type="object", properties={@OA\Property(property="phone_number", type="string", maxLength=20),@OA\Property(property="token", type="string", maxLength=255)}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/payment/callback/{gateway}",
 *
 *     @OA\Post(
 *         operationId="post_apps_payment_callback_gateway_0",
 *         tags={"Apps"},
 *         summary="Handle Callback",
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"trandata"},
 *             @OA\Property(property="trandata", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/payments",
 *
 *     @OA\Get(
 *         operationId="get_apps_payments_0",
 *         tags={"Apps"},
 *         summary="Get Payment History", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/uninstall/{appId}",
 *
 *     @OA\Post(
 *         operationId="post_apps_uninstall_app_d_0",
 *         tags={"Apps"},
 *         summary="Uninstall", security={{"sanctum":{}}},
 *         @OA\Parameter(name="appId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/whatsapp",
 *
 *     @OA\Get(
 *         operationId="get_apps_whatsapp_0",
 *         tags={"Apps"},
 *         summary="Whatsapp", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/whatsapp/install",
 *
 *     @OA\Post(
 *         operationId="post_apps_whatsapp_install_0",
 *         tags={"Apps"},
 *         summary="Install Whatsapp", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="settings", type="object", properties={@OA\Property(property="phone_number", type="string", maxLength=20),@OA\Property(property="token", type="string", maxLength=255)}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/whatsapp/uninstall",
 *
 *     @OA\Post(
 *         operationId="post_apps_whatsapp_uninstall_0",
 *         tags={"Apps"},
 *         summary="Uninstall Whatsapp", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/{appId}/payment/verify",
 *
 *     @OA\Post(
 *         operationId="post_apps_app_d_payment_verify_0",
 *         tags={"Apps"},
 *         summary="Verify Payment", security={{"sanctum":{}}},
 *         @OA\Parameter(name="appId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"payment_transaction_id"},
 *             @OA\Property(property="payment_transaction_id", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/apps/{appId}/purchase-url",
 *
 *     @OA\Get(
 *         operationId="get_apps_app_d_purchase_url_0",
 *         tags={"Apps"},
 *         summary="Get Purchase Url", security={{"sanctum":{}}},
 *         @OA\Parameter(name="appId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/auth/forgot-password",
 *
 *     @OA\Post(
 *         operationId="post_auth_forgot_password_0",
 *         tags={"Auth"},
 *         summary="Forgot Password",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"identifier","method"},
 *             @OA\Property(property="identifier", type="string"),
 *             @OA\Property(property="method", type="string", enum={"email","phone"}),
 *             @OA\Property(property="country_code", type="string", maxLength=10),
 *             @OA\Property(property="recaptcha_token", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/auth/google/callback",
 *
 *     @OA\Get(
 *         operationId="get_auth_google_callback_0",
 *         tags={"Auth"},
 *         summary="Callback",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/auth/google/redirect",
 *
 *     @OA\Get(
 *         operationId="get_auth_google_redirect_0",
 *         tags={"Auth"},
 *         summary="Redirect",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/auth/send-otp",
 *
 *     @OA\Post(
 *         operationId="post_auth_send_otp_0",
 *         tags={"Auth"},
 *         summary="Send Otp",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"phone"},
 *             @OA\Property(property="phone", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/auth/verify-otp",
 *
 *     @OA\Post(
 *         operationId="post_auth_verify_otp_0",
 *         tags={"Auth"},
 *         summary="Verify Otp",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"otp"},
 *             @OA\Property(property="otp", type="string"),
 *             @OA\Property(property="phone", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/auth/verify-reset-code",
 *
 *     @OA\Post(
 *         operationId="post_auth_verify_reset_code_0",
 *         tags={"Auth"},
 *         summary="Verify Reset Code",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"code","new_password"},
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="new_password", type="string", minLength=8),
 *             @OA\Property(property="recaptcha_token", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/blog-categories",
 *
 *     @OA\Get(
 *         operationId="get_blog_categories_0",
 *         tags={"Blog Categories"},
 *         summary="Categories", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/blogs",
 *
 *     @OA\Post(
 *         operationId="post_blogs_0",
 *         tags={"Blogs"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","content"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="excerpt", type="string"),
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"published","draft"}),
 *             @OA\Property(property="tags", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="featured", type="boolean"),
 *             @OA\Property(property="seo_title", type="string", maxLength=255),
 *             @OA\Property(property="seo_description", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_blogs_1",
 *         tags={"Blogs"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/blogs/upload-image",
 *
 *     @OA\Post(
 *         operationId="post_blogs_upload_image_0",
 *         tags={"Blogs"},
 *         summary="Upload Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"image"},
 *             @OA\Property(property="image", type="string", format="binary", maxLength=2048),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/blogs/{id}",
 *
 *     @OA\Post(
 *         operationId="post_blogs_id_0",
 *         tags={"Blogs"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","content"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="excerpt", type="string"),
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"published","draft"}),
 *             @OA\Property(property="tags", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="featured", type="boolean"),
 *             @OA\Property(property="seo_title", type="string", maxLength=255),
 *             @OA\Property(property="seo_description", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_blogs_id_1",
 *         tags={"Blogs"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_blogs_id_2",
 *         tags={"Blogs"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/buildings",
 *
 *     @OA\Get(
 *         operationId="get_buildings_0",
 *         tags={"Buildings"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_buildings_1",
 *         tags={"Buildings"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="deed_number", type="string", maxLength=255),
 *             @OA\Property(property="water_meter_numbers", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="electricity_meter_numbers", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="image", type="string", format="binary", maxLength=5120),
 *             @OA\Property(property="deed_image", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/buildings/upload-deed-image",
 *
 *     @OA\Post(
 *         operationId="post_buildings_upload_deed_image_0",
 *         tags={"Buildings"},
 *         summary="Upload Deed Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"deed_image"},
 *             @OA\Property(property="deed_image", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/buildings/upload-image",
 *
 *     @OA\Post(
 *         operationId="post_buildings_upload_image_0",
 *         tags={"Buildings"},
 *         summary="Upload Building Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"image"},
 *             @OA\Property(property="image", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/buildings/{id}",
 *
 *     @OA\Get(
 *         operationId="get_buildings_id_0",
 *         tags={"Buildings"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_buildings_id_1",
 *         tags={"Buildings"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="deed_number", type="string", maxLength=255),
 *             @OA\Property(property="water_meter_numbers", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="electricity_meter_numbers", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="image", type="string", maxLength=500),
 *             @OA\Property(property="deed_image", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_buildings_id_2",
 *         tags={"Buildings"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/categories",
 *
 *     @OA\Get(
 *         operationId="get_categories_0",
 *         tags={"Categories"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_categories_1",
 *         tags={"Categories"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/categories/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_categories_slug_0",
 *         tags={"Categories"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_categories_slug_1",
 *         tags={"Categories"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_categories_slug_2",
 *         tags={"Categories"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/categories/{slug}/posts",
 *
 *     @OA\Get(
 *         operationId="get_categories_slug_posts_0",
 *         tags={"Categories"},
 *         summary="Posts", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/chat",
 *
 *     @OA\Post(
 *         operationId="post_chat_0",
 *         tags={"Chat"},
 *         summary="Chat", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"message","user_id","whatsapp_number"},
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="whatsapp_number", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/about",
 *
 *     @OA\Get(
 *         operationId="get_content_about_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_content_about_1",
 *         tags={"Content"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","features","status"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="subtitle", type="string", maxLength=255),
 *             @OA\Property(property="history", type="string"),
 *             @OA\Property(property="mission", type="string"),
 *             @OA\Property(property="vision", type="string"),
 *             @OA\Property(property="image_path", type="string"),
 *             @OA\Property(property="features", type="object", properties={@OA\Property(property="*.id", type="integer"),@OA\Property(property="*.title", type="string", maxLength=255),@OA\Property(property="*.description", type="string")}),
 *             @OA\Property(property="status", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/banner",
 *
 *     @OA\Get(
 *         operationId="get_content_banner_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_content_banner_1",
 *         tags={"Content"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"banner_type","status","static","slider","common"},
 *             @OA\Property(property="banner_type", type="string", enum={"static","slider"}),
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="static", type="object", properties={@OA\Property(property="enabled", type="boolean"),@OA\Property(property="image", type="string"),@OA\Property(property="title", type="string", maxLength=200),@OA\Property(property="subtitle", type="string", maxLength=500),@OA\Property(property="caption", type="string", maxLength=500),@OA\Property(property="buttonText", type="string", maxLength=50),@OA\Property(property="buttonUrl", type="string", maxLength=255),@OA\Property(property="buttonStyle", type="string", enum={"primary","secondary","outline","link"}),@OA\Property(property="textAlignment", type="string", enum={"left","center","right"}),@OA\Property(property="overlayColor", type="string", maxLength=30),@OA\Property(property="textColor", type="string", maxLength=30)}),
 *             @OA\Property(property="slider", type="object", properties={@OA\Property(property="enabled", type="boolean"),@OA\Property(property="slides", type="array", @OA\Items(type="string")),@OA\Property(property="slides.*.id", type="string"),@OA\Property(property="slides.*.image", type="string"),@OA\Property(property="slides.*.title", type="string", maxLength=200),@OA\Property(property="slides.*.subtitle", type="string", maxLength=500),@OA\Property(property="slides.*.caption", type="string", maxLength=500),@OA\Property(property="slides.*.buttonText", type="string", maxLength=50),@OA\Property(property="slides.*.buttonUrl", type="string", maxLength=255),@OA\Property(property="slides.*.buttonStyle", type="string", enum={"primary","secondary","outline","link"}),@OA\Property(property="slides.*.textAlignment", type="string", enum={"left","center","right"}),@OA\Property(property="slides.*.enabled", type="boolean"),@OA\Property(property="autoplay", type="boolean"),@OA\Property(property="autoplaySpeed", type="integer", minimum=1000, maximum=10000),@OA\Property(property="showArrows", type="boolean"),@OA\Property(property="showDots", type="boolean"),@OA\Property(property="animation", type="string", enum={"fade","slide"}),@OA\Property(property="overlayColor", type="string", maxLength=30),@OA\Property(property="textColor", type="string", maxLength=30)}),
 *             @OA\Property(property="common", type="object", properties={@OA\Property(property="height", type="string", enum={"small","medium","large","full"}),@OA\Property(property="showSearchBox", type="boolean"),@OA\Property(property="searchBoxPosition", type="string", enum={"left","center","right"}),@OA\Property(property="responsive", type="boolean"),@OA\Property(property="fullWidth", type="boolean")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/customer-dropdown",
 *
 *     @OA\Get(
 *         operationId="get_content_customer_dropdown_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_customer_dropdown_1",
 *         tags={"Content"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="is_visible", type="boolean"),
 *             @OA\Property(property="show_login", type="boolean"),
 *             @OA\Property(property="show_register", type="boolean"),
 *             @OA\Property(property="show_dashboard", type="boolean"),
 *             @OA\Property(property="show_logout", type="boolean"),
 *             @OA\Property(property="additional_settings", type="object", properties={@OA\Property(property="button_text", type="string", maxLength=50),@OA\Property(property="button_style", type="string", enum={"primary","secondary","outline","link"}),@OA\Property(property="dropdown_position", type="string", enum={"left","right","center"})}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/customer-dropdown/toggle-visibility",
 *
 *     @OA\Post(
 *         operationId="post_content_customer_dropdown_toggle_visibility_0",
 *         tags={"Content"},
 *         summary="Toggle Visibility", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"enabled"},
 *             @OA\Property(property="enabled", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/footer",
 *
 *     @OA\Get(
 *         operationId="get_content_footer_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_footer_1",
 *         tags={"Content"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status","general","social","columns","newsletter","style"},
 *             @OA\Property(property="status", type="boolean"),
 *             @OA\Property(property="general", type="object", properties={@OA\Property(property="companyName", type="string", maxLength=100),@OA\Property(property="address", type="string", maxLength=255),@OA\Property(property="phone", type="string", maxLength=255),@OA\Property(property="email", type="string", format="email", maxLength=100),@OA\Property(property="workingHours", type="string", maxLength=100),@OA\Property(property="showContactInfo", type="boolean"),@OA\Property(property="showWorkingHours", type="boolean"),@OA\Property(property="copyrightText", type="string", maxLength=255),@OA\Property(property="showCopyright", type="boolean")}),
 *             @OA\Property(property="social", type="object", properties={@OA\Property(property="*.id", type="string"),@OA\Property(property="*.platform", type="string", enum={"facebook","twitter","instagram","linkedin","youtube","snapchat","tiktok"}),@OA\Property(property="*.url", type="string", maxLength=255),@OA\Property(property="*.enabled", type="boolean")}),
 *             @OA\Property(property="columns", type="object", properties={@OA\Property(property="*.id", type="string"),@OA\Property(property="*.title", type="string", maxLength=100),@OA\Property(property="*.enabled", type="boolean"),@OA\Property(property="*.links", type="array", @OA\Items(type="string")),@OA\Property(property="*.links.*.id", type="string"),@OA\Property(property="*.links.*.text", type="string", maxLength=100),@OA\Property(property="*.links.*.url", type="string", maxLength=255)}),
 *             @OA\Property(property="newsletter", type="object", properties={@OA\Property(property="enabled", type="boolean"),@OA\Property(property="title", type="string", maxLength=100),@OA\Property(property="description", type="string", maxLength=255),@OA\Property(property="buttonText", type="string", maxLength=50),@OA\Property(property="placeholderText", type="string", maxLength=100)}),
 *             @OA\Property(property="style", type="object", properties={@OA\Property(property="layout", type="string", enum={"full-width","contained"}),@OA\Property(property="backgroundColor", type="string", maxLength=20),@OA\Property(property="textColor", type="string", maxLength=20),@OA\Property(property="accentColor", type="string", maxLength=20),@OA\Property(property="columns", type="integer", minimum=1, maximum=4),@OA\Property(property="showSocialIcons", type="boolean"),@OA\Property(property="socialIconsPosition", type="string", enum={"top","bottom"})}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/general",
 *
 *     @OA\Get(
 *         operationId="get_content_general_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_general_1",
 *         tags={"Content"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"site_name"},
 *             @OA\Property(property="site_name", type="string", maxLength=255),
 *             @OA\Property(property="tagline", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=1000),
 *             @OA\Property(property="logo", type="string", maxLength=255),
 *             @OA\Property(property="favicon", type="string", maxLength=255),
 *             @OA\Property(property="maintenance_mode", type="boolean"),
 *             @OA\Property(property="show_breadcrumb", type="boolean"),
 *             @OA\Property(property="show_properties", type="boolean"),
 *             @OA\Property(property="additional_settings", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="primary_color", type="string", maxLength=50),
 *             @OA\Property(property="secondary_color", type="string", maxLength=50),
 *             @OA\Property(property="accent_color", type="string", maxLength=50),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/general/toggle-show-properties",
 *
 *     @OA\Post(
 *         operationId="post_content_general_toggle_show_properties_0",
 *         tags={"Content"},
 *         summary="Show Properties", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"enabled"},
 *             @OA\Property(property="enabled", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/menu",
 *
 *     @OA\Get(
 *         operationId="get_content_menu_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_menu_1",
 *         tags={"Content"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"menuItems","settings"},
 *             @OA\Property(property="menuItems", type="object", properties={@OA\Property(property="*.id", type="integer"),@OA\Property(property="*.label", type="string"),@OA\Property(property="*.url", type="string"),@OA\Property(property="*.isExternal", type="boolean"),@OA\Property(property="*.isActive", type="boolean"),@OA\Property(property="*.order", type="integer"),@OA\Property(property="*.parentId", type="integer"),@OA\Property(property="*.showOnMobile", type="boolean"),@OA\Property(property="*.showOnDesktop", type="boolean")}),
 *             @OA\Property(property="settings", type="object", properties={@OA\Property(property="menuPosition", type="string", enum={"top","bottom","left","right"}),@OA\Property(property="menuStyle", type="string", enum={"buttons","underline","minimal","standard","default"}),@OA\Property(property="mobileMenuType", type="string", enum={"hamburger","sidebar","fullscreen"}),@OA\Property(property="isSticky", type="boolean"),@OA\Property(property="isTransparent", type="boolean")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/content/sections",
 *
 *     @OA\Get(
 *         operationId="get_content_sections_0",
 *         tags={"Content"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/contracts",
 *
 *     @OA\Get(
 *         operationId="get_contracts_0",
 *         tags={"Contracts"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_contracts_1",
 *         tags={"Contracts"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_id","subject","contract_value","contract_type","start_date","contract_status"},
 *             @OA\Property(property="type", type="string", enum={"regular","rms"}),
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="subject", type="string", maxLength=255),
 *             @OA\Property(property="contract_value", type="number", minimum=0),
 *             @OA\Property(property="contract_type", type="string", enum={"Standard","Contracts under Seal","Lease Agreement","Other"}),
 *             @OA\Property(property="start_date", type="string"),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="contract_status", type="string", enum={"draft","signed","expired"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/contracts/customer/{customerId}",
 *
 *     @OA\Get(
 *         operationId="get_contracts_customer_customer_d_0",
 *         tags={"Contracts"},
 *         summary="Get By Customer", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/contracts/rental/{rentalId}",
 *
 *     @OA\Get(
 *         operationId="get_contracts_rental_rental_d_0",
 *         tags={"Contracts"},
 *         summary="Get By Rental", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/contracts/statistics",
 *
 *     @OA\Get(
 *         operationId="get_contracts_statistics_0",
 *         tags={"Contracts"},
 *         summary="Statistics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/contracts/{id}",
 *
 *     @OA\Get(
 *         operationId="get_contracts_id_0",
 *         tags={"Contracts"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_contracts_id_1",
 *         tags={"Contracts"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="type", type="string", enum={"regular","rms"}),
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="subject", type="string", maxLength=255),
 *             @OA\Property(property="contract_value", type="number", minimum=0),
 *             @OA\Property(property="contract_type", type="string", enum={"Standard","Contracts under Seal","Lease Agreement","Other"}),
 *             @OA\Property(property="start_date", type="string"),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="contract_status", type="string", enum={"draft","signed","expired"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_contracts_id_2",
 *         tags={"Contracts"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm",
 *
 *     @OA\Get(
 *         operationId="get_crm_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customer-appointments",
 *
 *     @OA\Get(
 *         operationId="get_crm_customer_appointments_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_customer_appointments_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_id","title","type","priority","datetime","duration"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="type", type="string", maxLength=100),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="duration", type="integer", minimum=1),
 *             @OA\Property(property="source", type="string", maxLength=50),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customer-appointments/{customer_appointment}",
 *
 *     @OA\Get(
 *         operationId="get_crm_customer_appointments_customer_appointment_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_appointment", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_customer_appointments_customer_appointment_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_appointment", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"priority"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="type", type="string", maxLength=100),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="duration", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_customer_appointments_customer_appointment_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_appointment", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"priority"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="type", type="string", maxLength=100),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="duration", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_customer_appointments_customer_appointment_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_appointment", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customer-reminders",
 *
 *     @OA\Get(
 *         operationId="get_crm_customer_reminders_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_customer_reminders_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"datetime"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="reminder_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customer-reminders/filter-options",
 *
 *     @OA\Get(
 *         operationId="get_crm_customer_reminders_filter_options_0",
 *         tags={"Crm"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customer-reminders/{customer_reminder}",
 *
 *     @OA\Get(
 *         operationId="get_crm_customer_reminders_customer_reminder_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_customer_reminders_customer_reminder_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="customer_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_customer_reminders_customer_reminder_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="customer_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_customer_reminders_customer_reminder_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/export",
 *
 *     @OA\Get(
 *         operationId="get_crm_customers_export_0",
 *         tags={"Crm"},
 *         summary="Export", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/filters",
 *
 *     @OA\Get(
 *         operationId="get_crm_customers_filters_0",
 *         tags={"Crm"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/import",
 *
 *     @OA\Post(
 *         operationId="post_crm_customers_import_0",
 *         tags={"Crm"},
 *         summary="Bulk Import", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary", maxLength=10240),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/import/template",
 *
 *     @OA\Get(
 *         operationId="get_crm_customers_import_template_0",
 *         tags={"Crm"},
 *         summary="Download Template", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/search",
 *
 *     @OA\Get(
 *         operationId="get_crm_customers_search_0",
 *         tags={"Crm"},
 *         summary="Search Customers", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/{id}/change-priority",
 *
 *     @OA\Post(
 *         operationId="post_crm_customers_id_change_priority_0",
 *         tags={"Crm"},
 *         summary="Change Customer Priority", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"priority_id"},
 *             @OA\Property(property="priority_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/{id}/change-procedure",
 *
 *     @OA\Post(
 *         operationId="post_crm_customers_id_change_procedure_0",
 *         tags={"Crm"},
 *         summary="Change Customer Procedure", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"procedure_id"},
 *             @OA\Property(property="procedure_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/{id}/change-stage",
 *
 *     @OA\Post(
 *         operationId="post_crm_customers_id_change_stage_0",
 *         tags={"Crm"},
 *         summary="Change Customer Stage", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"stage_id"},
 *             @OA\Property(property="stage_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/customers/{id}/change-type",
 *
 *     @OA\Post(
 *         operationId="post_crm_customers_id_change_type_0",
 *         tags={"Crm"},
 *         summary="Change Customer Type", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"type_id"},
 *             @OA\Property(property="type_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/priorities",
 *
 *     @OA\Get(
 *         operationId="get_crm_priorities_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_priorities_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","value","order"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="value", type="integer"),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/priorities/reorder",
 *
 *     @OA\Post(
 *         operationId="post_crm_priorities_reorder_0",
 *         tags={"Crm"},
 *         summary="Reorder Priorities", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"order"},
 *             @OA\Property(property="order", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/priorities/{id}/move",
 *
 *     @OA\Post(
 *         operationId="post_crm_priorities_id_move_0",
 *         tags={"Crm"},
 *         summary="Move Priority", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"direction"},
 *             @OA\Property(property="direction", type="string", enum={"up","down"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/priorities/{priority}",
 *
 *     @OA\Get(
 *         operationId="get_crm_priorities_priority_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="priority", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_priorities_priority_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="priority", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="value", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_priorities_priority_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="priority", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="value", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_priorities_priority_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="priority", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/procedures",
 *
 *     @OA\Get(
 *         operationId="get_crm_procedures_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_procedures_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"procedure_name","order"},
 *             @OA\Property(property="procedure_name", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/procedures/reorder",
 *
 *     @OA\Post(
 *         operationId="post_crm_procedures_reorder_0",
 *         tags={"Crm"},
 *         summary="Reorder Procedures", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"order"},
 *             @OA\Property(property="order", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/procedures/{id}/move",
 *
 *     @OA\Post(
 *         operationId="post_crm_procedures_id_move_0",
 *         tags={"Crm"},
 *         summary="Move Procedure", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"direction"},
 *             @OA\Property(property="direction", type="string", enum={"up","down"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/procedures/{procedure}",
 *
 *     @OA\Get(
 *         operationId="get_crm_procedures_procedure_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="procedure", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_procedures_procedure_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="procedure", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="procedure_name", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_procedures_procedure_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="procedure", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="procedure_name", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_procedures_procedure_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="procedure", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/property-requests/settings",
 *
 *     @OA\Get(
 *         operationId="get_crm_property_requests_settings_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_property_requests_settings_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"auto_create_customer"},
 *             @OA\Property(property="auto_create_customer", type="boolean"),
 *             @OA\Property(property="default_stage_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/reminder-types",
 *
 *     @OA\Get(
 *         operationId="get_crm_reminder_types_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_reminder_types_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="icon", type="string", maxLength=100),
 *             @OA\Property(property="order", type="integer", minimum=0),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/reminder-types/{reminder_type}",
 *
 *     @OA\Get(
 *         operationId="get_crm_reminder_types_reminder_type_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder_type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_reminder_types_reminder_type_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder_type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="icon", type="string", maxLength=100),
 *             @OA\Property(property="order", type="integer", minimum=0),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_reminder_types_reminder_type_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder_type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="icon", type="string", maxLength=100),
 *             @OA\Property(property="order", type="integer", minimum=0),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_reminder_types_reminder_type_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder_type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/reminders",
 *
 *     @OA\Get(
 *         operationId="get_crm_reminders_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_reminders_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_id","reminder_type_id","title","datetime"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="reminder_type_id", type="integer"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="priority", type="integer", enum={"0","1","2"}),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="source", type="string", enum={"manual","website","whatsapp","affiliate"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/reminders/{reminder}",
 *
 *     @OA\Get(
 *         operationId="get_crm_reminders_reminder_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_reminders_reminder_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_id","reminder_type_id","title","datetime"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="reminder_type_id", type="integer"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="priority", type="integer", enum={"0","1","2"}),
 *             @OA\Property(property="status", type="string", enum={"pending","completed","overdue","cancelled"}),
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_reminders_reminder_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_id","reminder_type_id","title","datetime"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="reminder_type_id", type="integer"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="priority", type="integer", enum={"0","1","2"}),
 *             @OA\Property(property="status", type="string", enum={"pending","completed","overdue","cancelled"}),
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_reminders_reminder_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="reminder", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/stages",
 *
 *     @OA\Get(
 *         operationId="get_crm_stages_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_stages_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"stage_name","order"},
 *             @OA\Property(property="stage_name", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="icon", type="string"),
 *             @OA\Property(property="order", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/stages/reorder",
 *
 *     @OA\Post(
 *         operationId="post_crm_stages_reorder_0",
 *         tags={"Crm"},
 *         summary="Reorder Stages", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"order"},
 *             @OA\Property(property="order", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/stages/{id}/move",
 *
 *     @OA\Post(
 *         operationId="post_crm_stages_id_move_0",
 *         tags={"Crm"},
 *         summary="Move Stage", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"direction"},
 *             @OA\Property(property="direction", type="string", enum={"up","down"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/stages/{stage}",
 *
 *     @OA\Get(
 *         operationId="get_crm_stages_stage_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="stage", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_stages_stage_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="stage", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="stage_name", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="icon", type="string"),
 *             @OA\Property(property="order", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_stages_stage_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="stage", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="stage_name", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="icon", type="string"),
 *             @OA\Property(property="order", type="integer"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_stages_stage_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="stage", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/types",
 *
 *     @OA\Get(
 *         operationId="get_crm_types_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_types_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","value","order"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="value", type="string", maxLength=50),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/types/reorder",
 *
 *     @OA\Post(
 *         operationId="post_crm_types_reorder_0",
 *         tags={"Crm"},
 *         summary="Reorder Types", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"order"},
 *             @OA\Property(property="order", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/types/{id}/move",
 *
 *     @OA\Post(
 *         operationId="post_crm_types_id_move_0",
 *         tags={"Crm"},
 *         summary="Move Types", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"direction"},
 *             @OA\Property(property="direction", type="string", enum={"up","down"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/crm/types/{type}",
 *
 *     @OA\Get(
 *         operationId="get_crm_types_type_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_types_type_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="value", type="string", maxLength=50),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_types_type_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="value", type="string", maxLength=50),
 *             @OA\Property(property="color", type="string", maxLength=50),
 *             @OA\Property(property="icon", type="string", maxLength=50),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_types_type_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers",
 *
 *     @OA\Get(
 *         operationId="get_customers_0",
 *         tags={"Customers"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_customers_1",
 *         tags={"Customers"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","phone_number","type_id"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone_number", type="string", maxLength=20),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="type_id", type="string"),
 *             @OA\Property(property="responsible_employee_id", type="string"),
 *             @OA\Property(property="stage_id", type="string"),
 *             @OA\Property(property="procedure_id", type="string"),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="interested_category_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="interested_property_ids", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/all",
 *
 *     @OA\Get(
 *         operationId="get_customers_all_0",
 *         tags={"Customers"},
 *         summary="All", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/bulk-import",
 *
 *     @OA\Post(
 *         operationId="post_customers_bulk_import_0",
 *         tags={"Customers"},
 *         summary="Bulk Import", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary"),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/bulk-import/template",
 *
 *     @OA\Post(
 *         operationId="post_customers_bulk_import_template_0",
 *         tags={"Customers"},
 *         summary="Download Template",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="tenant_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/export",
 *
 *     @OA\Get(
 *         operationId="get_customers_export_0",
 *         tags={"Customers"},
 *         summary="Export", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/filters",
 *
 *     @OA\Get(
 *         operationId="get_customers_filters_0",
 *         tags={"Customers"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/search",
 *
 *     @OA\Get(
 *         operationId="get_customers_search_0",
 *         tags={"Customers"},
 *         summary="Search", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/{id}",
 *
 *     @OA\Get(
 *         operationId="get_customers_id_0",
 *         tags={"Customers"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_customers_id_1",
 *         tags={"Customers"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone_number", type="string", maxLength=20),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="type_id", type="string"),
 *             @OA\Property(property="stage_id", type="string"),
 *             @OA\Property(property="responsible_employee_id", type="string"),
 *             @OA\Property(property="procedure_id", type="string"),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="interested_category_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="interested_property_ids", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_customers_id_2",
 *         tags={"Customers"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/customers/{id}/with-inquiries",
 *
 *     @OA\Get(
 *         operationId="get_customers_id_with_inquiries_0",
 *         tags={"Customers"},
 *         summary="Show With Inquiries", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_0",
 *         tags={"Dashboard"},
 *         summary="Dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/debug-ga-views",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_debug_ga_views_0",
 *         tags={"Dashboard"},
 *         summary="Debug G A Views", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/devices",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_devices_0",
 *         tags={"Dashboard"},
 *         summary="Devices", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/diagnostic-ga-test",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_diagnostic_ga_test_0",
 *         tags={"Dashboard"},
 *         summary="Diagnostic G A Test", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/ga-full-diagnostics",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_ga_full_diagnostics_0",
 *         tags={"Dashboard"},
 *         summary="Ga Full Diagnostics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/most-visited-pages",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_most_visited_pages_0",
 *         tags={"Dashboard"},
 *         summary="Most Visited Pages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/recent-activity",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_recent_activity_0",
 *         tags={"Dashboard"},
 *         summary="Get Recent Activity", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/setup-progress",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_setup_progress_0",
 *         tags={"Dashboard"},
 *         summary="Setup Progress", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/summary",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_summary_0",
 *         tags={"Dashboard"},
 *         summary="Summary", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/traffic-sources",
 *
 *     @OA\Get(
 *         operationId="get_dashboard_traffic_sources_0",
 *         tags={"Dashboard"},
 *         summary="Traffic Sources", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/dashboard/visitors",
 *
 *     @OA\Post(
 *         operationId="post_dashboard_visitors_0",
 *         tags={"Dashboard"},
 *         summary="Visitors", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="tenant_id", type="string", maxLength=255),
 *             @OA\Property(property="time_range", type="integer", enum={"7","30","90","365"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/delete-file",
 *
 *     @OA\Post(
 *         operationId="post_delete_file_0",
 *         tags={"Delete File"},
 *         summary="Delete", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"path"},
 *             @OA\Property(property="path", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/documentation",
 *
 *     @OA\Get(
 *         operationId="get_documentation_0",
 *         tags={"Documentation"},
 *         summary="Api",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/embeddings",
 *
 *     @OA\Post(
 *         operationId="post_embeddings_0",
 *         tags={"Embeddings"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"text"},
 *             @OA\Property(property="text", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/employee/addons",
 *
 *     @OA\Get(
 *         operationId="get_employee_addons_0",
 *         tags={"Employee"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_employee_addons_1",
 *         tags={"Employee"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"qty","plan_id"},
 *             @OA\Property(property="qty", type="integer", minimum=1),
 *             @OA\Property(property="plan_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/employee/addons/plans",
 *
 *     @OA\Get(
 *         operationId="get_employee_addons_plans_0",
 *         tags={"Employee"},
 *         summary="Plans", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/impersonate/{user}",
 *
 *     @OA\Post(
 *         operationId="post_impersonate_user_0",
 *         tags={"Impersonate"},
 *         summary="Start", security={{"sanctum":{}}},
 *         @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/impersonate/{user}/revoke",
 *
 *     @OA\Post(
 *         operationId="post_impersonate_user_revoke_0",
 *         tags={"Impersonate"},
 *         summary="Stop", security={{"sanctum":{}}},
 *         @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/installations/{installationId}/payment/status",
 *
 *     @OA\Get(
 *         operationId="get_installations_installation_d_payment_status_0",
 *         tags={"Installations"},
 *         summary="Get Payment Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="installationId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/isthara",
 *
 *     @OA\Post(
 *         operationId="post_isthara_0",
 *         tags={"Isthara"},
 *         summary="Store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","phone"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string"),
 *             @OA\Property(property="recaptcha_token", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/login",
 *
 *     @OA\Post(
 *         operationId="post_login_0",
 *         tags={"Login"},
 *         summary="Login",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"password"},
 *             @OA\Property(property="recaptcha_token", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone", type="string"),
 *             @OA\Property(property="password", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/logout",
 *
 *     @OA\Post(
 *         operationId="post_logout_0",
 *         tags={"Logout"},
 *         summary="Logout", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/make-payment",
 *
 *     @OA\Post(
 *         operationId="post_make_payment_0",
 *         tags={"Make Payment"},
 *         summary="Checkout", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"package_id"},
 *             @OA\Property(property="package_id", type="integer"),
 *             @OA\Property(property="period", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/make-payment-app",
 *
 *     @OA\Post(
 *         operationId="post_make_payment_app_0",
 *         tags={"Make Payment App"},
 *         summary="Checkout App", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"app_id"},
 *             @OA\Property(property="app_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/media",
 *
 *     @OA\Post(
 *         operationId="post_media_0",
 *         tags={"Media"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary", maxLength=51200),
 *             @OA\Property(property="mediable_type", type="string", enum={"App\\Models\\Api\\Post"}),
 *             @OA\Property(property="mediable_id", type="integer"),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/oauth2-callback",
 *
 *     @OA\Get(
 *         operationId="get_oauth2_callback_0",
 *         tags={"Oauth2 Callback"},
 *         summary="Oauth2 Callback",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/onboarding",
 *
 *     @OA\Post(
 *         operationId="post_onboarding_0",
 *         tags={"Onboarding"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","category","colors"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="category", type="string"),
 *             @OA\Property(property="colors", type="object", properties={@OA\Property(property="primary", type="string", maxLength=7),@OA\Property(property="secondary", type="string", maxLength=7),@OA\Property(property="accent", type="string", maxLength=7)}),
 *             @OA\Property(property="logo", type="string"),
 *             @OA\Property(property="favicon", type="string"),
 *             @OA\Property(property="valLicense", type="string"),
 *             @OA\Property(property="workingHours", type="string"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=255),
 *             @OA\Property(property="allow_update", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/pixels",
 *
 *     @OA\Get(
 *         operationId="get_pixels_0",
 *         tags={"Pixels"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_pixels_1",
 *         tags={"Pixels"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"platform","pixel_id"},
 *             @OA\Property(property="platform", type="string", enum={"facebook","tiktok","snapchat","gtm"}),
 *             @OA\Property(property="pixel_id", type="string", maxLength=255),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/pixels/{id}",
 *
 *     @OA\Get(
 *         operationId="get_pixels_id_0",
 *         tags={"Pixels"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_pixels_id_1",
 *         tags={"Pixels"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="platform", type="string", enum={"facebook","tiktok","snapchat","gtm"}),
 *             @OA\Property(property="pixel_id", type="string", maxLength=255),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_pixels_id_2",
 *         tags={"Pixels"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/pixels/{id}/toggle-status",
 *
 *     @OA\Patch(
 *         operationId="patch_pixels_id_toggle_status_0",
 *         tags={"Pixels"},
 *         summary="Toggle Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/posts",
 *
 *     @OA\Get(
 *         operationId="get_posts_0",
 *         tags={"Posts"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_posts_1",
 *         tags={"Posts"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","content"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="slug", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string", maxLength=100000),
 *             @OA\Property(property="excerpt", type="string", maxLength=500),
 *             @OA\Property(property="status", type="string", enum={"draft","published"}),
 *             @OA\Property(property="category_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="media_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="thumbnail_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/posts/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_posts_slug_0",
 *         tags={"Posts"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_posts_slug_1",
 *         tags={"Posts"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="slug", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string", maxLength=100000),
 *             @OA\Property(property="excerpt", type="string", maxLength=500),
 *             @OA\Property(property="status", type="string", enum={"draft","published"}),
 *             @OA\Property(property="category_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="media_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="thumbnail_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_posts_slug_2",
 *         tags={"Posts"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/projects",
 *
 *     @OA\Get(
 *         operationId="get_projects_0",
 *         tags={"Projects"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_projects_1",
 *         tags={"Projects"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"featured_image"},
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="description", type="string", minLength=15),
 *             @OA\Property(property="complete_status", type="string"),
 *             @OA\Property(property="units", type="integer"),
 *             @OA\Property(property="completion_date", type="string"),
 *             @OA\Property(property="developer", type="string", maxLength=255),
 *             @OA\Property(property="gallery_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="floorplan_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="min_price", type="number"),
 *             @OA\Property(property="max_price", type="number"),
 *             @OA\Property(property="featured", type="string"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="label", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="value", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/projects/{id}",
 *
 *     @OA\Get(
 *         operationId="get_projects_id_0",
 *         tags={"Projects"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_projects_id_1",
 *         tags={"Projects"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"featured_image"},
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="description", type="string", minLength=15),
 *             @OA\Property(property="gallery_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="floorplan_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="min_price", type="number"),
 *             @OA\Property(property="max_price", type="number"),
 *             @OA\Property(property="featured", type="string"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="label", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="value", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="complete_status", type="string"),
 *             @OA\Property(property="units", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_projects_id_2",
 *         tags={"Projects"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/projects/{id}/toggle-featured",
 *
 *     @OA\Patch(
 *         operationId="patch_projects_id_toggle_featured_0",
 *         tags={"Projects"},
 *         summary="Toggle Featured", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties",
 *
 *     @OA\Get(
 *         operationId="get_properties_0",
 *         tags={"Properties"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_properties_1",
 *         tags={"Properties"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"title","address","description","featured_image","property_type"},
 *             @OA\Property(property="payment_method", type="string"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="gallery", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="floor_planning_image", type="string"),
 *             @OA\Property(property="video_image", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="virtual_tour", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="beds", type="string"),
 *             @OA\Property(property="bath", type="string"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="area", type="string"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="project_id", type="string"),
 *             @OA\Property(property="city_id", type="string"),
 *             @OA\Property(property="state_id", type="string"),
 *             @OA\Property(property="featured", type="boolean"),
 *             @OA\Property(property="amenities", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="faqs", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="facade_id", type="number"),
 *             @OA\Property(property="length", type="number"),
 *             @OA\Property(property="width", type="number"),
 *             @OA\Property(property="street_width_north", type="number"),
 *             @OA\Property(property="street_width_south", type="number"),
 *             @OA\Property(property="street_width_east", type="number"),
 *             @OA\Property(property="street_width_west", type="number"),
 *             @OA\Property(property="building_age", type="integer"),
 *             @OA\Property(property="rooms", type="integer"),
 *             @OA\Property(property="bathrooms", type="integer"),
 *             @OA\Property(property="floors", type="integer"),
 *             @OA\Property(property="floor_number", type="integer"),
 *             @OA\Property(property="driver_room", type="integer"),
 *             @OA\Property(property="maid_room", type="integer"),
 *             @OA\Property(property="dining_room", type="integer"),
 *             @OA\Property(property="living_room", type="integer"),
 *             @OA\Property(property="majlis", type="integer"),
 *             @OA\Property(property="storage_room", type="integer"),
 *             @OA\Property(property="basement", type="integer"),
 *             @OA\Property(property="swimming_pool", type="integer"),
 *             @OA\Property(property="kitchen", type="integer"),
 *             @OA\Property(property="balcony", type="integer"),
 *             @OA\Property(property="garden", type="integer"),
 *             @OA\Property(property="annex", type="integer"),
 *             @OA\Property(property="elevator", type="integer"),
 *             @OA\Property(property="private_parking", type="integer"),
 *             @OA\Property(property="size", type="integer"),
 *             @OA\Property(property="building_id", type="integer"),
 *             @OA\Property(property="water_meter_number", type="string"),
 *             @OA\Property(property="electricity_meter_number", type="string"),
 *             @OA\Property(property="deed_number", type="string"),
 *             @OA\Property(property="advertising_license", type="string"),
 *             @OA\Property(property="owner_number", type="string"),
 *             @OA\Property(property="video_file", type="string", format="binary"),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/available-units",
 *
 *     @OA\Get(
 *         operationId="get_properties_available_units_0",
 *         tags={"Properties"},
 *         summary="Available Units", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/bulk-import",
 *
 *     @OA\Post(
 *         operationId="post_properties_bulk_import_0",
 *         tags={"Properties"},
 *         summary="Bulk Import", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary", maxLength=10240),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/bulk-import/template",
 *
 *     @OA\Get(
 *         operationId="get_properties_bulk_import_template_0",
 *         tags={"Properties"},
 *         summary="Download Template",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/cards",
 *
 *     @OA\Get(
 *         operationId="get_properties_cards_0",
 *         tags={"Properties"},
 *         summary="Cards", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/categories",
 *
 *     @OA\Get(
 *         operationId="get_properties_categories_0",
 *         tags={"Properties"},
 *         summary="Properties_categories", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/drafts",
 *
 *     @OA\Get(
 *         operationId="get_properties_drafts_0",
 *         tags={"Properties"},
 *         summary="List Drafts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/drafts/bulk-complete",
 *
 *     @OA\Post(
 *         operationId="post_properties_drafts_bulk_complete_0",
 *         tags={"Properties"},
 *         summary="Bulk Complete Drafts", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"property_ids"},
 *             @OA\Property(property="property_ids", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/drafts/{id}",
 *
 *     @OA\Get(
 *         operationId="get_properties_drafts_id_0",
 *         tags={"Properties"},
 *         summary="Show Draft", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_properties_drafts_id_1",
 *         tags={"Properties"},
 *         summary="Update Draft", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="address", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="type", type="string"),
 *             @OA\Property(property="beds", type="integer", minimum=0),
 *             @OA\Property(property="bath", type="integer", minimum=0),
 *             @OA\Property(property="area", type="number", minimum=0),
 *             @OA\Property(property="size", type="number", minimum=0),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="virtual_tour", type="string"),
 *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="payment_method", type="string"),
 *             @OA\Property(property="water_meter_number", type="string", maxLength=255),
 *             @OA\Property(property="electricity_meter_number", type="string", maxLength=255),
 *             @OA\Property(property="deed_number", type="string", maxLength=255),
 *             @OA\Property(property="advertising_license", type="string", maxLength=255),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="project_id", type="integer"),
 *             @OA\Property(property="building_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/drafts/{id}/complete",
 *
 *     @OA\Post(
 *         operationId="post_properties_drafts_id_complete_0",
 *         tags={"Properties"},
 *         summary="Complete Draft", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="address", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="type", type="string"),
 *             @OA\Property(property="beds", type="integer", minimum=0),
 *             @OA\Property(property="bath", type="integer", minimum=0),
 *             @OA\Property(property="area", type="number", minimum=0),
 *             @OA\Property(property="size", type="number", minimum=0),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="virtual_tour", type="string"),
 *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="payment_method", type="string"),
 *             @OA\Property(property="water_meter_number", type="string", maxLength=255),
 *             @OA\Property(property="electricity_meter_number", type="string", maxLength=255),
 *             @OA\Property(property="deed_number", type="string", maxLength=255),
 *             @OA\Property(property="advertising_license", type="string", maxLength=255),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="project_id", type="integer"),
 *             @OA\Property(property="building_id", type="integer"),
 *             @OA\Property(property="gallery_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="amenity_ids", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/export",
 *
 *     @OA\Get(
 *         operationId="get_properties_export_0",
 *         tags={"Properties"},
 *         summary="Export", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/export-for-import",
 *
 *     @OA\Get(
 *         operationId="get_properties_export_for_import_0",
 *         tags={"Properties"},
 *         summary="Export For Import", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/filter-options",
 *
 *     @OA\Get(
 *         operationId="get_properties_filter_options_0",
 *         tags={"Properties"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/reorder",
 *
 *     @OA\Post(
 *         operationId="post_properties_reorder_0",
 *         tags={"Properties"},
 *         summary="Properties_reorder", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"id","reorder"},
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="reorder", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/reorder-featured",
 *
 *     @OA\Post(
 *         operationId="post_properties_reorder_featured_0",
 *         tags={"Properties"},
 *         summary="Properties_reorder_featured", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"id","reorder_featured"},
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="reorder_featured", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/upload-deed-image",
 *
 *     @OA\Post(
 *         operationId="post_properties_upload_deed_image_0",
 *         tags={"Properties"},
 *         summary="Upload Deed Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"deed_image"},
 *             @OA\Property(property="deed_image", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/{id}",
 *
 *     @OA\Get(
 *         operationId="get_properties_id_0",
 *         tags={"Properties"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_properties_id_1",
 *         tags={"Properties"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"title","address","description","featured_image","property_type"},
 *             @OA\Property(property="payment_method", type="string"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="gallery", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="floor_planning_image", type="string"),
 *             @OA\Property(property="video_image", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="beds", type="string"),
 *             @OA\Property(property="bath", type="string"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="area", type="string"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="project_id", type="string"),
 *             @OA\Property(property="city_id", type="string"),
 *             @OA\Property(property="state_id", type="string"),
 *             @OA\Property(property="amenities", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="facade_id", type="number"),
 *             @OA\Property(property="length", type="number"),
 *             @OA\Property(property="width", type="number"),
 *             @OA\Property(property="street_width_north", type="number"),
 *             @OA\Property(property="street_width_south", type="number"),
 *             @OA\Property(property="street_width_east", type="number"),
 *             @OA\Property(property="street_width_west", type="number"),
 *             @OA\Property(property="building_age", type="integer"),
 *             @OA\Property(property="rooms", type="integer"),
 *             @OA\Property(property="bathrooms", type="integer"),
 *             @OA\Property(property="floors", type="integer"),
 *             @OA\Property(property="floor_number", type="integer"),
 *             @OA\Property(property="driver_room", type="integer"),
 *             @OA\Property(property="maid_room", type="integer"),
 *             @OA\Property(property="dining_room", type="integer"),
 *             @OA\Property(property="living_room", type="integer"),
 *             @OA\Property(property="majlis", type="integer"),
 *             @OA\Property(property="storage_room", type="integer"),
 *             @OA\Property(property="basement", type="integer"),
 *             @OA\Property(property="swimming_pool", type="integer"),
 *             @OA\Property(property="kitchen", type="integer"),
 *             @OA\Property(property="balcony", type="integer"),
 *             @OA\Property(property="garden", type="integer"),
 *             @OA\Property(property="annex", type="integer"),
 *             @OA\Property(property="elevator", type="integer"),
 *             @OA\Property(property="private_parking", type="integer"),
 *             @OA\Property(property="size", type="number"),
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="faqs", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="building_id", type="integer"),
 *             @OA\Property(property="water_meter_number", type="string"),
 *             @OA\Property(property="electricity_meter_number", type="string"),
 *             @OA\Property(property="deed_number", type="string"),
 *             @OA\Property(property="advertising_license", type="string"),
 *             @OA\Property(property="owner_number", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="virtual_tour", type="string"),
 *             @OA\Property(property="video_file", type="string", format="binary"),
 *             @OA\Property(property="show_reservations", type="boolean"),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_properties_id_2",
 *         tags={"Properties"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/{id}/toggle-featured",
 *
 *     @OA\Patch(
 *         operationId="patch_properties_id_toggle_featured_0",
 *         tags={"Properties"},
 *         summary="Toggle Featured", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/{id}/toggle-status",
 *
 *     @OA\Post(
 *         operationId="post_properties_id_toggle_status_0",
 *         tags={"Properties"},
 *         summary="Toggle Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/properties/{propertyId}/duplicate",
 *
 *     @OA\Post(
 *         operationId="post_properties_property_d_duplicate_0",
 *         tags={"Properties"},
 *         summary="Duplicate", security={{"sanctum":{}}},
 *         @OA\Parameter(name="propertyId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="featured", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/property-faqs",
 *
 *     @OA\Get(
 *         operationId="get_property_faqs_0",
 *         tags={"Property Faqs"},
 *         summary="Faqs", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/property/facades",
 *
 *     @OA\Get(
 *         operationId="get_property_facades_0",
 *         tags={"Property"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public-user/{id}",
 *
 *     @OA\Get(
 *         operationId="get_public_user_id_0",
 *         tags={"Public User"},
 *         summary="Show",
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/admin-article-categories",
 *
 *     @OA\Get(
 *         operationId="get_public_admin_article_categories_0",
 *         tags={"Public"},
 *         summary="Categories",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/admin-article-categories/{slug}/articles",
 *
 *     @OA\Get(
 *         operationId="get_public_admin_article_categories_slug_articles_0",
 *         tags={"Public"},
 *         summary="Category Articles",
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/admin-articles",
 *
 *     @OA\Get(
 *         operationId="get_public_admin_articles_0",
 *         tags={"Public"},
 *         summary="Articles",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/admin-articles/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_public_admin_articles_slug_0",
 *         tags={"Public"},
 *         summary="Show",
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/support-center/articles",
 *
 *     @OA\Get(
 *         operationId="get_public_support_center_articles_0",
 *         tags={"Public"},
 *         summary="Articles",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/support-center/articles/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_public_support_center_articles_slug_0",
 *         tags={"Public"},
 *         summary="Show",
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/support-center/categories",
 *
 *     @OA\Get(
 *         operationId="get_public_support_center_categories_0",
 *         tags={"Public"},
 *         summary="Categories",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/public/support-center/categories/{slug}/articles",
 *
 *     @OA\Get(
 *         operationId="get_public_support_center_categories_slug_articles_0",
 *         tags={"Public"},
 *         summary="Category Articles",
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/referrals",
 *
 *     @OA\Get(
 *         operationId="get_referrals_0",
 *         tags={"Referrals"},
 *         summary="Validate Code",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/referrals/{code}",
 *
 *     @OA\Get(
 *         operationId="get_referrals_code_0",
 *         tags={"Referrals"},
 *         summary="Show",
 *         @OA\Parameter(name="code", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/regions",
 *
 *     @OA\Get(
 *         operationId="get_regions_0",
 *         tags={"Regions"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/regions/{region}",
 *
 *     @OA\Get(
 *         operationId="get_regions_region_0",
 *         tags={"Regions"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="region", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/register",
 *
 *     @OA\Post(
 *         operationId="post_register_0",
 *         tags={"Register"},
 *         summary="Register",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="recaptcha_token", type="string"),
 *             @OA\Property(property="account_type", type="string", enum={"employee","tenant"}),
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="username", type="string"),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="phone", type="string", maxLength=191),
 *             @OA\Property(property="verified_token", type="string"),
 *             @OA\Property(property="first_name", type="string", maxLength=191),
 *             @OA\Property(property="last_name", type="string", maxLength=191),
 *             @OA\Property(property="industry_type", type="string", maxLength=100),
 *             @OA\Property(property="company_size", type="string", maxLength=50),
 *             @OA\Property(property="temp_token", type="string"),
 *             @OA\Property(property="referral_code", type="string"),
 *             @OA\Property(property="code", type="string"),
 *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_0",
 *         tags={"Rental Contracts"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_rental_contracts_1",
 *         tags={"Rental Contracts"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"rental_id","start_date","end_date","status"},
 *             @OA\Property(property="rental_id", type="integer"),
 *             @OA\Property(property="start_date", type="string"),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="status", type="string", enum={"pending","active"}),
 *             @OA\Property(property="file_path", type="string", maxLength=255),
 *             @OA\Property(property="property_id", type="integer", minimum=1),
 *             @OA\Property(property="project_id", type="integer", minimum=1),
 *             @OA\Property(property="property_name", type="string", maxLength=150),
 *             @OA\Property(property="project_name", type="string", maxLength=150),
 *             @OA\Property(property="grace_period_months", type="integer", minimum=0, maximum=2),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/all-contracts",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_all_contracts_0",
 *         tags={"Rental Contracts"},
 *         summary="All Contracts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/daily-follow-up",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_daily_follow_up_0",
 *         tags={"Rental Contracts"},
 *         summary="Daily Follow Up", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/filter",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_filter_0",
 *         tags={"Rental Contracts"},
 *         summary="Filter Contracts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/rental/{rentalId}",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_rental_rental_d_0",
 *         tags={"Rental Contracts"},
 *         summary="Get By Rental", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/statistics",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_statistics_0",
 *         tags={"Rental Contracts"},
 *         summary="Statistics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/{id}",
 *
 *     @OA\Get(
 *         operationId="get_rental_contracts_id_0",
 *         tags={"Rental Contracts"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_rental_contracts_id_1",
 *         tags={"Rental Contracts"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="start_date", type="string"),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="status", type="string", enum={"pending","active","expired","terminated"}),
 *             @OA\Property(property="file_path", type="string", maxLength=255),
 *             @OA\Property(property="property_id", type="integer", minimum=1),
 *             @OA\Property(property="project_id", type="integer", minimum=1),
 *             @OA\Property(property="property_name", type="string", maxLength=150),
 *             @OA\Property(property="project_name", type="string", maxLength=150),
 *             @OA\Property(property="grace_period_months", type="integer", minimum=0, maximum=2),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_rental_contracts_id_2",
 *         tags={"Rental Contracts"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/{id}/status",
 *
 *     @OA\Patch(
 *         operationId="patch_rental_contracts_id_status_0",
 *         tags={"Rental Contracts"},
 *         summary="Change Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status"},
 *             @OA\Property(property="status", type="string", enum={"pending","active","expired","terminated"}),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *             @OA\Property(property="effective_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/rental-contracts/{id}/terminate",
 *
 *     @OA\Post(
 *         operationId="post_rental_contracts_id_terminate_0",
 *         tags={"Rental Contracts"},
 *         summary="Terminate", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"termination_reason","terminate_on"},
 *             @OA\Property(property="termination_reason", type="string", maxLength=255),
 *             @OA\Property(property="terminate_on", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/domain",
 *
 *     @OA\Get(
 *         operationId="get_settings_domain_0",
 *         tags={"Settings"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_settings_domain_1",
 *         tags={"Settings"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"custom_name"},
 *             @OA\Property(property="custom_name", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/domain/request-ssl",
 *
 *     @OA\Patch(
 *         operationId="patch_settings_domain_request_ssl_0",
 *         tags={"Settings"},
 *         summary="Request Ssl", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"id"},
 *             @OA\Property(property="id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/domain/set-primary",
 *
 *     @OA\Patch(
 *         operationId="patch_settings_domain_set_primary_0",
 *         tags={"Settings"},
 *         summary="Set Primary", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"id"},
 *             @OA\Property(property="id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/domain/ssl-status",
 *
 *     @OA\Patch(
 *         operationId="patch_settings_domain_ssl_status_0",
 *         tags={"Settings"},
 *         summary="Update Ssl Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"domain_id","ssl"},
 *             @OA\Property(property="domain_id", type="integer"),
 *             @OA\Property(property="ssl", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/domain/verify",
 *
 *     @OA\Post(
 *         operationId="post_settings_domain_verify_0",
 *         tags={"Settings"},
 *         summary="Verify", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"id"},
 *             @OA\Property(property="id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/domain/{id}",
 *
 *     @OA\Get(
 *         operationId="get_settings_domain_id_0",
 *         tags={"Settings"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_settings_domain_id_1",
 *         tags={"Settings"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/payment",
 *
 *     @OA\Get(
 *         operationId="get_settings_payment_0",
 *         tags={"Settings"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/side-menus",
 *
 *     @OA\Get(
 *         operationId="get_settings_side_menus_0",
 *         tags={"Settings"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/theme",
 *
 *     @OA\Get(
 *         operationId="get_settings_theme_0",
 *         tags={"Settings"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/theme/purchase",
 *
 *     @OA\Post(
 *         operationId="post_settings_theme_purchase_0",
 *         tags={"Settings"},
 *         summary="Purchase", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"theme_id"},
 *             @OA\Property(property="theme_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/settings/theme/set-active",
 *
 *     @OA\Post(
 *         operationId="post_settings_theme_set_active_0",
 *         tags={"Settings"},
 *         summary="Set Active Theme", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"theme_id"},
 *             @OA\Property(property="theme_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/steps/complete",
 *
 *     @OA\Post(
 *         operationId="post_steps_complete_0",
 *         tags={"Steps"},
 *         summary="Complete Step", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"step"},
 *             @OA\Property(property="step", type="string", enum={"banner","footer","homepage_about_update","menu_builder","projects","properties"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/steps/progress",
 *
 *     @OA\Get(
 *         operationId="get_steps_progress_0",
 *         tags={"Steps"},
 *         summary="Get Steps", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/themes/payment/cancel/{user_theme_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_themes_payment_cancel_user_theme_id_gateway_0",
 *         tags={"Themes"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="user_theme_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_themes_payment_cancel_user_theme_id_gateway_1",
 *         tags={"Themes"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="user_theme_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/themes/payment/success/{user_theme_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_themes_payment_success_user_theme_id_gateway_0",
 *         tags={"Themes"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="user_theme_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_themes_payment_success_user_theme_id_gateway_1",
 *         tags={"Themes"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="user_theme_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/upload",
 *
 *     @OA\Post(
 *         operationId="post_upload_0",
 *         tags={"Upload"},
 *         summary="Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file","context"},
 *             @OA\Property(property="file", type="string", format="binary"),
 *             @OA\Property(property="context", type="string"),
 *             @OA\Property(property="sub_folder", type="string"),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/upload-multiple",
 *
 *     @OA\Post(
 *         operationId="post_upload_multiple_0",
 *         tags={"Upload Multiple"},
 *         summary="Upload Multiple", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"files","context"},
 *             @OA\Property(property="files", type="array", minLength=1, @OA\Items(type="string")),
 *             @OA\Property(property="context", type="string"),
 *             @OA\Property(property="sub_folder", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user",
 *
 *     @OA\Get(
 *         operationId="get_user_0",
 *         tags={"User"},
 *         summary="Get User Profile", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user-read-message",
 *
 *     @OA\Post(
 *         operationId="post_user_read_message_0",
 *         tags={"User Read Message"},
 *         summary="Read_message", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user/categories",
 *
 *     @OA\Get(
 *         operationId="get_user_categories_0",
 *         tags={"User"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_user_categories_1",
 *         tags={"User"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"categories"},
 *             @OA\Property(property="categories", type="object", properties={@OA\Property(property="*.id", type="integer"),@OA\Property(property="*.is_active", type="boolean")}),
 *             @OA\Property(property="show_even_if_empty", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user/cities",
 *
 *     @OA\Get(
 *         operationId="get_user_cities_0",
 *         tags={"User"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user/districts",
 *
 *     @OA\Get(
 *         operationId="get_user_districts_0",
 *         tags={"User"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user/getUserInfo",
 *
 *     @OA\Get(
 *         operationId="get_user_get_ser_nfo_0",
 *         tags={"User"},
 *         summary="Get User Profile", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/user/projects",
 *
 *     @OA\Get(
 *         operationId="get_user_projects_0",
 *         tags={"User"},
 *         summary="User Projects", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/dashboard",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_dashboard_0",
 *         tags={"Analytics"},
 *         summary="Dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/ga4/dashboard",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_ga4_dashboard_0",
 *         tags={"Analytics"},
 *         summary="Dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/ga4/properties-visits",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_ga4_properties_visits_0",
 *         tags={"Analytics"},
 *         summary="Properties Visits", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/ga4/top-pages",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_ga4_top_pages_0",
 *         tags={"Analytics"},
 *         summary="Top Pages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/page-view",
 *
 *     @OA\Post(
 *         operationId="post_v1_analytics_page_view_0",
 *         tags={"Analytics"},
 *         summary="Track",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"tenant_id","slug","path","page_type"},
 *             @OA\Property(property="tenant_id", type="string", maxLength=255),
 *             @OA\Property(property="slug", type="string", maxLength=255),
 *             @OA\Property(property="dynamic_slug", type="string", maxLength=255),
 *             @OA\Property(property="path", type="string", maxLength=500),
 *             @OA\Property(property="page_type", type="string", enum={"page","post","project","property"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/top-pages",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_top_pages_0",
 *         tags={"Analytics"},
 *         summary="Top Pages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/top-posts",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_top_posts_0",
 *         tags={"Analytics"},
 *         summary="Top Posts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/analytics/views-summary",
 *
 *     @OA\Get(
 *         operationId="get_v1_analytics_views_summary_0",
 *         tags={"Analytics"},
 *         summary="Summary", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/auth/logout",
 *
 *     @OA\Post(
 *         operationId="post_v1_auth_logout_0",
 *         tags={"Auth"},
 *         summary="Logout", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/auth/me",
 *
 *     @OA\Get(
 *         operationId="get_v1_auth_me_0",
 *         tags={"Auth"},
 *         summary="Me", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/communication/ops/delivery-attempts",
 *
 *     @OA\Get(
 *         operationId="get_v1_communication_ops_delivery_attempts_0",
 *         tags={"Communication"},
 *         summary="__invoke", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/communication/ops/health",
 *
 *     @OA\Get(
 *         operationId="get_v1_communication_ops_health_0",
 *         tags={"Communication"},
 *         summary="__invoke", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/communication/ops/reconciliation-summary",
 *
 *     @OA\Get(
 *         operationId="get_v1_communication_ops_reconciliation_summary_0",
 *         tags={"Communication"},
 *         summary="__invoke", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/communication/ops/stuck-items",
 *
 *     @OA\Get(
 *         operationId="get_v1_communication_ops_stuck_items_0",
 *         tags={"Communication"},
 *         summary="__invoke", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/communication/ops/webhook-events",
 *
 *     @OA\Get(
 *         operationId="get_v1_communication_ops_webhook_events_0",
 *         tags={"Communication"},
 *         summary="__invoke", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/conversations",
 *
 *     @OA\Get(
 *         operationId="get_v1_conversations_0",
 *         tags={"Conversations"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/conversations/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_conversations_id_0",
 *         tags={"Conversations"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/conversations/{id}/messages",
 *
 *     @OA\Get(
 *         operationId="get_v1_conversations_id_messages_0",
 *         tags={"Conversations"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/analytics",
 *
 *     @OA\Get(
 *         operationId="get_v1_credits_analytics_0",
 *         tags={"Credits"},
 *         summary="Get Analytics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/balance",
 *
 *     @OA\Get(
 *         operationId="get_v1_credits_balance_0",
 *         tags={"Credits"},
 *         summary="Get Balance", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/packages",
 *
 *     @OA\Get(
 *         operationId="get_v1_credits_packages_0",
 *         tags={"Credits"},
 *         summary="Get Packages",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/payment/cancel/{transaction_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_v1_credits_payment_cancel_transaction_id_gateway_0",
 *         tags={"Credits"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="transaction_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_credits_payment_cancel_transaction_id_gateway_1",
 *         tags={"Credits"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="transaction_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="trandata", type="string"),
 *             @OA\Property(property="payment_id", type="string", maxLength=255),
 *             @OA\Property(property="transaction_id", type="string", maxLength=255),
 *             @OA\Property(property="result", type="string", maxLength=255),
 *             @OA\Property(property="payment_result", type="string", maxLength=255),
 *             @OA\Property(property="status", type="string", maxLength=255),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *             @OA\Property(property="error", type="string", maxLength=1000),
 *             @OA\Property(property="payment_error", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/payment/success/{transaction_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_v1_credits_payment_success_transaction_id_gateway_0",
 *         tags={"Credits"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="transaction_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_credits_payment_success_transaction_id_gateway_1",
 *         tags={"Credits"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="transaction_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="trandata", type="string"),
 *             @OA\Property(property="payment_id", type="string", maxLength=255),
 *             @OA\Property(property="transaction_id", type="string", maxLength=255),
 *             @OA\Property(property="result", type="string", maxLength=255),
 *             @OA\Property(property="payment_result", type="string", maxLength=255),
 *             @OA\Property(property="status", type="string", maxLength=255),
 *             @OA\Property(property="error", type="string", maxLength=1000),
 *             @OA\Property(property="payment_error", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/purchase",
 *
 *     @OA\Post(
 *         operationId="post_v1_credits_purchase_0",
 *         tags={"Credits"},
 *         summary="Purchase Package", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"package_id","payment_method"},
 *             @OA\Property(property="package_id", type="integer"),
 *             @OA\Property(property="payment_method", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/credits/transactions",
 *
 *     @OA\Get(
 *         operationId="get_v1_credits_transactions_0",
 *         tags={"Credits"},
 *         summary="Get Transactions", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/cards",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_cards_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_crm_cards_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"card_request_id","card_procedure"},
 *             @OA\Property(property="card_request_id", type="integer"),
 *             @OA\Property(property="card_content", type="string"),
 *             @OA\Property(property="card_procedure", type="string"),
 *             @OA\Property(property="card_project", type="integer"),
 *             @OA\Property(property="card_property", type="integer"),
 *             @OA\Property(property="card_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/cards/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_cards_id_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_crm_cards_id_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"card_request_id","card_procedure"},
 *             @OA\Property(property="card_request_id", type="integer"),
 *             @OA\Property(property="card_content", type="string"),
 *             @OA\Property(property="card_procedure", type="string"),
 *             @OA\Property(property="card_project", type="integer"),
 *             @OA\Property(property="card_property", type="integer"),
 *             @OA\Property(property="card_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_crm_cards_id_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"card_request_id","card_procedure"},
 *             @OA\Property(property="card_request_id", type="integer"),
 *             @OA\Property(property="card_content", type="string"),
 *             @OA\Property(property="card_procedure", type="string"),
 *             @OA\Property(property="card_project", type="integer"),
 *             @OA\Property(property="card_property", type="integer"),
 *             @OA\Property(property="card_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_crm_cards_id_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/cards/{id}/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_cards_id_logs_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/requests",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_requests_0",
 *         tags={"Crm"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_crm_requests_1",
 *         tags={"Crm"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_name","customer_phone"},
 *             @OA\Property(property="customer_name", type="string", maxLength=255),
 *             @OA\Property(property="customer_phone", type="string", maxLength=32),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="property_id", type="integer"),
 *             @OA\Property(property="property_specifications", type="object", properties={@OA\Property(property="basic_information", type="array", @OA\Items(type="string")),@OA\Property(property="basic_information.address", type="string"),@OA\Property(property="basic_information.building", type="string"),@OA\Property(property="basic_information.price", type="number"),@OA\Property(property="basic_information.payment_method", type="string"),@OA\Property(property="basic_information.price_per_sqm", type="number"),@OA\Property(property="basic_information.listing_type", type="string"),@OA\Property(property="basic_information.property_category", type="string"),@OA\Property(property="basic_information.project", type="string"),@OA\Property(property="basic_information.city", type="string"),@OA\Property(property="basic_information.district", type="string"),@OA\Property(property="basic_information.area", type="string"),@OA\Property(property="basic_information.property_type", type="string"),@OA\Property(property="details", type="array", @OA\Items(type="string")),@OA\Property(property="details.features", type="array", @OA\Items(type="string")),@OA\Property(property="attributes", type="array", @OA\Items(type="string")),@OA\Property(property="attributes.area_sqft", type="number"),@OA\Property(property="attributes.year_built", type="integer"),@OA\Property(property="facilities", type="array", @OA\Items(type="string")),@OA\Property(property="facilities.bedrooms", type="integer"),@OA\Property(property="facilities.bathrooms", type="integer"),@OA\Property(property="facilities.rooms", type="integer"),@OA\Property(property="facilities.floors", type="integer"),@OA\Property(property="facilities.floor_number", type="integer"),@OA\Property(property="facilities.drivers_room", type="boolean"),@OA\Property(property="facilities.maids_room", type="boolean"),@OA\Property(property="facilities.dining_room", type="boolean"),@OA\Property(property="facilities.living_room", type="boolean"),@OA\Property(property="facilities.majlis", type="boolean"),@OA\Property(property="facilities.storage_room", type="boolean"),@OA\Property(property="facilities.basement", type="boolean"),@OA\Property(property="facilities.swimming_pool", type="boolean"),@OA\Property(property="facilities.kitchen", type="boolean"),@OA\Property(property="facilities.balcony", type="boolean"),@OA\Property(property="facilities.garden", type="boolean"),@OA\Property(property="facilities.annex", type="boolean"),@OA\Property(property="facilities.elevator", type="boolean"),@OA\Property(property="facilities.parking_space", type="integer")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/requests/reorder",
 *
 *     @OA\Post(
 *         operationId="post_v1_crm_requests_reorder_0",
 *         tags={"Crm"},
 *         summary="Reorder", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"stage_id","order"},
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="order", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/requests/{id}/change-stage",
 *
 *     @OA\Post(
 *         operationId="post_v1_crm_requests_id_change_stage_0",
 *         tags={"Crm"},
 *         summary="Change Stage", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"stage_id"},
 *             @OA\Property(property="stage_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/requests/{id}/details",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_requests_id_details_0",
 *         tags={"Crm"},
 *         summary="Details", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/requests/{request}",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_requests_request_0",
 *         tags={"Crm"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="request", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_crm_requests_request_1",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="request", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_name","customer_phone"},
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="customer_name", type="string", maxLength=255),
 *             @OA\Property(property="customer_phone", type="string", maxLength=32),
 *             @OA\Property(property="property_id", type="integer"),
 *             @OA\Property(property="property_specifications", type="object", properties={@OA\Property(property="basic_information", type="array", @OA\Items(type="string")),@OA\Property(property="basic_information.address", type="string"),@OA\Property(property="basic_information.building", type="string"),@OA\Property(property="basic_information.price", type="number"),@OA\Property(property="basic_information.payment_method", type="string"),@OA\Property(property="basic_information.price_per_sqm", type="number"),@OA\Property(property="basic_information.listing_type", type="string"),@OA\Property(property="basic_information.property_category", type="string"),@OA\Property(property="basic_information.project", type="string"),@OA\Property(property="basic_information.city", type="string"),@OA\Property(property="basic_information.district", type="string"),@OA\Property(property="basic_information.area", type="string"),@OA\Property(property="basic_information.property_type", type="string"),@OA\Property(property="details", type="array", @OA\Items(type="string")),@OA\Property(property="details.features", type="array", @OA\Items(type="string")),@OA\Property(property="attributes", type="array", @OA\Items(type="string")),@OA\Property(property="attributes.area_sqft", type="number"),@OA\Property(property="attributes.year_built", type="integer"),@OA\Property(property="facilities", type="array", @OA\Items(type="string")),@OA\Property(property="facilities.bedrooms", type="integer"),@OA\Property(property="facilities.bathrooms", type="integer"),@OA\Property(property="facilities.rooms", type="integer"),@OA\Property(property="facilities.floors", type="integer"),@OA\Property(property="facilities.floor_number", type="integer"),@OA\Property(property="facilities.drivers_room", type="boolean"),@OA\Property(property="facilities.maids_room", type="boolean"),@OA\Property(property="facilities.dining_room", type="boolean"),@OA\Property(property="facilities.living_room", type="boolean"),@OA\Property(property="facilities.majlis", type="boolean"),@OA\Property(property="facilities.storage_room", type="boolean"),@OA\Property(property="facilities.basement", type="boolean"),@OA\Property(property="facilities.swimming_pool", type="boolean"),@OA\Property(property="facilities.kitchen", type="boolean"),@OA\Property(property="facilities.balcony", type="boolean"),@OA\Property(property="facilities.garden", type="boolean"),@OA\Property(property="facilities.annex", type="boolean"),@OA\Property(property="facilities.elevator", type="boolean"),@OA\Property(property="facilities.parking_space", type="integer")}),
 *             @OA\Property(property="position", type="integer", minimum=0),
 *             @OA\Property(property="payment_method", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="type", type="string"),
 *             @OA\Property(property="beds", type="integer"),
 *             @OA\Property(property="bath", type="integer"),
 *             @OA\Property(property="area", type="number"),
 *             @OA\Property(property="status", type="integer"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="project_id", type="integer"),
 *             @OA\Property(property="region_id", type="integer"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="building_id", type="integer"),
 *             @OA\Property(property="water_meter_number", type="string"),
 *             @OA\Property(property="electricity_meter_number", type="string"),
 *             @OA\Property(property="deed_number", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="virtual_tour", type="string"),
 *             @OA\Property(property="size", type="number"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="state_id", type="integer"),
 *             @OA\Property(property="facade_id", type="number"),
 *             @OA\Property(property="length", type="number"),
 *             @OA\Property(property="width", type="number"),
 *             @OA\Property(property="street_width_north", type="number"),
 *             @OA\Property(property="street_width_south", type="number"),
 *             @OA\Property(property="street_width_east", type="number"),
 *             @OA\Property(property="street_width_west", type="number"),
 *             @OA\Property(property="building_age", type="integer"),
 *             @OA\Property(property="rooms", type="integer"),
 *             @OA\Property(property="bathrooms", type="integer"),
 *             @OA\Property(property="floors", type="integer"),
 *             @OA\Property(property="floor_number", type="integer"),
 *             @OA\Property(property="driver_room", type="integer"),
 *             @OA\Property(property="maid_room", type="integer"),
 *             @OA\Property(property="dining_room", type="integer"),
 *             @OA\Property(property="living_room", type="integer"),
 *             @OA\Property(property="majlis", type="integer"),
 *             @OA\Property(property="storage_room", type="integer"),
 *             @OA\Property(property="basement", type="integer"),
 *             @OA\Property(property="swimming_pool", type="integer"),
 *             @OA\Property(property="kitchen", type="integer"),
 *             @OA\Property(property="balcony", type="integer"),
 *             @OA\Property(property="garden", type="integer"),
 *             @OA\Property(property="annex", type="integer"),
 *             @OA\Property(property="elevator", type="integer"),
 *             @OA\Property(property="private_parking", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_crm_requests_request_2",
 *         tags={"Crm"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="request", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_name","customer_phone"},
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="customer_name", type="string", maxLength=255),
 *             @OA\Property(property="customer_phone", type="string", maxLength=32),
 *             @OA\Property(property="property_id", type="integer"),
 *             @OA\Property(property="property_specifications", type="object", properties={@OA\Property(property="basic_information", type="array", @OA\Items(type="string")),@OA\Property(property="basic_information.address", type="string"),@OA\Property(property="basic_information.building", type="string"),@OA\Property(property="basic_information.price", type="number"),@OA\Property(property="basic_information.payment_method", type="string"),@OA\Property(property="basic_information.price_per_sqm", type="number"),@OA\Property(property="basic_information.listing_type", type="string"),@OA\Property(property="basic_information.property_category", type="string"),@OA\Property(property="basic_information.project", type="string"),@OA\Property(property="basic_information.city", type="string"),@OA\Property(property="basic_information.district", type="string"),@OA\Property(property="basic_information.area", type="string"),@OA\Property(property="basic_information.property_type", type="string"),@OA\Property(property="details", type="array", @OA\Items(type="string")),@OA\Property(property="details.features", type="array", @OA\Items(type="string")),@OA\Property(property="attributes", type="array", @OA\Items(type="string")),@OA\Property(property="attributes.area_sqft", type="number"),@OA\Property(property="attributes.year_built", type="integer"),@OA\Property(property="facilities", type="array", @OA\Items(type="string")),@OA\Property(property="facilities.bedrooms", type="integer"),@OA\Property(property="facilities.bathrooms", type="integer"),@OA\Property(property="facilities.rooms", type="integer"),@OA\Property(property="facilities.floors", type="integer"),@OA\Property(property="facilities.floor_number", type="integer"),@OA\Property(property="facilities.drivers_room", type="boolean"),@OA\Property(property="facilities.maids_room", type="boolean"),@OA\Property(property="facilities.dining_room", type="boolean"),@OA\Property(property="facilities.living_room", type="boolean"),@OA\Property(property="facilities.majlis", type="boolean"),@OA\Property(property="facilities.storage_room", type="boolean"),@OA\Property(property="facilities.basement", type="boolean"),@OA\Property(property="facilities.swimming_pool", type="boolean"),@OA\Property(property="facilities.kitchen", type="boolean"),@OA\Property(property="facilities.balcony", type="boolean"),@OA\Property(property="facilities.garden", type="boolean"),@OA\Property(property="facilities.annex", type="boolean"),@OA\Property(property="facilities.elevator", type="boolean"),@OA\Property(property="facilities.parking_space", type="integer")}),
 *             @OA\Property(property="position", type="integer", minimum=0),
 *             @OA\Property(property="payment_method", type="string"),
 *             @OA\Property(property="price", type="number"),
 *             @OA\Property(property="pricePerMeter", type="number"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="type", type="string"),
 *             @OA\Property(property="beds", type="integer"),
 *             @OA\Property(property="bath", type="integer"),
 *             @OA\Property(property="area", type="number"),
 *             @OA\Property(property="status", type="integer"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="project_id", type="integer"),
 *             @OA\Property(property="region_id", type="integer"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="features", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="building_id", type="integer"),
 *             @OA\Property(property="water_meter_number", type="string"),
 *             @OA\Property(property="electricity_meter_number", type="string"),
 *             @OA\Property(property="deed_number", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="virtual_tour", type="string"),
 *             @OA\Property(property="size", type="number"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="state_id", type="integer"),
 *             @OA\Property(property="facade_id", type="number"),
 *             @OA\Property(property="length", type="number"),
 *             @OA\Property(property="width", type="number"),
 *             @OA\Property(property="street_width_north", type="number"),
 *             @OA\Property(property="street_width_south", type="number"),
 *             @OA\Property(property="street_width_east", type="number"),
 *             @OA\Property(property="street_width_west", type="number"),
 *             @OA\Property(property="building_age", type="integer"),
 *             @OA\Property(property="rooms", type="integer"),
 *             @OA\Property(property="bathrooms", type="integer"),
 *             @OA\Property(property="floors", type="integer"),
 *             @OA\Property(property="floor_number", type="integer"),
 *             @OA\Property(property="driver_room", type="integer"),
 *             @OA\Property(property="maid_room", type="integer"),
 *             @OA\Property(property="dining_room", type="integer"),
 *             @OA\Property(property="living_room", type="integer"),
 *             @OA\Property(property="majlis", type="integer"),
 *             @OA\Property(property="storage_room", type="integer"),
 *             @OA\Property(property="basement", type="integer"),
 *             @OA\Property(property="swimming_pool", type="integer"),
 *             @OA\Property(property="kitchen", type="integer"),
 *             @OA\Property(property="balcony", type="integer"),
 *             @OA\Property(property="garden", type="integer"),
 *             @OA\Property(property="annex", type="integer"),
 *             @OA\Property(property="elevator", type="integer"),
 *             @OA\Property(property="private_parking", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_crm_requests_request_3",
 *         tags={"Crm"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="request", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/crm/stages",
 *
 *     @OA\Get(
 *         operationId="get_v1_crm_stages_0",
 *         tags={"Crm"},
 *         summary="Stages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/customers",
 *
 *     @OA\Get(
 *         operationId="get_v1_customers_0",
 *         tags={"Customers"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_customers_1",
 *         tags={"Customers"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone_number", type="string", maxLength=50),
 *             @OA\Property(property="note", type="string", maxLength=2000),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="procedure_id", type="integer"),
 *             @OA\Property(property="type_id", type="integer"),
 *             @OA\Property(property="priority_id", type="integer"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/customers/{customer}",
 *
 *     @OA\Get(
 *         operationId="get_v1_customers_customer_0",
 *         tags={"Customers"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_customers_customer_1",
 *         tags={"Customers"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone_number", type="string", maxLength=50),
 *             @OA\Property(property="note", type="string", maxLength=2000),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="procedure_id", type="integer"),
 *             @OA\Property(property="type_id", type="integer"),
 *             @OA\Property(property="priority_id", type="integer"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_customers_customer_2",
 *         tags={"Customers"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone_number", type="string", maxLength=50),
 *             @OA\Property(property="note", type="string", maxLength=2000),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="procedure_id", type="integer"),
 *             @OA\Property(property="type_id", type="integer"),
 *             @OA\Property(property="priority_id", type="integer"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_customers_customer_3",
 *         tags={"Customers"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/customers/{id}/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_customers_id_logs_0",
 *         tags={"Customers"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/campaigns",
 *
 *     @OA\Get(
 *         operationId="get_v1_email_campaigns_0",
 *         tags={"Email"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_email_campaigns_1",
 *         tags={"Email"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","subject","body_html"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="subject", type="string", maxLength=255),
 *             @OA\Property(property="body_html", type="string"),
 *             @OA\Property(property="body_text", type="string"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"draft","scheduled"}),
 *             @OA\Property(property="scheduled_at", type="string"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/campaigns/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_email_campaigns_id_0",
 *         tags={"Email"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_email_campaigns_id_1",
 *         tags={"Email"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="subject", type="string", maxLength=255),
 *             @OA\Property(property="body_html", type="string"),
 *             @OA\Property(property="body_text", type="string"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"draft","scheduled"}),
 *             @OA\Property(property="scheduled_at", type="string"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_email_campaigns_id_2",
 *         tags={"Email"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/campaigns/{id}/pause",
 *
 *     @OA\Post(
 *         operationId="post_v1_email_campaigns_id_pause_0",
 *         tags={"Email"},
 *         summary="Pause", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/campaigns/{id}/resume",
 *
 *     @OA\Post(
 *         operationId="post_v1_email_campaigns_id_resume_0",
 *         tags={"Email"},
 *         summary="Resume", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"mode"},
 *             @OA\Property(property="mode", type="string", enum={"continue","restart"}),
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="manual_emails", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/campaigns/{id}/send",
 *
 *     @OA\Post(
 *         operationId="post_v1_email_campaigns_id_send_0",
 *         tags={"Email"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="manual_emails", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_email_logs_0",
 *         tags={"Email"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/messages/send",
 *
 *     @OA\Post(
 *         operationId="post_v1_email_messages_send_0",
 *         tags={"Email"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"recipient_email","subject","body_html"},
 *             @OA\Property(property="recipient_email", type="string", format="email"),
 *             @OA\Property(property="subject", type="string", maxLength=255),
 *             @OA\Property(property="body_html", type="string"),
 *             @OA\Property(property="body_text", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_email_stats_0",
 *         tags={"Email"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/templates",
 *
 *     @OA\Get(
 *         operationId="get_v1_email_templates_0",
 *         tags={"Email"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_email_templates_1",
 *         tags={"Email"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","subject","body_html"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="subject", type="string", maxLength=500),
 *             @OA\Property(property="body_html", type="string"),
 *             @OA\Property(property="body_text", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/templates/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_email_templates_id_0",
 *         tags={"Email"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_email_templates_id_1",
 *         tags={"Email"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="subject", type="string", maxLength=500),
 *             @OA\Property(property="body_html", type="string"),
 *             @OA\Property(property="body_text", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_email_templates_id_2",
 *         tags={"Email"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/email/webhooks/delivery",
 *
 *     @OA\Post(
 *         operationId="post_v1_email_webhooks_delivery_0",
 *         tags={"Email"},
 *         summary="Delivery",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="provider", type="string", maxLength=255),
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="gateway_message_id", type="string", maxLength=255),
 *             @OA\Property(property="status", type="string", enum={"delivered","failed","undelivered","rejected","bounced","spam","complained"}),
 *             @OA\Property(property="error_message", type="string", maxLength=1000),
 *             @OA\Property(property="delivered_at", type="string"),
 *             @OA\Property(property="events", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/employee-addons/payment/cancel/{addon_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_v1_employee_addons_payment_cancel_addon_id_gateway_0",
 *         tags={"Employee Addons"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_employee_addons_payment_cancel_addon_id_gateway_1",
 *         tags={"Employee Addons"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/employee-addons/payment/success/{addon_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_v1_employee_addons_payment_success_addon_id_gateway_0",
 *         tags={"Employee Addons"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_employee_addons_payment_success_addon_id_gateway_1",
 *         tags={"Employee Addons"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/employees",
 *
 *     @OA\Get(
 *         operationId="get_v1_employees_0",
 *         tags={"Employees"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_employees_1",
 *         tags={"Employees"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"email","password"},
 *             @OA\Property(property="first_name", type="string", maxLength=120),
 *             @OA\Property(property="last_name", type="string", maxLength=120),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=50),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="active", type="boolean"),
 *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="employeeRules", type="object", properties={@OA\Property(property="*.isActive", type="boolean"),@OA\Property(property="*.rules", type="array", @OA\Items(type="string")),@OA\Property(property="*.rules.*.id", type="string"),@OA\Property(property="*.rules.*.field", type="string", enum={"budgetMin","budgetMax","propertyType","city","source"}),@OA\Property(property="*.rules.*.operator", type="string", enum={"equals","greaterThan","lessThan","contains"}),@OA\Property(property="*.rules.*.value", type="string"),@OA\Property(property="*.employeeId", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/employees/available-permissions",
 *
 *     @OA\Get(
 *         operationId="get_v1_employees_available_permissions_0",
 *         tags={"Employees"},
 *         summary="Available Permissions", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/employees/available-roles",
 *
 *     @OA\Get(
 *         operationId="get_v1_employees_available_roles_0",
 *         tags={"Employees"},
 *         summary="Available Roles", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/employees/{employee}",
 *
 *     @OA\Get(
 *         operationId="get_v1_employees_employee_0",
 *         tags={"Employees"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_employees_employee_1",
 *         tags={"Employees"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="first_name", type="string", maxLength=120),
 *             @OA\Property(property="last_name", type="string", maxLength=120),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=50),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="active", type="boolean"),
 *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="employeeRules", type="object", properties={@OA\Property(property="*.isActive", type="boolean"),@OA\Property(property="*.rules", type="array", @OA\Items(type="string")),@OA\Property(property="*.rules.*.id", type="string"),@OA\Property(property="*.rules.*.field", type="string", enum={"budgetMin","budgetMax","propertyType","city","source"}),@OA\Property(property="*.rules.*.operator", type="string", enum={"equals","greaterThan","lessThan","contains"}),@OA\Property(property="*.rules.*.value", type="string"),@OA\Property(property="*.employeeId", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_employees_employee_2",
 *         tags={"Employees"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="first_name", type="string", maxLength=120),
 *             @OA\Property(property="last_name", type="string", maxLength=120),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=50),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="active", type="boolean"),
 *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="employeeRules", type="object", properties={@OA\Property(property="*.isActive", type="boolean"),@OA\Property(property="*.rules", type="array", @OA\Items(type="string")),@OA\Property(property="*.rules.*.id", type="string"),@OA\Property(property="*.rules.*.field", type="string", enum={"budgetMin","budgetMax","propertyType","city","source"}),@OA\Property(property="*.rules.*.operator", type="string", enum={"equals","greaterThan","lessThan","contains"}),@OA\Property(property="*.rules.*.value", type="string"),@OA\Property(property="*.employeeId", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_employees_employee_3",
 *         tags={"Employees"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/inquiry",
 *
 *     @OA\Get(
 *         operationId="get_v1_inquiry_0",
 *         tags={"Inquiry"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/job-applications",
 *
 *     @OA\Get(
 *         operationId="get_v1_job_applications_0",
 *         tags={"Job Applications"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/job-applications/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_job_applications_id_0",
 *         tags={"Job Applications"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_logs_0",
 *         tags={"Logs"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_0",
 *         tags={"Marketing"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_marketing_channels_1",
 *         tags={"Marketing"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","type","number"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=500),
 *             @OA\Property(property="type", type="string", maxLength=50),
 *             @OA\Property(property="number", type="string", maxLength=50),
 *             @OA\Property(property="business_id", type="string", maxLength=100),
 *             @OA\Property(property="phone_id", type="string", maxLength=100),
 *             @OA\Property(property="access_token", type="string", maxLength=500),
 *             @OA\Property(property="additional_settings", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/messages",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_messages_0",
 *         tags={"Marketing"},
 *         summary="Get Messages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/messages/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_messages_stats_0",
 *         tags={"Marketing"},
 *         summary="Get Message Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/send-whatsapp-to-customer",
 *
 *     @OA\Post(
 *         operationId="post_v1_marketing_channels_send_whatsapp_to_customer_0",
 *         tags={"Marketing"},
 *         summary="Send Whats App To Customer", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"customer_id","message"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="message", type="string", maxLength=1000),
 *             @OA\Property(property="channel_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/types",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_types_0",
 *         tags={"Marketing"},
 *         summary="Get Channel Types", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/usage",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_usage_0",
 *         tags={"Marketing"},
 *         summary="Get Usage", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_id_0",
 *         tags={"Marketing"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_marketing_channels_id_1",
 *         tags={"Marketing"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=500),
 *             @OA\Property(property="type", type="string", maxLength=50),
 *             @OA\Property(property="number", type="string", maxLength=50),
 *             @OA\Property(property="business_id", type="string", maxLength=100),
 *             @OA\Property(property="phone_id", type="string", maxLength=100),
 *             @OA\Property(property="access_token", type="string", maxLength=500),
 *             @OA\Property(property="additional_settings", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_marketing_channels_id_2",
 *         tags={"Marketing"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/send-message",
 *
 *     @OA\Post(
 *         operationId="post_v1_marketing_channels_id_send_message_0",
 *         tags={"Marketing"},
 *         summary="Send Message", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"to","message"},
 *             @OA\Property(property="to", type="string", maxLength=50),
 *             @OA\Property(property="message", type="string", maxLength=1000),
 *             @OA\Property(property="message_type", type="string", enum={"text","media","template"}),
 *             @OA\Property(property="media_url", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/settings",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_id_settings_0",
 *         tags={"Marketing"},
 *         summary="Get Marketing Settings", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_marketing_channels_id_settings_1",
 *         tags={"Marketing"},
 *         summary="Update Marketing Settings", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="crm_integration_enabled", type="boolean"),
 *             @OA\Property(property="appointment_system_integration_enabled", type="boolean"),
 *             @OA\Property(property="customers_page_integration_enabled", type="boolean"),
 *             @OA\Property(property="rental_page_integration_enabled", type="boolean"),
 *             @OA\Property(property="integration_settings", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="marketing_settings", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/statistics",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_id_statistics_0",
 *         tags={"Marketing"},
 *         summary="Statistics", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_channels_id_stats_0",
 *         tags={"Marketing"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/status",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_marketing_channels_id_status_0",
 *         tags={"Marketing"},
 *         summary="Update Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"is_connected"},
 *             @OA\Property(property="is_connected", type="boolean"),
 *             @OA\Property(property="is_verified", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/sync-verified",
 *
 *     @OA\Post(
 *         operationId="post_v1_marketing_channels_id_sync_verified_0",
 *         tags={"Marketing"},
 *         summary="Sync Verified", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/channels/{id}/system-integrations",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_marketing_channels_id_system_integrations_0",
 *         tags={"Marketing"},
 *         summary="Update System Integration Settings", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"crm_integration_enabled","appointment_system_integration_enabled","customers_page_integration_enabled","rental_page_integration_enabled"},
 *             @OA\Property(property="crm_integration_enabled", type="boolean"),
 *             @OA\Property(property="appointment_system_integration_enabled", type="boolean"),
 *             @OA\Property(property="customers_page_integration_enabled", type="boolean"),
 *             @OA\Property(property="rental_page_integration_enabled", type="boolean"),
 *             @OA\Property(property="integration_settings", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/settings",
 *
 *     @OA\Get(
 *         operationId="get_v1_marketing_settings_0",
 *         tags={"Marketing"},
 *         summary="Get All Marketing Settings", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/marketing/webhooks/whatsapp",
 *
 *     @OA\Post(
 *         operationId="post_v1_marketing_webhooks_whatsapp_0",
 *         tags={"Marketing"},
 *         summary="Whatsapp Webhook", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/customers",
 *
 *     @OA\Get(
 *         operationId="get_v1_matching_customers_0",
 *         tags={"Matching"},
 *         summary="Customers", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/customers/{customer_key}/properties",
 *
 *     @OA\Get(
 *         operationId="get_v1_matching_customers_customer_key_properties_0",
 *         tags={"Matching"},
 *         summary="Customer Properties", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customer_key", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/matches/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_matching_matches_id_0",
 *         tags={"Matching"},
 *         summary="Show Match", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/requests",
 *
 *     @OA\Get(
 *         operationId="get_v1_matching_requests_0",
 *         tags={"Matching"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/requests/{type}/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_matching_requests_type_id_0",
 *         tags={"Matching"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_matching_requests_type_id_1",
 *         tags={"Matching"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="budget_from", type="number"),
 *             @OA\Property(property="budget_to", type="number"),
 *             @OA\Property(property="area_from", type="number"),
 *             @OA\Property(property="area_to", type="number"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="districts_id", type="string"),
 *             @OA\Property(property="region", type="string"),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="inquiry_type", type="string"),
 *             @OA\Property(property="budget", type="number"),
 *             @OA\Property(property="currency", type="string"),
 *             @OA\Property(property="bedrooms", type="integer"),
 *             @OA\Property(property="bathrooms", type="integer"),
 *             @OA\Property(property="min_area_sqm", type="number"),
 *             @OA\Property(property="max_area_sqm", type="number"),
 *             @OA\Property(property="furnished", type="boolean"),
 *             @OA\Property(property="urgency", type="string"),
 *             @OA\Property(property="location", type="string"),
 *             @OA\Property(property="region_name", type="string"),
 *             @OA\Property(property="region_code", type="string"),
 *             @OA\Property(property="city", type="string"),
 *             @OA\Property(property="district", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="lang", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/requests/{type}/{id}/archive",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_matching_requests_type_id_archive_0",
 *         tags={"Matching"},
 *         summary="Archive", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/requests/{type}/{id}/read",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_matching_requests_type_id_read_0",
 *         tags={"Matching"},
 *         summary="Mark As Read", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/requests/{type}/{id}/unarchive",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_matching_requests_type_id_unarchive_0",
 *         tags={"Matching"},
 *         summary="Unarchive", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/matching/requests/{type}/{id}/unread",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_matching_requests_type_id_unread_0",
 *         tags={"Matching"},
 *         summary="Mark As Unread", security={{"sanctum":{}}},
 *         @OA\Parameter(name="type", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/me/abilities",
 *
 *     @OA\Get(
 *         operationId="get_v1_me_abilities_0",
 *         tags={"Me"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/messages/send",
 *
 *     @OA\Post(
 *         operationId="post_v1_messages_send_0",
 *         tags={"Messages"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"conversation_id","content"},
 *             @OA\Property(property="conversation_id", type="integer"),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="channel", type="string", enum={"whatsapp"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/check-property/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_check_property_id_0",
 *         tags={"Owner Rental"},
 *         summary="Check Property",
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/dashboard",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_dashboard_0",
 *         tags={"Owner Rental"},
 *         summary="Dashboard",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/financial-reports",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_financial_reports_0",
 *         tags={"Owner Rental"},
 *         summary="Financial Reports",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/forgot-password",
 *
 *     @OA\Post(
 *         operationId="post_v1_owner_rental_forgot_password_0",
 *         tags={"Owner Rental"},
 *         summary="Forgot Password",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"email"},
 *             @OA\Property(property="email", type="string", format="email"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/login",
 *
 *     @OA\Post(
 *         operationId="post_v1_owner_rental_login_0",
 *         tags={"Owner Rental"},
 *         summary="Login",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/logout",
 *
 *     @OA\Post(
 *         operationId="post_v1_owner_rental_logout_0",
 *         tags={"Owner Rental"},
 *         summary="Logout",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/maintenance-requests",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_maintenance_requests_0",
 *         tags={"Owner Rental"},
 *         summary="Maintenance Requests",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/me",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_me_0",
 *         tags={"Owner Rental"},
 *         summary="Me",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/properties",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_properties_0",
 *         tags={"Owner Rental"},
 *         summary="Properties",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/properties/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_properties_id_0",
 *         tags={"Owner Rental"},
 *         summary="Property Details",
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/rentals",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_rentals_0",
 *         tags={"Owner Rental"},
 *         summary="Rentals",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/reset-password",
 *
 *     @OA\Post(
 *         operationId="post_v1_owner_rental_reset_password_0",
 *         tags={"Owner Rental"},
 *         summary="Reset Password",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"token","email","password"},
 *             @OA\Property(property="token", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/owner-rental/tenants",
 *
 *     @OA\Get(
 *         operationId="get_v1_owner_rental_tenants_0",
 *         tags={"Owner Rental"},
 *         summary="Tenants",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/permissions",
 *
 *     @OA\Get(
 *         operationId="get_v1_permissions_0",
 *         tags={"Permissions"},
 *         summary="Permissions", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_permissions_1",
 *         tags={"Permissions"},
 *         summary="Store Permission", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=500),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/permissions/{id}",
 *
 *     @OA\Put(
 *         operationId="put_v1_permissions_id_0",
 *         tags={"Permissions"},
 *         summary="Update Permission", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=500),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_permissions_id_1",
 *         tags={"Permissions"},
 *         summary="Destroy Permission", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/dashboard",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_dashboard_0",
 *         tags={"Pms"},
 *         summary="Dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/projects",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_projects_0",
 *         tags={"Pms"},
 *         summary="Get Projects", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/properties",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_properties_0",
 *         tags={"Pms"},
 *         summary="Get Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_purchase_requests_0",
 *         tags={"Pms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_1",
 *         tags={"Pms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"client_name","client_email","client_phone","priority"},
 *             @OA\Property(property="client_name", type="string", maxLength=255),
 *             @OA\Property(property="client_email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="client_phone", type="string", maxLength=20),
 *             @OA\Property(property="client_national_id", type="string", maxLength=50),
 *             @OA\Property(property="property_id", type="string"),
 *             @OA\Property(property="project_id", type="string"),
 *             @OA\Property(property="priority", type="string"),
 *             @OA\Property(property="budget_amount", type="number", minimum=0),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="additional_notes", type="string"),
 *             @OA\Property(property="assigned_to", type="string"),
 *             @OA\Property(property="expected_completion_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_purchase_requests_id_0",
 *         tags={"Pms"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_pms_purchase_requests_id_1",
 *         tags={"Pms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"client_name","client_email","client_phone","priority"},
 *             @OA\Property(property="client_name", type="string", maxLength=255),
 *             @OA\Property(property="client_email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="client_phone", type="string", maxLength=20),
 *             @OA\Property(property="client_national_id", type="string", maxLength=50),
 *             @OA\Property(property="property_id", type="string"),
 *             @OA\Property(property="project_id", type="string"),
 *             @OA\Property(property="priority", type="string"),
 *             @OA\Property(property="budget_amount", type="number", minimum=0),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="additional_notes", type="string"),
 *             @OA\Property(property="assigned_to", type="string"),
 *             @OA\Property(property="overall_status", type="string"),
 *             @OA\Property(property="expected_completion_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_pms_purchase_requests_id_2",
 *         tags={"Pms"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{id}/simple-transition-stage",
 *
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_id_simple_transition_stage_0",
 *         tags={"Pms"},
 *         summary="Simple Transition Stage", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"current_stage_name","requirements_met"},
 *             @OA\Property(property="current_stage_name", type="string"),
 *             @OA\Property(property="requirements_met", type="array", @OA\Items(type="boolean")),
 *             @OA\Property(property="inspection_date", type="string"),
 *             @OA\Property(property="payment_amount", type="number", minimum=0),
 *             @OA\Property(property="expected_completion_date", type="string"),
 *             @OA\Property(property="additional_notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{id}/transition-stage",
 *
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_id_transition_stage_0",
 *         tags={"Pms"},
 *         summary="Transition Stage", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"current_stage_name","requirements_met"},
 *             @OA\Property(property="current_stage_name", type="string"),
 *             @OA\Property(property="requirements_met", type="array", @OA\Items(type="boolean")),
 *             @OA\Property(property="inspection_date", type="string"),
 *             @OA\Property(property="payment_amount", type="number", minimum=0),
 *             @OA\Property(property="expected_completion_date", type="string"),
 *             @OA\Property(property="additional_notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_purchase_requests_purchase_equest_d_stages_0",
 *         tags={"Pms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/bulk-update",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_pms_purchase_requests_purchase_equest_d_stages_bulk_update_0",
 *         tags={"Pms"},
 *         summary="Bulk Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"stages"},
 *             @OA\Property(property="stages", type="object", properties={@OA\Property(property="*.stage_id", type="integer"),@OA\Property(property="*.status", type="string"),@OA\Property(property="*.notes", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/statistics",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_purchase_requests_purchase_equest_d_stages_statistics_0",
 *         tags={"Pms"},
 *         summary="Statistics", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_purchase_requests_purchase_equest_d_stages_stage_d_0",
 *         tags={"Pms"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="stageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/mark-completed",
 *
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_purchase_equest_d_stages_stage_d_mark_completed_0",
 *         tags={"Pms"},
 *         summary="Mark Completed", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="stageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/mark-in-progress",
 *
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_purchase_equest_d_stages_stage_d_mark_in_progress_0",
 *         tags={"Pms"},
 *         summary="Mark In Progress", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="stageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/mark-pending",
 *
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_purchase_equest_d_stages_stage_d_mark_pending_0",
 *         tags={"Pms"},
 *         summary="Mark Pending", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="stageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/notes",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_pms_purchase_requests_purchase_equest_d_stages_stage_d_notes_0",
 *         tags={"Pms"},
 *         summary="Update Notes", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="stageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"notes"},
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/status",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_pms_purchase_requests_purchase_equest_d_stages_stage_d_status_0",
 *         tags={"Pms"},
 *         summary="Update Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="purchaseRequestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="stageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status"},
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/pms/staff",
 *
 *     @OA\Get(
 *         operationId="get_v1_pms_staff_0",
 *         tags={"Pms"},
 *         summary="Get Staff", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/projects/{id}/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_projects_id_logs_0",
 *         tags={"Projects"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/properties/{id}/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_properties_id_logs_0",
 *         tags={"Properties"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-settings",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_request_settings_0",
 *         tags={"Property Request Settings"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-settings/bulk",
 *
 *     @OA\Post(
 *         operationId="post_v1_property_request_settings_bulk_0",
 *         tags={"Property Request Settings"},
 *         summary="Bulk Upsert", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"items"},
 *             @OA\Property(property="items", type="object", properties={@OA\Property(property="*.field_key", type="string"),@OA\Property(property="*.is_visible", type="boolean"),@OA\Property(property="*.is_required", type="boolean"),@OA\Property(property="*.sort_order", type="integer"),@OA\Property(property="*.label_ar", type="string", maxLength=255),@OA\Property(property="*.label_en", type="string", maxLength=255),@OA\Property(property="*.meta", type="array", @OA\Items(type="string"))}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-settings/defaults",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_request_settings_defaults_0",
 *         tags={"Property Request Settings"},
 *         summary="Defaults", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-settings/reset",
 *
 *     @OA\Post(
 *         operationId="post_v1_property_request_settings_reset_0",
 *         tags={"Property Request Settings"},
 *         summary="Reset", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="keys", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-settings/{field}",
 *
 *     @OA\Put(
 *         operationId="put_v1_property_request_settings_field_0",
 *         tags={"Property Request Settings"},
 *         summary="Update One", security={{"sanctum":{}}},
 *         @OA\Parameter(name="field", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="is_visible", type="boolean"),
 *             @OA\Property(property="is_required", type="boolean"),
 *             @OA\Property(property="sort_order", type="integer"),
 *             @OA\Property(property="label_ar", type="string", maxLength=255),
 *             @OA\Property(property="label_en", type="string", maxLength=255),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-statuses",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_request_statuses_0",
 *         tags={"Property Request Statuses"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_property_request_statuses_1",
 *         tags={"Property Request Statuses"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name_ar","slug"},
 *             @OA\Property(property="name_ar", type="string", maxLength=100),
 *             @OA\Property(property="name_en", type="string", maxLength=100),
 *             @OA\Property(property="slug", type="string", maxLength=100),
 *             @OA\Property(property="display_order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-request-statuses/{id}",
 *
 *     @OA\Put(
 *         operationId="put_v1_property_request_statuses_id_0",
 *         tags={"Property Request Statuses"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name_ar","slug"},
 *             @OA\Property(property="name_ar", type="string", maxLength=100),
 *             @OA\Property(property="name_en", type="string", maxLength=100),
 *             @OA\Property(property="slug", type="string", maxLength=100),
 *             @OA\Property(property="display_order", type="integer", minimum=1),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_property_request_statuses_id_1",
 *         tags={"Property Request Statuses"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_requests_0",
 *         tags={"Property Requests"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_property_requests_1",
 *         tags={"Property Requests"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"full_name","phone"},
 *             @OA\Property(property="tenant_username", type="string", maxLength=255),
 *             @OA\Property(property="full_name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=20),
 *             @OA\Property(property="property_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="source", type="string"),
 *             @OA\Property(property="referral_source", type="string"),
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="category", type="string"),
 *             @OA\Property(property="region", type="integer"),
 *             @OA\Property(property="districts_id", type="integer"),
 *             @OA\Property(property="area_from", type="integer", minimum=0),
 *             @OA\Property(property="area_to", type="integer", minimum=0),
 *             @OA\Property(property="purchase_method", type="string"),
 *             @OA\Property(property="budget_from", type="string"),
 *             @OA\Property(property="budget_to", type="string"),
 *             @OA\Property(property="seriousness", type="string"),
 *             @OA\Property(property="purchase_goal", type="string"),
 *             @OA\Property(property="wants_similar_offers", type="boolean"),
 *             @OA\Property(property="contact_on_whatsapp", type="boolean"),
 *             @OA\Property(property="notes", type="string", maxLength=5000),
 *             @OA\Property(property="status_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/customer/{customerID}/employee",
 *
 *     @OA\Put(
 *         operationId="put_v1_property_requests_customer_customer_employee_0",
 *         tags={"Property Requests"},
 *         summary="Assign Employee To Customer", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerID", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="responsible_employee_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/filters",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_requests_filters_0",
 *         tags={"Property Requests"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/interest",
 *
 *     @OA\Post(
 *         operationId="post_v1_property_requests_interest_0",
 *         tags={"Property Requests"},
 *         summary="Store From Interest",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"tenant_username","property_id","full_name","phone"},
 *             @OA\Property(property="tenant_username", type="string", maxLength=255),
 *             @OA\Property(property="property_id", type="integer"),
 *             @OA\Property(property="full_name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=20),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/public",
 *
 *     @OA\Post(
 *         operationId="post_v1_property_requests_public_0",
 *         tags={"Property Requests"},
 *         summary="Store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"full_name","phone"},
 *             @OA\Property(property="tenant_username", type="string", maxLength=255),
 *             @OA\Property(property="full_name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=20),
 *             @OA\Property(property="property_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="source", type="string"),
 *             @OA\Property(property="referral_source", type="string"),
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="category", type="string"),
 *             @OA\Property(property="region", type="integer"),
 *             @OA\Property(property="districts_id", type="integer"),
 *             @OA\Property(property="area_from", type="integer", minimum=0),
 *             @OA\Property(property="area_to", type="integer", minimum=0),
 *             @OA\Property(property="purchase_method", type="string"),
 *             @OA\Property(property="budget_from", type="string"),
 *             @OA\Property(property="budget_to", type="string"),
 *             @OA\Property(property="seriousness", type="string"),
 *             @OA\Property(property="purchase_goal", type="string"),
 *             @OA\Property(property="wants_similar_offers", type="boolean"),
 *             @OA\Property(property="contact_on_whatsapp", type="boolean"),
 *             @OA\Property(property="notes", type="string", maxLength=5000),
 *             @OA\Property(property="status_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_requests_stats_0",
 *         tags={"Property Requests"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_property_requests_id_0",
 *         tags={"Property Requests"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_property_requests_id_1",
 *         tags={"Property Requests"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_property_requests_id_2",
 *         tags={"Property Requests"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="status_id", type="integer"),
 *             @OA\Property(property="full_name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=20),
 *             @OA\Property(property="region", type="integer"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="districts_id", type="integer"),
 *             @OA\Property(property="area_from", type="integer", minimum=0),
 *             @OA\Property(property="area_to", type="integer", minimum=0),
 *             @OA\Property(property="location", type="string", maxLength=255),
 *             @OA\Property(property="city", type="string", maxLength=128),
 *             @OA\Property(property="district", type="string", maxLength=128),
 *             @OA\Property(property="country_code", type="string", maxLength=2),
 *             @OA\Property(property="region_code", type="string", maxLength=4),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="location_confidence", type="number"),
 *             @OA\Property(property="purpose", type="string"),
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="category_id", type="integer"),
 *             @OA\Property(property="purchase_method", type="string"),
 *             @OA\Property(property="budget_from", type="number", minimum=0),
 *             @OA\Property(property="budget_to", type="number", minimum=0),
 *             @OA\Property(property="currency", type="string", maxLength=8),
 *             @OA\Property(property="seriousness", type="string"),
 *             @OA\Property(property="purchase_goal", type="string"),
 *             @OA\Property(property="wants_similar_offers", type="boolean"),
 *             @OA\Property(property="contact_on_whatsapp", type="boolean"),
 *             @OA\Property(property="is_read", type="boolean"),
 *             @OA\Property(property="is_archived", type="boolean"),
 *             @OA\Property(property="is_ignored", type="boolean"),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="bedrooms", type="integer", minimum=0),
 *             @OA\Property(property="bathrooms", type="integer", minimum=0),
 *             @OA\Property(property="furnished", type="boolean"),
 *             @OA\Property(property="customers_hub_stage_id", type="string", maxLength=255),
 *             @OA\Property(property="responsible_employee_id", type="integer"),
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="property_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="inquiry_type", type="string", maxLength=100),
 *             @OA\Property(property="lang", type="string", maxLength=8),
 *             @OA\Property(property="referral_source", type="string", maxLength=255),
 *             @OA\Property(property="detected_entities_json", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="notes", type="string", maxLength=5000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/{id}/employee",
 *
 *     @OA\Put(
 *         operationId="put_v1_property_requests_id_employee_0",
 *         tags={"Property Requests"},
 *         summary="Update Employee", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"responsible_employee_id"},
 *             @OA\Property(property="responsible_employee_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/{id}/priority",
 *
 *     @OA\Put(
 *         operationId="put_v1_property_requests_id_priority_0",
 *         tags={"Property Requests"},
 *         summary="Update Priority", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"priority"},
 *             @OA\Property(property="priority", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/{id}/properties",
 *
 *     @OA\Post(
 *         operationId="post_v1_property_requests_id_properties_0",
 *         tags={"Property Requests"},
 *         summary="Attach Properties", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"propertyIds"},
 *             @OA\Property(property="propertyIds", type="array", minLength=1, @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/{id}/properties/{propertyId}",
 *
 *     @OA\Delete(
 *         operationId="delete_v1_property_requests_id_properties_property_d_0",
 *         tags={"Property Requests"},
 *         summary="Detach Property", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="propertyId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/property-requests/{id}/status",
 *
 *     @OA\Put(
 *         operationId="put_v1_property_requests_id_status_0",
 *         tags={"Property Requests"},
 *         summary="Update Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status_id"},
 *             @OA\Property(property="status_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/employees-show-roles/{employee}/roles",
 *
 *     @OA\Get(
 *         operationId="get_v1_rbac_employees_show_roles_employee_roles_0",
 *         tags={"Rbac"},
 *         summary="Show Roles", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/employees-sync-perms/{employee}/perms",
 *
 *     @OA\Post(
 *         operationId="post_v1_rbac_employees_sync_perms_employee_perms_0",
 *         tags={"Rbac"},
 *         summary="Sync Perms", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/employees-sync-roles/{employee}/roles",
 *
 *     @OA\Post(
 *         operationId="post_v1_rbac_employees_sync_roles_employee_roles_0",
 *         tags={"Rbac"},
 *         summary="Sync Roles", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/permissions",
 *
 *     @OA\Get(
 *         operationId="get_v1_rbac_permissions_0",
 *         tags={"Rbac"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rbac_permissions_1",
 *         tags={"Rbac"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=191),
 *             @OA\Property(property="name_ar", type="string", maxLength=191),
 *             @OA\Property(property="name_en", type="string", maxLength=191),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/permissions/{permission}",
 *
 *     @OA\Put(
 *         operationId="put_v1_rbac_permissions_permission_0",
 *         tags={"Rbac"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="permission", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=191),
 *             @OA\Property(property="name_ar", type="string", maxLength=191),
 *             @OA\Property(property="name_en", type="string", maxLength=191),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rbac_permissions_permission_1",
 *         tags={"Rbac"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="permission", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/perms/me",
 *
 *     @OA\Get(
 *         operationId="get_v1_rbac_perms_me_0",
 *         tags={"Rbac"},
 *         summary="Me", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/roles",
 *
 *     @OA\Get(
 *         operationId="get_v1_rbac_roles_0",
 *         tags={"Rbac"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rbac_roles_1",
 *         tags={"Rbac"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/roles/{role}",
 *
 *     @OA\Put(
 *         operationId="put_v1_rbac_roles_role_0",
 *         tags={"Rbac"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rbac_roles_role_1",
 *         tags={"Rbac"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rbac/show-employees-data/{employee}",
 *
 *     @OA\Get(
 *         operationId="get_v1_rbac_show_employees_data_employee_0",
 *         tags={"Rbac"},
 *         summary="Show Employee Data", security={{"sanctum":{}}},
 *         @OA\Parameter(name="employee", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations",
 *
 *     @OA\Get(
 *         operationId="get_v1_reservations_0",
 *         tags={"Reservations"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations/bulk-action",
 *
 *     @OA\Post(
 *         operationId="post_v1_reservations_bulk_action_0",
 *         tags={"Reservations"},
 *         summary="Bulk Action", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"action","reservationIds"},
 *             @OA\Property(property="action", type="string", enum={"accept","reject"}),
 *             @OA\Property(property="reservationIds", type="array", minLength=1, @OA\Items(type="string")),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations/export/csv",
 *
 *     @OA\Get(
 *         operationId="get_v1_reservations_export_csv_0",
 *         tags={"Reservations"},
 *         summary="Export Csv", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_reservations_stats_0",
 *         tags={"Reservations"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_reservations_id_0",
 *         tags={"Reservations"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations/{id}/accept",
 *
 *     @OA\Post(
 *         operationId="post_v1_reservations_id_accept_0",
 *         tags={"Reservations"},
 *         summary="Accept", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="confirmPayment", type="boolean"),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/reservations/{id}/reject",
 *
 *     @OA\Post(
 *         operationId="post_v1_reservations_id_reject_0",
 *         tags={"Reservations"},
 *         summary="Reject", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="confirmPayment", type="boolean"),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/contracts",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_contracts_0",
 *         tags={"Rms"},
 *         summary="All Contracts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/contracts/{id}",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_rms_contracts_id_0",
 *         tags={"Rms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="start_date", type="string"),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="status", type="string", enum={"pending","active","expired","terminated"}),
 *             @OA\Property(property="file_path", type="string", maxLength=255),
 *             @OA\Property(property="property_id", type="integer", minimum=1),
 *             @OA\Property(property="project_id", type="integer", minimum=1),
 *             @OA\Property(property="property_name", type="string", maxLength=150),
 *             @OA\Property(property="project_name", type="string", maxLength=150),
 *             @OA\Property(property="grace_period_months", type="integer", minimum=0, maximum=2),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/contracts/{id}/status",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_rms_contracts_id_status_0",
 *         tags={"Rms"},
 *         summary="Change Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status"},
 *             @OA\Property(property="status", type="string", enum={"pending","active","expired","terminated"}),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *             @OA\Property(property="effective_date", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/contracts/{id}/terminate",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_contracts_id_terminate_0",
 *         tags={"Rms"},
 *         summary="Terminate", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"termination_reason","terminate_on"},
 *             @OA\Property(property="termination_reason", type="string", maxLength=255),
 *             @OA\Property(property="terminate_on", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/daily-follow-up",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_daily_follow_up_0",
 *         tags={"Rms"},
 *         summary="Daily Follow Up", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/dashboard",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_dashboard_0",
 *         tags={"Rms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/expenses/upload-image",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_expenses_upload_image_0",
 *         tags={"Rms"},
 *         summary="Upload Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"image"},
 *             @OA\Property(property="image", type="string", format="binary", maxLength=2048),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/installments",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_installments_0",
 *         tags={"Rms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/installments/{id}",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_rms_installments_id_0",
 *         tags={"Rms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="status", type="string", enum={"pending","paid","partial","overdue","void"}),
 *             @OA\Property(property="paid_amount", type="number", minimum=0),
 *             @OA\Property(property="paid_at", type="string"),
 *             @OA\Property(property="reference", type="string", maxLength=100),
 *             @OA\Property(property="notes", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/maintenance",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_maintenance_0",
 *         tags={"Rms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_maintenance_1",
 *         tags={"Rms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"rental_id","category","priority","title","description"},
 *             @OA\Property(property="rental_id", type="integer"),
 *             @OA\Property(property="category", type="string", maxLength=50),
 *             @OA\Property(property="priority", type="string", enum={"low","medium","high","critical"}),
 *             @OA\Property(property="title", type="string", maxLength=150),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="estimated_cost", type="number"),
 *             @OA\Property(property="payer", type="string", enum={"landlord","tenant","shared"}),
 *             @OA\Property(property="payer_share_percent", type="integer", minimum=0, maximum=100),
 *             @OA\Property(property="scheduled_date", type="string"),
 *             @OA\Property(property="assigned_to_vendor_id", type="integer"),
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/maintenance/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_maintenance_id_0",
 *         tags={"Rms"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_rms_maintenance_id_1",
 *         tags={"Rms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=150),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="estimated_cost", type="number"),
 *             @OA\Property(property="payer", type="string", enum={"landlord","tenant","shared"}),
 *             @OA\Property(property="payer_share_percent", type="integer", minimum=0, maximum=100),
 *             @OA\Property(property="scheduled_date", type="string"),
 *             @OA\Property(property="assigned_to_vendor_id", type="integer"),
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rms_maintenance_id_2",
 *         tags={"Rms"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/maintenance/{id}/status",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_maintenance_id_status_0",
 *         tags={"Rms"},
 *         summary="Update Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status"},
 *             @OA\Property(property="status", type="string", enum={"open","in_progress","on_hold","resolved","cancelled"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/payment-collection",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_payment_collection_0",
 *         tags={"Rms"},
 *         summary="All Payment Collections", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/payment-report",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_payment_report_0",
 *         tags={"Rms"},
 *         summary="Payment Report", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/payments/collections",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_payments_collections_0",
 *         tags={"Rms"},
 *         summary="Payments Collections", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/payments/due",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_payments_due_0",
 *         tags={"Rms"},
 *         summary="Payments Due", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/reminders",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_reminders_0",
 *         tags={"Rms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/reminders/{id}/dismiss",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_reminders_id_dismiss_0",
 *         tags={"Rms"},
 *         summary="Dismiss", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/reminders/{id}/snooze",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_reminders_id_snooze_0",
 *         tags={"Rms"},
 *         summary="Snooze", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"snooze_until"},
 *             @OA\Property(property="snooze_until", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_0",
 *         tags={"Rms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_1",
 *         tags={"Rms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"tenant_full_name","tenant_phone","rental_type","rental_duration","paying_plan","total_rental_amount"},
 *             @OA\Property(property="tenant_full_name", type="string", maxLength=150),
 *             @OA\Property(property="tenant_phone", type="string", maxLength=32),
 *             @OA\Property(property="tenant_email", type="string", format="email"),
 *             @OA\Property(property="tenant_job_title", type="string", maxLength=120),
 *             @OA\Property(property="tenant_social_status", type="string", enum={"single","married","divorced","widowed","other"}),
 *             @OA\Property(property="tenant_national_id", type="string", maxLength=20),
 *             @OA\Property(property="unit_id", type="integer"),
 *             @OA\Property(property="project_id", type="integer"),
 *             @OA\Property(property="building_id", type="string"),
 *             @OA\Property(property="move_in_date", type="string"),
 *             @OA\Property(property="rental_type", type="string", enum={"monthly","annual"}),
 *             @OA\Property(property="rental_duration", type="integer", minimum=1),
 *             @OA\Property(property="paying_plan", type="string", enum={"monthly","quarterly","semi_annual","annual"}),
 *             @OA\Property(property="total_rental_amount", type="number", minimum=0),
 *             @OA\Property(property="currency", type="string"),
 *             @OA\Property(property="contract_number", type="string", maxLength=255),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="cost_items", type="object", properties={@OA\Property(property="*.name", type="string", maxLength=255),@OA\Property(property="*.cost", type="number", minimum=0),@OA\Property(property="*.type", type="string", enum={"fixed","percentage"}),@OA\Property(property="*.payer", type="string", enum={"owner","tenant"}),@OA\Property(property="*.payment_frequency", type="string", enum={"one_time","per_installment"}),@OA\Property(property="*.percentage_of", type="number", minimum=0),@OA\Property(property="*.description", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/filter-options",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_filter_options_0",
 *         tags={"Rms"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/upload-receipt-image",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_upload_receipt_image_0",
 *         tags={"Rms"},
 *         summary="Upload Receipt Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"receipt_image"},
 *             @OA\Property(property="receipt_image", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_id_0",
 *         tags={"Rms"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_rms_rentals_id_1",
 *         tags={"Rms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="tenant_full_name", type="string", maxLength=150),
 *             @OA\Property(property="tenant_phone", type="string", maxLength=32),
 *             @OA\Property(property="tenant_email", type="string", format="email"),
 *             @OA\Property(property="tenant_job_title", type="string", maxLength=120),
 *             @OA\Property(property="tenant_social_status", type="string", enum={"single","married","divorced","widowed","other"}),
 *             @OA\Property(property="tenant_national_id", type="string", maxLength=20),
 *             @OA\Property(property="unit_id", type="integer"),
 *             @OA\Property(property="project_id", type="integer"),
 *             @OA\Property(property="building_id", type="string"),
 *             @OA\Property(property="move_in_date", type="string"),
 *             @OA\Property(property="rental_type", type="string", enum={"monthly","annual"}),
 *             @OA\Property(property="rental_duration", type="integer", minimum=1),
 *             @OA\Property(property="paying_plan", type="string", enum={"monthly","quarterly","semi_annual","annual"}),
 *             @OA\Property(property="total_rental_amount", type="number", minimum=0),
 *             @OA\Property(property="currency", type="string"),
 *             @OA\Property(property="contract_number", type="string", maxLength=255),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="cost_items", type="object", properties={@OA\Property(property="*.name", type="string", maxLength=255),@OA\Property(property="*.cost", type="number", minimum=0),@OA\Property(property="*.type", type="string", enum={"fixed","percentage"}),@OA\Property(property="*.payer", type="string", enum={"owner","tenant"}),@OA\Property(property="*.payment_frequency", type="string", enum={"one_time","per_installment"}),@OA\Property(property="*.percentage_of", type="number", minimum=0),@OA\Property(property="*.description", type="string")}),
 *             @OA\Property(property="payments", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="regenerate_schedule", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rms_rentals_id_2",
 *         tags={"Rms"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/collect-payment",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_id_collect_payment_0",
 *         tags={"Rms"},
 *         summary="Collect Payment", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"payment_method","transfer_to"},
 *             @OA\Property(property="auto_select", type="boolean"),
 *             @OA\Property(property="auto_select_amount", type="number", minimum=0),
 *             @OA\Property(property="auto_select_strategy", type="string", enum={"overdue_first","oldest_first","sequential"}),
 *             @OA\Property(property="amount", type="number", minimum=0),
 *             @OA\Property(property="payment_amount", type="number", minimum=0),
 *             @OA\Property(property="payments", type="object", properties={@OA\Property(property="*.installment_id", type="integer"),@OA\Property(property="*.payment_type", type="string", enum={"rent","cost_item","deposit"}),@OA\Property(property="*.cost_item_id", type="integer"),@OA\Property(property="*.amount", type="number", minimum=0),@OA\Property(property="*.notes", type="string", maxLength=255)}),
 *             @OA\Property(property="payment_method", type="string", enum={"cash","bank_transfer","credit_card","online_payment","check","other"}),
 *             @OA\Property(property="payment_date", type="string"),
 *             @OA\Property(property="reference", type="string", maxLength=100),
 *             @OA\Property(property="notes", type="string", maxLength=255),
 *             @OA\Property(property="bank_name", type="string", maxLength=100),
 *             @OA\Property(property="receipt_image_path", type="string", maxLength=500),
 *             @OA\Property(property="transfer_to", type="string", enum={"منصة ناجز","المالك","المكتب"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/current-collections",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_id_current_collections_0",
 *         tags={"Rms"},
 *         summary="Current Collections", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/details",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_id_details_0",
 *         tags={"Rms"},
 *         summary="Property Details", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/details-with-payments",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_id_details_with_payments_0",
 *         tags={"Rms"},
 *         summary="Details With Payments", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/end-contract",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_id_end_contract_0",
 *         tags={"Rms"},
 *         summary="End Contract", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"end_date"},
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="termination_reason", type="string", maxLength=255),
 *             @OA\Property(property="notes", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/payment-collection",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_id_payment_collection_0",
 *         tags={"Rms"},
 *         summary="Payment Collection", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/payments",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_id_payments_0",
 *         tags={"Rms"},
 *         summary="List Payments", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/renew",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_id_renew_0",
 *         tags={"Rms"},
 *         summary="Renew Rental", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"rental_type","rental_duration","paying_plan","total_rental_amount"},
 *             @OA\Property(property="rental_type", type="string", enum={"monthly","annual"}),
 *             @OA\Property(property="rental_duration", type="integer", minimum=1),
 *             @OA\Property(property="paying_plan", type="string", enum={"monthly","quarterly","semi_annual","annual"}),
 *             @OA\Property(property="total_rental_amount", type="number", minimum=0),
 *             @OA\Property(property="currency", type="string"),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="cost_items", type="object", properties={@OA\Property(property="*.name", type="string", maxLength=255),@OA\Property(property="*.cost", type="number", minimum=0),@OA\Property(property="*.type", type="string", enum={"fixed","percentage"}),@OA\Property(property="*.payer", type="string", enum={"owner","tenant"}),@OA\Property(property="*.payment_frequency", type="string", enum={"one_time","per_installment"}),@OA\Property(property="*.percentage_of", type="number", minimum=0),@OA\Property(property="*.description", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{id}/status",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_rms_rentals_id_status_0",
 *         tags={"Rms"},
 *         summary="Update Status", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status"},
 *             @OA\Property(property="status", type="string", enum={"active","inactive","terminated","ended","cancelled","draft"}),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="termination_reason", type="string", maxLength=500),
 *             @OA\Property(property="notes", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{rentalId}/contracts",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_rental_d_contracts_0",
 *         tags={"Rms"},
 *         summary="List By Rental", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_rental_d_contracts_1",
 *         tags={"Rms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"start_date","end_date","status"},
 *             @OA\Property(property="start_date", type="string"),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="status", type="string", enum={"pending","active"}),
 *             @OA\Property(property="file_path", type="string", maxLength=255),
 *             @OA\Property(property="generate_schedule", type="boolean"),
 *             @OA\Property(property="property_id", type="integer", minimum=1),
 *             @OA\Property(property="project_id", type="integer", minimum=1),
 *             @OA\Property(property="property_name", type="string", maxLength=150),
 *             @OA\Property(property="project_name", type="string", maxLength=150),
 *             @OA\Property(property="grace_period_months", type="integer", minimum=0, maximum=2),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{rentalId}/expenses",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_rentals_rental_d_expenses_0",
 *         tags={"Rms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_rental_d_expenses_1",
 *         tags={"Rms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"expense_name","amount_type","amount_value","cost_center"},
 *             @OA\Property(property="expense_name", type="string", maxLength=255),
 *             @OA\Property(property="image_path", type="string"),
 *             @OA\Property(property="amount_type", type="string", enum={"percentage","fixed"}),
 *             @OA\Property(property="amount_value", type="number", minimum=0),
 *             @OA\Property(property="cost_center", type="string", enum={"tenant","owner"}),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{rentalId}/expenses/{expenseId}",
 *
 *     @OA\Delete(
 *         operationId="delete_v1_rms_rentals_rental_d_expenses_expense_d_0",
 *         tags={"Rms"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="expenseId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{rentalId}/installments/regenerate",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_rental_d_installments_regenerate_0",
 *         tags={"Rms"},
 *         summary="Regenerate", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rentalId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/rentals/{rental}/payments/{payment}/reverse",
 *
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_rental_payments_payment_reverse_0",
 *         tags={"Rms"},
 *         summary="Reverse Payment", security={{"sanctum":{}}},
 *         @OA\Parameter(name="rental", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="payment", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/rms/sales-stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_rms_sales_stats_0",
 *         tags={"Rms"},
 *         summary="Sales Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/roles",
 *
 *     @OA\Get(
 *         operationId="get_v1_roles_0",
 *         tags={"Roles"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_roles_1",
 *         tags={"Roles"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/roles/{role}",
 *
 *     @OA\Get(
 *         operationId="get_v1_roles_role_0",
 *         tags={"Roles"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_roles_role_1",
 *         tags={"Roles"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_roles_role_2",
 *         tags={"Roles"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="name_ar", type="string", maxLength=255),
 *             @OA\Property(property="name_en", type="string", maxLength=255),
 *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_roles_role_3",
 *         tags={"Roles"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/campaigns",
 *
 *     @OA\Get(
 *         operationId="get_v1_sms_campaigns_0",
 *         tags={"Sms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_sms_campaigns_1",
 *         tags={"Sms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","message"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"draft","scheduled"}),
 *             @OA\Property(property="scheduled_at", type="string"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/campaigns/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_sms_campaigns_id_0",
 *         tags={"Sms"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_sms_campaigns_id_1",
 *         tags={"Sms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"draft","scheduled"}),
 *             @OA\Property(property="scheduled_at", type="string"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_sms_campaigns_id_2",
 *         tags={"Sms"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/campaigns/{id}/pause",
 *
 *     @OA\Post(
 *         operationId="post_v1_sms_campaigns_id_pause_0",
 *         tags={"Sms"},
 *         summary="Pause", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/campaigns/{id}/resume",
 *
 *     @OA\Post(
 *         operationId="post_v1_sms_campaigns_id_resume_0",
 *         tags={"Sms"},
 *         summary="Resume", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"mode"},
 *             @OA\Property(property="mode", type="string", enum={"continue","restart"}),
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="manual_phones", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/campaigns/{id}/send",
 *
 *     @OA\Post(
 *         operationId="post_v1_sms_campaigns_id_send_0",
 *         tags={"Sms"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="manual_phones", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/logs",
 *
 *     @OA\Get(
 *         operationId="get_v1_sms_logs_0",
 *         tags={"Sms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/messages/send",
 *
 *     @OA\Post(
 *         operationId="post_v1_sms_messages_send_0",
 *         tags={"Sms"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"recipient_phone","content"},
 *             @OA\Property(property="recipient_phone", type="string"),
 *             @OA\Property(property="content", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_sms_stats_0",
 *         tags={"Sms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/templates",
 *
 *     @OA\Get(
 *         operationId="get_v1_sms_templates_0",
 *         tags={"Sms"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_sms_templates_1",
 *         tags={"Sms"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","content","category"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="category", type="string", enum={"promotional","transactional","reminder","notification","follow_up"}),
 *             @OA\Property(property="variables", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/templates/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_sms_templates_id_0",
 *         tags={"Sms"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_sms_templates_id_1",
 *         tags={"Sms"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="category", type="string", enum={"promotional","transactional","reminder","notification","follow_up"}),
 *             @OA\Property(property="variables", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_sms_templates_id_2",
 *         tags={"Sms"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/sms/webhooks/delivery",
 *
 *     @OA\Post(
 *         operationId="post_v1_sms_webhooks_delivery_0",
 *         tags={"Sms"},
 *         summary="Delivery",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/components/catalog",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_components_catalog_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/getTenant",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_get_enant_0",
 *         tags={"Tenant Website"},
 *         summary="Store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"websiteName"},
 *             @OA\Property(property="websiteName", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/save-pages",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_save_pages_0",
 *         tags={"Tenant Website"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"tenantId"},
 *             @OA\Property(property="tenantId", type="string"),
 *             @OA\Property(property="pages", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="componentSettings", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="globalComponentsData", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="WebsiteLayout", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="ThemesBackup", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="StaticPages", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="branding", type="object", properties={@OA\Property(property="websiteBranding", type="array", @OA\Items(type="string"))}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/ai-export",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_ai_export_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/ai-export.txt",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_ai_export_txt_0",
 *         tags={"Tenant Website"},
 *         summary="Download Txt",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/forms/contact",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_forms_contact_0",
 *         tags={"Tenant Website"},
 *         summary="Store",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","message"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=50),
 *             @OA\Property(property="message", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/globals",
 *
 *     @OA\Put(
 *         operationId="put_v1_tenant_website_tenant_d_globals_0",
 *         tags={"Tenant Website"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"data"},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/job-applications",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_job_applications_0",
 *         tags={"Tenant Website"},
 *         summary="Store",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"name","phone","email","pdf"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=40),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=2000),
 *             @OA\Property(property="pdf", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/media",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_media_0",
 *         tags={"Tenant Website"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary", maxLength=51200),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/pages",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_pages_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_pages_1",
 *         tags={"Tenant Website"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"pageId","components"},
 *             @OA\Property(property="pageId", type="string"),
 *             @OA\Property(property="components", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/pages/{pageId}",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_pages_page_d_0",
 *         tags={"Tenant Website"},
 *         summary="Show",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="pageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_tenant_website_tenant_d_pages_page_d_1",
 *         tags={"Tenant Website"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="pageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"pageId","components"},
 *             @OA\Property(property="pageId", type="string"),
 *             @OA\Property(property="components", type="object", properties={@OA\Property(property="*.id", type="string"),@OA\Property(property="*.type", type="string"),@OA\Property(property="*.name", type="string"),@OA\Property(property="*.componentName", type="string"),@OA\Property(property="*.data", type="array", @OA\Items(type="string")),@OA\Property(property="*.position", type="integer")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_tenant_website_tenant_d_pages_page_d_2",
 *         tags={"Tenant Website"},
 *         summary="Patch", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="pageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"pageId","components"},
 *             @OA\Property(property="pageId", type="string"),
 *             @OA\Property(property="components", type="object", properties={@OA\Property(property="*.id", type="string"),@OA\Property(property="*.type", type="string"),@OA\Property(property="*.name", type="string"),@OA\Property(property="*.componentName", type="string"),@OA\Property(property="*.data", type="array", @OA\Items(type="string")),@OA\Property(property="*.position", type="integer")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_tenant_website_tenant_d_pages_page_d_3",
 *         tags={"Tenant Website"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="pageId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/pixels",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_pixels_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/posts",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_posts_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/posts/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_posts_slug_0",
 *         tags={"Tenant Website"},
 *         summary="Show",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/projects",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_projects_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/projects/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_projects_slug_0",
 *         tags={"Tenant Website"},
 *         summary="Show",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/properties",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_properties_0",
 *         tags={"Tenant Website"},
 *         summary="Index",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/properties/categories/direct",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_properties_categories_direct_0",
 *         tags={"Tenant Website"},
 *         summary="Properties_categories",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/properties/most-viewed",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_properties_most_viewed_0",
 *         tags={"Tenant Website"},
 *         summary="Most Viewed",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/properties/{slug}",
 *
 *     @OA\Get(
 *         operationId="get_v1_tenant_website_tenant_d_properties_slug_0",
 *         tags={"Tenant Website"},
 *         summary="Show",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/publish",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_publish_0",
 *         tags={"Tenant Website"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/reservations",
 *
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_reservations_0",
 *         tags={"Tenant Website"},
 *         summary="Store",
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"propertySlug","customerName","customerPhone"},
 *             @OA\Property(property="propertySlug", type="string", maxLength=200),
 *             @OA\Property(property="customerName", type="string", maxLength=100),
 *             @OA\Property(property="customerPhone", type="string", maxLength=40),
 *             @OA\Property(property="desiredDate", type="string"),
 *             @OA\Property(property="message", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/tenant-website/{tenantId}/settings",
 *
 *     @OA\Put(
 *         operationId="put_v1_tenant_website_tenant_d_settings_0",
 *         tags={"Tenant Website"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="tenantId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"settings"},
 *             @OA\Property(property="settings", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/user/owner-rentals",
 *
 *     @OA\Get(
 *         operationId="get_v1_user_owner_rentals_0",
 *         tags={"User"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_user_owner_rentals_1",
 *         tags={"User"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","email","phone","password"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone", type="string", maxLength=20),
 *             @OA\Property(property="password", type="string"),
 *             @OA\Property(property="id_number", type="string", maxLength=50),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="city", type="string", maxLength=100),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/user/owner-rentals/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_user_owner_rentals_id_0",
 *         tags={"User"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_user_owner_rentals_id_1",
 *         tags={"User"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","email","phone"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone", type="string", maxLength=20),
 *             @OA\Property(property="password", type="string"),
 *             @OA\Property(property="id_number", type="string", maxLength=50),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="city", type="string", maxLength=100),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_user_owner_rentals_id_2",
 *         tags={"User"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/user/owner-rentals/{id}/properties",
 *
 *     @OA\Post(
 *         operationId="post_v1_user_owner_rentals_id_properties_0",
 *         tags={"User"},
 *         summary="Assign Properties", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"property_ids"},
 *             @OA\Property(property="property_ids", type="array", minLength=1, @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v1_user_owner_rentals_id_properties_1",
 *         tags={"User"},
 *         summary="Get Assigned Properties", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/user/owner-rentals/{id}/properties/{propertyId}",
 *
 *     @OA\Delete(
 *         operationId="delete_v1_user_owner_rentals_id_properties_property_d_0",
 *         tags={"User"},
 *         summary="Remove Property", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="propertyId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/user/properties",
 *
 *     @OA\Get(
 *         operationId="get_v1_user_properties_0",
 *         tags={"User"},
 *         summary="Get My Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp-addons/payment/cancel/{addon_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_addons_payment_cancel_addon_id_gateway_0",
 *         tags={"Whatsapp Addons"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_addons_payment_cancel_addon_id_gateway_1",
 *         tags={"Whatsapp Addons"},
 *         summary="Payment Cancel",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp-addons/payment/success/{addon_id}/{gateway}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_addons_payment_success_addon_id_gateway_0",
 *         tags={"Whatsapp Addons"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_addons_payment_success_addon_id_gateway_1",
 *         tags={"Whatsapp Addons"},
 *         summary="Payment Success",
 *         @OA\Parameter(name="addon_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="gateway", in="path", required=true, @OA\Schema(type="string")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="paymentId", type="string"),
 *             @OA\Property(property="trandata", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/ai/config/{numberId}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_ai_config_number_d_0",
 *         tags={"Whatsapp"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="numberId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_ai_config_number_d_1",
 *         tags={"Whatsapp"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="numberId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="enabled", type="boolean"),
 *             @OA\Property(property="business_hours_only", type="boolean"),
 *             @OA\Property(property="business_hours_start", type="string"),
 *             @OA\Property(property="business_hours_end", type="string"),
 *             @OA\Property(property="timezone", type="string", maxLength=50),
 *             @OA\Property(property="scenarios", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="tone", type="string", maxLength=20),
 *             @OA\Property(property="language", type="string", maxLength=10),
 *             @OA\Property(property="custom_instructions", type="string"),
 *             @OA\Property(property="fallback_to_human", type="boolean"),
 *             @OA\Property(property="fallback_delay", type="integer", minimum=0),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/ai/config/{numberId}/toggle",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_whatsapp_ai_config_number_d_toggle_0",
 *         tags={"Whatsapp"},
 *         summary="Toggle", security={{"sanctum":{}}},
 *         @OA\Parameter(name="numberId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/ai/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_ai_stats_0",
 *         tags={"Whatsapp"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/automation/rules",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_automation_rules_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_automation_rules_1",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","trigger"},
 *             @OA\Property(property="wa_number_id", type="integer"),
 *             @OA\Property(property="name", type="string", maxLength=100),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="trigger", type="string", enum={"new_inquiry","no_response_24h","no_response_48h","no_response_72h","follow_up","appointment_reminder","property_match","price_change"}),
 *             @OA\Property(property="delay_minutes", type="integer", minimum=0),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/automation/rules/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_automation_rules_id_0",
 *         tags={"Whatsapp"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_automation_rules_id_1",
 *         tags={"Whatsapp"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="wa_number_id", type="integer"),
 *             @OA\Property(property="name", type="string", maxLength=100),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="trigger", type="string", enum={"new_inquiry","no_response_24h","no_response_48h","no_response_72h","follow_up","appointment_reminder","property_match","price_change"}),
 *             @OA\Property(property="delay_minutes", type="integer", minimum=0),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_whatsapp_automation_rules_id_2",
 *         tags={"Whatsapp"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/automation/rules/{id}/toggle",
 *
 *     @OA\Patch(
 *         operationId="patch_v1_whatsapp_automation_rules_id_toggle_0",
 *         tags={"Whatsapp"},
 *         summary="Toggle", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/automation/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_automation_stats_0",
 *         tags={"Whatsapp"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/campaigns",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_campaigns_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_campaigns_1",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"wa_number_id","name"},
 *             @OA\Property(property="wa_number_id", type="integer"),
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"draft","scheduled"}),
 *             @OA\Property(property="scheduled_at", type="string"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/campaigns/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_campaigns_id_0",
 *         tags={"Whatsapp"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_whatsapp_campaigns_id_1",
 *         tags={"Whatsapp"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="wa_number_id", type="integer"),
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="status", type="string", enum={"draft","scheduled"}),
 *             @OA\Property(property="scheduled_at", type="string"),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_whatsapp_campaigns_id_2",
 *         tags={"Whatsapp"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/campaigns/{id}/pause",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_campaigns_id_pause_0",
 *         tags={"Whatsapp"},
 *         summary="Pause", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/campaigns/{id}/resume",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_campaigns_id_resume_0",
 *         tags={"Whatsapp"},
 *         summary="Resume", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"mode"},
 *             @OA\Property(property="mode", type="string", enum={"continue","restart"}),
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="manual_phones", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/campaigns/{id}/send",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_campaigns_id_send_0",
 *         tags={"Whatsapp"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="manual_phones", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/conversations",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_conversations_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_1",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"external_party_identifier"},
 *             @OA\Property(property="external_party_identifier", type="string", maxLength=191),
 *             @OA\Property(property="wa_number_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/conversations/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_conversations_id_0",
 *         tags={"Whatsapp"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_whatsapp_conversations_id_1",
 *         tags={"Whatsapp"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="status", type="string", enum={"active","pending","resolved"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/conversations/{id}/messages",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_conversations_id_messages_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_id_messages_1",
 *         tags={"Whatsapp"},
 *         summary="Send", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"wa_number_id","content"},
 *             @OA\Property(property="wa_number_id", type="integer"),
 *             @OA\Property(property="content", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/conversations/{id}/messages/template",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_id_messages_template_0",
 *         tags={"Whatsapp"},
 *         summary="Send Template", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"wa_number_id","template_id"},
 *             @OA\Property(property="wa_number_id", type="integer"),
 *             @OA\Property(property="template_id", type="integer"),
 *             @OA\Property(property="variables", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/conversations/{id}/read",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_id_read_0",
 *         tags={"Whatsapp"},
 *         summary="Read", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/conversations/{id}/star",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_id_star_0",
 *         tags={"Whatsapp"},
 *         summary="Star", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/numbers",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_numbers_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_numbers_1",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"provider","phone_number"},
 *             @OA\Property(property="provider", type="string", enum={"meta","evolution"}),
 *             @OA\Property(property="phone_number", type="string", maxLength=20),
 *             @OA\Property(property="phone_number_id", type="string", maxLength=191),
 *             @OA\Property(property="provider_account_id", type="string", maxLength=191),
 *             @OA\Property(property="name", type="string", maxLength=100),
 *             @OA\Property(property="status", type="string", enum={"active","inactive","pending"}),
 *             @OA\Property(property="quota_limit", type="integer", minimum=0),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/numbers/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_numbers_id_0",
 *         tags={"Whatsapp"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_numbers_id_1",
 *         tags={"Whatsapp"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=100),
 *             @OA\Property(property="status", type="string", enum={"active","inactive","pending"}),
 *             @OA\Property(property="quota_limit", type="integer", minimum=0),
 *             @OA\Property(property="phone_number_id", type="string", maxLength=191),
 *             @OA\Property(property="provider_account_id", type="string", maxLength=191),
 *             @OA\Property(property="meta", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/stats",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_stats_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/templates",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_templates_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_templates_1",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","content"},
 *             @OA\Property(property="name", type="string", maxLength=100),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="category", type="string", maxLength=50),
 *             @OA\Property(property="variables", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="language", type="string", maxLength=10),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/templates/{id}",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_templates_id_0",
 *         tags={"Whatsapp"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_templates_id_1",
 *         tags={"Whatsapp"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=100),
 *             @OA\Property(property="content", type="string"),
 *             @OA\Property(property="category", type="string", maxLength=50),
 *             @OA\Property(property="variables", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="is_active", type="boolean"),
 *             @OA\Property(property="language", type="string", maxLength=10),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_whatsapp_templates_id_2",
 *         tags={"Whatsapp"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/webhook/incoming",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_webhook_incoming_0",
 *         tags={"Whatsapp"},
 *         summary="Incoming",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/webhook/status",
 *
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_webhook_status_0",
 *         tags={"Whatsapp"},
 *         summary="Status",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v1/whatsapp/webhook/verify",
 *
 *     @OA\Get(
 *         operationId="get_v1_whatsapp_webhook_verify_0",
 *         tags={"Whatsapp"},
 *         summary="Verify",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_webhook_verify_1",
 *         tags={"Whatsapp"},
 *         summary="Verify Post",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/analytics",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_analytics_0",
 *         tags={"Customers Hub"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="action", type="string", enum={"metrics","distributions","time_series","activity","pipeline_health"}),
 *             @OA\Property(property="timeRange", type="object", properties={@OA\Property(property="timeRange", type="string", enum={"today","yesterday","last7days","last30days","thisMonth","lastMonth","thisQuarter","lastQuarter","thisYear","lastYear","custom"}),@OA\Property(property="range", type="string", enum={"today","yesterday","last7days","last30days","thisMonth","lastMonth","thisQuarter","lastQuarter","thisYear","lastYear","custom"}),@OA\Property(property="customStartDate", type="string"),@OA\Property(property="customEndDate", type="string")}),
 *             @OA\Property(property="interval", type="string", enum={"day","week","month"}),
 *             @OA\Property(property="filters", type="object", properties={@OA\Property(property="priority", type="array", @OA\Items(type="string")),@OA\Property(property="source", type="array", @OA\Items(type="string"))}),
 *             @OA\Property(property="filters.priority", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="filters.source", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/analytics/performance",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_analytics_performance_0",
 *         tags={"Customers Hub"},
 *         summary="Performance", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="timeRange", type="object", properties={@OA\Property(property="timeRange", type="string", enum={"today","yesterday","last7days","last30days","thisMonth","lastMonth","thisQuarter","lastQuarter","thisYear","lastYear","custom"}),@OA\Property(property="customStartDate", type="string"),@OA\Property(property="customEndDate", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/analytics/sources",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_analytics_sources_0",
 *         tags={"Customers Hub"},
 *         summary="Sources", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="timeRange", type="object", properties={@OA\Property(property="timeRange", type="string", enum={"today","yesterday","last7days","last30days","thisMonth","lastMonth","thisQuarter","lastQuarter","thisYear","lastYear","custom"}),@OA\Property(property="customStartDate", type="string"),@OA\Property(property="customEndDate", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/analytics/trends",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_analytics_trends_0",
 *         tags={"Customers Hub"},
 *         summary="Trends", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="timeRange", type="object", properties={@OA\Property(property="timeRange", type="string", enum={"today","yesterday","last7days","last30days","thisMonth","lastMonth","thisQuarter","lastQuarter","thisYear","lastYear","custom"}),@OA\Property(property="customStartDate", type="string"),@OA\Property(property="customEndDate", type="string")}),
 *             @OA\Property(property="metrics", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/assignment/assign",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_assignment_assign_0",
 *         tags={"Customers Hub"},
 *         summary="Assign", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"employeeId"},
 *             @OA\Property(property="requestIds", type="array", minLength=1, @OA\Items(type="string")),
 *             @OA\Property(property="customerIds", type="array", minLength=1, @OA\Items(type="string")),
 *             @OA\Property(property="employeeId", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/assignment/auto-assign",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_assignment_auto_assign_0",
 *         tags={"Customers Hub"},
 *         summary="Auto Assign", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"employeeRules"},
 *             @OA\Property(property="employeeRules", type="object", properties={@OA\Property(property="*.employeeId", type="string"),@OA\Property(property="*.isActive", type="boolean"),@OA\Property(property="*.rules", type="array", @OA\Items(type="string")),@OA\Property(property="*.rules.*.id", type="string"),@OA\Property(property="*.rules.*.field", type="string", enum={"budgetMin","budgetMax","propertyType","city","source"}),@OA\Property(property="*.rules.*.operator", type="string", enum={"equals","greaterThan","lessThan","contains"}),@OA\Property(property="*.rules.*.value", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/assignment/employees",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_assignment_employees_0",
 *         tags={"Customers Hub"},
 *         summary="Employees", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/assignment/rules",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_assignment_rules_0",
 *         tags={"Customers Hub"},
 *         summary="Save Rules", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"employeeRules"},
 *             @OA\Property(property="employeeRules", type="object", properties={@OA\Property(property="*.employeeId", type="string"),@OA\Property(property="*.isActive", type="boolean"),@OA\Property(property="*.rules", type="array", @OA\Items(type="string")),@OA\Property(property="*.rules.*.id", type="string"),@OA\Property(property="*.rules.*.field", type="string", enum={"budgetMin","budgetMax","propertyType","city","source"}),@OA\Property(property="*.rules.*.operator", type="string", enum={"equals","greaterThan","lessThan","contains"}),@OA\Property(property="*.rules.*.value", type="string")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_assignment_rules_1",
 *         tags={"Customers Hub"},
 *         summary="Get Rules", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/assignment/unassigned-count",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_assignment_unassigned_count_0",
 *         tags={"Customers Hub"},
 *         summary="Unassigned Count", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/customers/{customerId}",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_customers_customer_d_0",
 *         tags={"Customers Hub"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v2_customers_hub_customers_customer_d_1",
 *         tags={"Customers Hub"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="phone_number", type="string", maxLength=20),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="customers_hub_stage_id", type="integer", maximum=50),
 *             @OA\Property(property="priority_id", type="integer"),
 *             @OA\Property(property="type_id", type="integer"),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *             @OA\Property(property="source", type="string", maxLength=50),
 *             @OA\Property(property="responsible_employee_id", type="integer"),
 *             @OA\Property(property="note", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/customers/{customerId}/preferences",
 *
 *     @OA\Put(
 *         operationId="put_v2_customers_hub_customers_customer_d_preferences_0",
 *         tags={"Customers Hub"},
 *         summary="Update Preferences", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="propertyType", type="string", maxLength=50),
 *             @OA\Property(property="budget", type="number"),
 *             @OA\Property(property="bedrooms", type="integer"),
 *             @OA\Property(property="bathrooms", type="integer"),
 *             @OA\Property(property="city", type="string", maxLength=100),
 *             @OA\Property(property="district", type="string", maxLength=100),
 *             @OA\Property(property="message", type="string", maxLength=5000),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/customers/{customerId}/properties",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_customers_customer_d_properties_0",
 *         tags={"Customers Hub"},
 *         summary="Add Property", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"propertyId"},
 *             @OA\Property(property="propertyId", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_customers_customer_d_properties_1",
 *         tags={"Customers Hub"},
 *         summary="List Properties", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/customers/{customerId}/properties/{propertyId}",
 *
 *     @OA\Delete(
 *         operationId="delete_v2_customers_hub_customers_customer_d_properties_property_d_0",
 *         tags={"Customers Hub"},
 *         summary="Remove Property", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="propertyId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/customers/{customerId}/tasks",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_customers_customer_d_tasks_0",
 *         tags={"Customers Hub"},
 *         summary="Add Task", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"type","datetime"},
 *             @OA\Property(property="type", type="string", enum={"contact","office_visit","property_viewing"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="priority", type="integer", minimum=0, maximum=3),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/customers/{customerId}/tasks/{taskId}",
 *
 *     @OA\Put(
 *         operationId="put_v2_customers_hub_customers_customer_d_tasks_task_d_0",
 *         tags={"Customers Hub"},
 *         summary="Update Task", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="taskId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="status", type="string", enum={"pending","completed","cancelled"}),
 *             @OA\Property(property="priority", type="integer", minimum=0, maximum=3),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v2_customers_hub_customers_customer_d_tasks_task_d_1",
 *         tags={"Customers Hub"},
 *         summary="Delete Task", security={{"sanctum":{}}},
 *         @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Parameter(name="taskId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/ignored-customers",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_ignored_customers_0",
 *         tags={"Customers Hub"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_ignored_customers_1",
 *         tags={"Customers Hub"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="phone", type="string", maxLength=30),
 *             @OA\Property(property="customer_id", type="integer", minimum=1),
 *             @OA\Property(property="reason", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/ignored-customers/{id}",
 *
 *     @OA\Delete(
 *         operationId="delete_v2_customers_hub_ignored_customers_id_0",
 *         tags={"Customers Hub"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/list",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_list_0",
 *         tags={"Customers Hub"},
 *         summary="List", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="action", type="string", enum={"list","stats"}),
 *             @OA\Property(property="includeStats", type="boolean"),
 *             @OA\Property(property="filters", type="object", properties={@OA\Property(property="search", type="string", maxLength=255),@OA\Property(property="stage", type="array", @OA\Items(type="string")),@OA\Property(property="priority", type="array", @OA\Items(type="string")),@OA\Property(property="type", type="array", @OA\Items(type="string")),@OA\Property(property="source", type="array", @OA\Items(type="string")),@OA\Property(property="assignedEmployeeId", type="integer"),@OA\Property(property="city", type="integer"),@OA\Property(property="district", type="integer"),@OA\Property(property="createdFrom", type="string"),@OA\Property(property="createdTo", type="string"),@OA\Property(property="sort_by", type="string", enum={"created_at","updated_at","name"}),@OA\Property(property="sort_dir", type="string", enum={"asc","desc"})}),
 *             @OA\Property(property="pagination", type="object", properties={@OA\Property(property="page", type="integer", minimum=1),@OA\Property(property="limit", type="integer", minimum=1, maximum=100)}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/list/bulk",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_list_bulk_0",
 *         tags={"Customers Hub"},
 *         summary="Bulk", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/list/filter-options",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_list_filter_options_0",
 *         tags={"Customers Hub"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/list/stats",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_list_stats_0",
 *         tags={"Customers Hub"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/pipeline",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_pipeline_0",
 *         tags={"Customers Hub"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="action", type="string", enum={"board","analytics","get_board"}),
 *             @OA\Property(property="includeAnalytics", type="boolean"),
 *             @OA\Property(property="filters", type="object", properties={@OA\Property(property="status", type="array", @OA\Items(type="string")),@OA\Property(property="status_id", type="array", @OA\Items(type="string")),@OA\Property(property="stage_id", type="array", @OA\Items(type="string")),@OA\Property(property="property_type", type="array", @OA\Items(type="string")),@OA\Property(property="city_id", type="integer"),@OA\Property(property="district_id", type="integer"),@OA\Property(property="districts_id", type="integer"),@OA\Property(property="budget_from", type="number"),@OA\Property(property="budget_to", type="number"),@OA\Property(property="assignedEmployeeId", type="integer"),@OA\Property(property="search", type="string", maxLength=255)}),
 *             @OA\Property(property="filters.status", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="filters.property_type", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/pipeline/bulk-move",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_pipeline_bulk_move_0",
 *         tags={"Customers Hub"},
 *         summary="Bulk Move", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"newStageId"},
 *             @OA\Property(property="requestIds", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="customerIds", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="newStageId", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/pipeline/move",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_pipeline_move_0",
 *         tags={"Customers Hub"},
 *         summary="Move", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"newStageId"},
 *             @OA\Property(property="requestId", type="integer"),
 *             @OA\Property(property="customerId", type="integer"),
 *             @OA\Property(property="inquiryId", type="integer"),
 *             @OA\Property(property="newStageId", type="string"),
 *             @OA\Property(property="notes", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/bulk",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_bulk_0",
 *         tags={"Customers Hub"},
 *         summary="Bulk", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"action","actionIds","data"},
 *             @OA\Property(property="action", type="string", enum={"complete","dismiss","snooze","assign","change_priority"}),
 *             @OA\Property(property="actionIds", type="array", minLength=1, maxLength=1000, @OA\Items(type="string")),
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/bulk-complete",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_bulk_complete_0",
 *         tags={"Customers Hub"},
 *         summary="Bulk Complete", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"actionIds"},
 *             @OA\Property(property="actionIds", type="array", minLength=1, maxLength=100, @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/bulk-dismiss",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_bulk_dismiss_0",
 *         tags={"Customers Hub"},
 *         summary="Bulk Dismiss", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"actionIds","reason"},
 *             @OA\Property(property="actionIds", type="array", minLength=1, maxLength=100, @OA\Items(type="string")),
 *             @OA\Property(property="reason", type="string", minLength=3, maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/filter-options",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_requests_filter_options_0",
 *         tags={"Customers Hub"},
 *         summary="Filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/list",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_list_0",
 *         tags={"Customers Hub"},
 *         summary="List", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="tab", type="string", enum={"inbox","followups","all","completed"}),
 *             @OA\Property(property="types", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="statuses", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="excludeStatuses", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="sources", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="priorities", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="assignees", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="due_date_bucket", type="string", enum={"overdue","today","week","no_date"}),
 *             @OA\Property(property="property_categories", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="property_types", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="cities", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="districts", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="budget_min", type="number", minimum=0),
 *             @OA\Property(property="budget_max", type="number", minimum=0),
 *             @OA\Property(property="date_from", type="string"),
 *             @OA\Property(property="date_to", type="string"),
 *             @OA\Property(property="search", type="string", maxLength=255),
 *             @OA\Property(property="sort_by", type="string", enum={"updatedAt","createdAt","dueDate","priority","customerName"}),
 *             @OA\Property(property="sort_dir", type="string", enum={"asc","desc"}),
 *             @OA\Property(property="limit", type="integer", minimum=1, maximum=100),
 *             @OA\Property(property="offset", type="integer", minimum=0),
 *             @OA\Property(property="objectTypes", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="stages", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="excludeStages", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="appointment_types", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_requests_list_1",
 *         tags={"Customers Hub"},
 *         summary="List", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/mark-viewed",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_mark_viewed_0",
 *         tags={"Customers Hub"},
 *         summary="Mark List Viewed", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_requests_request_d_0",
 *         tags={"Customers Hub"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v2_customers_hub_requests_request_d_1",
 *         tags={"Customers Hub"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="due_date", type="string"),
 *             @OA\Property(property="priority", type="string", enum={"low","medium","high"}),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="duration", type="integer", minimum=0),
 *             @OA\Property(property="status_id", type="integer"),
 *             @OA\Property(property="stage_id", type="string", maxLength=50),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/appointments",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_appointments_0",
 *         tags={"Customers Hub"},
 *         summary="Create Appointment For Property Request", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"type"},
 *             @OA\Property(property="type", type="string", enum={"site_visit","office_meeting","phone_call","video_call","contract_signing","other"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="duration", type="integer", minimum=1),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="string", enum={"low","medium","high","urgent"}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/complete",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_complete_0",
 *         tags={"Customers Hub"},
 *         summary="Complete", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/complete-data",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_complete_data_0",
 *         tags={"Customers Hub"},
 *         summary="Complete Data", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="property_type", type="string"),
 *             @OA\Property(property="purpose", type="string", enum={"rent","sale","buy","invest"}),
 *             @OA\Property(property="city", type="string", maxLength=255),
 *             @OA\Property(property="district", type="string", maxLength=255),
 *             @OA\Property(property="region", type="string", maxLength=255),
 *             @OA\Property(property="city_id", type="integer", minimum=1),
 *             @OA\Property(property="category_id", type="integer", minimum=1),
 *             @OA\Property(property="budget_from", type="number", minimum=0),
 *             @OA\Property(property="budget_to", type="number", minimum=0),
 *             @OA\Property(property="currency", type="string", maxLength=10),
 *             @OA\Property(property="bedrooms", type="integer", minimum=0),
 *             @OA\Property(property="bathrooms", type="integer", minimum=0),
 *             @OA\Property(property="area_from", type="integer", minimum=0),
 *             @OA\Property(property="area_to", type="integer", minimum=0),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/dismiss",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_dismiss_0",
 *         tags={"Customers Hub"},
 *         summary="Dismiss", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/ignore",
 *
 *     @OA\Patch(
 *         operationId="patch_v2_customers_hub_requests_request_d_ignore_0",
 *         tags={"Customers Hub"},
 *         summary="Ignore", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="is_ignored", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/matches",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_requests_request_d_matches_0",
 *         tags={"Customers Hub"},
 *         summary="Matches", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/notes",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_notes_0",
 *         tags={"Customers Hub"},
 *         summary="Add Note", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"note"},
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="addedBy", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/read",
 *
 *     @OA\Patch(
 *         operationId="patch_v2_customers_hub_requests_request_d_read_0",
 *         tags={"Customers Hub"},
 *         summary="Mark Read", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/rematch",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_rematch_0",
 *         tags={"Customers Hub"},
 *         summary="Rematch", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/reminders",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_reminders_0",
 *         tags={"Customers Hub"},
 *         summary="Create Reminder For Property Request", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","datetime","priority","type"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="priority", type="string", enum={"low","medium","high","urgent"}),
 *             @OA\Property(property="type", type="string", enum={"follow_up","payment_due","document_required","other"}),
 *             @OA\Property(property="notes", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/snooze",
 *
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_requests_request_d_snooze_0",
 *         tags={"Customers Hub"},
 *         summary="Snooze", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"snoozedUntil"},
 *             @OA\Property(property="snoozedUntil", type="string"),
 *             @OA\Property(property="reason", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/stats",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_requests_request_d_stats_0",
 *         tags={"Customers Hub"},
 *         summary="Action Stats", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/requests/{requestId}/unread",
 *
 *     @OA\Patch(
 *         operationId="patch_v2_customers_hub_requests_request_d_unread_0",
 *         tags={"Customers Hub"},
 *         summary="Mark Unread", security={{"sanctum":{}}},
 *         @OA\Parameter(name="requestId", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/stages",
 *
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_stages_0",
 *         tags={"Customers Hub"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_stages_1",
 *         tags={"Customers Hub"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"stage_name_ar","stage_name_en","color","order"},
 *             @OA\Property(property="stage_name_ar", type="string", maxLength=255),
 *             @OA\Property(property="stage_name_en", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/v2/customers-hub/stages/{stage_id}",
 *
 *     @OA\Put(
 *         operationId="put_v2_customers_hub_stages_stage_id_0",
 *         tags={"Customers Hub"},
 *         summary="Update", security={{"sanctum":{}}},
 *         @OA\Parameter(name="stage_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="stage_name_ar", type="string", maxLength=255),
 *             @OA\Property(property="stage_name_en", type="string", maxLength=255),
 *             @OA\Property(property="color", type="string"),
 *             @OA\Property(property="order", type="integer", minimum=1),
 *             @OA\Property(property="description", type="string"),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v2_customers_hub_stages_stage_id_1",
 *         tags={"Customers Hub"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="stage_id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/abort-chunked",
 *
 *     @OA\Post(
 *         operationId="post_video_abort_chunked_0",
 *         tags={"Video"},
 *         summary="Abort Chunked Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"upload_id","filename"},
 *             @OA\Property(property="upload_id", type="string"),
 *             @OA\Property(property="filename", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/complete-chunked",
 *
 *     @OA\Post(
 *         operationId="post_video_complete_chunked_0",
 *         tags={"Video"},
 *         summary="Complete Chunked Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"upload_id","filename","parts"},
 *             @OA\Property(property="upload_id", type="string"),
 *             @OA\Property(property="filename", type="string"),
 *             @OA\Property(property="parts", type="object", properties={@OA\Property(property="*.etag", type="string"),@OA\Property(property="*.part_number", type="integer")}),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/delete",
 *
 *     @OA\Delete(
 *         operationId="delete_video_delete_0",
 *         tags={"Video"},
 *         summary="Delete Video", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/initiate-chunked",
 *
 *     @OA\Post(
 *         operationId="post_video_initiate_chunked_0",
 *         tags={"Video"},
 *         summary="Initiate Chunked Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"filename","total_size"},
 *             @OA\Property(property="filename", type="string"),
 *             @OA\Property(property="content_type", type="string"),
 *             @OA\Property(property="total_size", type="integer", minimum=1),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/signed-url",
 *
 *     @OA\Post(
 *         operationId="post_video_signed_url_0",
 *         tags={"Video"},
 *         summary="Get Signed Upload Url", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"filename"},
 *             @OA\Property(property="filename", type="string"),
 *             @OA\Property(property="expires", type="integer", minimum=300, maximum=3600),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/upload",
 *
 *     @OA\Post(
 *         operationId="post_video_upload_0",
 *         tags={"Video"},
 *         summary="Upload Video", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"video"},
 *             @OA\Property(property="video", type="string", format="binary"),
 *             @OA\Property(property="context", type="string", enum={"property","project"}),
 *         ))),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/video/upload-chunk",
 *
 *     @OA\Post(
 *         operationId="post_video_upload_chunk_0",
 *         tags={"Video"},
 *         summary="Upload Chunk", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"chunk_data","upload_id","part_number","filename"},
 *             @OA\Property(property="chunk_data", type="string"),
 *             @OA\Property(property="upload_id", type="string"),
 *             @OA\Property(property="part_number", type="integer", minimum=1),
 *             @OA\Property(property="filename", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_0",
 *         tags={"Whatsapp"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp-ai/conversations",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_ai_conversations_0",
 *         tags={"Whatsapp Ai"},
 *         summary="Index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp-ai/conversations/stats",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_ai_conversations_stats_0",
 *         tags={"Whatsapp Ai"},
 *         summary="Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp-ai/conversations/{id}",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_ai_conversations_id_0",
 *         tags={"Whatsapp Ai"},
 *         summary="Show", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_whatsapp_ai_conversations_id_1",
 *         tags={"Whatsapp Ai"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp-ai/conversations/{id}/archive",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_ai_conversations_id_archive_0",
 *         tags={"Whatsapp Ai"},
 *         summary="Archive", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp-ai/webhook",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_ai_webhook_0",
 *         tags={"Whatsapp Ai"},
 *         summary="Handle",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_whatsapp_ai_webhook_1",
 *         tags={"Whatsapp Ai"},
 *         summary="Handle",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/addons",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_addons_0",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/addons/plans",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_addons_plans_0",
 *         tags={"Whatsapp"},
 *         summary="Plans", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/evolution-webhook",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_evolution_webhook_0",
 *         tags={"Whatsapp"},
 *         summary="Handle Evolution Webhook",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/link",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_link_0",
 *         tags={"Whatsapp"},
 *         summary="Store", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/meta/callback",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_meta_callback_0",
 *         tags={"Whatsapp"},
 *         summary="Callback",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/meta/redirect",
 *
 *     @OA\Get(
 *         operationId="get_whatsapp_meta_redirect_0",
 *         tags={"Whatsapp"},
 *         summary="Redirect", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/webhook",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_webhook_0",
 *         tags={"Whatsapp"},
 *         summary="Handle Whatsapp Webhook",
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/{id}",
 *
 *     @OA\Delete(
 *         operationId="delete_whatsapp_id_0",
 *         tags={"Whatsapp"},
 *         summary="Destroy", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/{id}/employee",
 *
 *     @OA\Put(
 *         operationId="put_whatsapp_id_employee_0",
 *         tags={"Whatsapp"},
 *         summary="Update Employee", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_whatsapp_id_employee_1",
 *         tags={"Whatsapp"},
 *         summary="Update Employee", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/{id}/link",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_id_link_0",
 *         tags={"Whatsapp"},
 *         summary="Link", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/whatsapp/{id}/unlink",
 *
 *     @OA\Post(
 *         operationId="post_whatsapp_id_unlink_0",
 *         tags={"Whatsapp"},
 *         summary="Unlink", security={{"sanctum":{}}},
 *         @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *         @OA\Response(response=200, description="OK", @OA\JsonContent(type="object", @OA\Property(property="status", type="string", example="success"), @OA\Property(property="data", type="object"), @OA\Property(property="message", type="string", nullable=true))),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 */
class GeneratedApiPathsDoc
{
    // Generated for L5-Swagger scan.
}
