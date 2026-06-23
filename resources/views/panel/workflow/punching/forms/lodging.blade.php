{{-- Lodging Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Location, Bill No, Bill Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Bill No</label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bill Date</label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
</div>

{{-- Row 2: Billing Name, Billing Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Billing Name</label>
        @if($tempData && ($tempData->billing_name ?? false))<span class="hint">{{ $tempData->billing_name }}</span>@endif
        <select name="Billing_Name" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->CompanyID ?? '' }}">{{ $punchDetail->CompanyName ?? 'Select' }}</option>
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
        <label>Hotel Name</label>
        @if($tempData && ($tempData->hotel_name ?? false))<span class="hint">{{ $tempData->hotel_name }}</span>@endif
        <input type="text" name="Hotel" class="f-input" value="{{ $punchDetail->Hotel ?? '' }}">
    </div>
    <div class="f-group">
        <label>Hotel Address</label>
        <input type="text" name="Hotel_Address" class="f-input" value="{{ $punchDetail->Hotel_Address ?? '' }}" readonly>
    </div>
</div>

{{-- Row 4: Billing Instruction, Booking ID, Check In, Check Out --}}
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
        <label>Check In Date/Time</label>
        <input type="text" name="Check_In" class="f-input" value="{{ $punchDetail->FromDateTime ?? '' }}">
    </div>
    <div class="f-group">
        <label>Check Out Date/Time</label>
        <input type="text" name="Check_Out" class="f-input" value="{{ $punchDetail->ToDateTime ?? '' }}">
    </div>
</div>

{{-- Row 5: Duration, No. of Rooms, Room Type, Meal Plan --}}
<div class="f-row">
    <div class="f-group">
        <label>Duration of Stay</label>
        <input type="text" name="Duration" class="f-input" value="{{ $punchDetail->Period ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>No. of Rooms</label>
        <input type="text" name="No_Room" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->ReferenceNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Room Type</label>
        <input type="text" name="Room_Type" class="f-input" value="{{ $punchDetail->TravelClass ?? '' }}">
    </div>
    <div class="f-group">
        <label>Meal Plan</label>
        <input type="text" name="Meal" class="f-input" value="{{ $punchDetail->Loc_Name ?? '' }}">
    </div>
</div>

{{-- Row 6: Rate, Amount, Other Charges, Discount, GST%, Grand Total --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Rate</label>
        <input type="text" name="Room_Rate" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->TariffPlan ?? '' }}">
    </div>
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Amount" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
    <div class="f-group">
        <label>Other Charges (+)</label>
        <input type="text" name="Other_Charge" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->OthCharge_Amount ?? '' }}">
    </div>
</div>
<div class="f-row cols-3">
    <div class="f-group">
        <label>Discount (-)</label>
        <input type="text" name="Discount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Discount ?? '' }}">
    </div>
    <div class="f-group">
        <label>GST (%)</label>
        <input type="text" name="Gst" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->GSTIN ?? '' }}">
    </div>
    <div class="f-group">
        <label>Grand Total</label>
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
