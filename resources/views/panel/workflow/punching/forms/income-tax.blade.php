{{-- Income Tax/TDS Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Section, Company, Nature of Payment, Assessment Year --}}
<div class="f-row">
    <div class="f-group">
        <label>Section</label>
        <input type="text" name="Section" class="f-input" value="{{ $punchDetail->Section ?? '' }}">
    </div>
    <div class="f-group">
        <label>Company</label>
        @if($tempData && ($tempData->company ?? false))<span class="hint">{{ $tempData->company }}</span>@endif
        <select name="Company" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->CompanyID ?? '' }}">{{ $punchDetail->CompanyName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Nature of Payment</label>
        @if($tempData && ($tempData->nature_of_payment ?? false))<span class="hint">{{ $tempData->nature_of_payment }}</span>@endif
        <select name="Payment_Nature" class="f-input">
            <option value="">Select</option>
            @foreach(['Income Tax','TDS','Advance Tax','Demand Challan'] as $type)
                <option value="{{ $type }}" {{ ($punchDetail->NatureOfPayment ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Assessment Year</label>
        @if($tempData && ($tempData->assessment_year ?? false))<span class="hint">{{ $tempData->assessment_year }}</span>@endif
        <input type="text" name="Assessment_Year" class="f-input" value="{{ $punchDetail->Financial_Year ?? '' }}">
    </div>
</div>

{{-- Row 2: Bank Name, BSR Code, Challan No, Challan Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Bank Name</label>
        <input type="text" name="Bank_Name" class="f-input" value="{{ $punchDetail->BankName ?? '' }}">
    </div>
    <div class="f-group">
        <label>BSR Code</label>
        <input type="text" name="BSR_Code" class="f-input" value="{{ $punchDetail->BSRCode ?? '' }}">
    </div>
    <div class="f-group">
        <label>Challan No</label>
        <input type="text" name="Challan_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Challan Date</label>
        <input type="date" name="Challan_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}">
    </div>
</div>

{{-- Row 3: Bank Reference No, Amount --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Bank Reference No</label>
        <input type="text" name="Ref_No" class="f-input" value="{{ $punchDetail->ReferenceNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Amount</label>
        <input type="text" name="Grand_Total" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
