<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Note - {{ $delivery->delivery_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1c1917; line-height: 1.4; }
        .page { padding: 24px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #292524; padding-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; color: #292524; text-transform: uppercase; letter-spacing: 1px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .section { margin-bottom: 16px; }
        .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #78716c; margin-bottom: 4px; }
        .grid-2 { display: table; width: 100%; }
        .grid-2 .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .info-row { margin-bottom: 2px; }
        .info-label { font-size: 9px; color: #78716c; }
        .info-value { font-size: 11px; color: #1c1917; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: #f5f5f4; border: 1px solid #e7e5e4; padding: 6px 8px; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #57534e; text-align: left; }
        table.items td { border: 1px solid #e7e5e4; padding: 6px 8px; font-size: 10px; }
        table.items td.num { text-align: right; }
        .transport-box { background: #fafaf9; border: 1px solid #e7e5e4; border-radius: 4px; padding: 10px; margin-bottom: 16px; }
        .signatures { display: table; width: 100%; margin-top: 40px; }
        .sig-col { display: table-cell; width: 33.33%; text-align: center; vertical-align: bottom; padding: 0 12px; }
        .sig-line { border-top: 1px solid #a8a29e; margin-top: 50px; padding-top: 4px; font-size: 9px; color: #78716c; }
        .meta-table { width: auto; border-collapse: collapse; }
        .meta-table td { padding: 2px 8px 2px 0; font-size: 10px; }
        .meta-table td.label { color: #78716c; font-size: 9px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div style="margin-bottom:20px;border-bottom:2px solid #292524;padding-bottom:12px;">
        <table style="width:100%;">
            <tr>
                <td style="vertical-align:top;">
                    <div class="company-name">{{ $delivery->company->name }}</div>
                    @if($delivery->company->address_line1)<div style="font-size:10px;color:#57534e;margin-top:2px;">{{ $delivery->company->address_line1 }}</div>@endif
                    @if($delivery->company->city || $delivery->company->state)
                    <div style="font-size:10px;color:#57534e;">{{ implode(', ', array_filter([$delivery->company->city, $delivery->company->state, $delivery->company->pincode])) }}</div>
                    @endif
                    @if($delivery->company->phone)<div style="font-size:9px;color:#78716c;margin-top:2px;">Phone: {{ $delivery->company->phone }}</div>@endif
                    @if($delivery->company->gstin)<div style="font-size:9px;color:#78716c;">GSTIN: {{ $delivery->company->gstin }}</div>@endif
                </td>
                <td style="text-align:right;vertical-align:top;">
                    <div class="title">Delivery Note</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Customer & Document Info --}}
    <div class="grid-2" style="margin-bottom:16px;">
        <div class="col">
            <div class="section-title">Deliver To</div>
            <div style="font-weight:bold;font-size:12px;">{{ $delivery->party->display_name ?? $delivery->party->name }}</div>
            @if($delivery->receiver_name)<div style="font-size:10px;color:#57534e;">Attn: {{ $delivery->receiver_name }}</div>@endif
            @if($delivery->delivery_address)<div style="font-size:10px;color:#57534e;margin-top:2px;white-space:pre-line;">{{ $delivery->delivery_address }}</div>@endif
            @if($delivery->receiver_phone)<div style="font-size:9px;color:#78716c;margin-top:2px;">Phone: {{ $delivery->receiver_phone }}</div>@endif
        </div>
        <div class="col" style="padding-right:0;">
            <table class="meta-table">
                <tr><td class="label">Delivery Note #</td><td style="font-weight:bold;font-family:monospace;">{{ $delivery->delivery_number }}</td></tr>
                <tr><td class="label">Dispatch Date</td><td>{{ $delivery->dispatch_date->format('d M Y') }}</td></tr>
                @if($delivery->order_number)<tr><td class="label">Order / PO Ref</td><td>{{ $delivery->order_number }}</td></tr>@endif
                @if($delivery->total_packages)<tr><td class="label">Total Packages</td><td>{{ $delivery->total_packages }}</td></tr>@endif
                @if($delivery->total_weight)<tr><td class="label">Total Weight</td><td>{{ $delivery->total_weight }}</td></tr>@endif
            </table>
        </div>
    </div>

    {{-- Transport Details --}}
    @if($delivery->transporter_name || $delivery->vehicle_number || $delivery->tracking_number)
    <div class="transport-box">
        <div class="section-title" style="margin-bottom:6px;">Transport Details</div>
        <table class="meta-table">
            @if($delivery->transport_mode)<tr><td class="label">Mode</td><td>{{ $delivery->transport_mode }}</td></tr>@endif
            @if($delivery->transporter_name)<tr><td class="label">Transporter</td><td>{{ $delivery->transporter_name }}</td></tr>@endif
            @if($delivery->vehicle_number)<tr><td class="label">Vehicle No.</td><td>{{ $delivery->vehicle_number }}</td></tr>@endif
            @if($delivery->driver_name)<tr><td class="label">Driver</td><td>{{ $delivery->driver_name }}@if($delivery->driver_phone) ({{ $delivery->driver_phone }})@endif</td></tr>@endif
            @if($delivery->tracking_number)<tr><td class="label">AWB / LR / Docket</td><td style="font-family:monospace;">{{ $delivery->tracking_number }}</td></tr>@endif
        </table>
    </div>
    @endif

    {{-- Items Table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;">Sr.</th>
                <th>Item Description</th>
                <th style="width:80px;">Product Code</th>
                <th style="width:60px;text-align:right;">Qty</th>
                <th style="width:45px;">Unit</th>
                <th style="width:70px;">Weight</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->items as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td style="font-weight:500;">{{ $item->description }}</td>
                <td style="font-family:monospace;font-size:9px;">{{ $item->product_code ?: '—' }}</td>
                <td class="num" style="font-weight:bold;">{{ rtrim(rtrim(number_format((float)$item->qty, 3), '0'), '.') }}</td>
                <td>{{ $item->unit ?: '—' }}</td>
                <td>{{ $item->weight ?: '—' }}</td>
                <td style="font-size:9px;color:#57534e;">{{ $item->remarks ?: '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Notes --}}
    @if($delivery->notes)
    <div style="margin-top:12px;">
        <div class="section-title">Notes</div>
        <div style="font-size:10px;color:#57534e;white-space:pre-line;">{{ $delivery->notes }}</div>
    </div>
    @endif

    {{-- Signatures --}}
    @php
      $signedLog = \App\Models\ApprovalLog::where('document_type', 'delivery_note')
          ->where('document_id', $delivery->id)
          ->whereNotNull('signature_path')
          ->where('action', 'approved')
          ->orderBy('level', 'desc')
          ->first();
      $sigFullPath = $signedLog && $signedLog->signature_path ? storage_path('app/public/' . $signedLog->signature_path) : null;
    @endphp
    <div class="signatures">
        <div class="sig-col">
            <div class="sig-line">Prepared By</div>
        </div>
        <div class="sig-col">
            @if($sigFullPath && file_exists($sigFullPath))
              <img src="data:image/png;base64,{{ base64_encode(file_get_contents($sigFullPath)) }}" style="max-width:100px;max-height:35px;margin:0 auto 4px;">
              <div style="font-size:8px;color:#57534e;margin-bottom:2px;">{{ $signedLog->user->name ?? '' }}</div>
            @endif
            <div class="sig-line">Dispatched By</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Receiver's Signature & Date</div>
        </div>
    </div>

</div>
</body>
</html>
