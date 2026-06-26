{{-- Default View (Read-Only) - Fallback for document types without specific view partials --}}

<div class="f-row cols-2">
    <div class="f-group">
        <label>Document Type</label>
        <input type="text" class="f-input" value="{{ $scanData->doc_type_label ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Bill/Invoice Date</label>
        <input type="date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" readonly>
    </div>
</div>

<div class="f-row cols-2">
    <div class="f-group">
        <label>Bill/Invoice No</label>
        <input type="text" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>From</label>
        <input type="text" class="f-input" value="{{ $punchDetail->FromName ?? '' }}" readonly>
    </div>
</div>

<div class="f-row cols-2">
    <div class="f-group">
        <label>To</label>
        <input type="text" class="f-input" value="{{ $punchDetail->ToName ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Location</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Loc_Name ?? '' }}" readonly>
    </div>
</div>

<div class="f-row">
    <div class="f-group">
        <label>Department</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Department ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Category</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Category ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Ledger</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Ledger ?? '' }}" readonly>
    </div>
</div>

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Sub Total</label>
        <input type="text" class="f-input" value="{{ $punchDetail->SubTotal ?? '0.00' }}" readonly>
    </div>
    <div class="f-group">
        <label>Total Amount</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Total_Amount ?? '0.00' }}" readonly>
    </div>
    <div class="f-group">
        <label>Grand Total</label>
        <input type="text" class="f-input" value="{{ $punchDetail->Grand_Total ?? '0.00' }}" readonly>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Remark</label>
        <textarea class="f-input" readonly>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>

<p style="text-align:center;color:#a8a29e;font-size:.7rem;padding:1rem;background:#fef3c7;border-radius:.5rem;margin-top:1rem">
    ℹ️ This document type doesn't have a specific view template yet. Showing basic information.
</p>
