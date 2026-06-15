<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;

class SuperScannerExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        $out = $this->rows->map(fn ($r) => [
            $r->company_name ?? '—',
            $r->total_scan ?? 0,
            $r->pending ?? 0,
            $r->approved ?? 0,
            $r->rejected ?? 0,
            $r->pending_naming ?? 0,
            $r->pending_verification ?? 0,
        ]);

        // Grand total row
        $out->push([
            'Grand Total',
            $this->rows->sum('total_scan'),
            $this->rows->sum('pending'),
            $this->rows->sum('approved'),
            $this->rows->sum('rejected'),
            $this->rows->sum('pending_naming'),
            $this->rows->sum('pending_verification'),
        ]);

        return $out;
    }

    public function headings(): array
    {
        return [
            ['Company', 'Scanning Process', '', '', '', 'Pending for Naming', 'Pending for Verification'],
            ['', 'Total Scan', 'Pending', 'Approved', 'Rejected', '', ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rows->count() + 3; // +2 for heading rows, +1 for grand total
        $lastColumn = 'G'; // 7 columns (A to G)

        // Apply borders to all data area (starting from row 1)
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        return [
            // First header row style
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF7F1D1D']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Second header row style
            2 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF7F1D1D']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Grand total row style
            $lastRow => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFEF9C3']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge cells for "Company" (A1:A2)
                $sheet->mergeCells('A1:A2');
                
                // Merge cells for "Scanning Process" (B1:E1)
                $sheet->mergeCells('B1:E1');
                
                // Merge cells for "Pending for Naming" (F1:F2)
                $sheet->mergeCells('F1:F2');
                
                // Merge cells for "Pending for Verification" (G1:G2)
                $sheet->mergeCells('G1:G2');
                
                // Add striped rows (alternating colors) - starting from row 3 (first data row)
                $lastRow = $this->rows->count() + 2; // Exclude grand total from stripes
                for ($row = 3; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) { // Even rows
                        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFF9FAFB'], // Light gray
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
