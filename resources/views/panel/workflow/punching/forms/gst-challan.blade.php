{{-- GST Challan Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: CPIN, Deposit Date, CIN, Bank Name --}}
<div class="f-row">
    <div class="f-group">
        <label>CPIN <span style="color:#dc2626">*</span></label>
        <input type="text" name="CPIN" class="f-input" required value="{{ $punchDetail->CPIN ?? '' }}">
    </div>
    <div class="f-group">
        <label>Deposit Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="Deposit_Date" class="f-input" required value="{{ $punchDetail->File_Date ?? '' }}">
    </div>
    <div class="f-group">
        <label>CIN</label>
        <input type="text" name="CIN" class="f-input" value="{{ $punchDetail->CIN ?? '' }}">
    </div>
    <div class="f-group">
        <label>Bank Name</label>
        <input type="text" name="Bank_Name" class="f-input" value="{{ $punchDetail->BankName ?? '' }}">
    </div>
</div>

{{-- Row 2: BRN, GSTIN, Email, Mobile --}}
<div class="f-row">
    <div class="f-group">
        <label>BRN</label>
        <input type="text" name="BRN" class="f-input" value="{{ $punchDetail->BankBSRCode ?? '' }}">
    </div>
    <div class="f-group">
        <label>GSTIN <span style="color:#dc2626">*</span></label>
        <input type="text" name="GSTIN" class="f-input" required value="{{ $punchDetail->GSTIN ?? '' }}">
    </div>
    <div class="f-group">
        <label>Email ID</label>
        <input type="text" name="Email" class="f-input" value="{{ $punchDetail->Email ?? '' }}">
    </div>
    <div class="f-group">
        <label>Mobile No</label>
        <input type="text" name="Mobile" class="f-input" value="{{ $punchDetail->MobileNo ?? '' }}">
    </div>
</div>

{{-- Row 3: Company Name, Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Company Name</label>
        <input type="text" name="Company" class="f-input" value="{{ $punchDetail->Company ?? '' }}">
    </div>
    <div class="f-group">
        <label>Address</label>
        <input type="text" name="Address" class="f-input" value="{{ $punchDetail->Related_Address ?? '' }}">
    </div>
</div>

{{-- GST Breakdown Table --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">GST Breakdown</span></div>
<div style="overflow-x:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:100px">Particular</th>
                <th style="width:80px">Tax</th>
                <th style="width:80px">Interest</th>
                <th style="width:80px">Penalty</th>
                <th style="width:80px">Fees</th>
                <th style="width:80px">Other</th>
                <th style="width:80px">Total</th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            @foreach(['CGST','SGST','IGST','Cess'] as $i => $label)
            <tr>
                <td><input type="text" name="Particular[]" value="{{ $label }}" readonly></td>
                <td><input type="text" name="Tax[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Interest[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Penalty[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Fees[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Other[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Total[]" readonly></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Total Challan Amount --}}
<div class="f-row cols-1">
    <div class="f-group">
        <label>Total Challan Amount <span style="color:#dc2626">*</span></label>
        <input type="text" name="Total_Amount" id="grandTotal" class="f-input" readonly required value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
