{{-- Labour Payment Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Voucher No, Payment Date, Payee, Location --}}
<div class="f-row">
    <div class="f-group">
        <label>Voucher No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Voucher_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Payment Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Payment_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Payee <span style="color:#dc2626">*</span></label>
        <input type="text" name="Payee" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 2: Particular, Total Amount, From Date, To Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Particular</label>
        <input type="text" name="Particular_Text" class="f-input" value="{{ $punchDetail->FileName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Total_Amount" class="f-input" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>From Date</label>
        <input type="date" name="From_Date" class="f-input" value="{{ $punchDetail->FromDateTime ? \Carbon\Carbon::parse($punchDetail->FromDateTime)->format('Y-m-d') : '' }}">
    </div>
    <div class="f-group">
        <label>To Date</label>
        <input type="date" name="To_Date" class="f-input" value="{{ $punchDetail->ToDateTime ? \Carbon\Carbon::parse($punchDetail->ToDateTime)->format('Y-m-d') : '' }}">
    </div>
</div>

{{-- Line Items: Head, Amount --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Payment Heads</span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="min-width:200px">Head</th>
                <th style="width:120px">Amount</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="labourHeadsBody">
            @php
                $labourDetails = \DB::table('labour_payment_detail')->where('Scan_Id', $scanData->Scan_Id)->get();
            @endphp
            @if($labourDetails->count() > 0)
                @foreach($labourDetails as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><select name="Head[]" class="labour-head-sel" style="width:100%"><option value="{{ $item->Head ?? '' }}" selected>{{ $item->Head ?? '' }}</option></select></td>
                    <td><input type="text" name="Amount[]" class="f-input labour-amt" inputmode="decimal" value="{{ $item->Amount ?? '' }}"></td>
                    <td>@if($idx === 0)<button type="button" class="btn-add-labour">+</button>@else<button type="button" class="btn-del-labour">−</button>@endif</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td>1</td>
                <td><select name="Head[]" class="labour-head-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="Amount[]" class="f-input labour-amt" inputmode="decimal"></td>
                <td><button type="button" class="btn-add-labour">+</button></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Sub Total --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Sub Total</label>
        <input type="text" name="Sub_Total" id="labourSubTotal" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
