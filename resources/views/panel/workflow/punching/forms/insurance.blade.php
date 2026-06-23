{{-- Insurance Policy Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Insurance Type, Insurance Company, Policy Number, Policy Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Insurance Type</label>
        <input type="text" name="Insurance_Type" class="f-input" value="{{ $punchDetail->File_Type ?? '' }}">
    </div>
    <div class="f-group">
        <label>Insurance Company</label>
        <input type="text" name="Insurance_Company" class="f-input" value="{{ $punchDetail->AgentName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Policy Number</label>
        <input type="text" name="Policy_Number" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Policy Date</label>
        <input type="date" name="Policy_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}">
    </div>
</div>

{{-- Row 2: From Date, To Date, Vehicle No, Location --}}
<div class="f-row">
    <div class="f-group">
        <label>From Date</label>
        <input type="date" name="From_Date" class="f-input" value="{{ $punchDetail->FromDateTime ?? '' }}">
    </div>
    <div class="f-group">
        <label>To Date</label>
        <input type="date" name="To_Date" class="f-input" value="{{ $punchDetail->ToDateTime ?? '' }}">
    </div>
    <div class="f-group">
        <label>Vehicle No</label>
        <input type="text" name="Vehicle_No" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Location</label>
        <input type="text" name="Location" class="f-input" value="{{ $punchDetail->Loc_Name ?? '' }}">
    </div>
</div>

{{-- Row 3: Premium Amount --}}
<div class="f-row">
    <div class="f-group">
        <label>Premium Amount</label>
        <input type="text" name="Premium_Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-group" style="margin-bottom:.5rem">
    <label>Remark</label>
    <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
</div>
