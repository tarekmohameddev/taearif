<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle WhatsApp webhook events
     */
    public function handleWebhook(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('WhatsApp webhook received', ['data' => $data]);
            
            // Handle button clicks
            if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
                $messages = $data['entry'][0]['changes'][0]['value']['messages'];
                
                foreach ($messages as $message) {
                    if (isset($message['interactive']['button_reply'])) {
                        $this->handleButtonClick($message);
                    }
                }
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json(['status' => 'error'], 500);
        }
    }
    
    /**
     * Handle button click interactions
     */
    private function handleButtonClick($message)
    {
        $buttonId = $message['interactive']['button_reply']['id'];
        $phoneNumber = $message['from'];
        
        Log::info('WhatsApp button clicked', [
            'button_id' => $buttonId,
            'phone' => $phoneNumber
        ]);
        
        // Send appropriate URL based on button clicked
        switch ($buttonId) {
            case 'visit_site':
                $this->sendSiteLink($phoneNumber);
                break;
            case 'visit_dashboard':
                $this->sendDashboardLink($phoneNumber);
                break;
        }
    }
    
    /**
     * Send site link to user
     */
    private function sendSiteLink($phoneNumber)
    {
        $siteUrl = env('APP_URL', 'https://taearifdev.com');
        
        $message = "🌐 رابط موقعك:\n{$siteUrl}";
        
        // Send the link message
        $this->sendWhatsAppMessage($phoneNumber, $message);
    }
    
    /**
     * Send dashboard link to user
     */
    private function sendDashboardLink($phoneNumber)
    {
        $dashboardUrl = env('FRONTEND_URL', 'https://app.taearif.com');
        
        $message = "📊 رابط لوحة التحكم:\n{$dashboardUrl}";
        
        // Send the link message
        $this->sendWhatsAppMessage($phoneNumber, $message);
    }
    
    /**
     * Send WhatsApp message
     */
    private function sendWhatsAppMessage($phoneNumber, $message)
    {
        try {
            $whatsappService = new \App\Services\WhatsAppService();
            $whatsappService->sendWelcomeMessage($phoneNumber, $message);
            
            Log::info('WhatsApp link message sent', [
                'phone' => $phoneNumber,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp link message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
        }
    }
}
