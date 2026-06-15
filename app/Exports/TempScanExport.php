<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;

class TempScanExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($r) => [
            $r->Scan_Id,
            $r->location_name  ?? '—',
            $r->File           ?? '—',
            $r->Temp_Scan_Date
                ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y H:i')
                : '—',
            $r->Final_Submit   === 'Y' ? 'Yes' : 'No',
            match ($r->Bill_Approved ?? null) {
                'Y'     => 'Approved',
                'N'     => 'Rejected',
                default => 'Pending',
            },
            $r->approver_name       ?? '—',
            $r->Bill_Approver_Remark ?? '—',
        ]);
    }

    public function headings(): array
    {
        return ['Scan ID', 'Location', 'File', 'Scan Date',
                'Final Submit', 'Bill Approved', 'Approver', 'Remark'];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rows->count() + 1; // +1 for heading
        $lastColumn = 'H'; // 8 columns (A to H)

        // Apply borders to all data area
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        return [
            // Header row style
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF7F1D1D']
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
                
                // Add striped rows (alternating colors) - starting from row 2 (first data row)
                $lastRow = $this->rows->count() + 1;
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 == 0) { // Even rows
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
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
