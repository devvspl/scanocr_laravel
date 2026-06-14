<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Direct Scan Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #333; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-center { text-align: center; }
        .badge { padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; }
        .badge-yes { background: #d4edda; color: #155724; }
        .badge-no { background: #f8f9fa; color: #6c757d; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Direct Scan Export</h1>
        <p>Generated on {{ date('d M Y H:i') }}</p>
        <p>Total Records: {{ $scans->count() }}</p>
    </div>

    @if($scans->count() > 0)
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Location</th>
                <th>Document Name</th>
                <th>File</th>
                <th>Scan Date</th>
                <th>Final Submit</th>
                <th>Bill Approved</th>
                <th>Approver</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scans as $index => $scan)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $scan->location_name ?? '—' }}</td>
                <td>{{ $scan->Document_name ?? '—' }}</td>
                <td>{{ $scan->File ?? '—' }}</td>
                <td class="text-center">{{ $scan->Scan_Date ? \Carbon\Carbon::parse($scan->Scan_Date)->format('d M Y') : '—' }}</td>
                <td class="text-center">
                    @if($scan->Final_Submit === 'Y')
                        <span class="badge badge-yes">Yes</span>
                    @else
                        <span class="badge badge-no">No</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($scan->Bill_Approved === 'Y')
                        <span class="badge badge-approved">Approved</span>
                    @elseif($scan->Bill_Approved === 'R')
                        <span class="badge badge-rejected">Rejected</span>
                    @else
                        <span class="badge badge-pending">Pending</span>
                    @endif
                </td>
                <td>{{ $scan->approver_name ?? '—' }}</td>
                <td>{{ $scan->Bill_Approver_Remark ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 40px; color: #666;">
        <p>No direct scans found to export.</p>
    </div>
    @endif
</body>
</html>