<?php

namespace App\Imports\Support;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lightweight PhpSpreadsheet pre-read for workbook shape checks
 * (sheet presence, header match, data-row counts) before Maatwebsite import.
 */
class WorkbookSheetInspector
{
    private Spreadsheet $spreadsheet;

    public function __construct(string $path)
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $this->spreadsheet = $reader->load($path);
    }

    public function sheetCount(): int
    {
        return $this->spreadsheet->getSheetCount();
    }

    public function hasSheet(int $index): bool
    {
        return $index >= 0 && $index < $this->sheetCount();
    }

    /**
     * Raw header cell values from row 1 (empty trailing cells trimmed).
     *
     * @return list<string>
     */
    public function headers(int $index): array
    {
        $sheet = $this->worksheet($index);
        if ($sheet === null) {
            return [];
        }

        $highestColumn = $sheet->getHighestDataColumn(1);
        $row = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0] ?? [];

        $headers = [];
        foreach ($row as $value) {
            if ($value === null || $value === '') {
                // Keep leading/middle empties so column positions stay aligned,
                // but stop once we've passed the last non-empty header.
                $headers[] = '';
                continue;
            }
            $headers[] = trim((string) $value);
        }

        while ($headers !== [] && end($headers) === '') {
            array_pop($headers);
        }

        return $headers;
    }

    /**
     * Whether every required header is present (Maatwebsite slug-style match).
     *
     * @param  list<string>  $required
     */
    public function headersMatch(int $index, array $required): bool
    {
        $normalized = [];
        foreach ($this->headers($index) as $header) {
            if ($header === '') {
                continue;
            }
            $normalized[] = Str::slug($header, '_');
        }

        foreach ($required as $need) {
            if (!in_array(Str::slug($need, '_'), $normalized, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Count of data rows (excludes header). Uses highest data row as an upper bound,
     * matching existing bulk-import patterns in the project.
     */
    public function dataRowCount(int $index): int
    {
        $sheet = $this->worksheet($index);
        if ($sheet === null) {
            return 0;
        }

        $highest = (int) $sheet->getHighestDataRow();

        return max(0, $highest - 1);
    }

    public function disconnect(): void
    {
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
    }

    private function worksheet(int $index): ?Worksheet
    {
        if (!$this->hasSheet($index)) {
            return null;
        }

        return $this->spreadsheet->getSheet($index);
    }
}
