<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Credit Note {{ $creditNote->credit_note_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1c1917;
            background: #fff;
        }

        .page {
            padding: 32px 36px;
        }

        .hdr {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .hdr-l {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .hdr-r {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
        }

        .co-name {
            font-size: 16px;
            font-weight: bold;
        }

        .co-addr {
            font-size: 8.5px;
            color: #444;
            line-height: 1.6;
            margin-top: 3px;
        }

        .co-gstin {
            font-size: 8px;
            color: #999;
            margin-top: 3px;
            font-family: monospace;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #b91c1c;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .num {
            font-size: 10px;
            color: #333;
            margin-top: 4px;
            font-family: monospace;
        }

        .rule {
            border: none;
            border-top: 2px solid #b91c1c;
            margin: 12px 0 10px;
        }

        .meta {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .meta-c {
            display: table-cell;
            vertical-align: top;
            padding-right: 12px;
        }

        .lbl {
            font-size: 7px;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .val {
            font-size: 9.5px;
            color: #1c1917;
            line-height: 1.5;
        }

        .bold {
            font-size: 11px;
            font-weight: bold;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .tbl thead tr {
            background: #b91c1c;
        }

        .tbl th {
            padding: 6px;
            font-size: 7.5px;
            font-weight: bold;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .tbl th.r {
            text-align: right;
        }

        .tbl th.l {
            text-align: left;
        }

        .tbl td {
            padding: 6px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .tbl td.r {
            text-align: right;
        }

        .tbl td.mono {
            font-family: monospace;
            font-size: 8.5px;
        }

        .bot {
            display: table;
            width: 100%;
            border-top: 1px solid #ccc;
        }

        .bot-l {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding: 12px 12px 12px 0;
        }

        .bot-r {
            display: table-cell;
            width: 45%;
            vertical-align: bottom;
            padding: 12px 0 12px 12px;
            border-left: 1px solid #ccc;
        }

        .tot {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .tot-l {
            display: table-cell;
            font-size: 9px;
            color: #555;
        }

        .tot-r {
            display: table-cell;
            font-size: 9px;
            color: #000;
            font-weight: 500;
            text-align: right;
        }

        .grand-sep {
            border: none;
            border-top: 2px solid #b91c1c;
            margin: 5px 0;
        }

        .grand {
            display: table;
            width: 100%;
        }

        .grand-l {
            display: table-cell;
            font-size: 12px;
            font-weight: bold;
        }

        .grand-r {
            display: table-cell;
            font-size: 14px;
            font-weight: bold;
            text-align: right;
            color: #b91c1c;
        }

        .note-lbl {
            font-size: 7px;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .note-txt {
            font-size: 8.5px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .footer {
            display: table;
            width: 100%;
            margin-top: 18px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }

        .foot-l {
            display: table-cell;
            font-size: 7.5px;
            color: #999;
            vertical-align: bottom;
        }

        .foot-sig {
            display: table-cell;
            width: 160px;
            text-align: center;
            vertical-align: bottom;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 36px;
            padding-top: 4px;
            font-size: 7.5px;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="hdr">
            <div class="hdr-l">
                <div class="co-name">{{ $creditNote->company->name }}</div>
                <div class="co-addr">
                    @if ($creditNote->company->address_line1)
                        {{ $creditNote->company->address_line1 }}
                        <br>
                    @endif
                    {{ implode(', ', array_filter([$creditNote->company->city, $creditNote->company->state, $creditNote->company->pincode])) }}
                </div>
                @if ($creditNote->company->gstin)
                    <div class="co-gstin">GSTIN: {{ $creditNote->company->gstin }}</div>
                @endif
            </div>
            <div class="hdr-r">
                <div class="title">Credit Note</div>
                <div class="num">{{ $creditNote->credit_note_number }}</div>
            </div>
        </div>
        <hr class="rule">
        <div class="meta">
            <div class="meta-c" style="width:38%;">
                <div class="lbl">Bill To</div>
                <div class="bold">{{ $creditNote->party->display_name ?? $creditNote->party->name }}</div>
                @if ($creditNote->billing_address)
                    <div class="val" style="margin-top:3px;">{{ $creditNote->billing_address }}</div>
                    @endif @if ($creditNote->party->gstin)
                        <div class="co-gstin">GSTIN: {{ $creditNote->party->gstin }}</div>
                    @endif
            </div>
            <div class="meta-c" style="width:14%;">
                <div class="lbl">Date</div>
                <div class="val">{{ $creditNote->credit_note_date->format('d M Y') }}</div>
            </div>
            <div class="meta-c" style="width:14%;">
                <div class="lbl">Reason</div>
                <div class="val">{{ $creditNote->reasonLabel() }}</div>
            </div>
            @if ($creditNote->reference_number)
                <div class="meta-c" style="width:14%;">
                    <div class="lbl">Reference</div>
                    <div class="val">{{ $creditNote->reference_number }}</div>
                </div>
            @endif
            <div class="meta-c" style="width:14%;">
                <div class="lbl">Tax Type</div>
                <div class="val">{{ $creditNote->is_igst ? 'IGST' : 'CGST+SGST' }}</div>
            </div>
        </div>
        <table class="tbl">
            <thead>
                <tr>
                    <th class="l" style="width:22px;">#</th>
                    <th class="l">Description</th>
                    <th class="l" style="width:55px;">HSN/SAC</th>
                    <th class="r" style="width:36px;">Qty</th>
                    <th class="l" style="width:32px;">Unit</th>
                    <th class="r" style="width:60px;">Rate</th>
                    <th class="r" style="width:60px;">Amount</th>
                    <th class="r" style="width:50px;">Disc</th>
                    <th class="r" style="width:60px;">Taxable</th>
                    <th class="r" style="width:36px;">Tax%</th>
                    <th class="r" style="width:65px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($creditNote->items as $i => $item)
                    <tr>
                        <td class="mono" style="color:#999;">{{ $i + 1 }}</td>
                        <td style="font-weight:500;">{{ $item->description }}</td>
                        <td class="mono" style="color:#666;">{{ $item->hsn_sac ?: '—' }}</td>
                        <td class="r mono">{{ rtrim(rtrim(number_format((float) $item->qty, 3), '0'), '.') }}</td>
                        <td style="color:#555;">{{ $item->unit ?: '—' }}</td>
                        <td class="r mono">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="r mono">{{ number_format((float) $item->qty * (float) $item->unit_price, 2) }}</td>
                        <td class="r mono" style="color:#999;">
                            {{ $item->discount_amount > 0 ? number_format((float) $item->discount_amount, 2) : '—' }}</td>
                        <td class="r mono" style="font-weight:bold;">
                            {{ number_format((float) $item->taxable_amount, 2) }}</td>
                        <td class="r mono" style="color:#666;">
                            @if ($creditNote->is_igst)
                                {{ number_format((float) $item->igst_rate, 2) }}%@else{{ number_format((float) $item->cgst_rate + (float) $item->sgst_rate, 2) }}%
                            @endif
                        </td>
                        <td class="r mono" style="font-weight:bold;">{{ number_format((float) $item->line_total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="bot">
            <div class="bot-l">
                @if ($creditNote->notes)
                    <div class="note-lbl">Notes</div>
                    <div class="note-txt">{{ $creditNote->notes }}</div>
                @endif
                @if ($creditNote->terms)
                    <div class="note-lbl">Terms</div>
                    <div class="note-txt">{{ $creditNote->terms }}</div>
                @endif
            </div>
            <div class="bot-r">
                <div class="tot">
                    <div class="tot-l">Subtotal</div>
                    <div class="tot-r">&#8377; {{ number_format((float) $creditNote->subtotal, 2) }}</div>
                </div>
                @if ($creditNote->discount_amount > 0)
                    <div class="tot">
                        <div class="tot-l">Discount</div>
                        <div class="tot-r" style="color:#b91c1c;">&#8722; &#8377;
                            {{ number_format((float) $creditNote->discount_amount, 2) }}</div>
                    </div>
                @endif
                <div class="tot">
                    <div class="tot-l">Taxable</div>
                    <div class="tot-r">&#8377; {{ number_format((float) $creditNote->taxable_amount, 2) }}</div>
                </div>
                @if ($creditNote->is_igst)
                    @if ($creditNote->igst_amount > 0)
                        <div class="tot">
                            <div class="tot-l">IGST</div>
                            <div class="tot-r">&#8377; {{ number_format((float) $creditNote->igst_amount, 2) }}</div>
                        </div>
                    @endif
                @else
                    @if ($creditNote->cgst_amount > 0)
                        <div class="tot">
                            <div class="tot-l">CGST</div>
                            <div class="tot-r">&#8377; {{ number_format((float) $creditNote->cgst_amount, 2) }}</div>
                        </div>
                        @endif @if ($creditNote->sgst_amount > 0)
                            <div class="tot">
                                <div class="tot-l">SGST</div>
                                <div class="tot-r">&#8377; {{ number_format((float) $creditNote->sgst_amount, 2) }}
                                </div>
                            </div>
                        @endif
                    @endif
                    <hr class="grand-sep">
                    <div class="grand">
                        <div class="grand-l">Credit Total</div>
                        <div class="grand-r">&#8377; {{ number_format((float) $creditNote->grand_total, 2) }}</div>
                    </div>
            </div>
        </div>
        <div class="footer">
            <div class="foot-l">Generated {{ now()->format('d M Y, h:i A') }} | Computer-generated document.</div>
            <div class="foot-sig">
                @php
                    $signedLog = \App\Models\ApprovalLog::where('document_type', 'credit_note')->where('document_id', $creditNote->id)->whereNotNull('signature_path')->where('action', 'approved')->orderBy('level', 'desc')->first();
                    $sigFullPath = $signedLog && $signedLog->signature_path ? storage_path('app/public/' . $signedLog->signature_path) : null;
                @endphp
                @if ($sigFullPath && file_exists($sigFullPath))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($sigFullPath)) }}"
                        style="max-width:120px;max-height:40px;margin:0 auto 4px;">
                    <div style="font-size:7.5px;color:#555;">{{ $signedLog->user->name ?? '' }}</div>
                @endif
                <div class="sig-line">Authorised Signatory</div>
            </div>
        </div>
    </div>
</body>

</html>
