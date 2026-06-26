{{-- Meals Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Hotel/Restaurant, Hotel Address, Bill Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Hotel / Restaurant <span style="color:#dc2626">*</span></label>
        <select name="Hotel" id="selMealsHotel" style="width:100%">
            @if($punchDetail && ($punchDetail->Hotel_Name ?? false))
                <option value="{{ $punchDetail->Hotel_Name }}" selected>{{ $punchDetail->Hotel_Name ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Hotel Address</label>
        <input type="text" name="Hotel_Address" class="f-input" value="{{ $punchDetail->Hotel_Address ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Bill Date <span style="color:#dc2626">*</span></label>
        <input type="date"  onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" name="Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Invoice No, Location, Occasion/Purpose --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Invoice No</label>
        <input type="text" name="InvoiceNo" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Occasion / Purpose <span style="color:#dc2626">*</span></label>
        <select name="Detail" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Breakfast','Lunch','Dinner','Tea/Snacks','Client Meeting','Team Meeting','Training','Travel Meal','Other'] as $occ)
                <option value="{{ $occ }}" {{ ($punchDetail->FileName ?? '') === $occ ? 'selected' : '' }}>{{ $occ }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Row 3: Amount --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Amount" class="f-input" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}" required>
    </div>
</div>

{{-- Employee List (multiple) --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Employees</span></div>
<div style="overflow-x:auto;max-height:200px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="min-width:200px">Employee</th>
                <th style="width:100px">Emp Code</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="mealsEmpBody">
            @php
                $mealsEmps = \DB::table('lodging_employee')->where('Scan_Id', $scanData->Scan_Id)->get();
            @endphp
            @if($mealsEmps->count() > 0)
                @foreach($mealsEmps as $idx => $emp)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><select name="Employee[]" class="meals-emp-sel" style="width:100%"><option value="{{ $emp->emp_id }}" selected>{{ $emp->emp_name ?? '' }}</option></select></td>
                    <td><input type="text" name="EmpCode[]" value="{{ $emp->emp_code ?? '' }}" readonly></td>
                    <td>@if($idx === 0)<button type="button" class="btn-add-meals-emp">+</button>@else<button type="button" class="btn-del-meals-emp">−</button>@endif</td>
                </tr>
                @endforeach
            @elseif($punchDetail && ($punchDetail->EmployeeID ?? false))
                <tr>
                    <td>1</td>
                    <td><select name="Employee[]" class="meals-emp-sel" style="width:100%"><option value="{{ $punchDetail->EmployeeID }}" selected>{{ $punchDetail->Employee_Name ?? '' }}</option></select></td>
                    <td><input type="text" name="EmpCode[]" value="{{ $punchDetail->EmployeeCode ?? '' }}" readonly></td>
                    <td><button type="button" class="btn-add-meals-emp">+</button></td>
                </tr>
            @else
            <tr>
                <td>1</td>
                <td><select name="Employee[]" class="meals-emp-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-add-meals-emp">+</button></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
