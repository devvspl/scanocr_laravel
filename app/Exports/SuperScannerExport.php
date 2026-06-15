<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuperScannerExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        $out = $this->rows->map(fn($r) => [
            $r->company_name,
            $r->total_scan,
            $r->pending,
            $r->approved,
            $r->rejected,
            $r->pending_naming,
            $r->pending_verification,
        ]);

        // Grand total row
        $out->push([
            'GRAND TOTAL',
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
            'Company',
            'Total Scan',
            'Pending',
            'Approved',
            'Rejected',
            'Pending for Naming',
            'Pending for Verification',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rows->count() + 2; // +1 heading +1 grand total
        return [
            1          => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F5F5F4']]],
            $lastRow   => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FEF9C3']]],
        ];
    }
}
