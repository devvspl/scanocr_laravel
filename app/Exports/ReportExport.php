<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Events\AfterSheet;

class ReportExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithEvents
{
    private Collection $rows;
    private array $headings;
    private string $title;

    public function __construct(Collection $rows, array $headings, string $title = 'Report')
    {
        $this->rows     = $rows;
        $this->headings = $headings;
        $this->title    = $title;
    }

    public function collection(): Collection
    {
        return $this->rows->values()->map(function ($row, $index) {
            $values = is_object($row) ? array_values((array) $row) : array_values((array) $row);
            // Prepend Sr No
            array_unshift($values, $index + 1);
            return $values;
        });
    }

    public function headings(): array
    {
        // Title row + heading row
        $colCount = count($this->headings) + 1; // +1 for Sr No
        return [
            // Row 1: Report title (will be merged)
            array_merge([$this->title], array_fill(0, $colCount - 1, '')),
            // Row 2: Column headings
            array_merge(['Sr No'], $this->headings),
        ];
    }

    public function title(): string
    {
        return substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $this->title), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $colCount  = count($this->headings) + 1;
        $lastCol   = Coordinate::stringFromColumnIndex($colCount);
        $lastRow   = $this->rows->count() + 2; // +2 for title row + heading row

        return [
            // Row 1: Title row
            1 => [
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1C1917']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F4']],
            ],
            // Row 2: Column headings
            2 => [
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7F1D1D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $colCount  = count($this->headings) + 1;
                $lastCol   = Coordinate::stringFromColumnIndex($colCount);
                $lastRow   = $this->rows->count() + 2;

                // Merge title row
                $sheet->mergeCells("A1:{$lastCol}1");

                // Row height for title
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Borders on heading row
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF44403C']],
                    ],
                ]);

                // Borders + font on data area
                if ($lastRow > 2) {
                    $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD6D3D1']],
                        ],
                        'font' => ['size' => 9],
                    ]);
                }

                // Outer border around entire content
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF78716C']],
                    ],
                ]);

                // Sr No column center align
                $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Skip alternating row colors for large datasets (>5000 rows) — too memory intensive
                if ($lastRow <= 5002) {
                    for ($row = 3; $row <= $lastRow; $row++) {
                        if ($row % 2 === 1) {
                            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFAFAF9']],
                            ]);
                        }
                    }
                }

                // Freeze panes below heading row
                $sheet->freezePane('A3');
            },
        ];
    }
}
