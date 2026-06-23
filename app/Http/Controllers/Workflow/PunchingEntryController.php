<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PunchingEntryController extends Controller
{
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

    // ─── Show Entry Form ─────────────────────────────────────────────────────

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
                's.Document_name', 's.DocType_Id', 'dt.label as doc_type_label',
                'dt.key as doc_type_key', 'c.name as company_name',
                'l.location_name', 's.Group_Id', 's.Location',
            ])
            ->first();

        if (!$scanData) abort(404);

        $formPartial = self::DOC_TYPE_FORMS[$scanData->DocType_Id] ?? 'invoice';
        $punchDetail = DB::table('punchfile')->where('Scan_Id', $scanId)->first();

        $supportFiles = DB::table('support_file as sf')
            ->leftJoin('supp_document_type_master as sdt', 'sdt.DocTypeId', '=', 'sf.DocTypeId')
            ->where('sf.Scan_Id', $scanId)
            ->select(['sf.Support_Id', 'sf.File', 'sf.File_Ext', 'sf.File_Location', 'sdt.DocTypeName as doc_type_name'])
            ->get();

        $tempData = null;
        $tempTable = 'ext_tempdata_' . $scanData->DocType_Id;
        if (\Schema::hasTable($tempTable)) {
            $tempData = DB::table($tempTable)->where('scan_id', $scanId)->first();
        }

        return view('panel.workflow.punching.entry', compact('scanData', 'punchDetail', 'supportFiles', 'tempData', 'formPartial'));
    }

    // ─── Get Line Items (paginated) ──────────────────────────────────────────

    public function getItems(Request $request, $scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = max(1, (int) $request->input('limit', 100));
        $offset = ($page - 1) * $limit;

        $total = DB::table('invoice_detail')->where('Scan_Id', $scanId)->count();
        $items = DB::table('invoice_detail')
            ->where('Scan_Id', $scanId)
            ->offset($offset)->limit($limit)->get();

        return response()->json([
            'status' => 200, 'data' => $items,
            'total_count' => $total, 'current_page' => $page,
            'total_pages' => ceil($total / $limit) ?: 1,
            'has_more' => ($page * $limit) < $total,
        ]);
    }

    // ─── Save Dispatcher ─────────────────────────────────────────────────────

    public function save(Request $request, $scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;
        $scanRecord = DB::table('scan_file')->where('Scan_Id', $scanId)->first();
        if (!$scanRecord) {
            return response()->json(['success' => false, 'message' => 'Scan not found.'], 404);
        }

        $isFinal = $request->input('action') === 'final_submit';
        $formKey = self::DOC_TYPE_FORMS[$scanRecord->DocType_Id] ?? 'invoice';
        $method  = 'save' . str_replace('-', '', ucwords($formKey, '-'));

        if (method_exists($this, $method)) {
            return $this->$method($request, $scanId, $scanRecord, $isFinal);
        }
        return $this->saveInvoice($request, $scanId, $scanRecord, $isFinal);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 1. Invoice (ID: 23)
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveInvoice(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        // Validation
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'Location' => 'nullable|string|max:255',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId,
                'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '',
                'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'ReferenceNo' => $request->input('Supplier_Ref', ''),
                'From_ID' => (int) $request->input('From', 0),
                'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0),
                'ToName' => $toName,
                'Loc_Add' => $request->input('Buyer_Address', ''),
                'AgencyAddress' => $request->input('Vendor_Address', ''),
                'ServiceNo' => $request->input('Buyer_Order', ''),
                'BookingDate' => $request->input('Buyer_Order_Date'),
                'Particular' => $request->input('Dispatch_Through', ''),
                'DueDate' => $request->input('Delivery_Note_Date'),
                'Department' => $deptName,
                'DepartmentID' => (int) $request->input('Department', 0),
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'FileName' => $request->input('File', ''),
                'FDRNo' => $request->input('LR_Number', ''),
                'File_Date' => $request->input('LR_Date'),
                'RegNo' => $request->input('Cartoon_Number', ''),
                'Loc_Name' => $request->input('Location', ''),
                'AgentName' => $request->input('Consignee_Name', ''),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Round_Off', 0),
                'TCS' => (float) $request->input('TCS', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
            ];

            // Save punchfile
            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Save line items (chunked for 500+ rows)
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId,
                        'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            // Mark as punched on final submit
            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 2. Sale Bill (ID: 54) — Same as Invoice
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveSaleBill(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        // Validation
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'Location' => 'nullable|string|max:255',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'ReferenceNo' => $request->input('Supplier_Ref', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Loc_Add' => $request->input('Buyer_Address', ''),
                'AgencyAddress' => $request->input('Vendor_Address', ''),
                'ServiceNo' => $request->input('Buyer_Order', ''),
                'BookingDate' => $request->input('Buyer_Order_Date'),
                'Particular' => $request->input('Dispatch_Through', ''),
                'DueDate' => $request->input('Delivery_Note_Date'),
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'FileName' => $request->input('File', ''),
                'FDRNo' => $request->input('LR_Number', ''),
                'File_Date' => $request->input('LR_Date'),
                'RegNo' => $request->input('Cartoon_Number', ''),
                'Loc_Name' => $request->input('Location', ''),
                'AgentName' => $request->input('Consignee_Name', ''),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Round_Off', 0),
                'TCS' => (float) $request->input('TCS', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId, 'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 3. Credit Note (ID: 56) — Same as Invoice
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveCreditNote(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'Location' => 'nullable|string|max:255',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'ReferenceNo' => $request->input('Supplier_Ref', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Loc_Add' => $request->input('Buyer_Address', ''),
                'AgencyAddress' => $request->input('Vendor_Address', ''),
                'ServiceNo' => $request->input('Buyer_Order', ''),
                'BookingDate' => $request->input('Buyer_Order_Date'),
                'Particular' => $request->input('Dispatch_Through', ''),
                'DueDate' => $request->input('Delivery_Note_Date'),
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'FileName' => $request->input('File', ''),
                'FDRNo' => $request->input('LR_Number', ''),
                'File_Date' => $request->input('LR_Date'),
                'RegNo' => $request->input('Cartoon_Number', ''),
                'Loc_Name' => $request->input('Location', ''),
                'AgentName' => $request->input('Consignee_Name', ''),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Round_Off', 0),
                'TCS' => (float) $request->input('TCS', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId, 'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 4. Vehicle Maintenance (ID: 44) — Same as Invoice
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveVehicleMaintenance(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'VehicleRegNo' => 'nullable|string|max:50',
            'Location' => 'nullable|string|max:255',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Company' => $toName, 'CompanyID' => (int) $request->input('To', 0),
                'File_No' => $request->input('Bill_No', ''),
                'BillDate' => $request->input('Bill_Date'),
                'Loc_Name' => $request->input('Location', ''),
                'VehicleRegNo' => $request->input('VehicleRegNo', ''),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Total_Amount' => (float) $request->input('Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId, 'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 5. Cash Voucher (ID: 7) — No line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveCashVoucher(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Payment_Mode' => 'nullable|string|max:100',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 6. Cash Deposits/Withdrawals (ID: 6) — Same as Cash Voucher
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveCashDeposits(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Payment_Mode' => 'nullable|string|max:100',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 7. Cash Receipt (ID: 48) — Same as Cash Voucher
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveCashReceipt(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Payment_Mode' => 'nullable|string|max:100',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 8. Electricity Bill (ID: 13) — No line items, has Consumer No
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveElectricityBill(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Consumer_No' => 'nullable|string|max:100',
            'Biller_Name' => 'nullable|string|max:255',
            'Period' => 'nullable|string|max:50',
            'Meter_No' => 'nullable|string|max:100',
            'Last_Date' => 'nullable|date',
            'Previous_Reading' => 'nullable|string|max:50',
            'Current_Reading' => 'nullable|string|max:50',
            'Unit_Consumed' => 'nullable|string|max:50',
            'Payment_Mode' => 'nullable|string|max:100',
            'Payment_Amount' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'Related_Person' => $request->input('Biller_Name', ''),
                'ReferenceNo' => $request->input('Consumer_No', ''),
                'Period' => $request->input('Period', ''),
                'MeterNumber' => $request->input('Meter_No', ''),
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'LastDateOfPayment' => $request->input('Last_Date'),
                'PreviousReading' => $request->input('Previous_Reading', ''),
                'CurrentReading' => $request->input('Current_Reading', ''),
                'UnitsConsumed' => $request->input('Unit_Consumed', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Payment_Amount' => (float) $request->input('Payment_Amount', 0),
                'Loc_Name' => $request->input('Location', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 9. Telephone Bill (ID: 42) — Same as Electricity Bill
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveTelephoneBill(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_Date' => 'nullable|date',
            'Invoice_No' => 'nullable|string|max:150',
            'Biller_Name' => 'nullable|string|max:255',
            'Phone_No' => 'nullable|string|max:50',
            'Period' => 'nullable|string|max:50',
            'Taxable_Value' => 'nullable|numeric',
            'CGST' => 'nullable|numeric',
            'SGST' => 'nullable|numeric',
            'IGST' => 'nullable|numeric',
            'Amount_Due' => 'nullable|numeric',
            'Amount_Outstanding' => 'nullable|numeric',
            'Last_Payment_Date' => 'nullable|date',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Invoice_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Amount_Outstanding'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'FromName' => $request->input('Biller_Name', ''),
                'File_No' => $request->input('Invoice_No', ''),
                'Period' => $request->input('Period', ''),
                'MobileNo' => $request->input('Phone_No', ''),
                'SubTotal' => (float) $request->input('Taxable_Value', 0),
                'CGST_Amount' => (float) $request->input('CGST', 0),
                'SGST_Amount' => (float) $request->input('SGST', 0),
                'GST_IGST_Amount' => (float) $request->input('IGST', 0),
                'Total_Amount' => (float) $request->input('Amount_Due', 0),
                'Grand_Total' => (float) $request->input('Amount_Outstanding', 0),
                'DueDate' => $request->input('Last_Payment_Date'),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 10. GST Challan (ID: 46) — With line items (gst_challan_detail)
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveGstChallan(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'CPIN' => 'nullable|string|max:100',
            'Deposit_Date' => 'nullable|date',
            'CIN' => 'nullable|string|max:100',
            'Bank_Name' => 'nullable|string|max:255',
            'BRN' => 'nullable|string|max:100',
            'GSTIN' => 'nullable|string|max:50',
            'Email' => 'nullable|string|max:255',
            'Mobile' => 'nullable|string|max:20',
            'Company' => 'nullable|string|max:255',
            'Address' => 'nullable|string|max:500',
            'Total_Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['CPIN'] = 'required|string|max:100';
            $rules['Total_Amount'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'CPIN' => $request->input('CPIN', ''),
                'File_Date' => $request->input('Deposit_Date'),
                'CIN' => $request->input('CIN', ''),
                'BankName' => $request->input('Bank_Name', ''),
                'BankBSRCode' => $request->input('BRN', ''),
                'GSTIN' => $request->input('GSTIN', ''),
                'Email' => $request->input('Email', ''),
                'MobileNo' => $request->input('Mobile', ''),
                'Company' => $request->input('Company', ''),
                'Related_Address' => $request->input('Address', ''),
                'Total_Amount' => (float) $request->input('Total_Amount', 0),
                'Grand_Total' => (float) $request->input('Total_Amount', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // GST Challan detail items
            DB::table('gst_challan_detail')->where('scan_id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars) && !empty($particulars)) {
                $details = [];
                foreach ($particulars as $i => $particular) {
                    $details[] = [
                        'scan_id' => $scanId,
                        'Particular' => (string) ($particular ?? ''),
                        'Tax' => (float) ($request->input('Tax')[$i] ?? 0),
                        'Interest' => (float) ($request->input('Interest')[$i] ?? 0),
                        'Penalty' => (float) ($request->input('Penalty')[$i] ?? 0),
                        'Fees' => (float) ($request->input('Fees')[$i] ?? 0),
                        'Other' => (float) ($request->input('Other')[$i] ?? 0),
                        'Total' => (float) ($request->input('Total')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($details, 100) as $chunk) {
                    DB::table('gst_challan_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 11. Two/Four Wheeler (ID: 1) — KM-based line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveTwoFourWheeler(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Driver' => 'nullable|string|max:150',
            'Opening_KM' => 'nullable|numeric',
            'Closing_KM' => 'nullable|numeric',
            'Total_KM' => 'nullable|numeric',
            'Rate_Per_KM' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'RegNo' => $request->input('Vehicle_No', ''),
                'AgentName' => $request->input('Driver', ''),
                'FDRNo' => $request->input('Opening_KM', ''),
                'ServiceNo' => $request->input('Closing_KM', ''),
                'Particular' => $request->input('Total_KM', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // KM-based line items
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular_Items', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId,
                        'Particular' => (string) $particular,
                        'HSN' => '',
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => 'KM',
                        'MRP' => (float) ($request->input('Rate')[$i] ?? 0),
                        'Discount' => 0,
                        'Price' => (float) ($request->input('Rate')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => 0, 'SGST' => 0, 'IGST' => 0, 'Cess' => 0,
                        'Total_Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 12. Hired Vehicle (ID: 17) — Same as Two/Four Wheeler
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveHiredVehicle(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Driver' => 'nullable|string|max:150',
            'Opening_KM' => 'nullable|numeric',
            'Closing_KM' => 'nullable|numeric',
            'Total_KM' => 'nullable|numeric',
            'Rate_Per_KM' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'AgentName' => $request->input('Driver', ''),
                'VehicleRs_PerKM' => $request->input('Rate_Per_KM', ''),
                'OpeningKM' => $request->input('Opening_KM', ''),
                'ClosingKM' => $request->input('Closing_KM', ''),
                'TotalRunKM' => $request->input('Total_KM', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 13. Local Conveyance (ID: 27) — Same as Two/Four Wheeler
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveLocalConveyance(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Driver' => 'nullable|string|max:150',
            'Opening_KM' => 'nullable|numeric',
            'Closing_KM' => 'nullable|numeric',
            'Total_KM' => 'nullable|numeric',
            'Rate_Per_KM' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';
            $deptName = $request->filled('Department') ? DB::table('departments')->where('id', $request->input('Department'))->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Department' => $deptName, 'DepartmentID' => (int) $request->input('Department', 0),
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'AgentName' => $request->input('Driver', ''),
                'VehicleRs_PerKM' => $request->input('Rate_Per_KM', ''),
                'TotalRunKM' => $request->input('Total_KM', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Loc_Name' => $request->input('Location', ''),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // KM-based line items (vehicle_traveling)
            DB::table('vehicle_traveling')->where('scan_id', $scanId)->delete();
            $dates = $request->input('Date', []);
            if (is_array($dates) && !empty($dates)) {
                $travelData = [];
                foreach ($dates as $i => $date) {
                    $travelData[] = [
                        'scan_id' => $scanId,
                        'JourneyStartDt' => $date,
                        'DistTraOpen' => (string) ($request->input('Dist_Opening')[$i] ?? ''),
                        'DistTraClose' => (string) ($request->input('Dist_Closing')[$i] ?? ''),
                        'Totalkm' => (float) ($request->input('Km')[$i] ?? 0),
                        'FilledTAmt' => (float) ($request->input('Amount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($travelData, 100) as $chunk) {
                    DB::table('vehicle_traveling')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 14. Vehicle Fuel (ID: 43)
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveVehicleFuel(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Due_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Fuel_Type' => 'nullable|string|max:50',
            'Liters' => 'nullable|numeric',
            'Rate' => 'nullable|numeric',
            'Odometer' => 'nullable|numeric',
            'Dealer_Code' => 'nullable|string|max:100',
            'Description' => 'nullable|string|max:500',
            'Grand_Total' => 'nullable|numeric',
            'Total_Discount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'FileName' => $request->input('Description', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'CompanyID' => (int) $request->input('To', 0),
                'Company' => $toName,
                'BSRCode' => $request->input('Dealer_Code', ''),
                'DueDate' => $request->input('Due_Date'),
                'Loc_Name' => $request->input('Location', ''),
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'MeterNumber' => $request->input('Liters', ''),
                'TariffPlan' => $request->input('Rate', ''),
                'Total_Amount' => (float) $request->input('Amount', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Line items (fuel entries)
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId, 'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 15. Labour Payment (ID: 47) — With line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveLabourPayment(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Payee' => 'nullable|string|max:255',
            'Particular' => 'nullable|string|max:500',
            'From_Date' => 'nullable|date',
            'To_Date' => 'nullable|date',
            'Sub_Total' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'Related_Person' => $request->input('Payee', ''),
                'FileName' => $request->input('Particular_Text', ''),
                'FromDateTime' => $request->input('From_Date'),
                'ToDateTime' => $request->input('To_Date'),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Line items (labour payment details)
            DB::table('labour_payment_detail')->where('scan_id', $scanId)->delete();
            $heads = $request->input('Head', []);
            if (is_array($heads) && !empty($heads)) {
                $details = [];
                foreach ($heads as $i => $head) {
                    if (empty(trim((string) ($head ?? '')))) continue;
                    $details[] = [
                        'scan_id' => $scanId,
                        'Head' => (string) $head,
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($details, 100) as $chunk) {
                    DB::table('labour_payment_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 16. Machine Operation (ID: 50) — Same as Labour Payment
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveMachineOperation(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Payee' => 'nullable|string|max:255',
            'Particular' => 'nullable|string|max:500',
            'From_Date' => 'nullable|date',
            'To_Date' => 'nullable|date',
            'Sub_Total' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'Related_Person' => $request->input('Payee', ''),
                'FileName' => $request->input('Particular_Text', ''),
                'FromDateTime' => $request->input('From_Date'),
                'ToDateTime' => $request->input('To_Date'),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Line items (labour/machine payment details)
            DB::table('labour_payment_detail')->where('scan_id', $scanId)->delete();
            $heads = $request->input('Head', []);
            if (is_array($heads) && !empty($heads)) {
                $details = [];
                foreach ($heads as $i => $head) {
                    if (empty(trim((string) ($head ?? '')))) continue;
                    $details[] = [
                        'scan_id' => $scanId,
                        'Head' => (string) $head,
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($details, 100) as $chunk) {
                    DB::table('labour_payment_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 17. Income Tax / TDS (ID: 20) — No line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveIncomeTax(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Challan_No' => 'nullable|string|max:150',
            'Challan_Date' => 'nullable|date',
            'Assessment_Year' => 'nullable|string|max:20',
            'Section' => 'nullable|string|max:100',
            'Payment_Nature' => 'nullable|string|max:255',
            'BSR_Code' => 'nullable|string|max:50',
            'Ref_No' => 'nullable|string|max:100',
            'Bank_Name' => 'nullable|string|max:255',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Challan_No'] = 'required|string|max:150';
            $rules['Challan_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $companyName = $request->filled('Company') ? DB::table('master_firm')->where('firm_id', $request->input('Company'))->value('firm_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'Company' => $companyName,
                'CompanyID' => (int) $request->input('Company', 0),
                'Financial_Year' => $request->input('Assessment_Year', ''),
                'Section' => $request->input('Section', ''),
                'BSRCode' => $request->input('BSR_Code', ''),
                'NatureOfPayment' => $request->input('Payment_Nature', ''),
                'File_No' => $request->input('Challan_No', ''),
                'File_Date' => $request->input('Challan_Date'),
                'ReferenceNo' => $request->input('Ref_No', ''),
                'BankName' => $request->input('Bank_Name', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 18. Insurance (ID: 22) — No line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveInsurance(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Policy_No' => 'nullable|string|max:100',
            'Policy_Date' => 'nullable|date',
            'Insurance_Type' => 'nullable|string|max:100',
            'Insurance_Company' => 'nullable|string|max:255',
            'From_Date' => 'nullable|date',
            'To_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Premium_Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Policy_No'] = 'required|string|max:100';
            $rules['Policy_Date'] = 'required|date';
            $rules['Premium_Amount'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_Type' => $request->input('Insurance_Type', ''),
                'AgentName' => $request->input('Insurance_Company', ''),
                'File_No' => $request->input('Policy_No', ''),
                'File_Date' => $request->input('Policy_Date'),
                'FromDateTime' => $request->input('From_Date'),
                'ToDateTime' => $request->input('To_Date'),
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => (float) $request->input('Premium_Amount', 0),
                'Grand_Total' => (float) $request->input('Premium_Amount', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 19. Lodging (ID: 28) — With line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveLodging(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Check_In' => 'nullable|date',
            'Check_Out' => 'nullable|date',
            'Room_Charge' => 'nullable|numeric',
            'Other_Charge' => 'nullable|numeric',
            'Discount' => 'nullable|numeric',
            'Gst' => 'nullable|string|max:50',
            'Duration' => 'nullable|string|max:50',
            'No_Room' => 'nullable|string|max:20',
            'Room_Type' => 'nullable|string|max:100',
            'Room_Rate' => 'nullable|string|max:100',
            'Booking_Id' => 'nullable|string|max:100',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $billingName = $request->filled('Billing_Name') ? DB::table('master_firm')->where('firm_id', $request->input('Billing_Name'))->value('firm_name') : '';
            $hotelName = $request->filled('Hotel') ? DB::table('master_hotel')->where('hotel_id', $request->input('Hotel'))->value('hotel_name') : $request->input('Hotel_Name', '');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'CompanyID' => (int) $request->input('Billing_Name', 0),
                'Company' => $billingName,
                'Related_Address' => $request->input('Billing_Address', ''),
                'Hotel' => $request->input('Hotel', ''),
                'Hotel_Name' => $hotelName,
                'Hotel_Address' => $request->input('Hotel_Address', ''),
                'Particular' => $request->input('Billing_Instruction', ''),
                'RegNo' => $request->input('Booking_Id', ''),
                'FromDateTime' => $request->input('Check_In'),
                'ToDateTime' => $request->input('Check_Out'),
                'Period' => $request->input('Duration', ''),
                'ReferenceNo' => $request->input('No_Room', ''),
                'TravelClass' => $request->input('Room_Type', ''),
                'TariffPlan' => $request->input('Room_Rate', ''),
                'Loc_Name' => $request->input('Location', ''),
                'SubTotal' => (float) $request->input('Room_Charge', 0),
                'OthCharge_Amount' => (float) $request->input('Other_Charge', 0),
                'Total_Discount' => (float) $request->input('Discount', 0),
                'GSTIN' => $request->input('Gst', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Line items (room charges breakdown)
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId, 'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 20. Meals (ID: 29) — No line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveMeals(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Detail' => 'nullable|string|max:500',
            'Hotel_Name' => 'nullable|string|max:255',
            'Hotel_Address' => 'nullable|string|max:500',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'FileName' => $request->input('Detail', ''),
                'EmployeeID' => $request->input('Employee', ''),
                'EmployeeCode' => $request->input('Emp_Code', ''),
                'Employee_Name' => $request->input('Employee_Name', ''),
                'Hotel_Name' => $request->input('Hotel_Name', ''),
                'Hotel_Address' => $request->input('Hotel_Address', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 21. Air (ID: 51) — With line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveAir(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Travel_Mode' => 'nullable|string|max:50',
            'TrainBusName' => 'nullable|string|max:255',
            'Quota' => 'nullable|string|max:50',
            'Class' => 'nullable|string|max:50',
            'Booking_Date' => 'nullable|date',
            'Journey_Date' => 'nullable|date',
            'Journey_From' => 'nullable|string|max:150',
            'Journey_Upto' => 'nullable|string|max:150',
            'Passenger' => 'nullable|string|max:500',
            'Booking_Status' => 'nullable|string|max:50',
            'Travel_Insurance' => 'nullable|string|max:50',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'TravelMode' => $request->input('Travel_Mode', ''),
                'FileName' => $request->input('TrainBusName', ''),
                'TravelQuota' => $request->input('Quota', ''),
                'TravelClass' => $request->input('Class', ''),
                'BookingDate' => $request->input('Booking_Date'),
                'FromDateTime' => $request->input('Journey_Date'),
                'FromName' => $request->input('Journey_From', ''),
                'ToName' => $request->input('Journey_Upto', ''),
                'PassengerDetail' => $request->input('Passenger', ''),
                'BookingStatus' => $request->input('Booking_Status', ''),
                'TravelInsurance' => $request->input('Travel_Insurance', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Line items (passengers/segments)
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId, 'Particular' => (string) $particular,
                        'HSN' => (string) ($request->input('HSN')[$i] ?? ''),
                        'Qty' => (float) ($request->input('Qty')[$i] ?? 0),
                        'Unit' => (string) ($request->input('Unit')[$i] ?? ''),
                        'MRP' => (float) ($request->input('MRP')[$i] ?? 0),
                        'Discount' => (float) ($request->input('Discount')[$i] ?? 0),
                        'Price' => (float) ($request->input('Price')[$i] ?? 0),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'GST' => (float) ($request->input('GST')[$i] ?? 0),
                        'SGST' => (float) ($request->input('SGST')[$i] ?? 0),
                        'IGST' => (float) ($request->input('IGST')[$i] ?? 0),
                        'Cess' => (float) ($request->input('Cess')[$i] ?? 0),
                        'Total_Amount' => (float) ($request->input('TAmount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($items, 100) as $chunk) {
                    DB::table('invoice_detail')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 22. Rail (ID: 52) — Same as Air
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveRail(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Travel_Mode' => 'nullable|string|max:50',
            'TrainBusName' => 'nullable|string|max:255',
            'Quota' => 'nullable|string|max:50',
            'Class' => 'nullable|string|max:50',
            'Booking_Date' => 'nullable|date',
            'Journey_Date' => 'nullable|date',
            'Journey_From' => 'nullable|string|max:150',
            'Journey_Upto' => 'nullable|string|max:150',
            'Passenger' => 'nullable|string|max:500',
            'Booking_Status' => 'nullable|string|max:50',
            'Travel_Insurance' => 'nullable|string|max:50',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'TravelMode' => $request->input('Travel_Mode', ''),
                'FileName' => $request->input('TrainBusName', ''),
                'TravelQuota' => $request->input('Quota', ''),
                'TravelClass' => $request->input('Class', ''),
                'BookingDate' => $request->input('Booking_Date'),
                'FromDateTime' => $request->input('Journey_Date'),
                'FromName' => $request->input('Journey_From', ''),
                'ToName' => $request->input('Journey_Upto', ''),
                'PassengerDetail' => $request->input('Passenger', ''),
                'BookingStatus' => $request->input('Booking_Status', ''),
                'TravelInsurance' => $request->input('Travel_Insurance', ''),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 23. Ticket Cancellation (ID: 55) — No line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveTicketCancellation(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'BillDate' => 'nullable|date',
            'AgentName' => 'nullable|string|max:255',
            'BookingDate' => 'nullable|date',
            'File_Date' => 'nullable|date',
            'Cancellation_Charge' => 'nullable|numeric',
            'OthCharge_Amount' => 'nullable|numeric',
            'Refund_Amount' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['BillDate'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('BillDate'),
                'AgentName' => $request->input('AgentName', ''),
                'BookingDate' => $request->input('BookingDate'),
                'File_Date' => $request->input('File_Date'),
                'SubTotal' => (float) $request->input('Cancellation_Charge', 0),
                'OthCharge_Amount' => (float) $request->input('OthCharge_Amount', 0),
                'Total_Discount' => (float) $request->input('Refund_Amount', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Ticket cancellation detail items
            DB::table('ticket_cancellation')->where('scan_id', $scanId)->delete();
            $employees = $request->input('Employee', []);
            if (is_array($employees) && !empty($employees)) {
                $details = [];
                foreach ($employees as $i => $empId) {
                    if (empty($empId)) continue;
                    $details[] = [
                        'scan_id' => $scanId,
                        'Emp_Id' => $empId,
                        'Emp_Name' => (string) ($request->input('Emp_Name')[$i] ?? ''),
                        'PNR' => (string) ($request->input('PNR')[$i] ?? ''),
                        'Amount' => (float) ($request->input('Ticket_Amount')[$i] ?? 0),
                    ];
                }
                foreach (array_chunk($details, 100) as $chunk) {
                    DB::table('ticket_cancellation')->insert($chunk);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // 24. Miscellaneous (ID: 31) — No line items
    // ═══════════════════════════════════════════════════════════════════════════

    private function saveMiscellaneous(Request $request, int $scanId, $scanRecord, bool $isFinal)
    {
        $rules = [
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Description' => 'nullable|string|max:1000',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Grand_Total'] = 'required|numeric|min:0';
        }
        $request->validate($rules);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $fromName = $request->filled('From') ? DB::table('master_firm')->where('firm_id', $request->input('From'))->value('firm_name') : '';
            $toName = $request->filled('To') ? DB::table('master_firm')->where('firm_id', $request->input('To'))->value('firm_name') : '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'Particular' => $request->input('Description', ''),
                'From_ID' => (int) $request->input('From', 0), 'FromName' => $fromName,
                'To_ID' => (int) $request->input('To', 0), 'ToName' => $toName,
                'Loc_Name' => $request->input('Location', ''),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y', 'Punch_By' => Auth::id(), 'Punch_Date' => now(),
                    'Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $isFinal ? 'Submitted successfully.' : 'Draft saved.', 'redirect' => $isFinal ? route('workflow.punching.index') : null]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Select2 Endpoints
    // ═══════════════════════════════════════════════════════════════════════════

    public function itemsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 50;

        $query = DB::table('master_item')
            ->where('status', 'A')->where('is_deleted', 'N')
            ->orderBy('item_name');
        if ($q !== '') $query->where('item_name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['item_name as id', 'item_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function createItem(Request $request)
    {
        $request->validate(['item_name' => 'required|string|max:255']);

        $id = DB::table('master_item')->insertGetId([
            'item_name' => $request->input('item_name'),
            'item_code' => '', 'status' => 'A', 'is_deleted' => 'N',
            'created_by' => Auth::id(), 'created_at' => now(),
        ]);
        DB::table('master_item')->where('item_id', $id)->update(['item_code' => sprintf('ITEM-%03d', $id)]);

        return response()->json(['success' => true, 'item' => ['id' => $request->input('item_name'), 'text' => $request->input('item_name')]]);
    }

    public function unitsSelect(Request $request)
    {
        $results = DB::table('master_unit')
            ->where('status', 'A')->where('is_deleted', 'N')
            ->orderBy('unit_name')
            ->get(['unit_id as id', 'unit_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => false]]);
    }

    public function buyersSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_firm')
            ->where('status', 'A')->where('firm_type', 'company')
            ->orderBy('firm_name');
        if ($q !== '') $query->where('firm_name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['firm_id as id', 'firm_name as text', 'address']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function vendorsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_firm')->where('status', 'A')->orderBy('firm_name');
        if ($q !== '') $query->where('firm_name', 'like', "%{$q}%");

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['firm_id as id', 'firm_name as text', 'address']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function departmentsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $query = DB::table('departments')->where('is_active', true)->orderBy('department_name');
        if ($q !== '') $query->where('department_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['id', 'department_name as text']), 'pagination' => ['more' => false]]);
    }

    public function categoriesSelect(Request $request)
    {
        $results = DB::table('master_category')->where('status', 'A')
            ->orderBy('category_name')->get(['category_name as id', 'category_name as text']);

        return response()->json(['results' => $results, 'pagination' => ['more' => false]]);
    }

    public function ledgersSelect(Request $request)
    {
        $q = $request->query('q', '');
        $query = DB::table('master_ledger')->where('status', 'A')->orderBy('ledger_name');
        if ($q !== '') $query->where('ledger_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['ledger_name as id', 'ledger_name as text']), 'pagination' => ['more' => false]]);
    }

    public function filesSelect(Request $request)
    {
        $buyerId = $request->query('buyer_id', '');
        $q = $request->query('q', '');

        $query = DB::table('master_file')->where('status', 'A')->orderBy('file_name');
        if ($buyerId) $query->where('firm_id', $buyerId);
        if ($q !== '') $query->where('file_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['file_name as id', 'file_name as text']), 'pagination' => ['more' => false]]);
    }

    public function locationsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $query = \App\Models\Location::active()->orderBy('location_name');
        if ($q !== '') $query->where('location_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['location_name as id', 'location_name as text']), 'pagination' => ['more' => false]]);
    }
}
