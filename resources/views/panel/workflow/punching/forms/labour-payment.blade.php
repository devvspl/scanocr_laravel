{{-- Labour Payment Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Voucher No, Payment Date, Payee, Location --}}
<div class="f-row">
    <div class="f-group">
        <label>Voucher No</label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Payment Date</label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Payee</label>
        <input type="text" name="Payee" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}">
    </div>
    <div class="f-group">
        <label>Location</label>
        <input type="text" name="Location" class="f-input" value="{{ $punchDetail->Loc_Name ?? '' }}">
    </div>
</div>

{{-- Row 2: Particular, Total Amount, From Date, To Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Particular</label>
        <input type="text" name="Particular_Text" class="f-input" value="{{ $punchDetail->FileName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Amount</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>From Date</label>
        <input type="date" name="From_Date" class="f-input" value="{{ $punchDetail->FromDateTime ?? '' }}">
    </div>
    <div class="f-group">
        <label>To Date</label>
        <input type="date" name="To_Date" class="f-input" value="{{ $punchDetail->ToDateTime ?? '' }}">
    </div>
</div>

{{-- Line Items: Head, Amount --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Payment Heads</span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:200px">Head</th>
                <th style="width:120px">Amount</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr>
                <td>1</td>
                <td><select name="Head[]" class="particular-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="Amount[]" class="calc-trigger" inputmode="decimal"></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Sub Total --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Sub Total</label>
        <input type="text" name="Sub_Total" id="subTotal" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
