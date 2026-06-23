{{-- Local Conveyance Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Mode, Location, Employee, Emp Code --}}
<div class="f-row">
    <div class="f-group">
        <label>Mode</label>
        @if($tempData && ($tempData->mode ?? false))<span class="hint">{{ $tempData->mode }}</span>@endif
        <select name="Travel_Mode" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Sharing Taxi/Cab','Auto','Bus'] as $mode)
                <option value="{{ $mode }}" {{ ($punchDetail->TravelMode ?? '') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
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
</div>

{{-- Row 2: Vehicle No, Month, Calculation Base, Per KM Rate --}}
<div class="f-row">
    <div class="f-group">
        <label>Vehicle No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Vehicle_No" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Month <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->month ?? false))<span class="hint">{{ $tempData->month }}</span>@endif
        <select name="Month" class="f-input" required>
            <option value="">Select</option>
            @foreach(['1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $k => $v)
                <option value="{{ $k }}" {{ ($punchDetail->Month ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Calculation Base</label>
        <select name="cal_by" class="f-input">
            <option value="KM_Base">K.M. Base</option>
            <option value="Fixed">Fixed</option>
        </select>
    </div>
    <div class="f-group">
        <label>Per KM Rate <span style="color:#dc2626">*</span></label>
        <input type="text" name="Rate_Per_KM" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->VehicleRs_PerKM ?? '' }}" required>
    </div>
</div>

{{-- Line Items: Date, Opening, Closing, Total KM, Amount --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Trip Details</span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:100px">Date</th>
                <th style="width:100px">Opening</th>
                <th style="width:100px">Closing</th>
                <th style="width:80px">Total KM</th>
                <th style="width:80px">Amount</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr>
                <td>1</td>
                <td><input type="date" name="Date[]"></td>
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
<div class="f-row cols-2">
    <div class="f-group">
        <label>Total KM</label>
        <input type="text" name="Total_KM" class="f-input" readonly value="{{ $punchDetail->TotalRunKM ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Amount</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
