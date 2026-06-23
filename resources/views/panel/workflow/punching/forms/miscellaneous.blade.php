{{-- Miscellaneous Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Company, Voucher No, Voucher Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Company <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->company ?? false))<span class="hint">{{ $tempData->company }}</span>@endif
        <select name="Company" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->CompanyID ?? '' }}">{{ $punchDetail->CompanyName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Voucher No <span style="color:#dc2626">*</span></label>
        <input type="text" name="VoucherNo" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Voucher Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Voucher_Date" class="f-input" value="{{ $punchDetail->RegPurDate ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Location, Vendor, Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Location ?? '' }}">{{ $punchDetail->Location ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Vendor <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vendor ?? false))<span class="hint">{{ $tempData->vendor }}</span>@endif
        <select name="Vendor" id="selVendor" style="width:100%">
            <option value="{{ $punchDetail->VendorID ?? '' }}">{{ $punchDetail->VendorName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->TotalAmount ?? '' }}" required>
    </div>
</div>

{{-- Row 3: Particular --}}
<div class="f-row">
    <div class="f-group">
        <label>Particular <span style="color:#dc2626">*</span></label>
        <input type="text" name="Particular" class="f-input" value="{{ $punchDetail->Additional_Exposure ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
