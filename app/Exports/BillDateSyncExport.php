<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// ═══════════════════════════════════════════════════════════════════════════
// Main export — 4 sheets
// Sheet 1: Overall summary by financial year
// Sheet 2: Bill Date coverage breakdown by FY (grouped, small)
// Sheet 3: FY mapping breakdown by FY (grouped, small)
// Sheet 4: Pending detail rows (1,872 rows — fast)
// ═══════════════════════════════════════════════════════════════════════════
class BillDateSyncExport implements WithMultipleSheets
{
    public function __construct(private array $totals) {}

    public function sheets(): array
    {
        return [
            new BillDateSyncSummarySheet($this->totals),
            new BillDateSyncFyBreakdownSheet(
                'Bill Date by FY',
                'FF166534',
                'FFF0FDF4'
            ),
            new BillDateSyncFyBreakdownSheet(
                'FY Mapping by FY',
                'FF7F1D1D',
                'FFFFF1F2',
                mapped: true
            ),
            new BillDateSyncPendingSheet(),
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Sheet 1 — Overall summary by financial year
// ═══════════════════════════════════════════════════════════════════════════
class BillDateSyncSummarySheet implements
    FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(private array $totals) {}

    public function title(): string { return 'Summary'; }

    public function collection(): Collection
    {
        $rows = DB::table('scan_file as sf')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 'sf.year_id')
            ->where('sf.Is_Deleted', 'N')
            ->selectRaw("
                COALESCE(fy.label, 'Unmapped') AS fy_label,
                COUNT(*)                        AS total,
                SUM(sf.bill_date IS NOT NULL)   AS with_bill_date,
                SUM(sf.year_id   IS NOT NULL)   AS with_fy_mapped,
                SUM(sf.bill_date IS NULL)        AS pending
            ")
            ->groupBy('fy.id', 'fy.label')
            ->orderByRaw('MIN(fy.start_date) ASC')
            ->get();

        $data = $rows->map(fn ($r) => [
            $r->fy_label,
            (int) $r->total,
            (int) $r->with_bill_date,
            (int) $r->with_fy_mapped,
            (int) $r->pending,
            $r->total > 0 ? round($r->with_bill_date / $r->total * 100, 1) . '%' : '0%',
            $r->total > 0 ? round($r->with_fy_mapped  / $r->total * 100, 1) . '%' : '0%',
        ]);

        $t = $this->totals;
        $data->push([
            'Grand Total',
            $t['total'],
            $t['with_bill_date'],
            $t['with_fy_mapped'],
            $t['pending'],
            $t['total'] > 0 ? round($t['with_bill_date'] / $t['total'] * 100, 1) . '%' : '0%',
            $t['total'] > 0 ? round($t['with_fy_mapped']  / $t['total'] * 100, 1) . '%' : '0%',
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Bill Date Sync — Summary Report', '', '', '', '', '', ''],
            ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', '', ''],
            ['Financial Year', 'Total Scans', 'With Bill Date', 'FY Mapped', 'Pending', 'Bill Date %', 'FY %'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();
        $sheet->getStyle("A3:G{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ]);
        return [
            1       => ['font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF7F1D1D']]],
            2       => ['font' => ['size' => 9, 'color' => ['argb' => 'FF9CA3AF']]],
            3       => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7F1D1D']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $last   => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF9C3']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $s    = $e->sheet->getDelegate();
                $last = $s->getHighestRow();
                $s->mergeCells('A1:G1');
                $s->mergeCells('A2:G2');
                $s->freezePane('A4');
                for ($r = 4; $r < $last; $r++) {
                    if ($r % 2 === 0) {
                        $s->getStyle("A{$r}:G{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
                        ]);
                    }
                }
                $s->getStyle("B4:G{$last}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
            },
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Sheets 2 & 3 — FY-grouped breakdown (doc-type level, still aggregated)
// ═══════════════════════════════════════════════════════════════════════════
class BillDateSyncFyBreakdownSheet implements
    FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private string $sheetTitle,
        private string $headerArgb,
        private string $stripeArgb,
        private bool   $mapped = false,
    ) {}

    public function title(): string { return $this->sheetTitle; }

    public function collection(): Collection
    {
        $rows = DB::table('scan_file as sf')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 'sf.year_id')
            ->where('sf.Is_Deleted', 'N')
            ->selectRaw("
                COALESCE(fy.label, 'Unmapped') AS fy_label,
                COALESCE(sf.Doc_Type, 'Unknown') AS doc_type,
                COUNT(*)                          AS total,
                SUM(sf.bill_date IS NOT NULL)     AS with_bill_date,
                SUM(sf.year_id   IS NOT NULL)     AS with_fy_mapped,
                SUM(sf.bill_date IS NULL)          AS pending
            ")
            ->groupBy('fy.id', 'fy.label', 'sf.Doc_Type')
            ->orderByRaw('MIN(fy.start_date) ASC, sf.Doc_Type ASC')
            ->get();

        return $rows->map(function ($r) {
            $synced = $this->mapped ? (int)$r->with_fy_mapped : (int)$r->with_bill_date;
            $pct    = $r->total > 0 ? round($synced / $r->total * 100, 1) . '%' : '0%';
            return [
                $r->fy_label,
                $r->doc_type,
                (int) $r->total,
                $synced,
                (int) $r->pending,
                $pct,
            ];
        });
    }

    public function headings(): array
    {
        $col = $this->mapped ? 'FY Mapped' : 'With Bill Date';
        return [
            [$this->sheetTitle . ' — Bill Date Sync Report', '', '', '', '', ''],
            ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', ''],
            ['Financial Year', 'Doc Type', 'Total', $col, 'Pending', 'Coverage %'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();
        $sheet->getStyle("A3:F{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ]);
        return [
            1     => ['font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => $this->headerArgb]]],
            2     => ['font' => ['size' => 9, 'color' => ['argb' => 'FF9CA3AF']]],
            3     => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->headerArgb]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $s    = $e->sheet->getDelegate();
                $last = $s->getHighestRow();
                $s->mergeCells('A1:F1');
                $s->mergeCells('A2:F2');
                $s->freezePane('A4');
                for ($r = 4; $r <= $last; $r++) {
                    if ($r % 2 === 0) {
                        $s->getStyle("A{$r}:F{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $this->stripeArgb]],
                        ]);
                    }
                }
                $s->getStyle("C4:F{$last}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
            },
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Sheet 4 — Pending detail rows (only ~1,872 rows — fast)
// ═══════════════════════════════════════════════════════════════════════════
class BillDateSyncPendingSheet implements
    FromQuery, WithHeadings, WithMapping, WithStyles,
    ShouldAutoSize, WithEvents, WithTitle, WithChunkReading
{
    public function title(): string { return 'Pending Sync'; }

    public function chunkSize(): int { return 500; }

    public function query()
    {
        return DB::table('scan_file as sf')
            ->leftJoin('financial_years as fy', 'fy.id', '=', 'sf.year_id')
            ->where('sf.Is_Deleted', 'N')
            ->whereNull('sf.bill_date')
            ->selectRaw("sf.Scan_Id, sf.Scan_Date, sf.Doc_Type, sf.File, sf.Location AS location_name, sf.Bill_Approved, sf.year_id")
            ->orderBy('sf.Scan_Id');
    }

    public function map($row): array
    {
        return [
            $row->Scan_Id,
            $row->Scan_Date ? \Carbon\Carbon::parse($row->Scan_Date)->format('d M Y') : '—',
            $row->Doc_Type      ?? '—',
            $row->File          ?? '—',
            $row->location_name ?? '—',
            match ($row->Bill_Approved ?? null) {
                'Y'     => 'Approved',
                'N'     => 'Rejected',
                default => 'Pending',
            },
            $row->year_id ? 'Mapped' : 'Not Mapped',
        ];
    }

    public function headings(): array
    {
        return [
            ['Pending Sync — Records Missing Bill Date', '', '', '', '', '', ''],
            ['Generated: ' . now()->format('d M Y H:i'), '', '', '', '', '', ''],
            ['Scan ID', 'Scan Date', 'Doc Type', 'File', 'Location', 'Bill Status', 'FY Status'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();
        $sheet->getStyle("A3:G{$last}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']]],
        ]);
        return [
            1 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FF92400E']]],
            2 => ['font' => ['size' => 9, 'color' => ['argb' => 'FF9CA3AF']]],
            3 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF92400E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $s    = $e->sheet->getDelegate();
                $last = $s->getHighestRow();
                $s->mergeCells('A1:G1');
                $s->mergeCells('A2:G2');
                $s->freezePane('A4');
                for ($r = 4; $r <= $last; $r++) {
                    if ($r % 2 === 0) {
                        $s->getStyle("A{$r}:G{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEFCE8']],
                        ]);
                    }
                }
                $s->getStyle("A4:A{$last}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            },
        ];
    }
}
