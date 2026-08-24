<?php

namespace App\Domain\DataExport\Services;

use App\Domain\DataExport\Models\DataExportImportLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records an audit-trail entry for every tenant data export/import attempt.
 *
 * Logging must never break the operation it is auditing, so every public
 * method swallows its own failures (after logging them to the app log).
 */
class DataExportImportLogger
{
    private const SHEET_KEYS = ['projects', 'customers', 'properties', 'requests'];

    public function __construct(private Request $request) {}

    /** Record a successful or failed export download. */
    public function recordExport(?int $userId, ?string $fileName, string $status, ?string $message = null): void
    {
        $this->write([
            'operation' => DataExportImportLog::OPERATION_EXPORT,
            'user_id' => $userId,
            'affected_username' => $this->resolveUsername($userId),
            'status' => $status,
            'file_name' => $fileName,
            'message' => $message,
        ]);
    }

    /**
     * Record an import from its result array (see TenantDataImportService::import()).
     *
     * @param  array<string, mixed>  $result
     */
    public function recordImport(
        ?int $userId,
        array $result,
        bool $updateExisting,
        ?int $adminId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $imported = $this->sumKey($result, 'imported');
        $updated = $this->sumKey($result, 'updated');
        $skipped = $this->sumKey($result, 'skipped');
        $hasErrors = $this->anySheet($result, 'errors');
        $hasWarnings = $this->anySheet($result, 'warnings');

        $status = DataExportImportLog::STATUS_SUCCESS;
        if ($hasErrors) {
            $status = DataExportImportLog::STATUS_PARTIAL;
        } elseif ($hasWarnings && ($imported + $updated) === 0) {
            $status = DataExportImportLog::STATUS_PARTIAL;
        }

        $this->write([
            'operation' => DataExportImportLog::OPERATION_IMPORT,
            'user_id' => $userId,
            'affected_username' => $this->resolveUsername($userId),
            'status' => $status,
            'update_existing' => $updateExisting,
            'imported_count' => $imported,
            'updated_count' => $updated,
            'skipped_count' => $skipped,
            'message' => $result['note'] ?? null,
            'metadata' => $this->buildMetadata($result),
        ], $adminId, $ipAddress, $userAgent);
    }

    /** Record an import that threw before producing a result. */
    public function recordImportFailure(
        ?int $userId,
        string $message,
        bool $updateExisting,
        ?int $adminId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        $this->write([
            'operation' => DataExportImportLog::OPERATION_IMPORT,
            'user_id' => $userId,
            'affected_username' => $this->resolveUsername($userId),
            'status' => DataExportImportLog::STATUS_FAILED,
            'update_existing' => $updateExisting,
            'message' => $message,
        ], $adminId, $ipAddress, $userAgent);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function write(
        array $attributes,
        ?int $adminId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        try {
            DataExportImportLog::create(array_merge([
                'admin_id' => $adminId ?? Auth::guard('admin')->id(),
                'ip_address' => $ipAddress ?? $this->request->ip(),
                'user_agent' => substr((string) ($userAgent ?? $this->request->userAgent()), 0, 1000),
                'created_at' => now(),
            ], $attributes));
        } catch (Throwable $e) {
            // Never let audit logging break the export/import itself.
            Log::warning('Failed to record data export/import log', [
                'message' => $e->getMessage(),
                'attributes' => $attributes,
            ]);
        }
    }

    private function resolveUsername(?int $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        return User::where('id', $userId)->value('username');
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function buildMetadata(array $result): array
    {
        $sheets = [];
        foreach (self::SHEET_KEYS as $key) {
            $sheet = $result[$key] ?? null;
            if (!is_array($sheet)) {
                continue;
            }

            $sheets[$key] = [
                'imported' => (int) ($sheet['imported'] ?? 0),
                'updated' => (int) ($sheet['updated'] ?? 0),
                'skipped' => (int) ($sheet['skipped'] ?? 0),
                'errors' => array_values($sheet['errors'] ?? []),
                'warnings' => array_values($sheet['warnings'] ?? []),
            ];
        }

        return ['sheets' => $sheets];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function sumKey(array $result, string $key): int
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
    private function anySheet(array $result, string $key): bool
    {
        foreach (self::SHEET_KEYS as $sheetKey) {
            $sheet = $result[$sheetKey] ?? null;
            if (is_array($sheet) && !empty($sheet[$key])) {
                return true;
            }
        }

        return false;
    }
}
