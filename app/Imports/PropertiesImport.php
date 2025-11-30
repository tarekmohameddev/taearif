<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PropertiesImport implements WithMultipleSheets
{
    public $sheetImport;

    public function __construct($userId, $limit = 1000)
    {
        $this->sheetImport = new PropertiesSingleSheetImport($userId, $limit);
    }

    public function sheets(): array
    {
        return [
            0 => $this->sheetImport,
        ];
    }
}
