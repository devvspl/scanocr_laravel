{{-- Machine Operation Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Company, Company Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Company <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->company_name ?? false))<span class="hint">{{ $tempData->company_name }}</span>@endif
        <select name="CompanyID" id="selBuyer" style="width:100%">
            @if($punchDetail && ($punchDetail->CompanyID ?? false))
                <option value="{{ $punchDetail->CompanyID }}" selected>{{ $punchDetail->Company ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Company Address</label>
        <input type="text" name="Related_Address" class="f-input" value="{{ $punchDetail->Related_Address ?? '' }}" readonly>
    </div>
</div>

{{-- Row 2: Vendor, Vendor Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vendor_name ?? false))<span class="hint">{{ $tempData->vendor_name }}</span>@endif
        <select name="To_ID" id="selVendor" style="width:100%">
            @if($punchDetail && ($punchDetail->To_ID ?? false))
                <option value="{{ $punchDetail->To_ID }}" selected>{{ $punchDetail->ToName ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Vendor Address</label>
        <input type="text" name="AgencyAddress" class="f-input" value="{{ $punchDetail->AgencyAddress ?? '' }}" readonly>
    </div>
</div>

{{-- Row 3: Vehicle No, Vehicle Type, Location, Invoice Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Vehicle No <span style="color:#dc2626">*</span></label>
        <input type="text" name="VehicleRegNo" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Vehicle Type <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->vehicle_type ?? false))<span class="hint">{{ $tempData->vehicle_type }}</span>@endif
        <select name="Vehicle_Type" class="f-input" required>
            <option value="">Select</option>
            <option value="Tractor" {{ ($punchDetail->Vehicle_Type ?? '') === 'Tractor' ? 'selected' : '' }}>Tractor</option>
            <option value="JCB" {{ ($punchDetail->Vehicle_Type ?? '') === 'JCB' ? 'selected' : '' }}>JCB</option>
        </select>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="location_id" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Invoice Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Invoice_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}" required>
    </div>
</div>

{{-- Row 4: Particular --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Particular</label>
        <input type="text" name="Particular" class="f-input" value="{{ $punchDetail->Particular ?? '' }}">
    </div>
</div>

{{-- Row 5: Hour, Trips, Rate per Trip, Total Amount --}}
<div class="f-row">
    <div class="f-group">
        <label>Hour</label>
        <input type="text" name="Hour" class="f-input" value="{{ $punchDetail->Period ?? '' }}">
    </div>
    <div class="f-group">
        <label>Trips <span style="color:#dc2626">*</span></label>
        <input type="text" name="Trip" class="f-input mo-calc" inputmode="decimal" value="{{ $punchDetail->TotalRunKM ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Rate per Trip <span style="color:#dc2626">*</span></label>
        <input type="text" name="Rate" class="f-input mo-calc" inputmode="decimal" value="{{ $punchDetail->RateOfInterest ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Total Amount</label>
        <input type="text" name="Total_Amount" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
