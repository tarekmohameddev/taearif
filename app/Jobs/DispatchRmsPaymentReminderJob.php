<?php

namespace App\Jobs;

use App\Domain\Communication\Sms\Services\SmsSingleMessageService;
use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppConversationService;
use App\Services\Rms\PaymentReminderAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchRmsPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public readonly int $logId) {}

    public function handle(PaymentReminderAutomationService $service, CommunicationService $communication, WhatsAppConversationService $conversations, SmsSingleMessageService $sms): void
    {
        $service->dispatch($this->logId, $communication, $conversations, $sms);
    }
}
