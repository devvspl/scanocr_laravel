{{-- Ticket Cancellation Form Partial --}}
{{-- Variables: $punchDetail (nullable), $tempData (nullable), $scanData --}}

{{-- Row 1: Agent Name, Date, Booking Date, Cancelled Date --}}
<div class="f-row">
    <div class="f-group">
        <label>Agent Name <span style="color:#dc2626">*</span></label>
        <select name="AgentName" id="selTicketCancelAgent" class="f-input" required>
            <option value="">Select Agent</option>
            @if(isset($punchDetail->AgentName) && $punchDetail->AgentName)
                <option value="{{ $punchDetail->AgentName }}" selected>{{ $punchDetail->AgentName }}</option>
            @endif
        </select>
    </div>
    <div class="f-group">
        <label>Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="BillDate" class="f-input" required onfocus="if (this.showPicker) this.showPicker(); else this.click();" @if(\App\Helpers\BillDateValidator::getCurrentFyRange()) min="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['start'] }}" max="{{ \App\Helpers\BillDateValidator::getCurrentFyRange()['end'] }}" @endif value="{{ $punchDetail->BillDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Date of Booking</label>
        <input type="date" name="BookingDate" class="f-input" value="{{ $punchDetail->BookingDate ?? '' }}">
    </div>
    <div class="f-group">
        <label>Cancelled Date <span style="color:#dc2626">*</span></label>
        <input type="date" name="File_Date" class="f-input" required value="{{ $punchDetail->File_Date ?? '' }}">
    </div>
</div>

{{-- Line Items: Employee, PNR, Amount --}}
<div style="margin:.6rem 0 .5rem"><span style="font-size:.65rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.03em">Passengers <span style="color:#dc2626">*</span></span></div>
<div style="overflow-x:auto;max-height:250px;overflow-y:auto;border:2px solid #e7e5e4;border-radius:.5rem;margin-bottom:.6rem">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:25px">#</th>
                <th style="width:200px">Employee <span style="color:#dc2626">*</span></th>
                <th style="width:120px">PNR Number <span style="color:#dc2626">*</span></th>
                <th style="width:100px">Amount <span style="color:#dc2626">*</span></th>
                <th style="width:25px"></th>
            </tr>
        </thead>
        <tbody id="ticketCancelItemsBody">
            @php
                $hasDetails = false;
                $ticketDetails = [];
                if(isset($punchDetail) && $punchDetail) {
                    $ticketDetails = DB::table('ticket_cancellation')->where('Scan_Id', $scanData->Scan_Id)->get();
                    $hasDetails = $ticketDetails->count() > 0;
                }
            @endphp
            @if($hasDetails)
                @foreach($ticketDetails as $idx => $detail)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <select name="Employee[]" class="ticket-cancel-emp-sel" style="width:100%" required>
                            <option value="{{ $detail->Emp_Id }}" selected>{{ $detail->Emp_Name }}</option>
                        </select>
                    </td>
                    <td><input type="text" name="PNR[]" value="{{ $detail->PNR }}" required></td>
                    <td><input type="text" name="Amount[]" class="calc-trigger" inputmode="decimal" value="{{ $detail->Amount }}" required></td>
                    <td>
                        @if($loop->last)
                            <button type="button" class="btn-add-row">+</button>
                        @else
                            <button type="button" class="btn-del-row">−</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
            <tr>
                <td>1</td>
                <td><select name="Employee[]" class="ticket-cancel-emp-sel" style="width:100%" required><option value="">Select</option></select></td>
                <td><input type="text" name="PNR[]" required></td>
                <td><input type="text" name="Amount[]" class="calc-trigger" inputmode="decimal" required></td>
                <td><button type="button" class="btn-add-row">+</button></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

{{-- Totals --}}
<div class="f-row">
    <div class="f-group">
        <label>Sub Total (Cancellation Charge) <span style="color:#dc2626">*</span></label>
        <input type="text" name="SubTotal" id="ticketCancelSubTotal" class="f-input" inputmode="decimal" required value="{{ $punchDetail->SubTotal ?? '' }}">
    </div>
    <div class="f-group">
        <label>Other Charges</label>
        <input type="text" name="OthCharge_Amount" id="ticketCancelOther" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->OthCharge_Amount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Total Discount (Refund Amount)</label>
        <input type="text" name="Total_Discount" id="ticketCancelRefund" class="f-input calc-trigger" inputmode="decimal" value="{{ $punchDetail->Total_Discount ?? '' }}">
    </div>
    <div class="f-group">
        <label>Grand Total <span style="color:#dc2626">*</span></label>
        <input type="text" name="Grand_Total" id="ticketCancelGrandTotal" class="f-input" readonly required value="{{ $punchDetail->Grand_Total ?? '' }}">
    </div>
</div>

{{-- Remark --}}
<div class="f-row cols-1">
    <div class="f-group" style="margin-bottom:.5rem">
        <label>Remark <span style="color:#dc2626">*</span></label>
        <textarea name="Remark" class="f-input" required>{{ $punchDetail->Remark ?? '' }}</textarea>
    </div>
</div>
