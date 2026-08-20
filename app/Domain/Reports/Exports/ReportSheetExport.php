<?php

declare(strict_types=1);

namespace App\Domain\Reports\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

final class ReportSheetExport implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private readonly string $title,
        private readonly array  $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                if (empty($this->rows)) {
                    return;
                }

                $colCount = count($this->rows[0] ?? []);
                if ($colCount === 0) {
                    return;
                }

                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

                $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E4D8C'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                for ($i = 1; $i <= $colCount; $i++) {
                    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
                }
            },
        ];
    }
}
