<?php
namespace App\Exports\Generated;

use App\Models\Generated\Invoice;
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

class InvoiceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected array $columns = ['invoice_no', 'invoice_date', 'purchase_order_no', 'purchase_order_date', 'buyer', 'vendor', 'buyer_address', 'vendor_address', 'dispatch_through', 'dispatch_date', 'line_items', 'subtotal', 'additional_discount', 'round_off', 'grand_total', 'invoice_summary', 'remark', 'auto_approve'];
    protected array $headingLabels = ['Invoice No.', 'Invoice Date', 'Purchase Order No.', 'Purchase Order Date', 'Buyer', 'Vendor', 'Buyer Address', 'Vendor Address', 'Dispatch Through', 'Dispatch Date', 'Line Items', 'Total', 'Additional Discount', 'Round Off', 'Grand Total', 'Invoice Summary', 'Remark / Comment', 'Auto Approve'];

    public function collection()
    {
        return Invoice::all();
    }

    public function headings(): array
    {
        return [$this->headingLabels];
    }

    public function map($row): array
    {
        return array_map(fn($col) => $row->{$col} ?? '', $this->columns);
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headingLabels));
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
                $sheet     = $event->sheet->getDelegate();
                $colCount  = count($this->headingLabels);
                $lastCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
                $totalRows = $sheet->getHighestRow();
                for ($row = 2; $row <= $totalRows; $row++) {
                    $isEven = $row % 2 === 1;
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isEven ? 'FEF2F2' : 'FFFFFF']],
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