{{-- Credit Note View Partial (Read-Only) --}}
{{-- Variables: $punchDetail, $scanData --}}

{{-- Row 1: Credit Note No, Credit Note Date --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Credit Note No</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->CreditNo ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Credit Note Date</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->CreditDate ? \Carbon\Carbon::parse($punchDetail->CreditDate)->format('d M Y') : '—' }}">
    </div>
</div>

{{-- Row 2: Vendor, Buyer --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor (From)</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->ToName ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Buyer (To)</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->FromName ?? '—' }}">
    </div>
</div>

{{-- Row 3: Vendor Address, Buyer Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor Address</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->AgencyAddress ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Buyer Address</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Loc_Add ?? '—' }}">
    </div>
</div>

{{-- Row 4: Payment Mode, Invoice No, Invoice Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Payment Mode</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->NatureOfPayment ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Invoice No</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->File_No ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Invoice Date</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->BillDate ? \Carbon\Carbon::parse($punchDetail->BillDate)->format('d M Y') : '—' }}">
    </div>
</div>

{{-- Row 5: Buyer Order No, Order Date, Dispatch Through, Delivery Note Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Buyer Order No</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->ServiceNo ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Order Date</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->BookingDate ? \Carbon\Carbon::parse($punchDetail->BookingDate)->format('d M Y') : '—' }}">
    </div>
    <div class="f-group">
        <label>Dispatch Through</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Particular ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Delivery Note Date</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->DueDate ? \Carbon\Carbon::parse($punchDetail->DueDate)->format('d M Y') : '—' }}">
    </div>
</div>

{{-- Row 6: Department, Category, Ledger, File --}}
<div class="f-row">
    <div class="f-group">
        <label>Department</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Department ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Category</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Category ?? '—' }}">
    </div>
    <div class="f-group">
        <label>Ledger</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Ledger ?? '—' }}">
    </div>
    <div class="f-group">
        <label>File</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->FileName ?? '—' }}">
    </div>
</div>

{{-- Row 7: Location, LR Number, LR Date, Cartoon Number --}}
<div class="f-row">
    <div class="f-group">
        <label>Location</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Loc_Name ?? '—' }}">
    </div>
    <div class="f-group">
        <label>LR Number</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->FDRNo ?? '—' }}">
    </div>
    <div class="f-group">
        <label>LR Date</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->File_Date ? \Carbon\Carbon::parse($punchDetail->File_Date)->format('d M Y') : '—' }}">
    </div>
    <div class="f-group">
        <label>Cartoon Number</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->RegNo ?? '—' }}">
    </div>
</div>

{{-- Line Items --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Line Items</span></div>
<div style="overflow-x:auto;max-height:300px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:180px">Particular</th>
                <th style="width:70px">HSN</th>
                <th style="width:55px">Qty</th>
                <th style="width:70px">Unit</th>
                <th style="width:70px">MRP</th>
                <th style="width:55px">Discount</th>
                <th style="width:70px">Price</th>
                <th style="width:80px">Amount</th>
                <th style="width:45px">GST%</th>
                <th style="width:45px">SGST%</th>
                <th style="width:45px">IGST%</th>
                <th style="width:45px">Cess%</th>
                <th style="width:80px">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = DB::table('invoice_detail')->where('Scan_Id', $punchDetail->Scan_Id)->get();
            @endphp
            @forelse($items as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><input type="text" readonly value="{{ $item->Particular ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->HSN ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Qty ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Unit ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->MRP ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Discount ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Price ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Amount ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->GST ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->SGST ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->IGST ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Cess ?? '' }}"></td>
                    <td><input type="text" readonly value="{{ $item->Total_Amount ?? '' }}"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align:center;color:#a8a29e;padding:1rem">No line items</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="f-row" style="display:flex;flex-wrap:nowrap;gap:.4rem">
    <div class="f-group" style="flex:1;min-width:100px">
        <label>Sub Total</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '0.00' }}">
    </div>
    <div class="f-group" style="flex:1;min-width:80px">
        <label>TCS %</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->TCS ?? '0' }}">
    </div>
    <div class="f-group" style="flex:1;min-width:100px">
        <label>Total Discount</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Total_Discount ?? '0.00' }}">
    </div>
    <div class="f-group" style="flex:1.3;min-width:140px">
        <label>Round Off</label>
        <input type="text" class="f-input" readonly value="{{ ($punchDetail->Round_Off_Value ?? '0.00') . ' (' . strtoupper($punchDetail->Round_Off_Type ?? 'none') . ')' }}">
    </div>
    <div class="f-group" style="flex:1;min-width:100px">
        <label>Total</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '0.00' }}">
    </div>
    <div class="f-group" style="flex:1;min-width:100px">
        <label>Grand Total</label>
        <input type="text" class="f-input" readonly value="{{ $punchDetail->Grand_Total ?? '0.00' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea class="f-input" readonly>{{ $punchDetail->Remark ?? '—' }}</textarea>
    </div>
</div>
