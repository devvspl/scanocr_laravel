<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\SaleInvoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesRegisterExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected ?string $dateFrom;
    protected ?string $dateTo;
    protected ?string $status;

    public function __construct(?string $dateFrom = null, ?string $dateTo = null, ?string $status = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
    }

    public function collection()
    {
        $company = Company::getDefault();
        $query = SaleInvoice::with('party:id,name,display_name,gstin')
            ->where('company_id', $company?->id)
            ->whereIn('status', ['approved', 'submitted']);

        if ($this->dateFrom) $query->whereDate('invoice_date', '>=', $this->dateFrom);
        if ($this->dateTo) $query->whereDate('invoice_date', '<=', $this->dateTo);
        if ($this->status) $query->where('status', $this->status);

        return $query->orderByDesc('invoice_date')->get();
    }

    public function headings(): array
    {
        return [['Invoice #', 'Date', 'Customer', 'GSTIN', 'Taxable', 'CGST', 'SGST', 'IGST', 'Total Tax', 'Grand Total', 'Paid', 'Due', 'Status']];
    }

    public function map($row): array
    {
        return [
            $row->invoice_number,
            $row->invoice_date->format('d-m-Y'),
            $row->party?->display_name ?? $row->party?->name ?? '',
            $row->party?->gstin ?? '',
            (float) $row->taxable_amount,
            (float) $row->cgst_amount,
            (float) $row->sgst_amount,
            (float) $row->igst_amount,
            (float) $row->total_tax,
            (float) $row->grand_total,
            (float) $row->amount_paid,
            (float) $row->amount_due,
            ucfirst($row->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'M';
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B91C1C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'M';
                $totalRows = $sheet->getHighestRow();
                for ($row = 2; $row <= $totalRows; $row++) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $row % 2 === 0 ? 'FEF2F2' : 'FFFFFF']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FECACA']]],
                    ]);
                }
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '7F1D1D']]],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }
}
