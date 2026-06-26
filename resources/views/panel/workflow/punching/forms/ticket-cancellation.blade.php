{{-- Ticket Cancellation Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Agent Name, Date, Booking Date, Cancelled Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Agent Name</label>
        <input type="text" name="AgentName" class="f-input" value="{{ $punchDetail->AgentName ?? '' }}">
    </div>
    <div class="f-group">
        <label>Date</label>
        <input type="date" name="BillDate" class="f-input"  onfocus="if (this.showPicker) this.showPicker(); else this.click();"   @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Date of Booking</label>
        <input type="date" name="BookingDate" class="f-input" value="{{ $punchDetail->BookingDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Cancelled Date</label>
        <input type="date" name="File_Date" class="f-input" value="{{ $punchDetail->File_Date ?? '' }}">
    </div>
</div>

{{-- Line Items: Employee, PNR, Amount --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Passengers</span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:200px">Employee</th>
                <th style="width:120px">PNR Number</th>
                <th style="width:100px">Amount</th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="itemsBody">
            <tr>
                <td>1</td>
                <td><select name="Employee[]" class="particular-sel" style="width:100%"><option value="">Select</option></select></td>
                <td><input type="text" name="PNR[]"></td>
                <td><input type="text" name="Ticket_Amount[]" class="calc-trigger" inputmode="decimal"></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Cancellation Charge</label>
        <input type="text" name="Cancellation_Charge" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
    <div class="f-group">
        <label>Other Charges</label>
        <input type="text" name="OthCharge_Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->OthCharge_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Refund Amount</label>
        <input type="text" name="Refund_Amount" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Discount ?? '' }}">
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
