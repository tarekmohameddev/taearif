<?php

declare(strict_types=1);

namespace App\Domain\Reports\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class ReportExcelExport implements WithMultipleSheets
{
    public function __construct(private readonly array $data) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->data as $sheetKey => $sectionData) {
            $rows = $this->flattenSection($sheetKey, $sectionData);
            if (!empty($rows)) {
                $sheets[] = new ReportSheetExport(
                    title: ucwords(str_replace('_', ' ', $sheetKey)),
                    rows: $rows,
                );
            }
        }

        return $sheets;
    }

    private function flattenSection(string $key, array $sectionData): array
    {
        // Summary sections are single objects; convert to row-per-key format
        if ($key === 'summary') {
            $rows = [['Metric', 'Value']];
            foreach ($sectionData as $field => $value) {
                if ($field === 'generated_at') {
                    continue;
                }
                if (is_array($value)) {
                    foreach ($value as $subKey => $subVal) {
                        $rows[] = [ucwords(str_replace('_', ' ', "{$field} {$subKey}")), $subVal];
                    }
                } else {
                    $rows[] = [ucwords(str_replace('_', ' ', $field)), $value];
                }
            }
            return $rows;
        }

        // Data sections are arrays of rows
        $items = $sectionData['data'] ?? $sectionData;
        if (empty($items) || !is_array($items)) {
            return [];
        }

        $firstRow = reset($items);
        if (!is_array($firstRow)) {
            return [];
        }

        $headers = array_map(
            fn ($k) => ucwords(str_replace('_', ' ', $k)),
            array_keys($firstRow)
        );

        $rows = [$headers];
        foreach ($items as $item) {
            $rows[] = array_values($item);
        }

        return $rows;
    }
}
