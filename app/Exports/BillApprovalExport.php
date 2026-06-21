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

class BillApprovalExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn($r, $i) => [
            $i + 1,
            $r->company_name ?? '—',
            $r->location_name ?? '—',
            $r->File ?? '—',
            $r->vendor_name ?? '—',
            $r->bill_voucher_date
                ? \Carbon\Carbon::parse($r->bill_voucher_date)->format('d M Y')
                : '—',
            $r->bill_no_voucher_no ?? '—',
            $r->scan_date
                ? \Carbon\Carbon::parse($r->scan_date)->format('d M Y')
                : '—',
            $r->scanned_by ?? '—',
            match ($r->Bill_Approved ?? null) {
                'Y'     => 'Approved',
                'R'     => 'Rejected',
                'N'     => 'Pending',
                default => 'Pending',
            },
            $r->Bill_Approver_Remark ?? '—',
        ]);
    }

    public function headings(): array
    {
        return ['#', 'Company', 'Location', 'File', 'Vendor',
                'Bill Date', 'Bill No', 'Scan Date', 'Scanned By', 'Status', 'Remark'];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow    = $this->rows->count() + 1;
        $lastColumn = 'K'; // 11 columns

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD6D3D1'],
                ],
            ],
        ]);

        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF7F1D1D'], // theme dark red
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $this->rows->count() + 1;

                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFFAFAF9'],
                            ],
                        ]);
                    }
                }
            },
        ];
    }
}
