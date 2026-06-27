    {{-- Cash Voucher Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Voucher No, Voucher Date, Location --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Voucher No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Voucher_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Voucher Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Voucher_Date" class="f-input" onfocus="if (this.showPicker) this.showPicker(); else this.click();" 
        @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) 
            min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" 
            max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" 
        @endif
        value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 2: Payee, Payer, Particular --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Payee <span style="color:#dc2626">*</span></label>
        <input type="text" name="Payee" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Payer <span style="color:#dc2626">*</span></label>
        <input type="text" name="Payer" class="f-input" value="{{ $punchDetail->AgentName ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Particular <span style="color:#dc2626">*</span></label>
        <input type="text" name="Particular" class="f-input" value="{{ $punchDetail->FileName ?? '' }}" required>
    </div>
</div>

{{-- Row 3: Amount --}}
<div class="f-row cols-1">
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
