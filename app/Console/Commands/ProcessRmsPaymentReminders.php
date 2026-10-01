<?php

namespace App\Console\Commands;

use App\Services\Rms\PaymentReminderAutomationService;
use Illuminate\Console\Command;

class ProcessRmsPaymentReminders extends Command
{
    protected $signature = 'rms:process-payment-reminders';
    protected $description = 'Schedule automated RMS installment payment reminders';

    public function handle(PaymentReminderAutomationService $service): int
    {
        $this->info('Scheduled ' . $service->scheduleDue(now('UTC')) . ' RMS payment reminder(s).');
        return self::SUCCESS;
    }
}
