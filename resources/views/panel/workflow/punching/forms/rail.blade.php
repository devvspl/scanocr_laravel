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
        <label>PNR Number</label>
        <input type="text" name="PNR_Number" class="f-input" value="{{ $punchDetail->ServiceNo ?? '' }}">
    </div>
</div>

{{-- Row 2: Booking Date, Journey Date, Booking ID, Transaction ID --}}
<div class="f-row">
    <div class="f-group">
        <label>Date of Booking</label>
        <input type="date" name="Booking_Date" class="f-input" value="{{ $punchDetail->BookingDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Journey Date</label>
        <input type="date" name="Journey_Date" class="f-input" value="{{ $punchDetail->FromDateTime ?? '' }}">
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
        <input type="text" name="Journey_Upto" class="f-input" value="{{ $punchDetail->TripEnded ?? '' }}">
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
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Passenger Details --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Passenger Details</label>
        <textarea name="Passenger" class="f-input" rows="2">{{ $punchDetail->PassengerDetail ?? '' }}</textarea>
    </div>
</div>

{{-- Row 5: Base Fare, GST, Surcharge, Other --}}
<div class="f-row">
    <div class="f-group">
        <label>Base Fare <span style="color:#dc2626">*</span></label>
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
        <label>Total Fare</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
