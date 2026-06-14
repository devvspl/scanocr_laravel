<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Temp Scan Export</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1c1917; background: #fff; }

/* Header */
.page-header { background: #7f1d1d; color: #fff; padding: 14px 20px; margin-bottom: 18px;
               display: flex; justify-content: space-between; align-items: flex-end; }
.page-header h1 { font-size: 16px; font-weight: 700; letter-spacing: .3px; }
.page-header .meta { font-size: 9px; opacity: .8; text-align: right; line-height: 1.6; }

/* Summary chips */
.summary { display: flex; gap: 12px; margin-bottom: 16px; padding: 0 20px; }
.chip { flex: 1; background: #fafaf9; border: 1px solid #e7e5e4; border-radius: 6px;
        padding: 8px 12px; }
.chip .label { font-size: 8px; color: #78716c; text-transform: uppercase; letter-spacing: .06em; }
.chip .value { font-size: 15px; font-weight: 700; color: #1c1917; margin-top: 2px; }

/* Table */
.table-wrap { padding: 0 20px 20px; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #7f1d1d; color: #fff; }
thead th { padding: 7px 8px; text-align: left; font-size: 8.5px; font-weight: 700;
           text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
tbody tr:nth-child(even) { background: #fafaf9; }
tbody tr:nth-child(odd)  { background: #fff; }
tbody td { padding: 6px 8px; border-bottom: 1px solid #f5f5f4; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }

/* Badges */
.badge { display: inline-block; padding: 2px 7px; border-radius: 9999px; font-size: 8px;
         font-weight: 700; }
.badge-yes     { background: #dcfce7; color: #15803d; }
.badge-no      { background: #f5f5f4; color: #78716c; }
.badge-pending { background: #fef9c3; color: #a16207; }
.badge-approved{ background: #dcfce7; color: #15803d; }
.badge-rejected{ background: #fee2e2; color: #b91c1c; }

/* Footer */
.page-footer { margin-top: 12px; padding: 10px 20px 0;
               border-top: 1px solid #e7e5e4;
               display: flex; justify-content: space-between;
               font-size: 8px; color: #a8a29e; }
</style>
</head>
<body>

<div class="page-header">
    <div>
        <h1>Temp Scan Report</h1>
        <div style="font-size:9px;opacity:.8;margin-top:3px;">Pending Scans — My Uploads</div>
    </div>
    <div class="meta">
        Exported by: {{ $exportedBy }}<br>
        Date: {{ $exportedAt }}<br>
        Total rows: {{ $rows->count() }}
    </div>
</div>

<div class="summary">
    <div class="chip">
        <div class="label">Total Scans</div>
        <div class="value">{{ $rows->count() }}</div>
    </div>
    <div class="chip">
        <div class="label">Final Submitted</div>
        <div class="value">{{ $rows->where('Final_Submit','Y')->count() }}</div>
    </div>
    <div class="chip">
        <div class="label">Bill Approved</div>
        <div class="value">{{ $rows->where('Bill_Approved','Y')->count() }}</div>
    </div>
    <div class="chip">
        <div class="label">Pending Approval</div>
        <div class="value">{{ $rows->whereNotIn('Bill_Approved',['Y','N'])->count() }}</div>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th style="width:50px">Scan ID</th>
                <th>Location</th>
                <th>File</th>
                <th style="width:80px">Scan Date</th>
                <th style="width:65px;text-align:center">Final Submit</th>
                <th style="width:65px;text-align:center">Bill Status</th>
                <th>Approver</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->Scan_Id }}</td>
                <td>{{ $r->location_name ?? '—' }}</td>
                <td style="word-break:break-all;max-width:110px;">{{ $r->File ?? '—' }}</td>
                <td>{{ $r->Temp_Scan_Date ? \Carbon\Carbon::parse($r->Temp_Scan_Date)->format('d M Y') : '—' }}</td>
                <td style="text-align:center">
                    @if($r->Final_Submit === 'Y')
                        <span class="badge badge-yes">Yes</span>
                    @else
                        <span class="badge badge-no">No</span>
                    @endif
                </td>
                <td style="text-align:center">
                    @if($r->Bill_Approved === 'Y')
                        <span class="badge badge-approved">Approved</span>
                    @elseif($r->Bill_Approved === 'N')
                        <span class="badge badge-rejected">Rejected</span>
                    @else
                        <span class="badge badge-pending">Pending</span>
                    @endif
                </td>
                <td>{{ $r->approver_name ?? '—' }}</td>
                <td style="max-width:100px;word-break:break-word;">
                    {{ $r->Bill_Approver_Remark ?? '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center;padding:20px;color:#a8a29e;">
                    No records found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="page-footer">
    <span>{{ config('app.name') }} — Temp Scan Report</span>
    <span>Generated {{ $exportedAt }}</span>
</div>

</body>
</html>
