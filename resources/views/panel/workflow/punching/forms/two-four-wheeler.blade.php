{{-- 2/4 Wheeler Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Employee, Emp Code, Bill Date, Vehicle No --}}
<div class="f-row">
    <div class="f-group">
        <label>Employee / Payee <span style="color:#dc2626">*</span></label>
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
        <label>Bill Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Vehicle No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Vehicle_No" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Vehicle Type, Location, Rs/KM --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Vehicle Type <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vehicle_type ?? false))<span class="hint">{{ $tempData->vehicle_type }}</span>@endif
        <select name="Vehicle_Type" class="f-input">
            <option value="Two Wheeler" {{ ($punchDetail->Vehicle_Type ?? '') === 'Two Wheeler' ? 'selected' : '' }}>Two Wheeler</option>
            <option value="Four Wheeler" {{ ($punchDetail->Vehicle_Type ?? '') === 'Four Wheeler' ? 'selected' : '' }}>Four Wheeler</option>
        </select>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Rs/KM</label>
        <input type="text" name="Rate" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->VehicleRs_PerKM ?? '' }}">
    </div>
</div>

{{-- Line Items: Opening KM, Closing KM, Total KM, Amount --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">KM Details</span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:120px">Opening KM</th>
                <th style="width:120px">Closing KM</th>
                <th style="width:100px">Total KM</th>
                <th style="width:100px">Amount</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr>
                <td>1</td>
                <td><input type="text" name="Dist_Opening[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Dist_Closing[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Km[]" readonly></td>
                <td><input type="text" name="Amount[]" readonly></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Total KM</label>
        <input type="text" name="Total_KM" id="totalField" class="f-input" readonly value="{{ $punchDetail->TotalRunKM ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Amount</label>
        <input type="text" name="Total_Amount" id="subTotal" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
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
