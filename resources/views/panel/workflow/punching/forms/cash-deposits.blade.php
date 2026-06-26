{{-- Cash Deposits/Withdrawals Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Type, Date, Bank Name --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Type <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->payment_mode ?? false))<span class="hint">{{ $tempData->payment_mode }}</span>@endif
        <select name="Type" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Cash Deposit','Cash Withdrawal','Cheque Deposit'] as $mode)
                <option value="{{ $mode }}" {{ ($punchDetail->File_Type ?? '') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Date"  onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Bank Name <span style="color:#dc2626">*</span></label>
        <input type="text" name="Bank_Name" class="f-input" value="{{ $punchDetail->BankName ?? '' }}" required>
    </div>
    </div>

    {{-- Row 2: Branch, Account No, Beneficiary Name --}}
    <div class="f-row cols-3">
    <div class="f-group">
        <label>Branch <span style="color:#dc2626">*</span></label>
        <input type="text" name="Branch" class="f-input" value="{{ $punchDetail->BankAddress ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Account No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Account_No" class="f-input" value="{{ $punchDetail->BankAccountNo ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Beneficiary Name <span style="color:#dc2626">*</span></label>
        <input type="text" name="Beneficiary_Name" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}" required>
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
