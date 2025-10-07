<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Api\Crm\CrmCard;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendCrmAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:send-appointment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders for CRM appointments 2 hours before scheduled time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting CRM appointment reminder process...');

        // Use Asia/Riyadh timezone
        $now = Carbon::now('Asia/Riyadh');
        
        // Calculate the time window: 2 hours from now
        // Since this runs hourly, we check appointments between 2-3 hours from now
        $twoHoursFromNow = $now->copy()->addHours(2);
        $threeHoursFromNow = $now->copy()->addHours(3);

        $this->info("Current time: {$now->format('Y-m-d H:i:s')}");
        $this->info("Looking for appointments between: {$twoHoursFromNow->format('Y-m-d H:i:s')} and {$threeHoursFromNow->format('Y-m-d H:i:s')}");

        // Find all appointment cards that need reminders
        $appointments = CrmCard::where('card_procedure', 'appointment')
            ->whereNotNull('card_date')
            ->whereNull('reminder_sent_at')
            ->whereBetween('card_date', [$twoHoursFromNow, $threeHoursFromNow])
            ->with(['customer', 'user'])
            ->get();

        $this->info("Found {$appointments->count()} appointments to process");

        $sentCount = 0;
        $failedCount = 0;
        $whatsAppService = new WhatsAppService();

        foreach ($appointments as $appointment) {
            try {
                $customer = $appointment->customer;
                
                if (!$customer) {
                    $this->warn("Appointment ID {$appointment->id}: Customer not found");
                    $failedCount++;
                    continue;
                }

                if (!$customer->phone_number) {
                    $this->warn("Appointment ID {$appointment->id}: Customer {$customer->name} has no phone number");
                    $failedCount++;
                    continue;
                }

                // Format the appointment date/time
                $appointmentDateTime = Carbon::parse($appointment->card_date)->timezone('Asia/Riyadh');
                $formattedDateTime = $appointmentDateTime->format('Y-m-d h:i A');
                $formattedDateArabic = $appointmentDateTime->locale('ar')->isoFormat('dddd، D MMMM YYYY - h:mm A');

                // Prepare the message
                $message = "مرحباً {$customer->name}،\n\n";
                $message .= "تذكير بموعدك القادم:\n";
                $message .= "📅 التاريخ والوقت: {$formattedDateArabic}\n\n";
                
                if ($appointment->card_content) {
                    $message .= "📝 التفاصيل: {$appointment->card_content}\n\n";
                }
                
                $message .= "يرجى التواصل معنا في حال الحاجة لإعادة جدولة الموعد.\n";
                $message .= "شكراً لك! 🙏";

                $this->info("Sending reminder to {$customer->name} ({$customer->phone_number})");
                
                // Send WhatsApp message
                $result = $whatsAppService->sendMessage(
                    $customer->phone_number,
                    $message
                );

                if ($result) {
                    // Mark reminder as sent
                    $appointment->reminder_sent_at = $now;
                    $appointment->save();
                    
                    $sentCount++;
                    $this->info("✓ Reminder sent successfully for appointment ID {$appointment->id}");
                    
                    // Log the successful send
                    Log::info('CRM appointment reminder sent', [
                        'appointment_id' => $appointment->id,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'phone' => $customer->phone_number,
                        'appointment_date' => $appointment->card_date,
                    ]);
                } else {
                    $failedCount++;
                    $this->error("✗ Failed to send reminder for appointment ID {$appointment->id}");
                    
                    Log::error('CRM appointment reminder failed', [
                        'appointment_id' => $appointment->id,
                        'customer_id' => $customer->id,
                        'phone' => $customer->phone_number,
                    ]);
                }

            } catch (\Exception $e) {
                $failedCount++;
                $this->error("✗ Error processing appointment ID {$appointment->id}: {$e->getMessage()}");
                
                Log::error('CRM appointment reminder exception', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Total appointments found: {$appointments->count()}");
        $this->info("Successfully sent: {$sentCount}");
        $this->info("Failed: {$failedCount}");
        $this->info('CRM appointment reminder process completed!');

        return Command::SUCCESS;
    }
}
