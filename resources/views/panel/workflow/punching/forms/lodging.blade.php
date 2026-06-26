{{-- Lodging Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Location, Bill No, Bill Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Bill No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Bill Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Bill_Date" class="f-input"  onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Billing Name, Billing Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Billing Name <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->billing_name ?? false))<span class="hint">{{ $tempData->billing_name }}</span>@endif
        <select name="Billing_Name" id="selBuyer" style="width:100%">
            @if($punchDetail && ($punchDetail->CompanyID ?? false))
                <option value="{{ $punchDetail->CompanyID }}" selected>{{ $punchDetail->Company ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Billing Address</label>
        <input type="text" name="Billing_Address" class="f-input" value="{{ $punchDetail->Related_Address ?? '' }}" readonly>
    </div>
</div>

{{-- Row 3: Hotel Name, Hotel Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Hotel Name <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->hotel_name ?? false))<span class="hint">{{ $tempData->hotel_name }}</span>@endif
        <select name="Hotel" id="selHotel" style="width:100%">
            @if($punchDetail && ($punchDetail->Hotel ?? false))
                <option value="{{ $punchDetail->Hotel }}" selected>{{ $punchDetail->Hotel_Name ?? $punchDetail->Hotel ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Hotel Address</label>
        <input type="text" name="Hotel_Address" class="f-input" value="{{ $punchDetail->Hotel_Address ?? '' }}" readonly>
    </div>
</div>

{{-- Row 4: Billing Instruction, Booking ID, Arrival Date, Departure Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Billing Instruction</label>
        <select name="Billing_Instruction" class="f-input">
            <option value="">Select</option>
            <option value="Direct" {{ ($punchDetail->Particular ?? '') === 'Direct' ? 'selected' : '' }}>Direct</option>
            <option value="Bill to Company" {{ ($punchDetail->Particular ?? '') === 'Bill to Company' ? 'selected' : '' }}>Bill to Company</option>
        </select>
    </div>
    <div class="f-group">
        <label>Booking ID</label>
        <input type="text" name="Booking_Id" class="f-input" value="{{ $punchDetail->RegNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Arrival Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Arrival_Date" class="f-input" value="{{ $punchDetail->FromDateTime ? \Carbon\Carbon::parse($punchDetail->FromDateTime)->format('Y-m-d') : '' }}" required>
    </div>
    <div class="f-group">
        <label>Departure Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Departure_Date" class="f-input" value="{{ $punchDetail->ToDateTime ? \Carbon\Carbon::parse($punchDetail->ToDateTime)->format('Y-m-d') : '' }}" required>
    </div>
</div>

{{-- Row 5: Duration, No. of Rooms, Room Type, Meal Plan --}}
<div class="f-row">
    <div class="f-group">
        <label>Duration of Stay</label>
        <input type="text" name="Duration" id="lodgingDuration" class="f-input" value="{{ $punchDetail->Period ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>No. of Rooms <span style="color:#dc2626">*</span></label>
        <input type="text" name="No_Room" class="f-input" inputmode="decimal" value="{{ $punchDetail->ReferenceNo ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Room Type</label>
        <select name="Room_Type" class="f-input">
            <option value="">Select</option>
            @foreach(['Single','Double','Twin','Triple','Suite','Deluxe','Super Deluxe','Executive','Family'] as $rt)
                <option value="{{ $rt }}" {{ ($punchDetail->TravelClass ?? '') === $rt ? 'selected' : '' }}>{{ $rt }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Meal Plan</label>
        <input type="text" name="Meal" class="f-input" value="{{ $punchDetail->Loc_Name ?? '' }}">
    </div>
</div>

{{-- Row 6: Room Rate, Amount, Other Charges --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Room Rate <span style="color:#dc2626">*</span></label>
        <input type="text" name="Room_Rate" class="f-input lodging-calc" inputmode="decimal" value="{{ $punchDetail->TariffPlan ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Amount" id="lodgingAmount" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
    <div class="f-group">
        <label>Other Charges (+)</label>
        <input type="text" name="Other_Charge" class="f-input lodging-calc" inputmode="decimal" value="{{ $punchDetail->OthCharge_Amount ?? '' }}">
    </div>
</div>

{{-- Row 7: Discount, GST%, Grand Total with Round Off --}}
<div class="f-row" style="display:flex;flex-wrap:nowrap;gap:.4rem">
    <div class="f-group" style="flex:0.8;min-width:0">
        <label>Discount (-)</label>
        <input type="text" name="Discount" class="f-input lodging-calc" inputmode="decimal" value="{{ $punchDetail->Total_Discount ?? '' }}">
    </div>
    <div class="f-group" style="flex:0.8;min-width:0">
        <label>GST (%)</label>
        <input type="text" name="Gst" class="f-input lodging-calc" inputmode="decimal" value="{{ $punchDetail->GSTIN ?? '' }}">
    </div>
    <div class="f-group" style="flex:1.2;min-width:0">
        <label>Round Off</label>
        <div style="display:flex;border:1px solid #d6d3d1;border-radius:.5rem;overflow:hidden;height:24px;background:#fafaf9">
            <label style="font-size:.6rem;font-weight:600;cursor:pointer;padding:0 .35rem;display:flex;align-items:center;border-right:1px solid #d6d3d1;user-select:none;transition:all .15s;margin-bottom:0" class="round-opt" data-val="upper">
                <input type="radio" name="Round_Off_Type" value="upper" style="display:none" {{ ($punchDetail->Round_Off_Type ?? '') === 'upper' ? 'checked' : '' }}>▲
            </label>
            <label style="font-size:.6rem;font-weight:600;cursor:pointer;padding:0 .35rem;display:flex;align-items:center;border-right:1px solid #d6d3d1;user-select:none;transition:all .15s;margin-bottom:0" class="round-opt" data-val="lower">
                <input type="radio" name="Round_Off_Type" value="lower" style="display:none" {{ ($punchDetail->Round_Off_Type ?? '') === 'lower' ? 'checked' : '' }}>▼
            </label>
            <label style="font-size:.58rem;font-weight:500;cursor:pointer;padding:0 .3rem;display:flex;align-items:center;border-right:1px solid #d6d3d1;user-select:none;transition:all .15s;margin-bottom:0" class="round-opt" data-val="none">
                <input type="radio" name="Round_Off_Type" value="none" style="display:none" {{ !in_array(($punchDetail->Round_Off_Type ?? ''), ['upper','lower']) ? 'checked' : '' }}>✕
            </label>
            <input type="text" name="Round_Off_Value" id="lodgingRoundOff" style="flex:1;border:none;outline:none;background:transparent;font-size:.7rem;padding:0 .4rem;min-width:0" readonly>
        </div>
    </div>
    <div class="f-group" style="flex:1;min-width:0">
        <label>Grand Total</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Employee List --}}
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
        <tbody id="lodgingEmpBody">
            @php
                $lodgingEmps = \DB::table('lodging_employee')->where('Scan_Id', $scanData->Scan_Id)->get();
            @endphp
            @if($lodgingEmps->count() > 0)
                @foreach($lodgingEmps as $idx => $emp)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><select name="Employee[]" class="lodging-emp-sel" style="width:100%"><option value="{{ $emp->emp_id }}" selected>{{ $emp->emp_name ?? '' }}</option></select></td>
                    <td><input type="text" name="EmpCode[]" value="{{ $emp->emp_code ?? '' }}" readonly></td>
                    <td>@if($idx === 0)<button type="button" class="btn-add-emp">+</button>@else<button type="button" class="btn-del-emp">−</button>@endif</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td>1</td>
                <td><select name="Employee[]" class="lodging-emp-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-add-emp">+</button></td>
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
