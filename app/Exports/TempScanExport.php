<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TempScanExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    ShouldAutoSize
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
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF7F1D1D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
