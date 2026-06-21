<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill Approval Export</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #292524; margin: 0; padding: 20px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #7f1d1d; }
        h2 { font-size: 15px; margin: 0; color: #7f1d1d; }
        .meta { font-size: 8px; color: #78716c; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #7f1d1d; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 6px; text-align: left; }
        td { padding: 5px 6px; border-bottom: 1px solid #e7e5e4; font-size: 8.5px; color: #292524; }
        tr:nth-child(even) td { background: #fafaf9; }
        .approved { color: #15803d; font-weight: 600; }
        .pending { color: #a16207; font-weight: 600; }
        .rejected { color: #b91c1c; font-weight: 600; }
        .footer { margin-top: 12px; padding-top: 8px; border-top: 1px solid #e7e5e4; font-size: 7px; color: #a8a29e; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2>Bill Approval Report</h2>
            <p class="meta">Exported by: {{ $exportedBy }} &bull; {{ $exportedAt }} &bull; {{ $rows->count() }} records</p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Company</th>
                <th>Location</th>
                <th>File</th>
                <th>Vendor</th>
                <th>Bill Date</th>
                <th>Bill No</th>
                <th>Scan Date</th>
                <th>Scanned By</th>
                <th>Status</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->company_name ?? '—' }}</td>
                <td>{{ $r->location_name ?? '—' }}</td>
                <td>{{ $r->File ?? '—' }}</td>
                <td>{{ $r->vendor_name ?? '—' }}</td>
                <td>{{ $r->bill_voucher_date ? \Carbon\Carbon::parse($r->bill_voucher_date)->format('d M Y') : '—' }}</td>
                <td>{{ $r->bill_no_voucher_no ?? '—' }}</td>
                <td>{{ $r->scan_date ? \Carbon\Carbon::parse($r->scan_date)->format('d M Y') : '—' }}</td>
                <td>{{ $r->scanned_by ?? '—' }}</td>
                <td class="{{ $r->Bill_Approved === 'Y' ? 'approved' : ($r->Bill_Approved === 'R' ? 'rejected' : 'pending') }}">
                    {{ $r->Bill_Approved === 'Y' ? 'Approved' : ($r->Bill_Approved === 'R' ? 'Rejected' : 'Pending') }}
                </td>
                <td>{{ $r->Bill_Approver_Remark ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">ScanOCR Application &bull; Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
