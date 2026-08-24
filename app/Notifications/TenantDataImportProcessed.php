<?php

namespace App\Notifications;

use App\Domain\DataExport\Models\TenantDataImportBatch;
use App\Models\User;
use Illuminate\Notifications\Notification;

class TenantDataImportProcessed extends Notification
{
    private const SHEET_KEYS = [
        'crm_settings',
        'amenities',
        'projects',
        'customers',
        'properties',
        'requests',
    ];

    public readonly int $batchId;

    public readonly string $status;

    public readonly string $tenantLabel;

    public readonly int $totalImported;

    public readonly int $totalUpdated;

    public readonly int $totalSkipped;

    public readonly bool $hasErrors;

    public function __construct(TenantDataImportBatch $batch)
    {
        $fresh = $batch->fresh() ?? $batch;

        $this->batchId = (int) $fresh->id;
        $this->status = (string) $fresh->status;
        $this->tenantLabel = self::resolveTenantLabel((int) $fresh->owner_id);

        $result = is_array($fresh->result) ? $fresh->result : [];
        $this->totalImported = self::sumKey($result, 'imported');
        $this->totalUpdated = self::sumKey($result, 'updated');
        $this->totalSkipped = self::sumKey($result, 'skipped');
        $this->hasErrors = self::statusHasErrors($fresh->status, $result);
    }

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        $isDone = $this->status === TenantDataImportBatch::STATUS_DONE;

        if ($isDone) {
            $titleAr = 'اكتمل استيراد بيانات المستأجر';
            $titleEn = 'Tenant data import completed';
            $messageAr = sprintf(
                'اكتمل استيراد بيانات %s (%d جديد، %d محدّث، %d متخطّى)%s.',
                $this->tenantLabel,
                $this->totalImported,
                $this->totalUpdated,
                $this->totalSkipped,
                $this->hasErrors ? ' — مع أخطاء' : ''
            );
            $messageEn = sprintf(
                'Import for %s finished (%d imported, %d updated, %d skipped)%s.',
                $this->tenantLabel,
                $this->totalImported,
                $this->totalUpdated,
                $this->totalSkipped,
                $this->hasErrors ? ' — with errors' : ''
            );
        } else {
            $titleAr = 'فشل استيراد بيانات المستأجر';
            $titleEn = 'Tenant data import failed';
            $messageAr = sprintf('فشل استيراد بيانات %s.', $this->tenantLabel);
            $messageEn = sprintf('Import failed for %s.', $this->tenantLabel);
        }

        return [
            'batch_id' => $this->batchId,
            'status' => $this->status,
            'title' => $titleAr . ' / ' . $titleEn,
            'title_ar' => $titleAr,
            'title_en' => $titleEn,
            'message' => $messageAr . ' / ' . $messageEn,
            'message_ar' => $messageAr,
            'message_en' => $messageEn,
            'tenant_label' => $this->tenantLabel,
            'total_imported' => $this->totalImported,
            'total_updated' => $this->totalUpdated,
            'total_skipped' => $this->totalSkipped,
            'has_errors' => $this->hasErrors,
            'url' => route('admin.register.user.import-batch', $this->batchId),
        ];
    }

    private static function resolveTenantLabel(int $ownerId): string
    {
        if ($ownerId <= 0) {
            return '—';
        }

        $user = User::with('basic_setting')->find($ownerId);
        if (! $user) {
            return '—';
        }

        $websiteName = $user->basic_setting?->website_title ?? $user->basic_setting?->company_name ?? null;

        if (is_string($websiteName) && $websiteName !== '') {
            return $websiteName . ' (' . $user->username . ')';
        }

        return (string) ($user->username ?? $user->email ?? '—');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function sumKey(array $result, string $key): int
    {
        $total = 0;

        foreach (self::SHEET_KEYS as $sheetKey) {
            $sheet = $result[$sheetKey] ?? null;
            if (is_array($sheet)) {
                $total += (int) ($sheet[$key] ?? 0);
            }
        }

        return $total;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function statusHasErrors(string $status, array $result): bool
    {
        if ($status === TenantDataImportBatch::STATUS_FAILED) {
            return true;
        }

        foreach (self::SHEET_KEYS as $sheetKey) {
            $sheet = $result[$sheetKey] ?? null;
            if (is_array($sheet) && ! empty($sheet['errors'])) {
                return true;
            }
        }

        return false;
    }
}
