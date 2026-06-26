{{-- Vehicle Maintenance Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Vendor Name, Billing To --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor Name <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vendor_name ?? false))<span class="hint">{{ $tempData->vendor_name }}</span>@endif
        <select name="Vendor_Name" id="selVendor" style="width:100%">
            <option value="{{ $punchDetail->From_ID ?? '' }}">{{ $punchDetail->FromName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Billing To <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->billing_to ?? false))<span class="hint">{{ $tempData->billing_to }}</span>@endif
        <select name="Billing_To" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->To_ID ?? '' }}">{{ $punchDetail->ToName ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 2: Invoice No, Invoice Date, Vehicle No, Location --}}
<div class="f-row">
    <div class="f-group">
        <label>Invoice No</label>
        <input type="text" name="InvoiceNo" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Invoice Date</label>
        <input type="date" name="Bill_Date" class="f-input"  onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Vehicle No</label>
        <input type="text" name="VehicleRegNo" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Work_Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
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
                <th style="width:80px">Total</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr>
                <td>1</td>
                <td><input type="text" name="Particular[]"></td>
                <td><input type="text" name="HSN[]"></td>
                <td><input type="text" name="Qty[]" class="calc-trigger" inputmode="decimal"></td>
                <td><select name="Unit[]" class="unit-sel"></select></td>
                <td><input type="text" name="MRP[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Discount[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Price[]" readonly></td>
                <td><input type="text" name="Amount[]" class="amt-field" readonly></td>
                <td><input type="text" name="GST[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="SGST[]" readonly></td>
                <td><input type="text" name="IGST[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="TAmount[]" class="total-field" readonly></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Sub Total</label>
        <input type="text" name="Sub_Total" id="subTotal" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total</label>
        <input type="text" name="Total" id="totalField" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Round Off</label>
        <input type="text" name="Total_Discount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Discount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Grand Total</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
