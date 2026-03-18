<?php

return [
    // Phase 1: Apps
    'App\Http\Controllers\Api\App\ApiInstallationController@install' => [
        'app_id' => 'required|integer|exists:api_apps,id',
        'settings' => 'nullable|array',
        'settings.phone_number' => 'nullable|string|max:20',
        'settings.token' => 'nullable|string|max:255',
    ],
    'App\Http\Controllers\Api\App\ApiInstallationController@uninstall' => [],
    'App\Http\Controllers\Api\App\ApiInstallationController@uninstallWhatsapp' => [],

    // Phase 5: V1 Marketing, RMS, SMS, Tenant, WhatsApp, V2 CustomersHub, WhatsApp app
    'App\Http\Controllers\Api\marketing\MarketingChannelController@syncVerified' => [],
    'App\Http\Controllers\Api\marketing\MarketingChannelController@whatsappWebhook' => [],
    'App\Http\Controllers\Api\V1\Rms\ReminderController@dismiss' => [],
    'App\Http\Controllers\Api\V1\Rms\InstallmentController@regenerate' => [],
    'App\Http\Controllers\Api\V1\Rms\RentalController@reversePayment' => [],
    'App\Http\Controllers\Api\V1\Sms\WebhookController@delivery' => [],
    'App\Http\Controllers\Api\V1\TenantWebsite\PublishController@store' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\AiConfigController@toggle' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\AutomationRuleController@toggle' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\ConversationController@read' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\ConversationController@star' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\WebhookController@incoming' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\WebhookController@status' => [],
    'App\Http\Controllers\Api\V1\WhatsApp\WebhookController@verifyPost' => [],
    'App\Http\Controllers\Api\V2\CustomersHub\ListController@bulk' => [],
    'App\Http\Controllers\Api\V2\CustomersHub\RequestsController@complete' => [],
    'App\Http\Controllers\Api\V2\CustomersHub\RequestsController@dismiss' => [],
    'Modules\WhatsappAI\Http\Controllers\ConversationController@archive' => [],
    'Modules\WhatsappAI\Http\Controllers\WebhookController@handle' => [],
    'App\Http\Controllers\Api\apps\whatsapp\WhatsappAddonController@store' => [],
    'App\Http\Controllers\Api\apps\whatsapp\ChatController@handleEvolutionWebhook' => [],
    'App\Http\Controllers\Api\apps\whatsapp\WhatsappController@store' => [],
    'App\Http\Controllers\Api\apps\whatsapp\ChatController@handleWhatsappWebhook' => [],
    'App\Http\Controllers\Api\apps\whatsapp\WhatsappController@updateEmployee' => [],
    'App\Http\Controllers\Api\apps\whatsapp\WhatsappController@link' => [],
    'App\Http\Controllers\Api\apps\whatsapp\WhatsappController@unlink' => [],
];