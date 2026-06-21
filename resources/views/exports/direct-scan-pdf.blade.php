<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Direct Scan Export</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #292524; background: #fff; padding: 20px; }
        .page-header { background: #7f1d1d; color: #fff; padding: 14px 20px; margin-bottom: 16px; border-radius: 4px; }
        .page-header h1 { font-size: 15px; font-weight: 700; }
        .page-header .meta { font-size: 8px; opacity: .8; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #7f1d1d; color: #fff; }
        thead th { padding: 6px 6px; text-align: left; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
        tbody tr:nth-child(even) { background: #fafaf9; }
        tbody td { padding: 5px 6px; border-bottom: 1px solid #f0eeec; font-size: 8.5px; vertical-align: middle; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-size: 7px; font-weight: 700; }
        .badge-yes { background: #dcfce7; color: #15803d; }
        .badge-no { background: #f5f5f4; color: #78716c; }
        .badge-approved { background: #dcfce7; color: #15803d; }
        .badge-pending { background: #fef9c3; color: #a16207; }
        .badge-rejected { background: #fee2e2; color: #b91c1c; }
        .footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e7e5e4; font-size: 7px; color: #a8a29e; text-align: center; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Direct Scan Report</h1>
        <div class="meta">Exported by: {{ $exportedBy }} &bull; {{ $exportedAt }} &bull; {{ $rows->count() }} records</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Location</th>
                <th>Document Name</th>
                <th>File</th>
                <th>Scan Date</th>
                <th>Final Submit</th>
                <th>Status</th>
                <th>Approver</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->location_name ?? '—' }}</td>
                <td>{{ $r->Document_name ?? '—' }}</td>
                <td style="word-break:break-all;max-width:100px">{{ $r->File ?? '—' }}</td>
                <td>{{ $r->Scan_Date ? \Carbon\Carbon::parse($r->Scan_Date)->format('d M Y') : '—' }}</td>
                <td style="text-align:center">
                    <span class="badge {{ ($r->Final_Submit ?? '') === 'Y' ? 'badge-yes' : 'badge-no' }}">{{ ($r->Final_Submit ?? '') === 'Y' ? 'Yes' : 'No' }}</span>
                </td>
                <td style="text-align:center">
                    @if(($r->Bill_Approved ?? '') === 'Y')
                        <span class="badge badge-approved">Approved</span>
                    @elseif(($r->Bill_Approved ?? '') === 'R' || ($r->temp_scan_reject ?? '') === 'Y')
                        <span class="badge badge-rejected">Rejected</span>
                    @else
                        <span class="badge badge-pending">Pending</span>
                    @endif
                </td>
                <td>{{ $r->approver_name ?? '—' }}</td>
                <td style="max-width:90px;word-break:break-word">{{ $r->Bill_Approver_Remark ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;padding:20px;color:#a8a29e">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">ScanOCR Application &bull; Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
