<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UserDataExport implements WithMultipleSheets
{
    public function __construct(
        private int $ownerId,
        private array $allowedUserIds,
    ) {}

    public function sheets(): array
    {
        return [
            new TenantPropertiesDataExportSheet($this->ownerId, $this->allowedUserIds),
            new ProjectsDataExportSheet($this->allowedUserIds),
            new CrmCustomersExport($this->ownerId, []),
            new PropertyRequestsDataExportSheet($this->ownerId),
        ];
    }
}
