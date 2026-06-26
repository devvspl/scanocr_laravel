{{-- Invoice View (Read-Only) --}}

{{-- Row 1: Invoice No, Invoice Date --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Invoice / Bill No</label>
        <input type="text" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Invoice / Bill Date</label>
        <input type="date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" readonly>
    </div>
</div>

{{-- Row 2: Vendor, Buyer --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor (From)</label>
        <input type="text" class="f-input" value="{{ $punchDetail->ToName ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Buyer (To)</label>
        <input type="text" class="f-input" value="{{ $punchDetail->FromName ?? '' }}" readonly>
    </div>
</div>

{{-- Row 3: Department, Category, Ledger, File --}}
<div class="f-row">
    <div class="f-group">
        <label>Department</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Department ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Category</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Category ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Ledger</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Ledger ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>File</label>
        <input type="text" class="f-input" value="{{ $punchDetail->FileName ?? '' }}" readonly>
    </div>
</div>

{{-- Row 4: Location --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Location</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Loc_Name ?? '' }}" readonly>
    </div>
</div>

{{-- Line Items --}}
@php
    $invoiceDetails = DB::table('invoice_detail')->where('Scan_Id', $scanData->Scan_Id)->get();
@endphp
@if($invoiceDetails->count() > 0)
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Line Items</span></div>
<div style="overflow-x:auto;max-height:300px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="min-width:150px">Particular</th>
                <th style="min-width:70px">HSN</th>
                <th style="min-width:60px">Qty</th>
                <th style="min-width:80px">Unit</th>
                <th style="min-width:75px">MRP</th>
                <th style="min-width:65px">Discount</th>
                <th style="min-width:75px">Price</th>
                <th style="min-width:85px">Amount</th>
                <th style="min-width:55px">GST%</th>
                <th style="min-width:55px">SGST%</th>
                <th style="min-width:55px">IGST%</th>
                <th style="min-width:55px">Cess%</th>
                <th style="min-width:85px">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoiceDetails as $idx => $detail)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td><input type="text" value="{{ $detail->Particular }}" readonly></td>
                <td><input type="text" value="{{ $detail->HSN }}" readonly></td>
                <td><input type="text" value="{{ $detail->Qty }}" readonly></td>
                <td><input type="text" value="{{ $detail->Unit }}" readonly></td>
                <td><input type="text" value="{{ $detail->MRP }}" readonly></td>
                <td><input type="text" value="{{ $detail->Discount }}" readonly></td>
                <td><input type="text" value="{{ $detail->Price }}" readonly></td>
                <td><input type="text" value="{{ $detail->Amount }}" readonly></td>
                <td><input type="text" value="{{ $detail->GST }}" readonly></td>
                <td><input type="text" value="{{ $detail->SGST }}" readonly></td>
                <td><input type="text" value="{{ $detail->IGST }}" readonly></td>
                <td><input type="text" value="{{ $detail->Cess }}" readonly></td>
                <td><input type="text" value="{{ $detail->Total_Amount }}" readonly></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Sub Total</label>
        <input type="text" class="f-input" value="{{ $punchDetail->SubTotal ?? '0.00' }}" readonly>
    </div>
    <div class="f-group">
        <label>TCS %</label>
        <input type="text" class="f-input" value="{{ $punchDetail->TCS ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Round Off</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Round_Off_Value ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Grand Total</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Grand_Total ?? '0.00' }}" readonly>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Remark</label>
        <textarea class="f-input" readonly>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
