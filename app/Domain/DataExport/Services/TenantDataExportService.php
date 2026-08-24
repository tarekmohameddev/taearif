<?php

namespace App\Domain\DataExport\Services;

use App\Exports\UserDataExport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TenantDataExportService
{
    /** Owner id + cached employee ids (mirror of PropertyController::export). */
    public function resolveAllowedUserIds(int $ownerId): array
    {
        $allowed = [$ownerId];
        try {
            $employeeIds = Cache::remember("tenant_employees_{$ownerId}", 300, function () use ($ownerId) {
                return User::where('tenant_id', $ownerId)
                    ->where('account_type', 'employee')
                    ->pluck('id')
                    ->toArray();
            });
            $allowed = array_values(array_unique(array_merge($allowed, $employeeIds)));
        } catch (\Throwable $e) {
            // owner-only on failure
        }

        return $allowed;
    }

    public function buildExport(int $ownerId): UserDataExport
    {
        return new UserDataExport($ownerId, $this->resolveAllowedUserIds($ownerId));
    }

    public function download(int $ownerId): BinaryFileResponse
    {
        $user = User::find($ownerId);
        $username = $user?->username ?? $user?->name ?? $user?->email ?? "user-{$ownerId}";
        $filename = 'taearif-data-export-' . Str::slug($username) . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download($this->buildExport($ownerId), $filename);
    }
}
