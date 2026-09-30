<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\WaNumber;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WhatsAppReportsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('username', 'kkkkk')->first();

        if ($user === null) {
            throw new RuntimeException('User kkkkk was not found.');
        }

        $number = WaNumber::query()->firstOrCreate(
            ['user_id' => $user->id, 'phone_number' => '+966500001430'],
            ['provider' => 'meta', 'status' => 'active', 'name' => 'Verification Number']
        );

        $templates = [];
        foreach ([
            ['Report Welcome July', 'Welcome July', 'UTILITY', '2026-07-05 09:00:00'],
            ['Report Promo August', 'Promo August', 'MARKETING', '2026-08-05 09:00:00'],
            ['Report Reminder September', 'Reminder September', 'UTILITY', '2026-09-05 09:00:00'],
        ] as [$name, $content, $category, $date]) {
            DB::table('wa_templates')->updateOrInsert(
                ['user_id' => $user->id, 'name' => $name],
                ['content' => $content, 'category' => $category, 'is_active' => 1, 'language' => 'en', 'created_at' => $date, 'updated_at' => $date]
            );
            $templates[$name] = DB::table('wa_templates')->where(['user_id' => $user->id, 'name' => $name])->value('id');
        }

        foreach ([
            ['July Campaign', 'Report Welcome July', '2026-07-10 10:00:00', 100, 90, 5],
            ['August Campaign', 'Report Promo August', '2026-08-10 10:00:00', 200, 180, 15],
            ['September Campaign', 'Report Reminder September', '2026-09-10 10:00:00', 300, 275, 20],
        ] as [$name, $template, $date, $recipients, $delivered, $failed]) {
            DB::table('wa_campaigns')->updateOrInsert(
                ['user_id' => $user->id, 'dispatch_reference' => 'demo-report-' . strtolower(str_replace(' ', '-', $name))],
                ['created_by_user_id' => $user->id, 'wa_number_id' => $number->id, 'name' => $name, 'message' => 'Report test campaign', 'template_id' => $templates[$template], 'status' => 'sent', 'sent_at' => $date, 'recipient_count' => $recipients, 'sent_count' => $recipients, 'delivered_count' => $delivered, 'failed_count' => $failed, 'reserved_credits' => $recipients, 'created_at' => $date, 'updated_at' => $date]
            );
        }

        foreach ([
            ['2026-07-15 10:00:00', 'pending', 11],
            ['2026-08-15 11:00:00', 'resolved', 22],
            ['2026-09-15 12:00:00', 'active', 33],
        ] as [$date, $status, $credits]) {
            $key = Carbon::parse($date)->format('Ym');
            $conversationId = DB::table('conversations')->where([
                'user_id' => $user->id,
                'channel' => 'whatsapp',
                'external_party_identifier' => 'demo-report-' . $key,
            ])->value('id');

            if (! $conversationId) {
                $conversationId = DB::table('conversations')->insertGetId([
                    'user_id' => $user->id,
                    'channel' => 'whatsapp',
                    'external_party_identifier' => 'demo-report-' . $key,
                    'last_message_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            DB::table('wa_conversation_states')->updateOrInsert(
                ['conversation_id' => $conversationId],
                ['user_id' => $user->id, 'wa_number_id' => $number->id, 'status' => $status, 'last_message_time' => $date, 'created_at' => $date, 'updated_at' => $date]
            );

            DB::table('credit_transactions')->updateOrInsert(
                ['user_id' => $user->id, 'reference_number' => 'demo-report-credit-' . $key],
                ['transaction_type' => 'usage', 'credits_amount' => -$credits, 'status' => 'completed', 'created_at' => $date, 'updated_at' => $date]
            );

            $messageId = DB::table('messages')->where('provider_message_id', 'demo-report-message-' . $key)->value('id');
            if (! $messageId) {
                $messageId = DB::table('messages')->insertGetId([
                    'conversation_id' => $conversationId,
                    'user_id' => $user->id,
                    'content' => 'Report verification message',
                    'direction' => 'inbound',
                    'status' => 'received',
                    'provider_message_id' => 'demo-report-message-' . $key,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            DB::table('wa_ai_response_logs')->updateOrInsert(
                ['user_id' => $user->id, 'message_id' => $messageId],
                ['wa_number_id' => $number->id, 'conversation_id' => $conversationId, 'scenario' => 'report-test', 'response_time_ms' => 1500, 'handed_off' => $status === 'resolved', 'language' => 'en', 'created_at' => $date, 'updated_at' => $date]
            );
        }

        foreach ([
            ['Report Follow Up', 'keyword', 50, 45, '2026-09-20 10:00:00'],
            ['Report Greeting', 'new_conversation', 30, 27, '2026-08-20 10:00:00'],
        ] as [$name, $trigger, $triggered, $successes, $lastTriggered]) {
            DB::table('wa_automation_rules')->updateOrInsert(
                ['user_id' => $user->id, 'wa_number_id' => $number->id, 'name' => $name],
                ['trigger' => $trigger, 'is_active' => 1, 'triggered_count' => $triggered, 'success_count' => $successes, 'last_triggered_at' => $lastTriggered, 'created_at' => '2026-07-01 10:00:00', 'updated_at' => $lastTriggered]
            );
        }
    }
}
