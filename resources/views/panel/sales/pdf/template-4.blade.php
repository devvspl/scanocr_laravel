{{-- Template 4: Minimal Clean — ultra-minimal, generous whitespace, borderless table --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:DejaVu Sans,sans-serif; font-size:12px; color:#374151; background:#fff; }
.page { padding:38px 40px; }

/* Header */
.hdr { display:table; width:100%; margin-bottom:30px; }
.hdr-l { display:table-cell; width:50%; vertical-align:top; }
.hdr-r { display:table-cell; width:50%; vertical-align:top; text-align:right; }
.co-name  { font-size:17px; font-weight:bold; color:#111827; }
.co-legal { font-size:10px; color:#9ca3af; margin-top:1px; }
.co-addr  { font-size:10px; color:#6b7280; line-height:1.7; margin-top:5px; }
.co-gstin { font-size:9.5px; color:#6b7280; margin-top:4px; font-family:DejaVu Sans Mono,monospace; }
.inv-title { font-size:32px; font-weight:bold; color:#111827; letter-spacing:-1px; }
.inv-num   { font-size:11px; color:#6b7280; margin-top:4px; font-family:DejaVu Sans Mono,monospace; }
.inv-date  { font-size:10px; color:#9ca3af; margin-top:2px; }

/* Badge */
.badge { display:inline-block; padding:3px 10px; border-radius:10px; font-size:9px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; margin-top:6px; background:#f3f4f6; color:#6b7280; }

/* Thin rule */
.rule { border:none; border-top:1px solid #f3f4f6; margin:16px 0; }
.rule-med { border:none; border-top:1px solid #e5e7eb; margin:12px 0; }

/* Meta */
.meta-grid { display:table; width:100%; margin-bottom:22px; }
.meta-cell { display:table-cell; vertical-align:top; padding-right:16px; }
.meta-lbl  { font-size:9px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:1.2px; margin-bottom:4px; }
.meta-val  { font-size:11px; color:#374151; line-height:1.5; }
.meta-bold { font-size:12px; font-weight:bold; color:#111827; }
.meta-mono { font-family:DejaVu Sans Mono,monospace; font-size:9.5px; color:#6b7280; margin-top:3px; }

/* Table — borderless style */
.tbl { width:100%; border-collapse:collapse; }
.tbl thead tr { border-bottom:1px solid #d1d5db; }
.tbl th { padding:9px 5px; font-size:9px; font-weight:bold; color:#6b7280; text-transform:uppercase; letter-spacing:0.8px; background:#fff; }
.tbl th.r { text-align:right; } .tbl th.l { text-align:left; }
.tbl td { padding:9px 5px; font-size:11px; border-bottom:none; vertical-align:top; }
.tbl td.r { text-align:right; } .tbl td.mono { font-family:DejaVu Sans Mono,monospace; font-size:10px; }
.desc-main { font-weight:bold; font-size:11px; color:#111827; }
.desc-sub  { font-size:9.5px; color:#9ca3af; margin-top:1px; }

/* Bottom */
.bot { display:table; width:100%; margin-top:8px; border-top:1px solid #e5e7eb; }
.bot-notes  { display:table-cell; width:55%; vertical-align:top; padding:16px 20px 16px 0; }
.bot-totals { display:table-cell; width:45%; vertical-align:bottom; padding:16px 0 16px 20px; }
.note-lbl { font-size:9px; font-weight:bold; color:#9ca3af; text-transform:uppercase; letter-spacing:1.2px; margin-bottom:4px; }
.note-txt  { font-size:10px; color:#6b7280; line-height:1.6; margin-bottom:12px; }
.tot-row { display:table; width:100%; margin-bottom:4px; }
.tot-l { display:table-cell; font-size:11px; color:#6b7280; }
.tot-r { display:table-cell; font-size:11px; color:#374151; font-weight:500; text-align:right; }
.tot-r-red { color:#b91c1c; } .tot-r-grn { color:#15803d; }
.grand-row { display:table; width:100%; border-top:2px solid #111827; padding-top:8px; margin-top:6px; }
.grand-l { display:table-cell; font-size:13px; font-weight:bold; color:#111827; }
.grand-r { display:table-cell; font-size:15px; font-weight:bold; color:#111827; text-align:right; }

/* Footer */
.footer { display:table; width:100%; margin-top:26px; padding-top:10px; border-top:1px solid #f3f4f6; }
.foot-l   { display:table-cell; font-size:9px; color:#9ca3af; vertical-align:bottom; }
.foot-sig { display:table-cell; width:160px; text-align:center; vertical-align:bottom; }
.sig-line { border-top:1px solid #d1d5db; margin-top:36px; padding-top:4px; font-size:9px; color:#6b7280; }
</style>
</head>
<body>
<div class="page">
  <div class="hdr">
    <div class="hdr-l">
      <div class="co-name">{{ $invoice->company->name }}</div>
      @if($invoice->company->legal_name && $invoice->company->legal_name !== $invoice->company->name)
        <div class="co-legal">{{ $invoice->company->legal_name }}</div>
      @endif
      <div class="co-addr">
        @if($invoice->company->address_line1){{ $invoice->company->address_line1 }}<br>@endif
        @if($invoice->company->address_line2){{ $invoice->company->address_line2 }}<br>@endif
        {{ implode(', ', array_filter([$invoice->company->city, $invoice->company->state, $invoice->company->pincode])) }}
      </div>
      @if($invoice->company->gstin)<div class="co-gstin">GSTIN: {{ $invoice->company->gstin }}</div>@endif
    </div>
    <div class="hdr-r">
      <div class="inv-title">Invoice</div>
      <div class="inv-num">{{ $invoice->invoice_number }}</div>
      <div class="inv-date">{{ $invoice->invoice_date->format('d M Y') }}</div>
      @php $b=$invoice->statusBadge(); @endphp
      <br><div class="badge">{{ $b['label'] }}</div>
    </div>
  </div>

  <hr class="rule-med">

  <div class="meta-grid">
    <div class="meta-cell" style="width:40%;">
      <div class="meta-lbl">Bill To</div>
      <div class="meta-bold">{{ $invoice->party->display_name ?? $invoice->party->name }}</div>
      @if($invoice->billing_address)<div class="meta-val" style="margin-top:3px;">{{ $invoice->billing_address }}</div>@endif
      @if($invoice->party->gstin)<div class="meta-mono">GSTIN: {{ $invoice->party->gstin }}</div>@endif
    </div>
    <div class="meta-cell" style="width:15%;">
      <div class="meta-lbl">Due Date</div>
      <div class="meta-val">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</div>
    </div>
    @if($invoice->reference_number)
    <div class="meta-cell" style="width:15%;">
      <div class="meta-lbl">Reference</div>
      <div class="meta-val">{{ $invoice->reference_number }}</div>
    </div>
    @endif
    @if($invoice->place_of_supply)
    <div class="meta-cell" style="width:15%;">
      <div class="meta-lbl">Place of Supply</div>
      <div class="meta-val">{{ $invoice->place_of_supply }}</div>
    </div>
    @endif
    <div class="meta-cell" style="width:15%;">
      <div class="meta-lbl">Tax Type</div>
      <div class="meta-val">{{ $invoice->is_igst ? 'IGST' : 'CGST+SGST' }}</div>
    </div>
  </div>

  <table class="tbl">
    <thead>
      <tr>
        <th class="l" style="width:22px;">#</th>
        <th class="l">Description</th>
        <th class="l" style="width:58px;">HSN/SAC</th>
        <th class="r" style="width:38px;">Qty</th>
        <th class="l" style="width:34px;">Unit</th>
        <th class="r" style="width:62px;">Rate (&#8377;)</th>
        <th class="r" style="width:62px;">Amount (&#8377;)</th>
        <th class="r" style="width:54px;">Disc (&#8377;)</th>
        <th class="r" style="width:62px;">Taxable (&#8377;)</th>
        <th class="r" style="width:38px;">Tax%</th>
        <th class="r" style="width:68px;">Total (&#8377;)</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $i => $item)
      <tr>
        <td class="mono" style="color:#9ca3af;">{{ $i+1 }}</td>
        <td><div class="desc-main">{{ $item->description }}</div>@if($item->product && $item->product->name !== $item->description)<div class="desc-sub">{{ $item->product->name }}</div>@endif</td>
        <td class="mono" style="color:#6b7280;">{{ $item->hsn_sac ?: '—' }}</td>
        <td class="r mono">{{ rtrim(rtrim(number_format((float)$item->qty,3),'0'),'.') }}</td>
        <td style="color:#6b7280;">{{ $item->unit ?: '—' }}</td>
        <td class="r mono">{{ number_format((float)$item->unit_price,2) }}</td>
        <td class="r mono">{{ number_format((float)$item->qty*(float)$item->unit_price,2) }}</td>
        <td class="r mono" style="color:#9ca3af;">{{ $item->discount_amount>0 ? number_format((float)$item->discount_amount,2) : '—' }}</td>
        <td class="r mono" style="font-weight:bold;">{{ number_format((float)$item->taxable_amount,2) }}</td>
        <td class="r mono" style="color:#6b7280;">@if($invoice->is_igst){{ number_format((float)$item->igst_rate,2) }}%@else{{ number_format((float)$item->cgst_rate+(float)$item->sgst_rate,2) }}%@endif</td>
        <td class="r mono" style="font-weight:bold;">{{ number_format((float)$item->line_total,2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="bot">
    <div class="bot-notes">
      @if($invoice->notes)<div class="note-lbl">Notes</div><div class="note-txt">{{ $invoice->notes }}</div>@endif
      @if($invoice->terms)<div class="note-lbl">Terms &amp; Conditions</div><div class="note-txt">{{ $invoice->terms }}</div>@endif
      @if($invoice->narration)<div class="note-lbl">Narration</div><div class="note-txt" style="font-style:italic;color:#9ca3af;">{{ $invoice->narration }}</div>@endif
    </div>
    <div class="bot-totals">
      <div class="tot-row"><div class="tot-l">Subtotal</div><div class="tot-r">&#8377; {{ number_format((float)$invoice->subtotal,2) }}</div></div>
      @if($invoice->discount_amount>0)<div class="tot-row"><div class="tot-l">Discount</div><div class="tot-r tot-r-red">&#8722; &#8377; {{ number_format((float)$invoice->discount_amount,2) }}</div></div>@endif
      <div class="tot-row"><div class="tot-l">Taxable</div><div class="tot-r">&#8377; {{ number_format((float)$invoice->taxable_amount,2) }}</div></div>
      @if($invoice->is_igst)
        @if($invoice->igst_amount>0)<div class="tot-row"><div class="tot-l">IGST</div><div class="tot-r">&#8377; {{ number_format((float)$invoice->igst_amount,2) }}</div></div>@endif
      @else
        @if($invoice->cgst_amount>0)<div class="tot-row"><div class="tot-l">CGST</div><div class="tot-r">&#8377; {{ number_format((float)$invoice->cgst_amount,2) }}</div></div>@endif
        @if($invoice->sgst_amount>0)<div class="tot-row"><div class="tot-l">SGST</div><div class="tot-r">&#8377; {{ number_format((float)$invoice->sgst_amount,2) }}</div></div>@endif
      @endif
      <div class="grand-row">
        <div class="grand-l">Grand Total</div>
        <div class="grand-r">&#8377; {{ number_format((float)$invoice->grand_total,2) }}</div>
      </div>
      @php
        $advanceAmt = (float)$invoice->advance_amount;
        $totalPaid = (float)$invoice->amount_paid;
        $additionalPaid = max($totalPaid - $advanceAmt, 0);
      @endphp
      @if($advanceAmt > 0)
      <div class="tot-row" style="margin-top:5px;"><div class="tot-l">Advance Payment</div><div class="tot-r" style="color:#1e40af;">− &#8377; {{ number_format($advanceAmt,2) }}</div></div>
      @endif
      @if($additionalPaid > 0)
      <div class="tot-row"><div class="tot-l">Additional Payment</div><div class="tot-r tot-r-grn">− &#8377; {{ number_format($additionalPaid,2) }}</div></div>
      @endif
      @if($advanceAmt > 0 || $additionalPaid > 0)
      <div class="tot-row"><div class="tot-l" style="font-weight:bold;">Amount Due</div><div class="tot-r tot-r-red" style="font-weight:bold;">&#8377; {{ number_format((float)$invoice->amount_due,2) }}</div></div>
      @endif
    </div>
  </div>

  <div class="footer">
    <div class="foot-l">Generated {{ now()->format('d M Y, h:i A') }} &nbsp;|&nbsp; Computer-generated document.</div>
    <div class="foot-sig">
      @php
        $signedLog = \App\Models\ApprovalLog::where('document_type', 'invoice')
            ->where('document_id', $invoice->id)
            ->whereNotNull('signature_path')
            ->where('action', 'approved')
            ->orderBy('level', 'desc')
            ->first();
        $sigFullPath = $signedLog && $signedLog->signature_path ? storage_path('app/public/' . $signedLog->signature_path) : null;
      @endphp
      @if($sigFullPath && file_exists($sigFullPath))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($sigFullPath)) }}" style="max-width:120px;max-height:40px;margin:0 auto 4px;">
        <div style="font-size:9px;color:#6b7280;">{{ $signedLog->user->name ?? '' }}</div>
      @endif
      <div class="sig-line">Authorised Signatory</div>
    </div>
  </div>
</div>
</body>
</html>
