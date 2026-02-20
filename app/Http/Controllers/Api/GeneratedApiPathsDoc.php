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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="register", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"fullname","bank_name","bank_account_number","iban"},
 *             @OA\Property(property="fullname", type="string", maxLength=255),
 *             @OA\Property(property="bank_name", type="string", maxLength=255),
 *             @OA\Property(property="bank_account_number", type="string", maxLength=30),
 *             @OA\Property(property="iban", type="string", maxLength=34),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="live Test", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Page Locations", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Realtime", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="search Analytics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Tenants List", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Today", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="install", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="handle Callback",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Payment History", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="uninstall", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="whatsapp", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="install Whatsapp", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="uninstall Whatsapp", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="verify Payment", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Purchase Url", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="forgot Password",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="callback",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="redirect",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="verify Reset Code",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="categories", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_blogs_1",
 *         tags={"Blogs"},
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_blogs_id_1",
 *         tags={"Blogs"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_blogs_id_2",
 *         tags={"Blogs"},
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_buildings_1",
 *         tags={"Buildings"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Deed Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Building Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_buildings_id_1",
 *         tags={"Buildings"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_buildings_id_2",
 *         tags={"Buildings"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_categories_1",
 *         tags={"Categories"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_categories_slug_1",
 *         tags={"Categories"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_categories_slug_2",
 *         tags={"Categories"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="posts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="chat", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_content_about_1",
 *         tags={"Content"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_content_banner_1",
 *         tags={"Content"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_customer_dropdown_1",
 *         tags={"Content"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle Visibility", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_footer_1",
 *         tags={"Content"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_general_1",
 *         tags={"Content"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_content_menu_1",
 *         tags={"Content"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_contracts_1",
 *         tags={"Contracts"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get By Customer", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get By Rental", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="statistics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_contracts_id_1",
 *         tags={"Contracts"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_contracts_id_2",
 *         tags={"Contracts"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_customer_appointments_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_customer_appointments_customer_appointment_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_customer_appointments_customer_appointment_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_customer_appointments_customer_appointment_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_customer_reminders_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"datetime"},
 *             @OA\Property(property="customer_id", type="integer"),
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="reminder_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_customer_reminders_customer_reminder_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="customer_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_customer_reminders_customer_reminder_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="priority", type="integer", enum={"1","2","3"}),
 *             @OA\Property(property="datetime", type="string"),
 *             @OA\Property(property="customer_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_customer_reminders_customer_reminder_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="export", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Import", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="download Template", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="search Customers", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Customer Priority", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Customer Procedure", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Customer Stage", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Customer Type", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_priorities_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reorder Priorities", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="move Priority", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_priorities_priority_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_priorities_priority_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_priorities_priority_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_procedures_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reorder Procedures", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="move Procedure", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_procedures_procedure_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_procedures_procedure_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_procedures_procedure_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_property_requests_settings_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"auto_create_customer"},
 *             @OA\Property(property="auto_create_customer", type="boolean"),
 *             @OA\Property(property="default_stage_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_reminder_types_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_reminder_types_reminder_type_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_reminder_types_reminder_type_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_reminder_types_reminder_type_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_reminders_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_reminders_reminder_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_reminders_reminder_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_reminders_reminder_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_stages_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reorder Stages", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="move Stage", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_stages_stage_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_stages_stage_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_stages_stage_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_crm_types_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reorder Types", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="move Types", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_crm_types_type_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_crm_types_type_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_crm_types_type_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_customers_1",
 *         tags={"Customers"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","phone_number","type_id"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone_number", type="string", maxLength=20),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="type_id", type="integer"),
 *             @OA\Property(property="responsible_employee_id", type="integer"),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="procedure_id", type="integer"),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="interested_category_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="interested_property_ids", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="all", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Import", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="download Template",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="export", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="search", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_customers_id_1",
 *         tags={"Customers"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="phone_number", type="string", maxLength=20),
 *             @OA\Property(property="city_id", type="integer"),
 *             @OA\Property(property="district_id", type="integer"),
 *             @OA\Property(property="note", type="string"),
 *             @OA\Property(property="type_id", type="integer"),
 *             @OA\Property(property="stage_id", type="integer"),
 *             @OA\Property(property="responsible_employee_id", type="integer"),
 *             @OA\Property(property="procedure_id", type="integer"),
 *             @OA\Property(property="password", type="string", minLength=6),
 *             @OA\Property(property="interested_category_ids", type="array", @OA\Items(type="integer")),
 *             @OA\Property(property="interested_property_ids", type="array", @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_customers_id_2",
 *         tags={"Customers"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show With Inquiries", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="debug G A Views", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="devices", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="diagnostic G A Test", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="ga Full Diagnostics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="most Visited Pages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Recent Activity", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="setup Progress", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="summary", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="traffic Sources", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="visitors", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 *
 * @OA\PathItem(
 *
 *     path="/debug-oss",
 *
 *     @OA\Get(
 *         operationId="get_debug_oss_0",
 *         tags={"Debug Oss"},
 *         summary="api/debug-oss",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="delete", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="api",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_employee_addons_1",
 *         tags={"Employee"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="plans", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="start", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stop", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Payment Status", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="login",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="logout", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="checkout", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="checkout App", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary", maxLength=51200),
 *             @OA\Property(property="mediable_type", type="string", enum={"App\\Models\\Api\\Post"}),
 *             @OA\Property(property="mediable_id", type="integer"),
 *         ))),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="oauth2 Callback",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_pixels_1",
 *         tags={"Pixels"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_pixels_id_1",
 *         tags={"Pixels"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_pixels_id_2",
 *         tags={"Pixels"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_posts_1",
 *         tags={"Posts"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","content"},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="slug", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string", maxLength=100000),
 *             @OA\Property(property="excerpt", type="string", maxLength=500),
 *             @OA\Property(property="status", type="string", enum={"draft","published"}),
 *             @OA\Property(property="category_ids", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="media_ids", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="thumbnail_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_posts_slug_1",
 *         tags={"Posts"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="title", type="string", maxLength=255),
 *             @OA\Property(property="slug", type="string", maxLength=255),
 *             @OA\Property(property="content", type="string", maxLength=100000),
 *             @OA\Property(property="excerpt", type="string", maxLength=500),
 *             @OA\Property(property="status", type="string", enum={"draft","published"}),
 *             @OA\Property(property="category_ids", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="media_ids", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="thumbnail_id", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_posts_slug_2",
 *         tags={"Posts"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_projects_1",
 *         tags={"Projects"},
 *         summary="store", security={{"sanctum":{}}},
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
 *             @OA\Property(property="featured", type="boolean"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="label", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="value", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_projects_id_1",
 *         tags={"Projects"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"featured_image"},
 *             @OA\Property(property="featured_image", type="string"),
 *             @OA\Property(property="video_url", type="string"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="description", type="string", minLength=15),
 *             @OA\Property(property="gallery_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="floorplan_images", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="min_price", type="number"),
 *             @OA\Property(property="max_price", type="number"),
 *             @OA\Property(property="featured", type="boolean"),
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="latitude", type="number"),
 *             @OA\Property(property="longitude", type="number"),
 *             @OA\Property(property="label", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="value", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="complete_status", type="string"),
 *             @OA\Property(property="units", type="integer"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_projects_id_2",
 *         tags={"Projects"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle Featured", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_properties_1",
 *         tags={"Properties"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","address","description","featured_image"},
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
 *             @OA\Property(property="type", type="string"),
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
 *             @OA\Property(property="video_file", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="available Units", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Import", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="download Template",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="cards", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="properties_categories", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="list Drafts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Complete Drafts", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show Draft", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_properties_drafts_id_1",
 *         tags={"Properties"},
 *         summary="update Draft", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="complete Draft", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="export", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="export For Import", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="properties_reorder", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="properties_reorder_featured", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Deed Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_properties_id_1",
 *         tags={"Properties"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"title","address","description","featured_image"},
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
 *             @OA\Property(property="size", type="integer"),
 *             @OA\Property(property="building_id", type="integer"),
 *             @OA\Property(property="water_meter_number", type="string"),
 *             @OA\Property(property="electricity_meter_number", type="string"),
 *             @OA\Property(property="deed_number", type="string"),
 *             @OA\Property(property="advertising_license", type="string"),
 *             @OA\Property(property="owner_number", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_properties_id_2",
 *         tags={"Properties"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle Featured", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="duplicate", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="faqs", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="categories",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="category Articles",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="articles",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="articles",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="categories",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="category Articles",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="validate Code",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="register",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_rental_contracts_1",
 *         tags={"Rental Contracts"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="all Contracts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="daily Follow Up", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Contracts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get By Rental", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="statistics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_rental_contracts_id_1",
 *         tags={"Rental Contracts"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_rental_contracts_id_2",
 *         tags={"Rental Contracts"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="terminate", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_settings_domain_1",
 *         tags={"Settings"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="request Ssl", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="set Primary", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Ssl Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="verify", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_settings_domain_id_1",
 *         tags={"Settings"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="purchase", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="set Active Theme", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="complete Step", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Steps", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Cancel",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_themes_payment_cancel_user_theme_id_gateway_1",
 *         tags={"Themes"},
 *         summary="payment Cancel",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Success",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_themes_payment_success_user_theme_id_gateway_1",
 *         tags={"Themes"},
 *         summary="payment Success",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Multiple", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get User Profile", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="read_message", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_user_categories_1",
 *         tags={"User"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get User Profile", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="user Projects", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="properties Visits", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="top Pages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="track",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"tenant_id","slug","path","page_type"},
 *             @OA\Property(property="tenant_id", type="string", maxLength=255),
 *             @OA\Property(property="slug", type="string", maxLength=255),
 *             @OA\Property(property="dynamic_slug", type="string", maxLength=255),
 *             @OA\Property(property="path", type="string", maxLength=500),
 *             @OA\Property(property="page_type", type="string", enum={"page","post","project","property"}),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="top Pages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="top Posts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="summary", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="logout", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="me", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         @OA\Response(response=200, description="OK"),
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
 *         @OA\Response(response=200, description="OK"),
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
 *         @OA\Response(response=200, description="OK"),
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
 *         @OA\Response(response=200, description="OK"),
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
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Analytics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Balance", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Packages",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Cancel",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Success",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="purchase Package", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Transactions", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_crm_cards_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_crm_cards_id_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_crm_cards_id_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_crm_cards_id_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_crm_requests_1",
 *         tags={"Crm"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reorder", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Stage", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="details", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_crm_requests_request_1",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_crm_requests_request_2",
 *         tags={"Crm"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_crm_requests_request_3",
 *         tags={"Crm"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stages", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_customers_1",
 *         tags={"Customers"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_customers_customer_1",
 *         tags={"Customers"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_customers_customer_2",
 *         tags={"Customers"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_customers_customer_3",
 *         tags={"Customers"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Cancel",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_employee_addons_payment_cancel_addon_id_gateway_1",
 *         tags={"Employee Addons"},
 *         summary="payment Cancel",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Success",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_employee_addons_payment_success_addon_id_gateway_1",
 *         tags={"Employee Addons"},
 *         summary="payment Success",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_employees_1",
 *         tags={"Employees"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="available Permissions", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="available Roles", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_employees_employee_1",
 *         tags={"Employees"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_employees_employee_2",
 *         tags={"Employees"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_employees_employee_3",
 *         tags={"Employees"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_marketing_channels_1",
 *         tags={"Marketing"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="send Whats App To Customer", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Channel Types", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Usage", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_marketing_channels_id_1",
 *         tags={"Marketing"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_marketing_channels_id_2",
 *         tags={"Marketing"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="send Message", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Marketing Settings", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_marketing_channels_id_settings_1",
 *         tags={"Marketing"},
 *         summary="update Marketing Settings", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="statistics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="sync Verified", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update System Integration Settings", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get All Marketing Settings", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="whatsapp Webhook", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="customers", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="customer Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show Match", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_matching_requests_type_id_1",
 *         tags={"Matching"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="archive", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="mark As Read", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="unarchive", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="mark As Unread", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="check Property",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dashboard",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="financial Reports",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="forgot Password",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="login",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="logout",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="maintenance Requests",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="me",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="properties",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="property Details",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="rentals",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reset Password",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"token","email","password"},
 *             @OA\Property(property="token", type="string"),
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="tenants",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="permissions", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_permissions_1",
 *         tags={"Permissions"},
 *         summary="store Permission", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Permission", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_permissions_id_1",
 *         tags={"Permissions"},
 *         summary="destroy Permission", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dashboard", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Projects", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_pms_purchase_requests_1",
 *         tags={"Pms"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_pms_purchase_requests_id_1",
 *         tags={"Pms"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_pms_purchase_requests_id_2",
 *         tags={"Pms"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="simple Transition Stage", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="transition Stage", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="statistics", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="mark Completed", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="mark In Progress", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="mark Pending", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Notes", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Staff", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Upsert", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="defaults", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reset", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update One", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_property_requests_1",
 *         tags={"Property Requests"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="assign Employee To Customer", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_property_requests_id_1",
 *         tags={"Property Requests"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_property_requests_id_2",
 *         tags={"Property Requests"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Employee", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show Roles", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="sync Perms", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="sync Roles", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rbac_permissions_1",
 *         tags={"Rbac"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rbac_permissions_permission_1",
 *         tags={"Rbac"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="me", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rbac_roles_1",
 *         tags={"Rbac"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rbac_roles_role_1",
 *         tags={"Rbac"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show Employee Data", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Action", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"action","reservationIds"},
 *             @OA\Property(property="action", type="string", enum={"accept","reject"}),
 *             @OA\Property(property="reservationIds", type="array", minLength=1, @OA\Items(type="string")),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="export Csv", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="accept", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="confirmPayment", type="boolean"),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reject", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="confirmPayment", type="boolean"),
 *             @OA\Property(property="notes", type="string", maxLength=1000),
 *             @OA\Property(property="reason", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="all Contracts", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
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
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="change Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="terminate", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="daily Follow Up", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"image"},
 *             @OA\Property(property="image", type="string", format="binary", maxLength=2048),
 *         ))),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="status", type="string", enum={"pending","paid","partial","overdue","void"}),
 *             @OA\Property(property="paid_amount", type="number", minimum=0),
 *             @OA\Property(property="paid_at", type="string"),
 *             @OA\Property(property="reference", type="string", maxLength=100),
 *             @OA\Property(property="notes", type="string", maxLength=255),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_maintenance_1",
 *         tags={"Rms"},
 *         summary="store", security={{"sanctum":{}}},
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
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_rms_maintenance_id_1",
 *         tags={"Rms"},
 *         summary="update", security={{"sanctum":{}}},
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
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rms_maintenance_id_2",
 *         tags={"Rms"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="all Payment Collections", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Report", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payments Collections", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payments Due", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dismiss", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="snooze", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"snooze_until"},
 *             @OA\Property(property="snooze_until", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_1",
 *         tags={"Rms"},
 *         summary="store", security={{"sanctum":{}}},
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
 *             @OA\Property(property="cost_items", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Receipt Image", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"receipt_image"},
 *             @OA\Property(property="receipt_image", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_rms_rentals_id_1",
 *         tags={"Rms"},
 *         summary="update", security={{"sanctum":{}}},
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
 *             @OA\Property(property="cost_items", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="payments", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="regenerate_schedule", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_rms_rentals_id_2",
 *         tags={"Rms"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="collect Payment", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"payment_method","transfer_to"},
 *             @OA\Property(property="auto_select", type="boolean"),
 *             @OA\Property(property="auto_select_amount", type="number", minimum=0),
 *             @OA\Property(property="auto_select_strategy", type="string", enum={"overdue_first","oldest_first","sequential"}),
 *             @OA\Property(property="amount", type="number", minimum=0),
 *             @OA\Property(property="payment_amount", type="number", minimum=0),
 *             @OA\Property(property="payments", type="array", @OA\Items(type="string")),
 *             @OA\Property(property="payment_method", type="string", enum={"cash","bank_transfer","credit_card","online_payment","check","other"}),
 *             @OA\Property(property="payment_date", type="string"),
 *             @OA\Property(property="reference", type="string", maxLength=100),
 *             @OA\Property(property="notes", type="string", maxLength=255),
 *             @OA\Property(property="bank_name", type="string", maxLength=100),
 *             @OA\Property(property="receipt_image_path", type="string", maxLength=500),
 *             @OA\Property(property="transfer_to", type="string", enum={"منصة ناجز","المالك","المكتب"}),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="current Collections", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="property Details", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="details With Payments", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="end Contract", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"end_date"},
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="termination_reason", type="string", maxLength=255),
 *             @OA\Property(property="notes", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Collection", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="list Payments", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="renew Rental", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"rental_type","rental_duration","paying_plan","total_rental_amount"},
 *             @OA\Property(property="rental_type", type="string", enum={"monthly","annual"}),
 *             @OA\Property(property="rental_duration", type="integer", minimum=1),
 *             @OA\Property(property="paying_plan", type="string", enum={"monthly","quarterly","semi_annual","annual"}),
 *             @OA\Property(property="total_rental_amount", type="number", minimum=0),
 *             @OA\Property(property="currency", type="string"),
 *             @OA\Property(property="notes", type="string"),
 *             @OA\Property(property="cost_items", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Status", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"status"},
 *             @OA\Property(property="status", type="string", enum={"active","inactive","terminated","ended","cancelled","draft"}),
 *             @OA\Property(property="end_date", type="string"),
 *             @OA\Property(property="termination_reason", type="string", maxLength=500),
 *             @OA\Property(property="notes", type="string", maxLength=500),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="list By Rental", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_rental_d_contracts_1",
 *         tags={"Rms"},
 *         summary="store", security={{"sanctum":{}}},
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
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_rms_rentals_rental_d_expenses_1",
 *         tags={"Rms"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"expense_name","amount_type","amount_value","cost_center"},
 *             @OA\Property(property="expense_name", type="string", maxLength=255),
 *             @OA\Property(property="image_path", type="string"),
 *             @OA\Property(property="amount_type", type="string", enum={"percentage","fixed"}),
 *             @OA\Property(property="amount_value", type="number", minimum=0),
 *             @OA\Property(property="cost_center", type="string", enum={"tenant","owner"}),
 *             @OA\Property(property="is_active", type="boolean"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="regenerate", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="reverse Payment", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_roles_1",
 *         tags={"Roles"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_roles_role_1",
 *         tags={"Roles"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_roles_role_2",
 *         tags={"Roles"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_roles_role_3",
 *         tags={"Roles"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_sms_campaigns_1",
 *         tags={"Sms"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_sms_campaigns_id_1",
 *         tags={"Sms"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_sms_campaigns_id_2",
 *         tags={"Sms"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_sms_templates_1",
 *         tags={"Sms"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_sms_templates_id_1",
 *         tags={"Sms"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_sms_templates_id_2",
 *         tags={"Sms"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="delivery",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="download Txt",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"name","message"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=50),
 *             @OA\Property(property="message", type="string"),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"data"},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store",
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"name","phone","email","pdf"},
 *             @OA\Property(property="name", type="string", maxLength=255),
 *             @OA\Property(property="phone", type="string", maxLength=40),
 *             @OA\Property(property="email", type="string", format="email", maxLength=255),
 *             @OA\Property(property="description", type="string", maxLength=2000),
 *             @OA\Property(property="pdf", type="string", format="binary", maxLength=5120),
 *         ))),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data", @OA\Schema(type="object", required={"file"},
 *             @OA\Property(property="file", type="string", format="binary", maxLength=51200),
 *         ))),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_tenant_website_tenant_d_pages_1",
 *         tags={"Tenant Website"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"pageId","components"},
 *             @OA\Property(property="pageId", type="string"),
 *             @OA\Property(property="components", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_tenant_website_tenant_d_pages_page_d_1",
 *         tags={"Tenant Website"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"pageId","components"},
 *             @OA\Property(property="pageId", type="string"),
 *             @OA\Property(property="components", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_tenant_website_tenant_d_pages_page_d_2",
 *         tags={"Tenant Website"},
 *         summary="patch", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"pageId","components"},
 *             @OA\Property(property="pageId", type="string"),
 *             @OA\Property(property="components", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_tenant_website_tenant_d_pages_page_d_3",
 *         tags={"Tenant Website"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="properties_categories",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"propertySlug","customerName","customerPhone"},
 *             @OA\Property(property="propertySlug", type="string", maxLength=200),
 *             @OA\Property(property="customerName", type="string", maxLength=100),
 *             @OA\Property(property="customerPhone", type="string", maxLength=40),
 *             @OA\Property(property="desiredDate", type="string"),
 *             @OA\Property(property="message", type="string", maxLength=1000),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"settings"},
 *             @OA\Property(property="settings", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_user_owner_rentals_1",
 *         tags={"User"},
 *         summary="store", security={{"sanctum":{}}},
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
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_user_owner_rentals_id_1",
 *         tags={"User"},
 *         summary="update", security={{"sanctum":{}}},
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
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_user_owner_rentals_id_2",
 *         tags={"User"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="assign Properties", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"property_ids"},
 *             @OA\Property(property="property_ids", type="array", minLength=1, @OA\Items(type="integer")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v1_user_owner_rentals_id_properties_1",
 *         tags={"User"},
 *         summary="get Assigned Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="remove Property", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get My Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Cancel",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_addons_payment_cancel_addon_id_gateway_1",
 *         tags={"Whatsapp Addons"},
 *         summary="payment Cancel",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="payment Success",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_addons_payment_success_addon_id_gateway_1",
 *         tags={"Whatsapp Addons"},
 *         summary="payment Success",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_ai_config_number_d_1",
 *         tags={"Whatsapp"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_automation_rules_1",
 *         tags={"Whatsapp"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_automation_rules_id_1",
 *         tags={"Whatsapp"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_whatsapp_automation_rules_id_2",
 *         tags={"Whatsapp"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="toggle", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_1",
 *         tags={"Whatsapp"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v1_whatsapp_conversations_id_1",
 *         tags={"Whatsapp"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_conversations_id_messages_1",
 *         tags={"Whatsapp"},
 *         summary="send", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="send Template", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="read", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="star", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_numbers_1",
 *         tags={"Whatsapp"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_numbers_id_1",
 *         tags={"Whatsapp"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_templates_1",
 *         tags={"Whatsapp"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v1_whatsapp_templates_id_1",
 *         tags={"Whatsapp"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v1_whatsapp_templates_id_2",
 *         tags={"Whatsapp"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="incoming",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="status",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="verify",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v1_whatsapp_webhook_verify_1",
 *         tags={"Whatsapp"},
 *         summary="verify Post",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="performance", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="sources", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="trends", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="assign", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="auto Assign", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="employees", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="save Rules", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_assignment_rules_1",
 *         tags={"Customers Hub"},
 *         summary="get Rules", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="unassigned Count", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Put(
 *         operationId="put_v2_customers_hub_customers_customer_d_1",
 *         tags={"Customers Hub"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Preferences", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="add Property", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Get(
 *         operationId="get_v2_customers_hub_customers_customer_d_properties_1",
 *         tags={"Customers Hub"},
 *         summary="list Properties", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="remove Property", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="add Task", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Task", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v2_customers_hub_customers_customer_d_tasks_task_d_1",
 *         tags={"Customers Hub"},
 *         summary="delete Task", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="list", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Move", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="move", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Complete", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="bulk Dismiss", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="filter Options", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="list", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_v2_customers_hub_requests_request_d_1",
 *         tags={"Customers Hub"},
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="create Appointment For Property Request", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="complete", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="dismiss", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="add Note", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="create Reminder For Property Request", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="action Stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_v2_customers_hub_stages_1",
 *         tags={"Customers Hub"},
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_v2_customers_hub_stages_stage_id_1",
 *         tags={"Customers Hub"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="abort Chunked Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="complete Chunked Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="delete Video", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="initiate Chunked Upload", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="get Signed Upload Url", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Video", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="upload Chunk", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="index", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="stats", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="show", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Delete(
 *         operationId="delete_whatsapp_ai_conversations_id_1",
 *         tags={"Whatsapp Ai"},
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="archive", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="handle",
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Post(
 *         operationId="post_whatsapp_ai_webhook_1",
 *         tags={"Whatsapp Ai"},
 *         summary="handle",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="plans", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="handle Evolution Webhook",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="store", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="callback",
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="redirect", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="handle Whatsapp Webhook",
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="destroy", security={{"sanctum":{}}},
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="update Employee", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     ),
 *     @OA\Patch(
 *         operationId="patch_whatsapp_id_employee_1",
 *         tags={"Whatsapp"},
 *         summary="update Employee", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="link", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
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
 *         summary="unlink", security={{"sanctum":{}}},
 *         @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={},
 *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
 *         )),
 *         @OA\Response(response=200, description="OK"),
 *         @OA\Response(response=401, description="Unauthenticated")
 *     )
 *
 * )
 */
class GeneratedApiPathsDoc
{
    // Generated for L5-Swagger scan.
}
