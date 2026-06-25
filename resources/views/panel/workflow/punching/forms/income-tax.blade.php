{{-- Income Tax/TDS Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Section, Company, Nature of Payment, Assessment Year --}}
<div class="f-row">
    <div class="f-group">
        <label>Section <span style="color:#dc2626">*</span></label>
        <select name="Section" class="f-input" required>
            <option value="">Select</option>
            @foreach([
                '194R - Benefit or perquisite in respect of business or profession',
                '194H - Commission or brokerage',
                '194JB - Fee for professional service or royalty etc @10%',
                '194JA - Fees for Technical Services @2%',
                '194A - Interest other than Interest on securities',
                '194C - Payment to Contractor / Subcontractor (1%)',
                '194C - Payment to Contractor / Subcontractor (2%)',
                '194I - Rent (Land, building or furniture)',
                '194Q - TDS on purchase of Goods',
            ] as $sec)
                @php $secCode = trim(explode(' - ', $sec)[0]); @endphp
                <option value="{{ $secCode }}" {{ ($punchDetail->Section ?? '') === $secCode ? 'selected' : '' }}>{{ $sec }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Company <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->company ?? false))<span class="hint">{{ $tempData->company }}</span>@endif
        <select name="Company" id="selBuyer" style="width:100%">
            @if($punchDetail && ($punchDetail->CompanyID ?? false))
                <option value="{{ $punchDetail->CompanyID }}" selected>{{ $punchDetail->Company ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Nature of Payment <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->nature_of_payment ?? false))<span class="hint">{{ $tempData->nature_of_payment }}</span>@endif
        <select name="Payment_Nature" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Income Tax','TDS','Advance Tax','Demand Challan','Form 16-A'] as $type)
                <option value="{{ $type }}" {{ ($punchDetail->NatureOfPayment ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Assessment Year <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->assessment_year ?? false))<span class="hint">{{ $tempData->assessment_year }}</span>@endif
        @php
            $currentYear = (int) date('Y');
            $ayears = [];
            for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--) {
                $ayears[] = ($y - 1) . '-' . $y;
            }
        @endphp
        <select name="Assessment_Year" class="f-input" required>
            <option value="">Select</option>
            @foreach($ayears as $ay)
                <option value="{{ $ay }}" {{ ($punchDetail->Financial_Year ?? '') === $ay ? 'selected' : '' }}>{{ $ay }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Row 2: Bank Name, BSR Code, Challan No, Challan Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Bank Name <span style="color:#dc2626">*</span></label>
        <input type="text" name="Bank_Name" class="f-input" value="{{ $punchDetail->BankName ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>BSR Code <span style="color:#dc2626">*</span></label>
        <input type="text" name="BSR_Code" class="f-input" value="{{ $punchDetail->BSRCode ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Challan No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Challan_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Challan Date <span style="color:#dc2626">*</span></label>
        <input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange())
                                    min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}"
                                    max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}"
                                @endif name="Challan_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}" required>
    </div>
</div>

{{-- Row 3: Bank Reference No, Amount --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Bank Reference No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Ref_No" class="f-input" value="{{ $punchDetail->ReferenceNo ?? '' }}" required>
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
