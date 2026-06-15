<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:Arial,sans-serif;font-size:10px;margin:0;padding:16px}
        h1{font-size:14px;margin:0 0 4px;color:#1c1917}
        .meta{font-size:9px;color:#78716c;margin-bottom:12px}
        table{width:100%;border-collapse:collapse}
        th{background:#f5f5f4;color:#78716c;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:6px 8px;border-bottom:2px solid #e7e5e4;text-align:center}
        th:first-child{text-align:left}
        td{padding:5px 8px;border-bottom:1px solid #f0eeec;font-size:9.5px;color:#292524;text-align:center}
        td:first-child{text-align:left}
        tr:last-child td{border-bottom:none;background:#fef9c3;font-weight:700}
        .g1{background:#fafaf9}
        .num{font-weight:600}
    </style>
</head>
<body>
    <h1>Scan Summary Report</h1>
    <div class="meta">
        Exported by {{ $exportedBy }} &bull; {{ $exportedAt }}
        @if($fromDate || $toDate)
            &bull; Period: {{ $fromDate ?: '—' }} to {{ $toDate ?: '—' }}
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">Company</th>
                <th colspan="4" style="border-left:1px solid #d6d3d1">Old Way</th>
                <th colspan="2" style="border-left:1px solid #d6d3d1">Current</th>
                <th colspan="2" style="border-left:1px solid #d6d3d1">Pending</th>
            </tr>
            <tr>
                <th style="border-left:1px solid #d6d3d1">Total</th>
                <th>Pending</th>
                <th>Approved</th>
                <th>Rejected</th>
                <th style="border-left:1px solid #d6d3d1">Total</th>
                <th>Rejected</th>
                <th style="border-left:1px solid #d6d3d1">Naming</th>
                <th>Verify</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $r)
            <tr class="{{ $i % 2 === 0 ? 'g1' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->company_name }}</td>
                <td class="num">{{ $r->old_total_scan }}</td>
                <td class="num">{{ $r->old_pending }}</td>
                <td class="num">{{ $r->old_approved }}</td>
                <td class="num">{{ $r->old_rejected }}</td>
                <td class="num">{{ $r->total_scan }}</td>
                <td class="num">{{ $r->rejected }}</td>
                <td class="num">{{ $r->pending_naming }}</td>
                <td class="num">{{ $r->pending_verification }}</td>
            </tr>
            @endforeach
            <tr>
                <td></td>
                <td>GRAND TOTAL</td>
                <td>{{ $rows->sum('old_total_scan') }}</td>
                <td>{{ $rows->sum('old_pending') }}</td>
                <td>{{ $rows->sum('old_approved') }}</td>
                <td>{{ $rows->sum('old_rejected') }}</td>
                <td>{{ $rows->sum('total_scan') }}</td>
                <td>{{ $rows->sum('rejected') }}</td>
                <td>{{ $rows->sum('pending_naming') }}</td>
                <td>{{ $rows->sum('pending_verification') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
