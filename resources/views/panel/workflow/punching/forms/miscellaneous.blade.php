{{-- Miscellaneous Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Bill No, Bill Date --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Voucher No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
</div>

{{-- Row 2: From, To, Location --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>From (Company)</label>
        @if($tempData && ($tempData->company ?? false))<span class="hint">{{ $tempData->company }}</span>@endif
        <select name="From" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->From_ID ?? '' }}">{{ $punchDetail->FromName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>To (Vendor) <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vendor ?? false))<span class="hint">{{ $tempData->vendor }}</span>@endif
        <select name="To" id="selVendor" style="width:100%">
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

{{-- Row 3: Description, Grand Total --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Description / Particular <span style="color:#dc2626">*</span></label>
        <input type="text" name="Description" class="f-input" value="{{ $punchDetail->Particular ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Grand_Total ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
