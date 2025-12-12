<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappAI\Http\Controllers\WebhookController;
use Modules\WhatsappAI\Http\Controllers\ConversationController;

/*
|--------------------------------------------------------------------------
| WhatsApp AI Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('whatsapp-ai')->group(function () {
    
    // Webhook (public - no authentication)
    // Meta will send GET request for verification and POST for messages
    Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handle'])
        ->name('whatsappai.webhook');
    
    // Admin API (authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index'])
            ->name('whatsappai.conversations.index');
        
        Route::get('/conversations/stats', [ConversationController::class, 'stats'])
            ->name('whatsappai.conversations.stats');
        
        Route::get('/conversations/{id}', [ConversationController::class, 'show'])
            ->name('whatsappai.conversations.show');
        
        Route::post('/conversations/{id}/archive', [ConversationController::class, 'archive'])
            ->name('whatsappai.conversations.archive');
        
        Route::delete('/conversations/{id}', [ConversationController::class, 'destroy'])
            ->name('whatsappai.conversations.destroy');
    });
});