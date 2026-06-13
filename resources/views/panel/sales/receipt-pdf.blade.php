<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c1917; background: #fff; }
        .page { padding: 32px 36px; }
        .hdr { display: table; width: 100%; margin-bottom: 20px; }
        .hdr-l { display: table-cell; width: 55%; vertical-align: top; }
        .hdr-r { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
        .co-name { font-size: 16px; font-weight: bold; }
        .co-addr { font-size: 8.5px; color: #444; line-height: 1.6; margin-top: 3px; }
        .co-gstin { font-size: 8px; color: #999; margin-top: 3px; font-family: monospace; }
        .title { font-size: 24px; font-weight: bold; color: #b91c1c; letter-spacing: 2px; text-transform: uppercase; }
        .num { font-size: 10px; color: #333; margin-top: 4px; font-family: monospace; }
        .rule { border: none; border-top: 2px solid #b91c1c; margin: 12px 0 10px; }
        .meta { display: table; width: 100%; margin-bottom: 16px; }
        .meta-c { display: table-cell; vertical-align: top; padding-right: 12px; }
        .lbl { font-size: 7px; font-weight: bold; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .val { font-size: 9.5px; color: #1c1917; line-height: 1.5; }
        .bold { font-size: 11px; font-weight: bold; }
        .amount-box { border: 2px solid #b91c1c; border-radius: 4px; padding: 12px 16px; margin: 20px 0; text-align: center; }
        .amount-label { font-size: 8px; font-weight: bold; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .amount-value { font-size: 22px; font-weight: bold; color: #b91c1c; }
        .details-tbl { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .details-tbl td { padding: 6px 8px; font-size: 9px; border-bottom: 1px solid #eee; vertical-align: top; }
        .details-tbl td.lbl-cell { font-weight: bold; color: #555; width: 140px; background: #fafaf9; }
        .details-tbl td.val-cell { color: #1c1917; }
        .note-lbl { font-size: 7px; font-weight: bold; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .note-txt { font-size: 8.5px; color: #555; line-height: 1.5; margin-bottom: 8px; }
        .footer { display: table; width: 100%; margin-top: 24px; border-top: 1px solid #ccc; padding-top: 8px; }
        .foot-l { display: table-cell; font-size: 7.5px; color: #999; vertical-align: bottom; }
        .foot-sig { display: table-cell; width: 160px; text-align: center; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #000; margin-top: 36px; padding-top: 4px; font-size: 7.5px; color: #555; }
    </style>
</head>
<body>
    <div class="page">
        <div class="hdr">
            <div class="hdr-l">
                <div class="co-name">{{ $receipt->company->name }}</div>
                <div class="co-addr">
                    @if($receipt->company->address_line1){{ $receipt->company->address_line1 }}<br>@endif
                    {{ implode(', ', array_filter([$receipt->company->city, $receipt->company->state, $receipt->company->pincode])) }}
                </div>
                @if($receipt->company->gstin)<div class="co-gstin">GSTIN: {{ $receipt->company->gstin }}</div>@endif
            </div>
            <div class="hdr-r">
                <div class="title">Receipt</div>
                <div class="num">{{ $receipt->receipt_number }}</div>
            </div>
        </div>
        <hr class="rule">
        <div class="meta">
            <div class="meta-c" style="width:40%;">
                <div class="lbl">Received From</div>
                <div class="bold">{{ $receipt->party->display_name ?? $receipt->party->name }}</div>
                @if($receipt->party->billing_address)<div class="val" style="margin-top:3px;">{{ $receipt->party->billing_address }}</div>@endif
                @if($receipt->party->gstin)<div class="co-gstin">GSTIN: {{ $receipt->party->gstin }}</div>@endif
            </div>
            <div class="meta-c" style="width:15%;">
                <div class="lbl">Date</div>
                <div class="val">{{ $receipt->receipt_date->format('d M Y') }}</div>
            </div>
            <div class="meta-c" style="width:15%;">
                <div class="lbl">Payment Mode</div>
                <div class="val">{{ $receipt->paymentMethodLabel() }}</div>
            </div>
            @if($receipt->payment_reference)
            <div class="meta-c" style="width:15%;">
                <div class="lbl">Reference</div>
                <div class="val">{{ $receipt->payment_reference }}</div>
            </div>
            @endif
            @if($receipt->payment_date)
            <div class="meta-c" style="width:15%;">
                <div class="lbl">Payment Date</div>
                <div class="val">{{ $receipt->payment_date->format('d M Y') }}</div>
            </div>
            @endif
        </div>

        <div class="amount-box">
            <div class="amount-label">Amount Received</div>
            <div class="amount-value">&#8377; {{ number_format((float)$receipt->amount, 2) }}</div>
        </div>

        <table class="details-tbl">
            @if($receipt->bank_name)
            <tr><td class="lbl-cell">Bank Name</td><td class="val-cell">{{ $receipt->bank_name }}</td></tr>
            @endif
            @if($receipt->bank_account)
            <tr><td class="lbl-cell">Bank Account</td><td class="val-cell">{{ $receipt->bank_account }}</td></tr>
            @endif
            @if($receipt->saleInvoice)
            <tr><td class="lbl-cell">Against Invoice</td><td class="val-cell">{{ $receipt->saleInvoice->invoice_number }}</td></tr>
            @endif
            @if($receipt->description)
            <tr><td class="lbl-cell">Description</td><td class="val-cell">{{ $receipt->description }}</td></tr>
            @endif
        </table>

        @if($receipt->narration)
        <div style="margin-top:12px;">
            <div class="note-lbl">Narration</div>
            <div class="note-txt">{{ $receipt->narration }}</div>
        </div>
        @endif

        <div class="footer">
            <div class="foot-l">Generated {{ now()->format('d M Y, h:i A') }} | Computer-generated document.</div>
            <div class="foot-sig">
                <div class="sig-line">Authorised Signatory</div>
            </div>
        </div>
    </div>
</body>
</html>
