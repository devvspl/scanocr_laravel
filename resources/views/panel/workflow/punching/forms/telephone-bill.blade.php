{{-- Telephone Bill Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Bill Date, Invoice No, Biller Name --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Bill / Invoice Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Bill_Date" class="f-input" required onfocus="if (this.showPicker) this.showPicker(); else this.click();" @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" @endif value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Invoice / Bill No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Invoice_No" class="f-input" required value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Biller Name <span style="color:#dc2626">*</span></label>
        <input type="text" name="Biller_Name" class="f-input" required value="{{ $punchDetail->FromName ?? '' }}">
    </div>
</div>

{{-- Row 2: Telephone No, Invoice Period, Taxable Value --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Telephone No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Phone_No" class="f-input" required value="{{ $punchDetail->MobileNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Invoice Period</label>
        <input type="text" name="Period" class="f-input" value="{{ $punchDetail->Period ?? '' }}">
    </div>
    <div class="f-group">
        <label>Invoice Taxable Value</label>
        <input type="text" name="Taxable_Value" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
</div>

{{-- Row 3: CGST, SGST, IGST --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>CGST</label>
        <input type="text" name="CGST" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->CGST_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>SGST</label>
        <input type="text" name="SGST" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->SGST_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>IGST</label>
        <input type="text" name="IGST" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->GST_IGST_Amount ?? '' }}">
    </div>
</div>

{{-- Row 4: Total Amount Due, Total Outstanding, Last Payment Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Total Amount Due</label>
        <input type="text" name="Amount_Due" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Amount Outstanding <span style="color:#dc2626">*</span></label>
        <input type="text" name="Amount_Outstanding" class="f-input calc-trigger" inputmode="decimal" required value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
    <div class="f-group">
        <label>Last Payment Date</label>
        <input type="date" name="Last_Payment_Date" class="f-input" value="{{ $punchDetail->DueDate ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
