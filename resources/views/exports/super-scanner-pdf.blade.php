<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Scan Summary Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #292524; background: #fff; padding: 20px; }
        .page-header { background: #7f1d1d; color: #fff; padding: 14px 20px; margin-bottom: 16px; border-radius: 4px; }
        .page-header h1 { font-size: 15px; font-weight: 700; }
        .page-header .meta { font-size: 8px; opacity: .8; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #7f1d1d; color: #fff; }
        thead th { padding: 6px 6px; text-align: center; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        thead th:nth-child(1), thead th:nth-child(2) { text-align: left; }
        tbody tr:nth-child(even) { background: #fafaf9; }
        tbody td { padding: 5px 6px; border-bottom: 1px solid #f0eeec; font-size: 9px; text-align: center; vertical-align: middle; }
        tbody td:nth-child(1) { text-align: center; color: #a8a29e; width: 30px; }
        tbody td:nth-child(2) { text-align: left; font-weight: 500; }
        .num { font-weight: 600; }
        tr.grand-total td { background: #fef9c3 !important; font-weight: 700; border-top: 2px solid #e7e5e4; }
        .footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e7e5e4; font-size: 7px; color: #a8a29e; text-align: center; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Scan Summary Report</h1>
        <div class="meta">
            Exported by: {{ $exportedBy }} &bull; {{ $exportedAt }}
            @if($fromDate || $toDate) &bull; Period: {{ $fromDate ?: '—' }} to {{ $toDate ?: '—' }} @endif
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Company</th>
                <th>Total</th>
                <th>Pending</th>
                <th>Approved</th>
                <th>Rejected</th>
                <th>Pending Naming</th>
                <th>Pending Verify</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->company_name }}</td>
                <td class="num">{{ $r->total_scan ?? 0 }}</td>
                <td class="num">{{ $r->pending ?? 0 }}</td>
                <td class="num">{{ $r->approved ?? 0 }}</td>
                <td class="num">{{ $r->rejected ?? 0 }}</td>
                <td class="num">{{ $r->pending_naming ?? 0 }}</td>
                <td class="num">{{ $r->pending_verification ?? 0 }}</td>
            </tr>
            @endforeach
            <tr class="grand-total">
                <td>—</td>
                <td>Grand Total</td>
                <td>{{ $rows->sum('total_scan') }}</td>
                <td>{{ $rows->sum('pending') }}</td>
                <td>{{ $rows->sum('approved') }}</td>
                <td>{{ $rows->sum('rejected') }}</td>
                <td>{{ $rows->sum('pending_naming') }}</td>
                <td>{{ $rows->sum('pending_verification') }}</td>
            </tr>
        </tbody>
    </table>
    <div class="footer">ScanOCR Application &bull; Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
