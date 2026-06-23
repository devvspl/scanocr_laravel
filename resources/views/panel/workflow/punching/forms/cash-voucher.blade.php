{{-- Cash Voucher Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Company, Voucher No, Voucher Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Company <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->company_name ?? false))<span class="hint">{{ $tempData->company_name }}</span>@endif
        <select name="CompanyID" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->CompanyID ?? '' }}">{{ $punchDetail->CompanyName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Voucher No</label>
        <input type="text" name="Voucher_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Voucher Date</label>
        <input type="date" name="Voucher_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
</div>

{{-- Row 2: Location, Payee, Payer --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Payee</label>
        <input type="text" name="Payee" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}">
    </div>
    <div class="f-group">
        <label>Payer</label>
        <input type="text" name="Payer" class="f-input" value="{{ $punchDetail->AgentName ?? '' }}">
    </div>
</div>

{{-- Row 3: Amount, Particular --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Particular</label>
        <input type="text" name="Particular" class="f-input" value="{{ $punchDetail->FileName ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
