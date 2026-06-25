{{-- Local Conveyance Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData, $kmRows --}}

{{-- Row 1: Mode, Location, Employee, Emp Code --}}
<div class="f-row">
    <div class="f-group">
        <label>Mode <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->mode ?? false))<span class="hint">{{ $tempData->mode }}</span>@endif
        <select name="Travel_Mode" class="f-input" required>
            <option value="">Select</option>
            @foreach(['Sharing Taxi/Cab','Auto','Bus'] as $mode)
                <option value="{{ $mode }}" {{ ($punchDetail->TravelMode ?? '') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Location <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Employee <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->employee_name ?? false))<span class="hint">{{ $tempData->employee_name }}</span>@endif
        <select name="Employee" id="selLocalEmployee" style="width:100%">
            @if($punchDetail && ($punchDetail->EmployeeID ?? false))
                <option value="{{ $punchDetail->EmployeeID }}" selected>{{ $punchDetail->Employee_Name ?? '' }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Emp Code</label>
        <input type="text" name="Emp_Code" class="f-input" value="{{ $punchDetail->EmployeeCode ?? '' }}" readonly>
    </div>
</div>

{{-- Row 2: Vehicle No, Month, Calculation Base, Per KM Rate --}}
<div class="f-row">
    <div class="f-group">
        <label>Vehicle No <span style="color:#dc2626">*</span></label>
        <input type="text" name="Vehicle_No" class="f-input" value="{{ $punchDetail->VehicleRegNo ?? '' }}" required>
    </div>
    <div class="f-group">
        <label>Month <span style="color:#dc2626">*</span></label>
        @if($tempData && ($tempData->month ?? false))<span class="hint">{{ $tempData->month }}</span>@endif
        <select name="Month" class="f-input" required>
            <option value="">Select</option>
            @foreach(['1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $k => $v)
                <option value="{{ $k }}" {{ ($punchDetail->Month ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Calculation Base</label>
        <select name="cal_by" id="calBySelect" class="f-input">
            <option value="KM_Base" {{ ($punchDetail->Cal_By ?? '') === 'KM_Base' ? 'selected' : '' }}>K.M. Base</option>
            <option value="Fixed" {{ ($punchDetail->Cal_By ?? '') === 'Fixed' ? 'selected' : '' }}>Fixed</option>
        </select>
    </div>
    <div class="f-group">
        <label id="rateLabel">{{ ($punchDetail->Cal_By ?? '') === 'Fixed' ? 'Fixed Amount' : 'Per KM Rate' }} <span style="color:#dc2626">*</span></label>
        <input type="text" name="Rate_Per_KM" class="f-input lc-calc" inputmode="decimal" value="{{ $punchDetail->VehicleRs_PerKM ?? '' }}" required>
    </div>
</div>

{{-- Line Items: Date, Opening, Closing, Total KM, Amount --}}
<div id="lcTripSection">
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Trip Details</span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:100px">Date</th>
                <th style="width:100px">Opening</th>
                <th style="width:100px">Closing</th>
                <th style="width:80px">Total KM</th>
                <th style="width:80px">Amount</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            @if(isset($kmRows) && $kmRows->count() > 0)
                @foreach($kmRows as $idx => $row)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><input type="date" onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange())
                                    min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}"
                                    max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}"
                                @endif  name="Date[]" value="{{ $row->JourneyStartDt ? \Carbon\Carbon::parse($row->JourneyStartDt)->format('Y-m-d') : '' }}"></td>
                    <td><input type="text" name="Dist_Opening[]" class="lc-calc" inputmode="decimal" value="{{ $row->DistTraOpen ?? '' }}"></td>
                    <td><input type="text" name="Dist_Closing[]" class="lc-calc" inputmode="decimal" value="{{ $row->DistTraClose ?? '' }}"></td>
                    <td><input type="text" name="Km[]" readonly value="{{ $row->Totalkm ?? '' }}"></td>
                    <td><input type="text" name="Amount[]" readonly value="{{ $row->FilledTAmt ?? '' }}"></td>
                    <td>@if($idx === 0)<button type="button" class="btn-add-row">+</button>@else<button type="button" class="btn-del-row">−</button>@endif</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td>1</td>
                <td><input type="date" name="Date[]"></td>
                <td><input type="text" name="Dist_Opening[]" class="lc-calc" inputmode="decimal"></td>
                <td><input type="text" name="Dist_Closing[]" class="lc-calc" inputmode="decimal"></td>
                <td><input type="text" name="Km[]" readonly></td>
                <td><input type="text" name="Amount[]" readonly></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>
</div> {{-- end lcTripSection --}}

{{-- Totals with Round Off --}}
<div class="f-row" style="display:flex;flex-wrap:nowrap;gap:.4rem">
    <div class="f-group" style="flex:1;min-width:0">
        <label>Total KM</label>
        <input type="text" name="Total_KM" id="lcTotalKm" class="f-input" readonly value="{{ $punchDetail->TotalRunKM ?? '' }}">
    </div>
    <div class="f-group" style="flex:1;min-width:0">
        <label>Total Amount</label>
        <input type="text" name="Total_Amount" id="lcSubTotal" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
    <div class="f-group" style="flex:0.8;min-width:0">
        <label>Discount (-)</label>
        <input type="text" name="Total_Discount" id="lcDiscount" class="f-input lc-calc" inputmode="decimal" value="{{ $punchDetail->Total_Discount ?? '' }}">
    </div>
    <div class="f-group" style="flex:1.2;min-width:0">
        <label>Round Off</label>
        <div style="display:flex;border:1px solid #d6d3d1;border-radius:.5rem;overflow:hidden;height:24px;background:#fafaf9">
            <label style="font-size:.6rem;font-weight:600;cursor:pointer;padding:0 .35rem;display:flex;align-items:center;border-right:1px solid #d6d3d1;user-select:none;transition:all .15s;margin-bottom:0" class="round-opt" data-val="upper">
                <input type="radio" name="Round_Off_Type" value="upper" style="display:none" {{ ($punchDetail->Round_Off_Type ?? '') === 'upper' ? 'checked' : '' }}>▲
            </label>
            <label style="font-size:.6rem;font-weight:600;cursor:pointer;padding:0 .35rem;display:flex;align-items:center;border-right:1px solid #d6d3d1;user-select:none;transition:all .15s;margin-bottom:0" class="round-opt" data-val="lower">
                <input type="radio" name="Round_Off_Type" value="lower" style="display:none" {{ ($punchDetail->Round_Off_Type ?? '') === 'lower' ? 'checked' : '' }}>▼
            </label>
            <label style="font-size:.58rem;font-weight:500;cursor:pointer;padding:0 .3rem;display:flex;align-items:center;border-right:1px solid #d6d3d1;user-select:none;transition:all .15s;margin-bottom:0" class="round-opt" data-val="none">
                <input type="radio" name="Round_Off_Type" value="none" style="display:none" {{ !in_array(($punchDetail->Round_Off_Type ?? ''), ['upper','lower']) ? 'checked' : '' }}>✕
            </label>
            <input type="text" name="Round_Off_Value" id="lcRoundOff" style="flex:1;border:none;outline:none;background:transparent;font-size:.7rem;padding:0 .4rem;min-width:0" inputmode="decimal" value="" readonly>
        </div>
    </div>
    <div class="f-group" style="flex:1;min-width:0">
        <label>Grand Total</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
