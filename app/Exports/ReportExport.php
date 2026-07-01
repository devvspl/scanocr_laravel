<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromGenerator;
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

/**
 * Memory-efficient export using FromGenerator.
 * Rows are yielded one at a time — no full collection in RAM.
 * Per-row styling is skipped for large datasets (> LARGE_DATASET).
 */
class ReportExport extends DefaultValueBinder implements
    FromGenerator,
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

    private const LARGE_DATASET = 5000;

    public function __construct(Collection $rows, array $headings, string $title = 'Report')
    {
        $this->rows     = $rows;
        $this->headings = $headings;
        $this->title    = $title;
        $this->rowCount = $rows->count();
    }

    /**
     * FromGenerator: yields one row array at a time — constant memory.
     */
    public function generator(): \Generator
    {
        $index = 1;
        foreach ($this->rows as $row) {
            $values = array_values((array) $row);
            array_unshift($values, $index++); // Sr No
            yield $values;
        }
    }

    public function headings(): array
    {
        $colCount = count($this->headings) + 1; // +1 for Sr No
        return [
            // Row 1: report title (merged in AfterSheet)
            array_merge([$this->title], array_fill(0, $colCount - 1, '')),
            // Row 2: column headers
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
            1 => [
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF1C1917']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF5F5F4']],
            ],
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
                $lastRow  = $this->rowCount + 2; // +2 for title + header

                // Merge title row
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // Header borders
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF44403C']]],
                ]);

                if ($lastRow > 2) {
                    // Data font size — single range call, no loop
                    $sheet->getStyle("A3:{$lastCol}{$lastRow}")->getFont()->setSize(9);

                    // Sr No center aligned
                    $sheet->getStyle("A3:A{$lastRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Thin borders on data — only for small datasets
                    if ($this->rowCount <= self::LARGE_DATASET) {
                        $sheet->getStyle("A3:{$lastCol}{$lastRow}")->applyFromArray([
                            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD6D3D1']]],
                        ]);

                        // Alternating row colors — only small datasets
                        for ($row = 3; $row <= $lastRow; $row += 2) {
                            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFFAFAF9');
                        }
                    }
                }

                // Outer border
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF78716C']]],
                ]);

                // Column widths
                if ($this->rowCount <= self::LARGE_DATASET) {
                    // Auto-size for small datasets
                    for ($col = 1; $col <= $colCount; $col++) {
                        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
                    }
                } else {
                    // Fixed widths for large datasets — auto-size reads every cell (slow + memory)
                    $sheet->getColumnDimension('A')->setWidth(7);   // Sr No
                    for ($col = 2; $col <= $colCount; $col++) {
                        $sheet->getColumnDimensionByColumn($col)->setWidth(20);
                    }
                }

                $sheet->freezePane('A3');
            },
        ];
    }
}
