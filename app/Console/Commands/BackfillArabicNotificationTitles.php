<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off backfill for historical mobile notification copy.
 *
 * Choice: exact-match UPDATE only on fixed English titles (and fixed complete/
 * dismiss/snooze/no-name bodies). Parameterized bodies that embed names/dates
 * are left in English — rewriting them safely would require fragile regex.
 */
class BackfillArabicNotificationTitles extends Command
{
    protected $signature = 'notifications:backfill-arabic-titles {--dry-run : Report counts without updating}';

    protected $description = 'Backfill fixed English app_notifications titles/bodies to Arabic (exact match only)';

    public function handle(): int
    {
        if (! Schema::hasTable('app_notifications')) {
            $this->warn('app_notifications table missing. Nothing to do.');

            return self::SUCCESS;
        }

        // Exact English title → Arabic title
        $titleMap = [
            'New property request' => 'طلب عقار جديد',
            'New contact message' => 'رسالة تواصل جديدة',
            'Property request stage updated' => 'تم تحديث مرحلة الطلب',
            'Property request priority updated' => 'تحديث أولوية طلب العقار',
            'Property request assigned' => 'تعيين طلب العقار',
            'Property request updated' => 'تحديث طلب العقار',
            'Appointment scheduled' => 'موعد مجدول',
            'Reminder created' => 'تم إنشاء تذكير',
            'Reminder due soon' => 'تذكير مستحق قريباً',
            'Reminder overdue' => 'تذكير متأخر',
            'Property request completed' => 'تم إكمال طلب العقار',
            'Property request dismissed' => 'تم استبعاد طلب العقار',
            'Property request snoozed' => 'تم تأجيل طلب العقار',
        ];

        // Exact English body → Arabic body (fixed strings only; no parameterized bodies)
        $bodyMap = [
            'A new property request was submitted.' => 'تم إرسال طلب عقار جديد.',
            'A new contact message was received.' => 'تم استلام رسالة تواصل جديدة.',
            'A property request was marked as completed' => 'تم تعليم طلب العقار كمكتمل',
            'A property request was dismissed' => 'تم استبعاد طلب العقار',
            'A property request was snoozed' => 'تم تأجيل طلب العقار',
        ];

        $dryRun = (bool) $this->option('dry-run');
        $titleUpdated = 0;
        $bodyUpdated = 0;

        foreach ($titleMap as $english => $arabic) {
            $query = DB::table('app_notifications')->where('title', $english);
            $count = (clone $query)->count();
            if ($count === 0) {
                continue;
            }
            $this->line("title: \"{$english}\" → {$count} row(s)");
            if (! $dryRun) {
                $titleUpdated += $query->update(['title' => $arabic]);
            } else {
                $titleUpdated += $count;
            }
        }

        foreach ($bodyMap as $english => $arabic) {
            $query = DB::table('app_notifications')->where('body', $english);
            $count = (clone $query)->count();
            if ($count === 0) {
                continue;
            }
            $this->line("body: \"{$english}\" → {$count} row(s)");
            if (! $dryRun) {
                $bodyUpdated += $query->update(['body' => $arabic]);
            } else {
                $bodyUpdated += $count;
            }
        }

        $prefix = $dryRun ? 'Dry run: would update' : 'Updated';
        $this->info("{$prefix} {$titleUpdated} title(s) and {$bodyUpdated} body(ies).");

        return self::SUCCESS;
    }
}
