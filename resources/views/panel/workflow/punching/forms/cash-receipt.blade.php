{{-- Cash Receipt Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Receipt No, Receipt Date, Payment Mode --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Receipt No</label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Receipt Date</label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
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

{{-- Row 2: From (Received From), To (Receiver), Location --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Received From <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->from_name ?? false))<span class="hint">{{ $tempData->from_name }}</span>@endif
        <select name="From" id="selVendor" style="width:100%">
            <option value="{{ $punchDetail->From_ID ?? '' }}">{{ $punchDetail->FromName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Receiver <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->to_name ?? false))<span class="hint">{{ $tempData->to_name }}</span>@endif
        <select name="To" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->To_ID ?? '' }}">{{ $punchDetail->ToName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 3: Department, Category, Ledger --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Department</label>
        <select name="Department" id="selDept" style="width:100%">
            <option value="{{ $punchDetail->DepartmentID ?? '' }}">{{ $punchDetail->Department ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Category</label>
        <select name="Category" id="selCategory" style="width:100%">
            <option value="{{ $punchDetail->Category ?? '' }}">{{ $punchDetail->Category ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Ledger</label>
        <select name="Ledger" id="selLedger" style="width:100%">
            <option value="{{ $punchDetail->Ledger ?? '' }}">{{ $punchDetail->Ledger ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 4: Grand Total --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
