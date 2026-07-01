<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Events\AfterSheet;

class ReportExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithEvents,
    WithCustomValueBinder
{
    private Collection $rows;
    private array      $headings;
    private string     $title;
    private int        $rowCount;

    // Threshold: above this, skip per-row styling to save memory
    private const LARGE_DATASET = 5000;

    public function __construct(Collection $rows, array $headings, string $title = 'Report')
    {
        $this->rows     = $rows;
        $this->headings = $headings;
        $this->title    = $title;
        $this->rowCount = $rows->count();
    }

    public function collection(): Collection
    {
        // Use lazy map to avoid building a second full copy in memory
        return $this->rows->values()->map(function ($row, $index) {
            $values = array_values((array) $row);
            array_unshift($values, $index + 1); // Sr No
            return $values;
        });
    }

    public function headings(): array
    {
        $colCount = count($this->headings) + 1; // +1 for Sr No
        return [
            array_merge([$this->title], array_fill(0, $colCount - 1, '')),
            array_merge(['Sr No'], $this->headings),
        ];
    }

    public function title(): string
    {
        return substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $this->title), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Title row
            1 => [
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1C1917']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F4']],
            ],
            // Header row
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
                $sheet    = $event->sheet->getDelegate();
                $colCount = count($this->headings) + 1;
                $lastCol  = Coordinate::stringFromColumnIndex($colCount);
                $lastRow  = $this->rowCount + 2; // +2 for title + header rows

                // Merge title row across all columns
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Header row borders
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF44403C']]],
                ]);

                if ($lastRow > 2) {
                    if ($this->rowCount <= self::LARGE_DATASET) {
                        // Full styling for small datasets
                        $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD6D3D1']]],
                            'font'    => ['size' => 9],
                        ]);

                        // Alternating row colors
                        for ($row = 3; $row <= $lastRow; $row++) {
                            if ($row % 2 === 1) {
                                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                                    ->setFillType(Fill::FILL_SOLID)
                                    ->getStartColor()->setARGB('FFFAFAF9');
                            }
                        }
                    } else {
                        // Large dataset: only apply font size to data range (range-based, no per-row loop)
                        $sheet->getStyle("A3:{$lastCol}{$lastRow}")->getFont()->setSize(9);
                    }

                    // Sr No column center-aligned
                    $sheet->getStyle("A3:A{$lastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Outer border
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF78716C']]],
                ]);

                // Auto-size only for small datasets — too slow for large ones
                if ($this->rowCount <= self::LARGE_DATASET) {
                    foreach (range(1, $colCount) as $col) {
                        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                    }
                } else {
                    // Fixed column widths for large datasets
                    $sheet->getColumnDimension('A')->setWidth(8);  // Sr No
                    foreach (range(2, $colCount) as $col) {
                        $sheet->getColumnDimensionByColumn($col)->setWidth(18);
                    }
                }

                // Freeze header rows
                $sheet->freezePane('A3');
            },
        ];
    }
}
