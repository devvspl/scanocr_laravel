<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\ScanFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PunchingEntryController extends Controller
{
    /**
     * Map document type IDs (is_punch=1) to their blade partial names.
     */
    private const DOC_TYPE_FORMS = [
        1  => 'two-four-wheeler',
        6  => 'cash-deposits',
        7  => 'cash-voucher',
        13 => 'electricity-bill',
        17 => 'hired-vehicle',
        20 => 'income-tax',
        22 => 'insurance',
        23 => 'invoice',
        27 => 'local-conveyance',
        28 => 'lodging',
        29 => 'meals',
        31 => 'miscellaneous',
        42 => 'telephone-bill',
        43 => 'vehicle-fuel',
        44 => 'vehicle-maintenance',
        46 => 'gst-challan',
        47 => 'labour-payment',
        48 => 'cash-receipt',
        50 => 'machine-operation',
        51 => 'air',
        52 => 'rail',
        54 => 'sale-bill',
        55 => 'ticket-cancellation',
        56 => 'credit-note',
    ];

    /**
     * Document types that have line items (invoice_detail table).
     */
    private const HAS_LINE_ITEMS = [1, 17, 23, 27, 28, 43, 44, 47, 50, 51, 52, 54, 56];

    /**
     * Get the form partial name for a given document type ID.
     * Falls back to 'invoice' for unknown types.
     */
    private function getFormPartial(int $docTypeId): string
    {
        return self::DOC_TYPE_FORMS[$docTypeId] ?? 'invoice';
    }

    /**
     * GET /workflow/punching/entry/{scan}
     * Show the punching entry form page.
     */
    public function show($scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;

        $scanData = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->where('s.Scan_Id', $scanId)
            ->select([
                's.Scan_Id', 's.File', 's.File_Location', 's.File_Ext',
                's.Document_name', 's.DocType_Id', 'dt.label as doc_type_label', 'dt.key as doc_type_key',
                'c.name as company_name', 'l.location_name',
                's.Group_Id', 's.Location',
            ])
            ->first();

        if (!$scanData) abort(404);

        $formPartial = $this->getFormPartial($scanData->DocType_Id);

        // Get existing punchfile data (for edit/draft)
        $punchDetail = DB::table('punchfile')->where('Scan_Id', $scanId)->first();

        // Get supporting files
        $supportFiles = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as sdt', 'sdt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scanId)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'sdt.DocTypeName as doc_type_name'])
            ->get();

        // Get temp extracted data (AI suggestions)
        $tempData = null;
        $tempTable = 'ext_tempdata_' . $scanData->DocType_Id;
        if (\Schema::hasTable($tempTable)) {
            $tempData = DB::table($tempTable)->where('scan_id', $scanId)->first();
        }

        return view('panel.workflow.punching.entry', compact('scanData', 'punchDetail', 'supportFiles', 'tempData', 'formPartial'));
    }

    /**
     * GET /workflow/punching/entry/{scan}/items  (AJAX JSON — paginated line items)
     */
    public function getItems(Request $request, $scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = max(1, (int) $request->input('limit', 100));
        $offset = ($page - 1) * $limit;

        $total = DB::table('invoice_detail')->where('Scan_Id', $scanId)->count();

        $items = DB::table('invoice_detail')
            ->where('Scan_Id', $scanId)
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'status'       => 200,
            'data'         => $items,
            'total_count'  => $total,
            'current_page' => $page,
            'total_pages'  => ceil($total / $limit) ?: 1,
            'has_more'     => ($page * $limit) < $total,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SAVE — Dispatcher
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * POST /workflow/punching/entry/{scan}/save  (AJAX JSON)
     * Dispatches to document-type-specific save handler.
     */
    public function save(Request $request, $scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;
        $scanRecord = DB::table('scan_file')->where('Scan_Id', $scanId)->first();
        if (!$scanRecord) {
            return response()->json(['success' => false, 'message' => 'Scan not found.'], 404);
        }

        $docTypeId = $scanRecord->DocType_Id;
        $isFinal = $request->input('action') === 'final_submit';

        // Dispatch to type-specific handler
        $formKey = self::DOC_TYPE_FORMS[$docTypeId] ?? 'invoice';
        $method = 'save' . str_replace('-', '', ucwords($formKey, '-'));
        if (method_exists($this, $method)) {
            return $this->$method($request, $scanId, $scanRecord, $isFinal);
        }

        return $this->saveInvoice($request, $scanId, $scanRecord, $isFinal);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SAVE — Shared Helpers
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Build the common punchfile column array from request inputs.
     */
    private function buildBasePunchData(Request $request, int $scanId, $scanRecord): array
    {
        $docTypeId = $scanRecord->DocType_Id;
        $docType   = DB::table('document_types')->where('id', $docTypeId)->value('key');

        $fromName = $request->filled('From')
            ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name')
            : null;
        $toName = $request->filled('To')
            ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name')
            : null;
        $deptName = $request->filled('Department')
            ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name')
            : null;

        return [
            'Scan_Id'         => $scanId,
            'Group_Id'        => $scanRecord->Group_Id,
            'DocType'         => $docType ?? '',
            'DocTypeId'       => $docTypeId,
            'BillDate'        => $request->input('Bill_Date') ?: null,
            'File_No'         => $request->input('Bill_No', '') ?: '',
            'NatureOfPayment' => $request->input('Payment_Mode', '') ?: '',
            'ReferenceNo'     => $request->input('Supplier_Ref', '') ?: '',
            'From_ID'         => (int) ($request->input('From', 0) ?: 0),
            'FromName'        => $fromName ?? '',
            'To_ID'           => (int) ($request->input('To', 0) ?: 0),
            'ToName'          => $toName ?? '',
            'Loc_Add'         => $request->input('Buyer_Address', '') ?: '',
            'AgencyAddress'   => $request->input('Vendor_Address', '') ?: '',
            'ServiceNo'       => $request->input('Buyer_Order', '') ?: ($request->input('Buyer_Order_No', '') ?: ''),
            'BookingDate'     => $request->input('Buyer_Order_Date') ?: ($request->input('Order_Date') ?: null),
            'Particular'      => $request->input('Dispatch_Through', '') ?: '',
            'DueDate'         => $request->input('Delivery_Note_Date') ?: null,
            'Department'      => $deptName ?? '',
            'DepartmentID'    => (int) ($request->input('Department', 0) ?: 0),
            'Category'        => $request->input('Category', '') ?: '',
            'Ledger'          => $request->input('Ledger', '') ?: '',
            'FileName'        => $request->input('File', '') ?: '',
            'FDRNo'           => $request->input('LR_Number', '') ?: ($request->input('FDR_No', '') ?: ''),
            'File_Date'       => $request->input('LR_Date') ?: ($request->input('File_Date') ?: null),
            'RegNo'           => $request->input('Cartoon_Number', '') ?: ($request->input('Reg_No', '') ?: ''),
            'Loc_Name'        => $request->input('Location', '') ?: '',
            'AgentName'       => $request->input('Agent_Name', '') ?: ($request->input('Consignee_Name', '') ?: ''),
            'SubTotal'        => (float) ($request->input('Sub_Total', 0) ?: 0),
            'Total_Amount'    => (float) ($request->input('Total', 0) ?: ($request->input('Grand_Total', 0) ?: 0)),
            'Grand_Total'     => (float) ($request->input('Grand_Total', 0) ?: 0),
            'Total_Discount'  => (float) ($request->input('Total_Discount', 0) ?: ($request->input('Round_Off', 0) ?: 0)),
            'TCS'             => (float) ($request->input('TCS', 0) ?: 0),
            'Remark'          => $request->input('Remark', '') ?: '',
            'Created_By'      => Auth::id(),
            'Created_Date'    => now()->toDateTimeString(),
        ];
    }

    /**
     * Insert or update punchfile + sub_punchfile. Returns FileID.
     */
    private function savePunchfile(int $scanId, array $data): int
    {
        $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();

        if ($existing) {
            DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
            $fileID = $existing->FileID;
            DB::table('sub_punchfile')->where('FileID', $fileID)->update([
                'Amount'  => '-' . ($data['Grand_Total'] ?: 0),
                'Comment' => $data['Remark'] ?? '',
            ]);
        } else {
            $fileID = DB::table('punchfile')->insertGetId($data);
            DB::table('sub_punchfile')->insert([
                'FileID'  => $fileID,
                'Amount'  => '-' . ($data['Grand_Total'] ?: 0),
                'Comment' => $data['Remark'] ?? '',
            ]);
        }

        return $fileID;
    }

    /**
     * Save line items to invoice_detail table, chunked for 500+ rows.
     */
    private function saveLineItems(Request $request, int $scanId): void
    {
        DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();

        $particulars = $request->input('Particular', []);
        if (!is_array($particulars)) return;

        $items = [];
        foreach ($particulars as $i => $particular) {
            if (empty(trim((string) ($particular ?? '')))) continue;

            $items[] = [
                'Scan_Id'      => $scanId,
                'Particular'   => (string) $particular,
                'HSN'          => (string) ($request->input('HSN')[$i] ?? ''),
                'Qty'          => (float) ($request->input('Qty')[$i] ?? 0),
                'Unit'         => (string) ($request->input('Unit')[$i] ?? ''),
                'MRP'          => (float) ($request->input('MRP')[$i] ?? 0),
                'Discount'     => (float) ($request->input('Discount')[$i] ?? 0),
                'Price'        => (float) ($request->input('Price')[$i] ?? 0),
                'Amount'       => (float) ($request->input('Amount')[$i] ?? 0),
                'GST'          => (float) ($request->input('GST')[$i] ?? 0),
                'SGST'         => (float) ($request->input('SGST')[$i] ?? 0),
                'IGST'         => (float) ($request->input('IGST')[$i] ?? 0),
                'Cess'         => (float) ($request->input('Cess')[$i] ?? 0),
                'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
            ];
        }

        if (!empty($items)) {
            foreach (array_chunk($items, 100) as $chunk) {
                DB::table('invoice_detail')->insert($chunk);
            }
        }
    }

    /**
     * Save KM-based line items (for vehicle/conveyance types).
     */
    private function saveKmLineItems(Request $request, int $scanId): void
    {
        DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();

        $particulars = $request->input('Particular', []);
        if (!is_array($particulars)) return;

        $items = [];
        foreach ($particulars as $i => $particular) {
            if (empty(trim((string) ($particular ?? '')))) continue;

            $items[] = [
                'Scan_Id'      => $scanId,
                'Particular'   => (string) $particular,
                'HSN'          => '',
                'Qty'          => (float) ($request->input('Qty')[$i] ?? 0),
                'Unit'         => (string) ($request->input('Unit')[$i] ?? 'KM'),
                'MRP'          => (float) ($request->input('Rate')[$i] ?? 0),
                'Discount'     => 0,
                'Price'        => (float) ($request->input('Rate')[$i] ?? 0),
                'Amount'       => (float) ($request->input('Amount')[$i] ?? 0),
                'GST'          => 0,
                'SGST'         => 0,
                'IGST'         => 0,
                'Cess'         => 0,
                'Total_Amount' => (float) ($request->input('Amount')[$i] ?? 0),
            ];
        }

        if (!empty($items)) {
            foreach (array_chunk($items, 100) as $chunk) {
                DB::table('invoice_detail')->insert($chunk);
            }
        }
    }

    /**
     * Mark scan as punched (final submit).
     */
    private function markAsPunched(int $scanId): void
    {
        DB::table('scan_file')->where('Scan_Id', $scanId)->update([
            'File_Punched'    => 'Y',
            'Punch_By'        => Auth::id(),
            'Punch_Date'      => now(),
            'Is_Rejected'     => 'N',
            'Reject_Date'     => null,
            'Edit_Permission' => 'N',
        ]);
    }

    /**
     * Wrap the type-specific save logic in a transaction and return JSON response.
     */
    private function wrapInTransaction(callable $callback, bool $isFinal): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $callback();
            DB::commit();
            return response()->json([
                'success'  => true,
                'message'  => $isFinal ? 'Submitted successfully.' : 'Draft saved.',
                'redirect' => $isFinal ? route('workflow.punching.index') : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SAVE — Document-Type-Specific Handlers
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Invoice (23) — full form with buyer/vendor, addresses, line items + GST/TCS.
     */
    private function saveInvoice(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'  => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Remark'   => 'nullable|string|max:5000',
            'Grand_Total' => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['From']        = 'required|integer|min:1';
            $rules['To']          = 'required|integer|min:1';
            $rules['Location']    = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $this->savePunchfile($scanId, $data);
            $this->saveLineItems($request, $scanId);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Sale Bill (54) — same structure as Invoice.
     */
    private function saveSaleBill(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveInvoice($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Credit Note (56) — same structure as Invoice.
     */
    private function saveCreditNote(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveInvoice($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Vehicle Maintenance (44) — same as Invoice (Buyer, Vendor, Line Items with parts/labour).
     */
    private function saveVehicleMaintenance(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveInvoice($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Cash Voucher (7) — Bill No, Date, Payment Mode, Buyer, Vendor, Dept, Category, Location, Grand Total. NO line items.
     */
    private function saveCashVoucher(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'      => 'nullable|string|max:150',
            'Bill_Date'    => 'nullable|date',
            'Remark'       => 'nullable|string|max:5000',
            'Payment_Mode' => 'nullable|string|max:100',
            'Grand_Total'  => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Cash Deposits (6) — same structure as Cash Voucher.
     */
    private function saveCashDeposits(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveCashVoucher($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Cash Receipt (48) — same structure as Cash Voucher.
     */
    private function saveCashReceipt(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveCashVoucher($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Electricity Bill (13) — Bill No, Date, Vendor, Amount, Consumer No. NO line items.
     */
    private function saveElectricityBill(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'      => 'nullable|string|max:150',
            'Bill_Date'    => 'nullable|date',
            'Remark'       => 'nullable|string|max:5000',
            'Grand_Total'  => 'nullable|numeric',
            'Consumer_No'  => 'nullable|string|max:100',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['ReferenceNo'] = $request->input('Consumer_No', '') ?: ($request->input('Account_No', '') ?: '');
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Telephone Bill (42) — same structure as Electricity Bill.
     */
    private function saveTelephoneBill(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveElectricityBill($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * GST Challan (46) — same structure as Electricity Bill.
     */
    private function saveGstChallan(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveElectricityBill($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * 2/4 Wheeler (1) — Vehicle No, Driver, Opening/Closing KM, Rate/KM + KM-based line items.
     */
    private function saveTwoFourWheeler(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'     => 'nullable|string|max:150',
            'Bill_Date'   => 'nullable|date',
            'Remark'      => 'nullable|string|max:5000',
            'Grand_Total' => 'nullable|numeric',
            'Vehicle_No'  => 'nullable|string|max:50',
            'Driver'      => 'nullable|string|max:150',
            'Opening_KM'  => 'nullable|numeric',
            'Closing_KM'  => 'nullable|numeric',
            'Total_KM'    => 'nullable|numeric',
            'Rate_Per_KM' => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Location']    = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['RegNo']     = $request->input('Vehicle_No', '') ?: '';
            $data['AgentName'] = $request->input('Driver', '') ?: '';
            $data['FDRNo']     = $request->input('Opening_KM', '') ?: '';
            $data['ServiceNo'] = $request->input('Closing_KM', '') ?: '';
            $data['Particular'] = $request->input('Total_KM', '') ?: '';
            $this->savePunchfile($scanId, $data);
            $this->saveKmLineItems($request, $scanId);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Hired Vehicle (17) — same structure as 2/4 Wheeler.
     */
    private function saveHiredVehicle(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveTwoFourWheeler($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Local Conveyance (27) — same structure as 2/4 Wheeler.
     */
    private function saveLocalConveyance(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveTwoFourWheeler($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Vehicle Fuel (43) — Vehicle No, Fuel Type, Liters, Rate, Amount, Odometer + line items.
     */
    private function saveVehicleFuel(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'     => 'nullable|string|max:150',
            'Bill_Date'   => 'nullable|date',
            'Remark'      => 'nullable|string|max:5000',
            'Grand_Total' => 'nullable|numeric',
            'Vehicle_No'  => 'nullable|string|max:50',
            'Fuel_Type'   => 'nullable|string|max:50',
            'Liters'      => 'nullable|numeric',
            'Rate'        => 'nullable|numeric',
            'Odometer'    => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Location']    = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['RegNo']          = $request->input('Vehicle_No', '') ?: '';
            $data['NatureOfPayment'] = $request->input('Fuel_Type', '') ?: '';
            $data['ReferenceNo']    = $request->input('Odometer', '') ?: '';
            $data['SubTotal']       = (float) ($request->input('Liters', 0) ?: 0);
            $this->savePunchfile($scanId, $data);
            $this->saveLineItems($request, $scanId);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Labour Payment (47) — Bill No, Date, Buyer, Vendor, Dept, Location + line items.
     */
    private function saveLabourPayment(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'     => 'nullable|string|max:150',
            'Bill_Date'   => 'nullable|date',
            'Remark'      => 'nullable|string|max:5000',
            'Grand_Total' => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['From']        = 'required|integer|min:1';
            $rules['To']          = 'required|integer|min:1';
            $rules['Location']    = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $this->savePunchfile($scanId, $data);
            $this->saveLineItems($request, $scanId);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Machine Operation (50) — same structure as Labour Payment.
     */
    private function saveMachineOperation(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveLabourPayment($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Income Tax/TDS (20) — Bill No, Date, Assessment Year, PAN, Amount, Challan No. NO line items.
     */
    private function saveIncomeTax(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'          => 'nullable|string|max:150',
            'Bill_Date'        => 'nullable|date',
            'Remark'           => 'nullable|string|max:5000',
            'Grand_Total'      => 'nullable|numeric',
            'Assessment_Year'  => 'nullable|string|max:20',
            'PAN'              => 'nullable|string|max:20',
            'Challan_No'       => 'nullable|string|max:50',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['ReferenceNo'] = $request->input('PAN', '') ?: '';
            $data['ServiceNo']   = $request->input('Challan_No', '') ?: '';
            $data['FDRNo']       = $request->input('Assessment_Year', '') ?: '';
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Insurance (22) — Bill No, Date, Policy No, Premium, Insurer, Vehicle/Asset. NO line items.
     */
    private function saveInsurance(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'        => 'nullable|string|max:150',
            'Bill_Date'      => 'nullable|date',
            'Remark'         => 'nullable|string|max:5000',
            'Grand_Total'    => 'nullable|numeric',
            'Policy_No'      => 'nullable|string|max:100',
            'Premium_Amount' => 'nullable|numeric',
            'Insurer'        => 'nullable|string|max:255',
            'Vehicle_Asset'  => 'nullable|string|max:255',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['ReferenceNo'] = $request->input('Policy_No', '') ?: '';
            $data['AgentName']   = $request->input('Insurer', '') ?: '';
            $data['RegNo']       = $request->input('Vehicle_Asset', '') ?: '';
            $data['SubTotal']    = (float) ($request->input('Premium_Amount', 0) ?: 0);
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Lodging (28) — Hotel/Vendor, Check-in, Check-out, Room Charge, Tax + line items (room charges).
     */
    private function saveLodging(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'      => 'nullable|string|max:150',
            'Bill_Date'    => 'nullable|date',
            'Remark'       => 'nullable|string|max:5000',
            'Grand_Total'  => 'nullable|numeric',
            'Check_In'     => 'nullable|date',
            'Check_Out'    => 'nullable|date',
            'Room_Charge'  => 'nullable|numeric',
            'Tax'          => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['BookingDate'] = $request->input('Check_In') ?: null;
            $data['DueDate']     = $request->input('Check_Out') ?: null;
            $data['SubTotal']    = (float) ($request->input('Room_Charge', 0) ?: 0);
            $data['TCS']         = (float) ($request->input('Tax', 0) ?: 0);
            $this->savePunchfile($scanId, $data);
            $this->saveLineItems($request, $scanId);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Meals (29) — Vendor, No of Persons, Amount, Location. NO line items.
     */
    private function saveMeals(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'       => 'nullable|string|max:150',
            'Bill_Date'     => 'nullable|date',
            'Remark'        => 'nullable|string|max:5000',
            'Grand_Total'   => 'nullable|numeric',
            'No_Of_Persons' => 'nullable|integer|min:0',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['ReferenceNo'] = (string) ($request->input('No_Of_Persons', '') ?: '');
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Air (51) — Passenger, From City, To City, Travel Date, Fare, Tax + passenger-wise line items.
     */
    private function saveAir(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'     => 'nullable|string|max:150',
            'Bill_Date'   => 'nullable|date',
            'Remark'      => 'nullable|string|max:5000',
            'Grand_Total' => 'nullable|numeric',
            'Passenger'   => 'nullable|string|max:255',
            'From_City'   => 'nullable|string|max:150',
            'To_City'     => 'nullable|string|max:150',
            'Travel_Date' => 'nullable|date',
            'Fare'        => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['AgentName']   = $request->input('Passenger', '') ?: '';
            $data['Loc_Add']     = $request->input('From_City', '') ?: '';
            $data['AgencyAddress'] = $request->input('To_City', '') ?: '';
            $data['BookingDate'] = $request->input('Travel_Date') ?: null;
            $data['SubTotal']    = (float) ($request->input('Fare', 0) ?: 0);
            $this->savePunchfile($scanId, $data);
            $this->saveLineItems($request, $scanId);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Rail (52) — same structure as Air.
     */
    private function saveRail(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        return $this->saveAir($request, $scanId, $scanRecord, $isFinal);
    }

    /**
     * Ticket Cancellation (55) — Original Ticket No, Cancellation Charge, Refund Amount. NO line items.
     */
    private function saveTicketCancellation(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'              => 'nullable|string|max:150',
            'Bill_Date'            => 'nullable|date',
            'Remark'               => 'nullable|string|max:5000',
            'Grand_Total'          => 'nullable|numeric',
            'Original_Ticket_No'   => 'nullable|string|max:150',
            'Cancellation_Charge'  => 'nullable|numeric',
            'Refund_Amount'        => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['ReferenceNo']   = $request->input('Original_Ticket_No', '') ?: '';
            $data['SubTotal']      = (float) ($request->input('Cancellation_Charge', 0) ?: 0);
            $data['Total_Discount'] = (float) ($request->input('Refund_Amount', 0) ?: 0);
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    /**
     * Miscellaneous (31) — Description, Vendor, Amount. Most flexible. NO line items.
     */
    private function saveMiscellaneous(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No'      => 'nullable|string|max:150',
            'Bill_Date'    => 'nullable|date',
            'Remark'       => 'nullable|string|max:5000',
            'Grand_Total'  => 'nullable|numeric',
            'Description'  => 'nullable|string|max:1000',
        ];
        if ($isFinal) {
            $rules['Bill_No']     = 'required|string|max:150';
            $rules['Bill_Date']   = 'required|date';
            $rules['Remark']      = 'required|string|max:5000';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        return $this->wrapInTransaction(function () use ($request, $scanId, $scanRecord, $isFinal) {
            $data = $this->buildBasePunchData($request, $scanId, $scanRecord);
            $data['Particular'] = $request->input('Description', '') ?: '';
            $this->savePunchfile($scanId, $data);
            if ($isFinal) $this->markAsPunched($scanId);
        }, $isFinal);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Select2 Endpoints
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * GET /workflow/punching/entry/select/items  (Select2 AJAX — item/particular search)
     */
    public function itemsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 50;

        $query = DB::table('master_item')
            ->where('status', 'A')
            ->where('is_deleted', 'N')
            ->orderBy('item_name');

        if ($q !== '') {
            $query->where('item_name', 'like', "%{$q}%");
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['item_name as id', 'item_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * POST /workflow/punching/entry/select/items/create  (Create new item)
     */
    public function createItem(Request $request)
    {
        $request->validate(['item_name' => 'required|string|max:255']);

        $id = DB::table('master_item')->insertGetId([
            'item_name'  => $request->input('item_name'),
            'item_code'  => $request->input('item_code', ''),
            'status'     => 'A',
            'is_deleted' => 'N',
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        $itemCode = sprintf('ITEM-%03d', $id);
        DB::table('master_item')->where('item_id', $id)->update(['item_code' => $itemCode]);

        return response()->json(['success' => true, 'item' => ['id' => $request->input('item_name'), 'text' => $request->input('item_name')]]);
    }

    /**
     * GET /workflow/punching/entry/select/units
     */
    public function unitsSelect(Request $request)
    {
        $results = DB::table('master_unit')
            ->where('status', 'A')
            ->where('is_deleted', 'N')
            ->orderBy('unit_name')
            ->get(['unit_id as id', 'unit_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/punching/entry/select/buyers  (companies/buyers)
     */
    public function buyersSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_firm')
            ->where('status', 'A')
            ->where('firm_type', 'company')
            ->orderBy('firm_name');

        if ($q !== '') $query->where('firm_name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['firm_id as id', 'firm_name as text', 'address']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/punching/entry/select/vendors
     */
    public function vendorsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_firm')
            ->where('status', 'A')
            ->orderBy('firm_name');

        if ($q !== '') $query->where('firm_name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['firm_id as id', 'firm_name as text', 'address']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    /**
     * GET /workflow/punching/entry/select/departments
     */
    public function departmentsSelect(Request $request)
    {
        $q = $request->query('q', '');

        $query = DB::table('departments')->where('is_active', true)->orderBy('department_name');
        if ($q !== '') $query->where('department_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['id', 'department_name as text']), 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/punching/entry/select/categories
     */
    public function categoriesSelect(Request $request)
    {
        $results = DB::table('master_category')
            ->where('status', 'A')
            ->orderBy('category_name')
            ->get(['category_name as id', 'category_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/punching/entry/select/ledgers
     */
    public function ledgersSelect(Request $request)
    {
        $q = $request->query('q', '');

        $query = DB::table('master_ledger')->where('status', 'A')->orderBy('ledger_name');
        if ($q !== '') $query->where('ledger_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['ledger_name as id', 'ledger_name as text']), 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/punching/entry/select/files
     */
    public function filesSelect(Request $request)
    {
        $buyerId = $request->query('buyer_id', '');
        $q       = $request->query('q', '');

        $query = DB::table('master_file')->where('status', 'A')->orderBy('file_name');
        if ($buyerId) $query->where('firm_id', $buyerId);
        if ($q !== '') $query->where('file_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['file_name as id', 'file_name as text']), 'pagination' => ['more' => false]]);
    }

    /**
     * GET /workflow/punching/entry/select/locations
     */
    public function locationsSelect(Request $request)
    {
        $q = $request->query('q', '');

        $query = \App\Models\Location::active()->orderBy('location_name');
        if ($q !== '') $query->where('location_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['location_name as id', 'location_name as text']), 'pagination' => ['more' => false]]);
    }
}
