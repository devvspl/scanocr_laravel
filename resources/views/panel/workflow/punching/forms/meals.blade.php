{{-- Meals Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Hotel Name, Bill No, Bill Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Hotel Name</label>
        <input type="text" name="Hotel" class="f-input" value="{{ $punchDetail->Hotel_Name ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bill No</label>
        <input type="text" name="InvoiceNo" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bill Date</label>
        <input type="date" name="Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
</div>

{{-- Row 2: Hotel Address --}}
<div class="f-row">
    <div class="f-group">
        <label>Hotel Address</label>
        <input type="text" name="Hotel_Address" class="f-input" value="{{ $punchDetail->Hotel_Address ?? '' }}">
    </div>
</div>

{{-- Row 3: Employee, Emp Code, Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Employee</label>
        @if($tempData && ($tempData->employee_name ?? false))<span class="hint">{{ $tempData->employee_name }}</span>@endif
        <select name="Employee" id="selVendor" style="width:100%">
            <option value="{{ $punchDetail->EmployeeID ?? '' }}">{{ $punchDetail->EmployeeName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Emp Code</label>
        <input type="text" name="Emp_Code" class="f-input" value="{{ $punchDetail->EmployeeCode ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Row 4: Location, Detail --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Detail</label>
        <input type="text" name="Detail" class="f-input" value="{{ $punchDetail->FileName ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
