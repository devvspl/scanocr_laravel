{{-- Miscellaneous Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}
@php
    // Miscellaneous uses punchfile2 table
    $pf2 = \DB::table('punchfile2')->where('Scan_Id', $scanData->Scan_Id)->first();
@endphp

{{-- Row 1: Voucher No, Voucher Date, Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Voucher No <span style="color:#dc2626">*</span></label>
        <input type="text" name="VoucherNo" class="f-input" value="{{ $pf2->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Voucher Date <span style="color:#dc2626">*</span></label>
        <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange())
                                    min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}"
                                    max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}"
                                @endif   name="Voucher_Date" class="f-input" value="{{ $pf2->RegPurDate ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Date <span style="color:#dc2626">*</span></label>
        <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();"  name="File_Date" class="f-input" value="{{ $pf2->File_Date ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Company (From), Vendor (To), Location --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Company (From) <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->company ?? false))<span class="hint">{{ $tempData->company }}</span>@endif
        <select name="Company" id="selBuyer" style="width:100%">
            @if($pf2 && ($pf2->CompanyID ?? false))
                <option value="{{ $pf2->CompanyID }}" selected>{{ $pf2->Company ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Vendor (To) <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vendor ?? false))<span class="hint">{{ $tempData->vendor }}</span>@endif
        <select name="Vendor" id="selVendor" style="width:100%">
            @if($pf2 && ($pf2->VendorID ?? false))
                <option value="{{ $pf2->VendorID }}" selected>{{ $pf2->Vendor ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $pf2->Location ?? '' }}">{{ $pf2->Location ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 3: Particular, Amount --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Particular / Description <span style="color:#dc2626">*</span></label>
        <input type="text" name="Particular" class="f-input" value="{{ $pf2->Additional_Exposure ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Amount" class="f-input" inputmode="decimal" value="{{ $pf2->TotalAmount ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $pf2->Remark ?? '' }}</textarea>
    </div>
</div>
