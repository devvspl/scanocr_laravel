{{-- Electricity Bill Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Location, Payment Date --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Payment Date</label>
        <input type="date" name="PaymentDate" class="f-input" value="{{ $punchDetail->PremiumDate ?? '' }}">
    </div>
</div>

{{-- Row 2: Biller Name, BP No, Bill Period, Meter Number --}}
<div class="f-row">
    <div class="f-group">
        <label>Biller Name</label>
        <input type="text" name="Biller_Name" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}">
    </div>
    <div class="f-group">
        <label>BP No</label>
        <input type="text" name="Consumer_No" class="f-input" value="{{ $punchDetail->ReferenceNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bill Period</label>
        <input type="text" name="Period" class="f-input" value="{{ $punchDetail->Period ?? '' }}">
    </div>
    <div class="f-group">
        <label>Meter Number</label>
        <input type="text" name="Meter_No" class="f-input" value="{{ $punchDetail->MeterNumber ?? '' }}">
    </div>
</div>

{{-- Row 3: Bill Date, Bill No, Previous Reading, Current Reading --}}
<div class="f-row">
    <div class="f-group">
        <label>Bill Date</label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bill No</label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Previous Reading</label>
        <input type="text" name="Previous_Reading" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->PreviousReading ?? '' }}">
    </div>
    <div class="f-group">
        <label>Current Reading</label>
        <input type="text" name="Current_Reading" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->CurrentReading ?? '' }}">
    </div>
</div>

{{-- Row 4: Unit Consumed, Last Date of Payment, Payment Mode, Bill Amount, Payment Amount --}}
<div class="f-row">
    <div class="f-group">
        <label>Unit Consumed</label>
        <input type="text" name="Unit_Consumed" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->UnitsConsumed ?? '' }}">
    </div>
    <div class="f-group">
        <label>Last Date of Payment</label>
        <input type="date" name="Last_Date" class="f-input" value="{{ $punchDetail->LastDateOfPayment ?? '' }}">
    </div>
    <div class="f-group">
        <label>Payment Mode</label>
        @if($tempData && ($tempData->payment_mode ?? false))<span class="hint">{{ $tempData->payment_mode }}</span>@endif
        <select name="Payment_Mode" class="f-input">
            <option value="">Select</option>
            @foreach(['Cash','Cheque','RTGS','NEFT','UPI','Net Banking'] as $mode)
                <option value="{{ $mode }}" {{ ($punchDetail->NatureOfPayment ?? '') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Bill Amount</label>
        <input type="text" name="Grand_Total" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

<div class="f-row cols-1">
    <div class="f-group">
        <label>Payment Amount</label>
        <input type="text" name="Payment_Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Payment_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
