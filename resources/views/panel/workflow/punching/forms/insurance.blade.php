{{-- Insurance Policy Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Insurance Type, Insurance Company, Policy Number --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Insurance Type <span style="color:#dc2626">*</span></label>
        <select name="Insurance_Type" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Vehicle Insurance','Health Insurance','Life Insurance','Property Insurance','Fire Insurance','Other'] as $type)
                <option value="{{ $type }}" {{ ($punchDetail->File_Type ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Insurance Company <span style="color:#dc2626">*</span></label>
        <input type="text" name="Insurance_Company" class="f-input" value="{{ $punchDetail->AgentName ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Policy Number <span style="color:#dc2626">*</span></label>
        <input type="text" name="Policy_Number" class="f-input" value="{{ $punchDetail->File_No ?? '' }}" required>
    </div>
</div>

{{-- Row 2: Policy Date, From Date, To Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Policy Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Policy_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>From Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="From_Date" class="f-input" value="{{ $punchDetail->FromDateTime ? \Carbon\Carbon::parse($punchDetail->FromDateTime)->format('Y-m-d') : '' }}" required>
    </div>
    <div class="f-group">
        <label>To Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="To_Date" class="f-input" value="{{ $punchDetail->ToDateTime ? \Carbon\Carbon::parse($punchDetail->ToDateTime)->format('Y-m-d') : '' }}" required>
    </div>
</div>

{{-- Row 3: Vehicle No, Location, Premium Amount --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Vehicle No</label>
        <input type="text" name="Vehicle_No" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Premium Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Premium_Amount" class="f-input" inputmode="decimal" value="{{ $punchDetail->Total_Amount ?? '' }}" required>
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
