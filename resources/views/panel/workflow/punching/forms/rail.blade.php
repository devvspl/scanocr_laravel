{{-- Rail Travel Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Mode, Train Number, Agent Name, PNR Number --}}
<div class="f-row">
    <div class="f-group">
        <label>Mode</label>
        <input type="text" name="mode" class="f-input" value="Rail" readonly>
    </div>
    <div class="f-group">
        <label>Train Number</label>
        <input type="text" name="Train_Number" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Agent Name</label>
        <input type="text" name="Agent_Name" class="f-input" value="{{ $punchDetail->AgentName ?? '' }}">
    </div>
    <div class="f-group">
        <label>PNR Number <span style="color:#dc2626">*</span></label>
        <input type="text" name="PNR_Number" class="f-input" value="{{ $punchDetail->ServiceNo ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Booking Date, Journey Date, Booking ID, Transaction ID --}}
<div class="f-row">
    <div class="f-group">
        <label>Date of Booking <span style="color:#dc2626">*</span></label>
        <input type="date" name="Booking_Date" class="f-input" value="{{ $punchDetail->BookingDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Journey Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Journey_Date" class="f-input" value="{{ $punchDetail->FromDateTime ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Booking ID</label>
        <input type="text" name="Booking_Id" class="f-input" value="{{ $punchDetail->FDRNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Transaction ID</label>
        <input type="text" name="Transaction_Id" class="f-input" value="{{ $punchDetail->RegNo ?? '' }}">
    </div>
</div>

{{-- Row 3: Journey From, Journey To, Travel Class, Quota --}}
<div class="f-row">
    <div class="f-group">
        <label>Journey From</label>
        <input type="text" name="Journey_From" class="f-input" value="{{ $punchDetail->TripStarted ?? '' }}">
    </div>
    <div class="f-group">
        <label>Journey To</label>
        <input type="text" name="Journey_To" class="f-input" value="{{ $punchDetail->TripEnded ?? '' }}">
    </div>
    <div class="f-group">
        <label>Travel Class</label>
        @if($tempData && ($tempData->travel_class ?? false))<span class="hint">{{ $tempData->travel_class }}</span>@endif
        <select name="Travel_Class" class="f-input">
            <option value="">Select</option>
            @foreach(['Sleeper','III AC','II AC','I AC'] as $cls)
                <option value="{{ $cls }}" {{ ($punchDetail->TravelClass ?? '') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Quota</label>
        @if($tempData && ($tempData->quota ?? false))<span class="hint">{{ $tempData->quota }}</span>@endif
        <select name="Travel_Quota" class="f-input">
            <option value="">Select</option>
            @foreach(['General','Ladies','Senior Citizen','Divyang','Tatkal','Premium Tatkal'] as $q)
                <option value="{{ $q }}" {{ ($punchDetail->TravelQuota ?? '') === $q ? 'selected' : '' }}>{{ $q }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Row 4: Location --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="location_id" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Passenger Details --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Passenger Details</label>
        <textarea name="Passenger_Details" class="f-input" rows="2">{{ $punchDetail->PassengerDetail ?? '' }}</textarea>
    </div>
</div>


{{-- Row 4b: Employee Selection --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Employees <span style="color:#dc2626">*</span></span></div>
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
        <tbody id="railEmpBody">
            @php
                $railEmps = \DB::table('lodging_employee')->where('scan_id', $scanData->Scan_Id)->get();
            @endphp
            @if($railEmps->count() > 0)
                @foreach($railEmps as $idx => $emp)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><select name="Employee[]" class="rail-emp-sel" style="width:100%" required><option value="{{ $emp->emp_id }}" selected>{{ $emp->emp_name ?? '' }}</option></select></td>
                    <td><input type="text" name="EmpCode[]" value="{{ $emp->emp_code ?? '' }}" readonly></td>
                    <td>@if($idx === 0)<button type="button" class="btn-add-emp">+</button>@else<button type="button" class="btn-del-emp">−</button>@endif</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td>1</td>
                <td><select name="Employee[]" class="rail-emp-sel" style="width:100%" required><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-add-emp">+</button></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>



{{-- Row 5: Base Fare, GST, Surcharge, Other --}}
<div class="f-row">
    <div class="f-group">
        <label>Base Fare</label>
        <input type="text" name="Base_Fare" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Base_Fare ?? '' }}">
    </div>
    <div class="f-group">
        <label>GST (in Rs.)</label>
        <input type="text" name="GST" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->GSTIN ?? '' }}">
    </div>
    <div class="f-group">
        <label>Fees &amp; Surcharge</label>
        <input type="text" name="Surcharge" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Surcharge ?? '' }}">
    </div>
    <div class="f-group">
        <label>Other</label>
        <input type="text" name="Other" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->OthCharge_Amount ?? '' }}">
    </div>
</div>

{{-- Total Fare --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Total Fare <span style="color:#dc2626">*</span></label>
        <input type="text" name="Total_Amount" id="grandTotal" class="f-input" value="{{ $punchDetail->Total_Amount ?? $punchDetail->Grand_Total ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
