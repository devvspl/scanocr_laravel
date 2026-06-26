{{-- Air Travel Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Mode, Agent Name, PNR Number --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Mode</label>
        <input type="text" name="mode" class="f-input" value="Air" readonly>
    </div>
    <div class="f-group">
        <label>Agent Name <span style="color:#dc2626">*</span></label>
        <select name="Agent_Name" id="selAirAgent" style="width:100%" required>
            @if($punchDetail && $punchDetail->AgentName)
                <option value="{{ $punchDetail->AgentName }}" selected>{{ $punchDetail->AgentName }}</option>
            @else
                <option value="">Select Agent</option>
            @endif
        </select>
        <input type="text" name="Agent_Name" id="airAgentInput" class="f-input" style="display:none;margin-top:0.3rem" placeholder="Type agent name manually" value="{{ $punchDetail->AgentName ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>PNR Number <span style="color:#dc2626">*</span></label>
        <input type="text" name="PNR_Number" class="f-input" value="{{ $punchDetail->ServiceNo ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Booking Date, Journey Date, Airline, Ticket Number --}}
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
        <label>Airline <span style="color:#dc2626">*</span></label>
        <select name="Airline" id="selAirline" style="width:100%" required>
            @if($punchDetail && $punchDetail->Airline)
                <option value="{{ $punchDetail->Airline }}" selected>{{ $punchDetail->Airline }}</option>
            @else
                <option value="">Select Airline</option>
            @endif
        </select>
        <input type="text" name="Airline" id="airlineInput" class="f-input" style="display:none;margin-top:0.3rem" placeholder="Type airline name manually" value="{{ $punchDetail->Airline ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Ticket Number <span style="color:#dc2626">*</span></label>
        <input type="text" name="Ticket_Number" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
</div>

{{-- Row 3: Journey From, Journey To, Travel Class, Location --}}
<div class="f-row">
    <div class="f-group">
        <label>Journey From <span style="color:#dc2626">*</span></label>
        <input type="text" name="Journey_From" class="f-input" value="{{ $punchDetail->TripStarted ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Journey To <span style="color:#dc2626">*</span></label>
        <input type="text" name="Journey_To" class="f-input" value="{{ $punchDetail->TripEnded ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Travel Class <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->travel_class ?? false))<span class="hint">{{ $tempData->travel_class }}</span>@endif
        <select name="Travel_Class" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Economy','Premium Economy','Business','First'] as $cls)
                <option value="{{ $cls }}" {{ ($punchDetail->TravelClass ?? '') === $cls ? 'selected' : '' }}>{{ $cls }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="location_id" id="selLocation" style="width:100%" required>
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 4: Passenger Details --}}
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
        <tbody id="airEmpBody">
            @php
                $airEmps = \DB::table('lodging_employee')->where('scan_id', $scanData->Scan_Id)->get();
            @endphp
            @if($airEmps->count() > 0)
                @foreach($airEmps as $idx => $emp)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><select name="Employee[]" class="air-emp-sel" style="width:100%" required><option value="{{ $emp->emp_id }}" selected>{{ $emp->emp_name ?? '' }}</option></select></td>
                    <td><input type="text" name="EmpCode[]" value="{{ $emp->emp_code ?? '' }}" readonly></td>
                    <td>@if($idx === 0)<button type="button" class="btn-add-emp">+</button>@else<button type="button" class="btn-del-emp">−</button>@endif</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td>1</td>
                <td><select name="Employee[]" class="air-emp-sel" style="width:100%" required><option value="">Select</option></select></td>
                <td><input type="text" name="EmpCode[]" readonly></td>
                <td><button type="button" class="btn-add-emp">+</button></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>



{{-- Row 5: Base Fare, GST, Surcharge, CUTE Charge, Extra Luggage, Other --}}
<div class="f-row cols-3">
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
</div>
<div class="f-row cols-3">
    <div class="f-group">
        <label>CUTE Charge</label>
        <input type="text" name="Cute_Charge" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Cute_Charge ?? '' }}">
    </div>
    <div class="f-group">
        <label>Extra Luggage</label>
        <input type="text" name="Extra_Luggage" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Extra_Luggage ?? '' }}">
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
        <input type="text" name="Total_Amount" id="grandTotal" class="f-input" value="{{ $punchDetail->Total_Amount ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
