{{-- Credit Note Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Credit Note No, Credit Note Date --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Credit Note No</label>
        <input type="text" name="CreditNo" class="f-input" value="{{ $punchDetail->CreditNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Credit Note Date</label>
        <input type="date" name="CreditDate" class="f-input" value="{{ $punchDetail->CreditDate ?? '' }}">
    </div>
</div>

{{-- Row 2: Vendor, Buyer --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor</label>
        @if($tempData && ($tempData->vendor ?? false))<span class="hint">{{ $tempData->vendor }}</span>@endif
        <select name="To" id="selVendor" style="width:100%">
            <option value="{{ $punchDetail->To_ID ?? '' }}">{{ $punchDetail->ToName ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Buyer</label>
        @if($tempData && ($tempData->buyer ?? false))<span class="hint">{{ $tempData->buyer }}</span>@endif
        <select name="From" id="selBuyer" style="width:100%">
            <option value="{{ $punchDetail->From_ID ?? '' }}">{{ $punchDetail->FromName ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 3: Vendor Address, Buyer Address --}}
<div class="f-row cols-2">
    <div class="f-group">
        <label>Vendor Address</label>
        <input type="text" name="Vendor_Address" class="f-input" value="{{ $punchDetail->AgencyAddress ?? '' }}" readonly>
    </div>
    <div class="f-group">
        <label>Buyer Address</label>
        <input type="text" name="Buyer_Address" class="f-input" value="{{ $punchDetail->Loc_Add ?? '' }}" readonly>
    </div>
</div>

{{-- Row 4: Payment Mode, Invoice No, Invoice Date --}}
<div class="f-row cols-3">
    <div class="f-group">
        <label>Payment Mode</label>
        @if($tempData && ($tempData->mode_of_payment ?? false))<span class="hint">{{ $tempData->mode_of_payment }}</span>@endif
        <select name="Payment_Mode" class="f-input">
            <option value="">Select</option>
            @foreach(['Credit','Cash','Cheque'] as $m)
                <option value="{{ $m }}" {{ ($punchDetail->NatureOfPayment ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select>
    </div>
    <div class="f-group">
        <label>Invoice No</label>
        <input type="text" name="Bill_No" class="f-input" value="{{ $punchDetail->File_No ?? '' }}">
    </div>
    <div class="f-group">
        <label>Invoice Date</label>
        <input type="date" name="Bill_Date" class="f-input" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
</div>

{{-- Row 5: Buyer Order No, Order Date, Dispatch Through, Delivery Note Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Buyer Order No</label>
        <input type="text" name="Buyer_Order" class="f-input" value="{{ $punchDetail->ServiceNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>Order Date</label>
        <input type="date" name="Buyer_Order_Date" class="f-input" value="{{ $punchDetail->BookingDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Dispatch Through</label>
        <input type="text" name="Dispatch_Through" class="f-input" value="{{ $punchDetail->Particular ?? '' }}">
    </div>
    <div class="f-group">
        <label>Delivery Note Date</label>
        <input type="date" name="Delivery_Note_Date" class="f-input" value="{{ $punchDetail->DueDate ?? '' }}">
    </div>
</div>

{{-- Row 6: Department, Category, Ledger, File --}}
<div class="f-row">
    <div class="f-group">
        <label>Department</label>
        @if($tempData && ($tempData->department ?? false))<span class="hint">{{ $tempData->department }}</span>@endif
        <select name="Department" id="selDept" style="width:100%">
            <option value="{{ $punchDetail->DepartmentID ?? '' }}">{{ $punchDetail->Department ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Category</label>
        <select name="Category" id="selCategory" style="width:100%">
            <option value="{{ $punchDetail->Category ?? '' }}">{{ $punchDetail->Category ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>Ledger</label>
        <select name="Ledger" id="selLedger" style="width:100%">
            <option value="{{ $punchDetail->Ledger ?? '' }}">{{ $punchDetail->Ledger ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>File</label>
        <select name="File" id="selFile" style="width:100%">
            <option value="{{ $punchDetail->FileName ?? '' }}">{{ $punchDetail->FileName ?? 'Select' }}</option>
        </select>
    </div>
</div>

{{-- Row 7: Location, LR Number, LR Date, Cartoon Number --}}
<div class="f-row">
    <div class="f-group">
        <label>Location</label>
        @if($tempData && ($tempData->location ?? false))<span class="hint">{{ $tempData->location }}</span>@endif
        <select name="Location" id="selLocation" style="width:100%">
            <option value="{{ $punchDetail->Loc_Name ?? '' }}">{{ $punchDetail->Loc_Name ?? 'Select' }}</option>
        </select>
    </div>
    <div class="f-group">
        <label>LR Number</label>
        <input type="text" name="LR_Number" class="f-input" value="{{ $punchDetail->FDRNo ?? '' }}">
    </div>
    <div class="f-group">
        <label>LR Date</label>
        <input type="date" name="LR_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}">
    </div>
    <div class="f-group">
        <label>Cartoon Number</label>
        <input type="text" name="Cartoon_Number" class="f-input" value="{{ $punchDetail->RegNo ?? '' }}">
    </div>
</div>

{{-- Line Items --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Line Items</span></div>
<div style="overflow-x:auto;max-height:300px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:180px">Particular</th>
                <th style="width:70px">HSN</th>
                <th style="width:55px">Qty</th>
                <th style="width:70px">Unit</th>
                <th style="width:70px">MRP</th>
                <th style="width:55px">Discount</th>
                <th style="width:70px">Price</th>
                <th style="width:80px">Amount</th>
                <th style="width:45px">GST%</th>
                <th style="width:45px">SGST%</th>
                <th style="width:45px">IGST%</th>
                <th style="width:45px">Cess%</th>
                <th style="width:80px">Total</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr>
                <td>1</td>
                <td><select name="Particular[]" class="particular-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="HSN[]"></td>
                <td><input type="text" name="Qty[]" class="calc-trigger" inputmode="decimal"></td>
                <td><select name="Unit[]" class="unit-sel"></select></td>
                <td><input type="text" name="MRP[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Discount[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Price[]" readonly></td>
                <td><input type="text" name="Amount[]" class="amt-field" readonly></td>
                <td><input type="text" name="GST[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="SGST[]" readonly></td>
                <td><input type="text" name="IGST[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="Cess[]" class="calc-trigger" inputmode="decimal"></td>
                <td><input type="text" name="TAmount[]" class="total-field" readonly></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Sub Total</label>
        <input type="text" name="Sub_Total" id="subTotal" class="f-input" readonly value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
    <div class="f-group">
        <label>TCS %</label>
        <input type="text" name="TCS" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->TCS ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total</label>
        <input type="text" name="Total" id="totalField" class="f-input" readonly value="{{ $punchDetail->Total_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Grand Total</label>
        <input type="text" name="Grand_Total" id="grandTotal" class="f-input" readonly value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark</label>
        <textarea name="Remark" class="f-input">{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
