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

class OutstandingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected ?string $ageing;

    public function __construct(?string $ageing = null)
    {
        $this->ageing = $ageing;
    }

    public function collection()
    {
        $company = Company::getDefault();
        $today = now();

        $query = SaleInvoice::with('party:id,name,display_name,gstin,phone,mobile')
            ->where('company_id', $company?->id)
            ->where('status', 'approved')
            ->where('amount_due', '>', 0);

        if ($this->ageing) {
            if ($this->ageing === '0-30') $query->whereDate('due_date', '>=', $today->copy()->subDays(30));
            elseif ($this->ageing === '31-60') $query->whereDate('due_date', '<', $today->copy()->subDays(30))->whereDate('due_date', '>=', $today->copy()->subDays(60));
            elseif ($this->ageing === '61-90') $query->whereDate('due_date', '<', $today->copy()->subDays(60))->whereDate('due_date', '>=', $today->copy()->subDays(90));
            elseif ($this->ageing === '90+') $query->whereDate('due_date', '<', $today->copy()->subDays(90));
        }

        return $query->orderBy('due_date')->get();
    }

    public function headings(): array
    {
        return [['Invoice #', 'Invoice Date', 'Due Date', 'Customer', 'GSTIN', 'Phone', 'Grand Total', 'Paid', 'Due', 'Overdue Days', 'Ageing']];
    }

    public function map($row): array
    {
        $dueDate = $row->due_date ?? $row->invoice_date;
        $overdueDays = $dueDate->isPast() ? (int) $dueDate->diffInDays(now()) : 0;

        $ageing = 'Current';
        if ($overdueDays > 90) $ageing = '90+ days';
        elseif ($overdueDays > 60) $ageing = '61-90 days';
        elseif ($overdueDays > 30) $ageing = '31-60 days';
        elseif ($overdueDays > 0) $ageing = '1-30 days';

        return [
            $row->invoice_number,
            $row->invoice_date->format('d-m-Y'),
            $dueDate->format('d-m-Y'),
            $row->party?->display_name ?? $row->party?->name ?? '',
            $row->party?->gstin ?? '',
            $row->party?->phone ?? $row->party?->mobile ?? '',
            (float) $row->grand_total,
            (float) $row->amount_paid,
            (float) $row->amount_due,
            $overdueDays,
            $ageing,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'K';
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
                $lastCol = 'K';
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
