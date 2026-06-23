{{-- Cash Deposits/Withdrawals Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Type, Date, Bank Name, Branch --}}
<div class="f-row">
    <div class="f-group">
        <label>Type</label>
        @if($tempData && ($tempData->type ?? false))<span class="hint">{{ $tempData->type }}</span>@endif
        <select name="Type" class="f-input">
            <option value="">Select</option>
            <option value="Cash Deposit" {{ ($punchDetail->File_Type ?? '') === 'Cash Deposit' ? 'selected' : '' }}>Cash Deposit</option>
            <option value="Cash Withdrawal" {{ ($punchDetail->File_Type ?? '') === 'Cash Withdrawal' ? 'selected' : '' }}>Cash Withdrawal</option>
        </select>
    </div>
    <div class="f-group">
        <label>Date</label>
        <input type="date" name="Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bank Name</label>
        <input type="text" name="Bank_Name" class="f-input" value="{{ $punchDetail->BankName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Branch</label>
        <input type="text" name="Branch" class="f-input" value="{{ $punchDetail->BankAddress ?? '' }}">
    </div>
</div>

{{-- Row 2: Account No, Beneficiary Name, Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Account No</label>
        <input type="text" name="Account_No" class="f-input" value="{{ $punchDetail->BankAccountNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Beneficiary Name</label>
        <input type="text" name="Beneficiary_Name" class="f-input" value="{{ $punchDetail->Related_Person ?? '' }}">
    </div>
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
