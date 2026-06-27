{{-- Cash Receipt Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Receipt No, Receipt Date, Payment Mode --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Receipt No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Receipt_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Receipt Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Receipt_Date" class="f-input" onfocus="if (this.showPicker) this.showPicker(); else this.click();" 
        @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) 
            min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" 
            max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" 
        @endif
        value="{{ $punchDetail->BillDate ?? '' }}" required>
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
</div>

{{-- Row 2: Company, Received From, Receiver --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Company <span style="color:#dc2626">*</span></label>
        <select name="CompanyID" id="selBuyer" style="width:100%">
            @if($punchDetail && ($punchDetail->CompanyID ?? false))
                <option value="{{ $punchDetail->CompanyID }}" selected>{{ $punchDetail->Company ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Received From <span style="color:#dc2626">*</span></label>
        <input type="text" name="ReceivedFrom" class="f-input" value="{{ $punchDetail->FromName ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Receiver <span style="color:#dc2626">*</span></label>
        <input type="text" name="Receiver" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}" required>
    </div>
</div>

{{-- Row 3: Particular, Location, Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Particular</label>
        <input type="text" name="Particular" class="f-input" value="{{ $punchDetail->FileName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Amount" class="f-input" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
