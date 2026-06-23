{{-- Vehicle Fuel Form Partial --}}
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

{{-- Row 2: Dealer Code, Invoice No, Invoice Date, Due Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Dealer Code</label>
        <input type="text" name="Dealer_Code" class="f-input" value="{{ $punchDetail->BSRCode ?? '' }}">
    </div>
    <div class="f-group">
        <label>Invoice No</label>
        <input type="text" name="InvoiceNo" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Invoice Date</label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Due Date</label>
        <input type="date" name="Due_Date" class="f-input" value="{{ $punchDetail->DueDate ?? '' }}">
    </div>
</div>

{{-- Row 3: Location, Vehicle No --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Work_Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Vehicle No</label>
        <input type="text" name="VehicleNo" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}" required>
    </div>
</div>

{{-- Row 4: Description --}}
<div class="f-row">
    <div class="f-group">
        <label>Description</label>
        <input type="text" name="Description" class="f-input" value="{{ $punchDetail->FileName ?? '' }}">
    </div>
</div>

{{-- Row 5: Liter, Per Liter Rate, Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Liter</label>
        <input type="text" name="Liter" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->MeterNumber ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Per Liter Rate</label>
        <input type="text" name="Rate" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->TariffPlan ?? '' }}">
    </div>
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Amount" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Row 6: Round Off, Grand Total --}}
<div class="f-row cols-2">
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
