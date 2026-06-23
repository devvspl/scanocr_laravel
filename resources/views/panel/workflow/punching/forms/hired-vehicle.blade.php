{{-- Hired Vehicle Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Agency Name, Agency Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Agency Name <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->agency_name ?? false))<span class="hint">{{ $tempData->agency_name }}</span>@endif
        <select name="Agency_Name" id="selVendor" style="width:100%">
            <option value="{{ $punchDetail->From_ID ?? '' }}">{{ $punchDetail->FromName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Agency Address</label>
        <input type="text" name="Agency_Address" class="f-input" value="{{ $punchDetail->AgencyAddress ?? '' }}" readonly>
    </div>
</div>

{{-- Row 2: Billing Name, Billing Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Billing Name <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->billing_name ?? false))<span class="hint">{{ $tempData->billing_name }}</span>@endif
        <select name="Billing_Name" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->To_ID ?? '' }}">{{ $punchDetail->ToName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Billing Address</label>
        <input type="text" name="Billing_Address" class="f-input" value="{{ $punchDetail->Related_Address ?? '' }}" readonly>
    </div>
</div>

{{-- Row 3: Employee, Emp Code, Vehicle No --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Employee</label>
        @if($tempData && ($tempData->employee_name ?? false))<span class="hint">{{ $tempData->employee_name }}</span>@endif
        <input type="text" name="Employee" class="f-input" value="{{ $punchDetail->EmployeeName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Emp Code</label>
        <input type="text" name="Emp_Code" class="f-input" value="{{ $punchDetail->EmployeeCode ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Vehicle No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Vehicle_No" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}" required>
    </div>
</div>

{{-- Row 4: Location, Invoice No, Invoice Date, Per KM Rate --}}
<div class="f-row">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Invoice No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Invoice_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Invoice Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Invoice_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Per KM Rate <span style="color:#dc2626">*</span></label>
        <input type="text" name="Per_KM_Rate" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->VehicleRs_PerKM ?? '' }}" required>
    </div>
</div>

{{-- Row 5: Booking Date, End Date, Start Reading, Closing Reading --}}
<div class="f-row">
    <div class="f-group">
        <label>Booking Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Journey_Start" class="f-input" value="{{ $punchDetail->FromDateTime ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>End Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Journey_End" class="f-input" value="{{ $punchDetail->ToDateTime ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Start Reading <span style="color:#dc2626">*</span></label>
        <input type="text" name="Opening_Reading" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->OpeningKm ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Closing Reading <span style="color:#dc2626">*</span></label>
        <input type="text" name="Closing_Reading" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->ClosingKm ?? '' }}" required>
    </div>
</div>

{{-- Row 6: Total KM, Other Charges, Total Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Total KM</label>
        <input type="text" name="Total_KM" class="f-input" readonly value="{{ $punchDetail->TotalRunKM ?? '' }}">
    </div>
    <div class="f-group">
        <label>Other Charges</label>
        <input type="text" name="Other_Charge" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->OthCharge_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Amount</label>
        <input type="text" name="Total_Amount" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
