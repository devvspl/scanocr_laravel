{{-- Electricity Bill Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Location, Payment Date, Biller Name --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Payment Date <span style="color:#dc2626">*</span></label>
        <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();" name="PaymentDate" class="f-input" value="{{ $punchDetail->PremiumDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Biller Name <span style="color:#dc2626">*</span></label>
        <input type="text" name="Biller_Name" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}" required>
    </div>
</div>

{{-- Row 2: BP No, Bill Period, Meter Number --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>BP No <span style="color:#dc2626">*</span></label>
        <input type="text" name="BP_No" class="f-input" value="{{ $punchDetail->ReferenceNo ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Bill Period <span style="color:#dc2626">*</span></label>
        @php
            $savedPeriod = $punchDetail->Period ?? '';
            $periodParts = $savedPeriod ? explode(' to ', $savedPeriod) : ['',''];
        @endphp
        <div style="display:flex;gap:.3rem;align-items:center">
            <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();" name="Period_From" class="f-input" style="flex:1;width: 100px;" value="{{ trim($periodParts[0] ?? '') }}" required>
            <span style="font-size:.6rem;color:#78716c">to</span>
            <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();" name="Period_To" class="f-input" style="flex:1;width: 100px;" value="{{ trim($periodParts[1] ?? '') }}" required>
        </div>
        <input type="hidden" name="Period" value="{{ $savedPeriod }}">
    </div>
    <div class="f-group">
        <label>Meter Number <span style="color:#dc2626">*</span></label>
        <input type="text" name="Meter_No" class="f-input" value="{{ $punchDetail->MeterNumber ?? '' }}" required>
    </div>
</div>

{{-- Row 3: Bill Date, Bill No, Previous Reading --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Bill Date <span style="color:#dc2626">*</span></label>
        <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();" 
        @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) 
            min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" 
            max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" 
        @endif
        name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Bill No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label style="display:flex;justify-content:space-between;align-items:center">
            <span>Previous Reading <span style="color:#7f1d1d">*</span></span>
            <span id="prevReadingHint" class="hint" style="display:none;cursor:pointer;margin:0" title="Click to apply this value"></span>
        </label>
        <input type="text" name="Previous_Reading" class="f-input" inputmode="decimal" value="{{ $punchDetail->PreviousReading ?? '' }}" required>
    </div>
</div>

{{-- Row 4: Current Reading, Unit Consumed, Last Date of Payment --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Current Reading <span style="color:#dc2626">*</span></label>
        <input type="text" name="Current_Reading" class="f-input" inputmode="decimal" value="{{ $punchDetail->CurrentReading ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Unit Consumed <span style="color:#dc2626">*</span></label>
        <input type="text" name="Unit_Consumed" class="f-input" inputmode="decimal" value="{{ $punchDetail->UnitsConsumed ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Last Date of Payment</label>
        <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();" name="Last_Date" class="f-input" value="{{ $punchDetail->LastDateOfPayment ?? '' }}">
    </div>
</div>

{{-- Row 5: Payment Mode, Bill Amount, Payment Amount --}}
<div class="f-row cols-3">
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
        <label>Bill Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Bill_Amount" class="f-input" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Payment Amount</label>
        <input type="text" name="Payment_Amount" class="f-input" inputmode="decimal" value="{{ $punchDetail->Payment_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
