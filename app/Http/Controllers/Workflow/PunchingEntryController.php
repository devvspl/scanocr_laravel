<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\ScanActionLog;
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

    public function show(Request $request, $scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;
        $isViewMode = $request->query('view') == 1; // Check if in view-only mode
        // dd(app()->isLocal());
        // ── Gate check (skipped in local dev) ─────────────────────────────────
        // In production, only allow access to this form when the scan has been
        // rejected back for editing: Edit_Permission=Y, File_Punched=N, Punch_By > 0.
        // In local env we bypass the gate so developers can open any scan directly.
        if (!app()->isLocal() && !$isViewMode) {
            $gate = DB::table('scan_file')
                ->where('Scan_Id', $scanId)
                ->where('Edit_Permission', 'Y')
                ->where('File_Punched', 'N')
                ->where('Punch_By', '>', 0)
                ->exists();

            if (!$gate) {
                return response(view('errors.scan-not-editable'), 403);
            }
        }
        // ──────────────────────────────────────────────────────────────────────

        $scanData = DB::table('scan_file as s')
            ->leftJoin('master_work_location as l', 'l.location_id', '=', 's.Location')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('users as puncher', 'puncher.id', '=', 's.Punch_By')
            ->leftJoin('users as approver', 'approver.id', '=', 's.Approve_By')
            ->where('s.Scan_Id', $scanId)
            ->select([
                's.Scan_Id', 's.File', 's.File_Location', 's.File_Ext',
                's.Document_name', 's.DocType_Id', 'dt.label as doc_type_label',
                'dt.key as doc_type_key', 'c.name as company_name',
                'l.location_name', 's.Group_Id', 's.Location',
                's.File_Punched', 's.Punch_Date', 'puncher.name as punched_by_name',
                's.File_Approved', 's.Approve_Date', 'approver.name as approved_by_name',
                's.Is_Rejected', 's.Reject_Date', 's.Reject_Remark',
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

        // Load KM detail rows for two-four-wheeler / hired-vehicle / local-conveyance
        $kmRows = collect();
        if (in_array($formPartial, ['two-four-wheeler', 'hired-vehicle', 'local-conveyance'])) {
            $kmRows = DB::table('vehicle_traveling')->where('Scan_Id', $scanId)->get();
        }

        // Check if scan is eligible for approval (only in view mode)
        $canApprove = false;
        if ($isViewMode) {
            $approvalCheck = DB::table('scan_file')
                ->where('Scan_Id', $scanId)
                ->where('File_Punched', 'Y')
                ->where('File_Approved', 'N')
                ->where(function ($q) {
                    $q->whereNull('Is_Rejected')->orWhere('Is_Rejected', 'N');
                })
                ->exists();
            $canApprove = $approvalCheck;
        }

        return view('panel.workflow.punching.entry', compact('scanData', 'punchDetail', 'supportFiles', 'tempData', 'formPartial', 'kmRows', 'isViewMode', 'canApprove'));
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

    // ─── Scan History (offcanvas) ────────────────────────────────────────────

    public function history($scan)
    {
        $scanId = is_object($scan) ? $scan->Scan_Id : (int) $scan;

        $scanData = DB::table('scan_file as s')
            ->leftJoin('companies as c', 'c.id', '=', 's.Group_Id')
            ->leftJoin('document_types as dt', 'dt.id', '=', 's.DocType_Id')
            ->leftJoin('users as u1', 'u1.id', '=', 's.Temp_Scan_By')
            ->leftJoin('users as u2', 'u2.id', '=', 's.Scan_By')
            ->leftJoin('users as u3', 'u3.id', '=', 's.classified_by')
            ->leftJoin('users as u4', 'u4.id', '=', 's.Punch_By')
            ->leftJoin('users as u5', 'u5.id', '=', 's.Approve_By')
            ->leftJoin('users as u6', 'u6.id', '=', 's.Bill_Approver')
            ->leftJoin('users as u7', 'u7.id', '=', 's.Deleted_By')
            ->leftJoin('users as u8', 'u8.id', '=', 's.Scan_Resend_By')
            ->where('s.Scan_Id', $scanId)
            ->select([
                's.*',
                'c.name as company_name',
                'dt.label as doc_type_label',
                'u1.name as temp_scan_by_name',
                'u2.name as scan_by_name',
                'u3.name as classified_by_name',
                'u4.name as punch_by_name',
                'u5.name as approve_by_name',
                'u6.name as bill_approver_name',
                'u7.name as deleted_by_name',
                'u8.name as resend_by_name',
            ])
            ->first();

        if (!$scanData) {
            return response()->json(['html' => '<div style="text-align:center;color:#b91c1c;font-size:.7rem;padding:2rem">Scan not found.</div>']);
        }

        // Format date helper
        $fmtDate = function($val) {
            if (!$val || $val === '-' || $val === '0000-00-00 00:00:00') return '-';
            try {
                $dt = \Carbon\Carbon::parse($val);
                return $dt->format('H:i:s') !== '00:00:00'
                    ? $dt->format('d-m-Y')
                    : $dt->format('d-m-Y');
            } catch (\Exception $e) {
                return $val;
            }
        };

        // Build timeline HTML
        $html = '<div style="font-size:.68rem;color:#44403c">';

        // Stage styling helper
        $stages = [];

        // 1. Temp Scan
        $stages[] = [
            'label' => 'Temp Scanning',
            'status' => $scanData->Temp_Scan === 'Y' ? 'done' : 'pending',
            'by' => $scanData->temp_scan_by_name ?? '-',
            'date' => $fmtDate($scanData->Temp_Scan_Date ?? null),
            'details' => [],
        ];

        // 2. Scan Complete / Super Scanner
        $stages[] = [
            'label' => 'Scanning Complete',
            'status' => ($scanData->Scan_Complete && $scanData->Scan_Complete !== 'N') ? 'done' : 'pending',
            'by' => $scanData->scan_by_name ?? '-',
            'date' => $fmtDate($scanData->Scan_Date ?? null),
            'details' => [],
        ];

        // 3. Bill Approval
        $stages[] = [
            'label' => 'Bill Approval',
            'status' => ($scanData->Bill_Approved && $scanData->Bill_Approved !== 'N') ? 'done' : 'pending',
            'by' => $scanData->bill_approver_name ?? '-',
            'date' => $fmtDate($scanData->Bill_Approver_Date ?? null),
            'details' => $scanData->Bill_Approver_Remark ? ['Remark' => $scanData->Bill_Approver_Remark] : [],
        ];

        // 4. Classification
        $isClassified = $scanData->classified_by || ($scanData->is_autoclassified === 'Y');
        $stages[] = [
            'label' => 'Classification',
            'status' => $isClassified ? 'done' : 'pending',
            'by' => $scanData->classified_by_name ?? ($scanData->is_autoclassified === 'Y' ? 'Auto' : '-'),
            'date' => $fmtDate($scanData->classified_date ?? null),
            'details' => [],
        ];

        // 3b. Extraction
        $extractQueue = DB::table('tbl_queues')
            ->leftJoin('users as uq', 'uq.id', '=', 'tbl_queues.created_by')
            ->where('tbl_queues.scan_id', $scanId)
            ->orderBy('tbl_queues.id', 'desc')
            ->select(['tbl_queues.*', 'uq.name as queue_by_name'])
            ->first();

        $extractStatus = 'pending';
        $extractDate = '-';
        $extractBy = '-';
        $extractDetails = [];

        if ($extractQueue) {
            $extractBy = $extractQueue->queue_by_name ?? '-';
            if ($extractQueue->status === 'completed' && $extractQueue->result === 'success') {
                $extractStatus = 'done';
                $extractDate = $fmtDate($extractQueue->completed_at);
            } elseif ($extractQueue->status === 'failed') {
                $extractStatus = 'rejected';
                $extractDate = $fmtDate($extractQueue->completed_at ?? $extractQueue->started_at);
                if ($extractQueue->error_message) {
                    $extractDetails['Error'] = \Illuminate\Support\Str::limit($extractQueue->error_message, 100);
                }
            } elseif (in_array($extractQueue->status, ['processing', 'pending'])) {
                $extractStatus = 'in-progress';
                $extractDate = $fmtDate($extractQueue->started_at ?? $extractQueue->created_at);
            } else {
                $extractDate = $fmtDate($extractQueue->created_at);
            }
            $extractDetails['Status'] = ucfirst($extractQueue->status) . ($extractQueue->result ? ' (' . $extractQueue->result . ')' : '');
        } elseif ($scanData->is_extract === 'Y') {
            $extractStatus = 'done';
        }

        $stages[] = [
            'label' => 'Extraction',
            'status' => $extractStatus,
            'by' => $extractBy,
            'date' => $extractDate,
            'details' => $extractDetails,
        ];

        // 4. Punching
        $stages[] = [
            'label' => 'Data Punching',
            'status' => $scanData->File_Punched === 'Y' ? 'done' : ($scanData->Punch_By ? 'in-progress' : 'pending'),
            'by' => $scanData->punch_by_name ?? '-',
            'date' => $fmtDate($scanData->Punch_Date ?? null),
            'details' => [],
        ];

        // 5. Rejection (if applicable)
        if ($scanData->Is_Rejected === 'Y') {
            $stages[] = [
                'label' => 'Rejected',
                'status' => 'rejected',
                'by' => '-',
                'date' => $fmtDate($scanData->Reject_Date ?? null),
                'details' => ['Remark' => $scanData->Reject_Remark ?? '-'],
            ];
        }

        // 6. Resend (if applicable)
        if ($scanData->Scan_Resend === 'Y') {
            $stages[] = [
                'label' => 'Resent',
                'status' => 'info',
                'by' => $scanData->resend_by_name ?? '-',
                'date' => $fmtDate($scanData->Scan_Resend_Date ?? null),
                'details' => ['Remark' => $scanData->Scan_Resend_Remark ?? '-'],
            ];
        }

        // Render timeline
        foreach ($stages as $i => $stage) {
            $color = match($stage['status']) {
                'done' => '#15803d',
                'in-progress' => '#d97706',
                'rejected' => '#b91c1c',
                'info' => '#7f1d1d',
                default => '#a8a29e',
            };
            $icon = match($stage['status']) {
                'done' => '✓',
                'in-progress' => '●',
                'rejected' => '✕',
                'info' => 'ℹ',
                default => '○',
            };

            $html .= '<div style="display:flex;gap:.6rem;margin-bottom:.6rem">';
            $html .= '<div style="display:flex;flex-direction:column;align-items:center;min-width:18px">';
            $html .= '<div style="width:18px;height:18px;border-radius:50%;background:' . $color . ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:700">' . $icon . '</div>';
            if ($i < count($stages) - 1) {
                $html .= '<div style="width:2px;flex:1;background:#e7e5e4;margin-top:2px"></div>';
            }
            $html .= '</div>';
            $html .= '<div style="flex:1;padding-bottom:.5rem">';
            $html .= '<div style="font-weight:600;color:' . $color . ';margin-bottom:.15rem">' . e($stage['label']) . '</div>';
            $html .= '<div style="color:#78716c;font-size:.6rem">By: ' . e($stage['by']) . ' • ' . e($stage['date']) . '</div>';
            if (!empty($stage['details'])) {
                foreach ($stage['details'] as $key => $val) {
                    $html .= '<div style="color:#57534e;font-size:.6rem;margin-top:.1rem"><span style="font-weight:500">' . e($key) . ':</span> ' . e($val) . '</div>';
                }
            }
            $html .= '</div></div>';
        }

        $html .= '</div>';

        return response()->json(['html' => $html]);
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
            $response = $this->$method($request, $scanId, $scanRecord, $isFinal);
        } else {
            $response = $this->saveInvoice($request, $scanId, $scanRecord, $isFinal);
        }

        // Log the action if save was successful
        $responseData = json_decode($response->getContent(), true);
        if (!empty($responseData['success'])) {
            $action = $isFinal ? 'punch_submitted' : 'punch_draft_saved';
            $label  = $isFinal ? 'Punch Data Submitted' : 'Punch Draft Saved';
            ScanActionLog::log($scanId, $action, $label, null, ['doc_type' => $formKey]);
        }

        return $response;
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
            $rules['Remark'] = 'required|string|min:1|max:5000';
            $rules['Particular'] = 'required|array|min:1';
            $rules['Particular.*'] = 'required|string|min:1';
            $rules['Qty'] = 'required|array|min:1';
            $rules['Qty.*'] = 'required|numeric|min:0';
            $rules['MRP'] = 'required|array|min:1';
            $rules['MRP.*'] = 'required|numeric|min:0';
        }
        $request->validate($rules, [
            'Bill_No.required' => 'Invoice No is required.',
            'Bill_Date.required' => 'Invoice Date is required.',
            'From.required' => 'Buyer is required.',
            'To.required' => 'Vendor is required.',
            'Location.required' => 'Location is required.',
            'Grand_Total.required' => 'Grand Total is required.',
            'Remark.required' => 'Remark is required.',
            'Particular.required' => 'At least one Line Item is required.',
            'Particular.min' => 'At least one Line Item is required.',
            'Particular.*.required' => 'Particular is required in each row.',
            'Qty.*.required' => 'Qty is required in each row.',
            'Qty.*.numeric' => 'Qty must be a number.',
            'MRP.*.required' => 'MRP is required in each row.',
            'MRP.*.numeric' => 'MRP must be a number.',
        ]);

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
                'ServiceNo' => $request->input('Buyer_Order_No', ''),
                'BookingDate' => $request->input('Order_Date'),
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
                'Total_Amount' => (float) $request->input('Total', $request->input('Grand_Total', 0)),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
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
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
            }

            // Save line items (chunked for 500+ rows)
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            $hsns = $request->input('HSN', []);
            $qtys = $request->input('Qty', []);
            $units = $request->input('Unit', []);
            $mrps = $request->input('MRP', []);
            $discounts = $request->input('Discount', []);
            $prices = $request->input('Price', []);
            $amounts = $request->input('Amount', []);
            $gsts = $request->input('GST', []);
            $sgsts = $request->input('SGST', []);
            $igsts = $request->input('IGST', []);
            $cesses = $request->input('Cess', []);
            $tamounts = $request->input('TAmount', []);

            if (is_array($particulars) && !empty($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId,
                        'Particular' => (string) $particular,
                        'HSN' => (string) ($hsns[$i] ?? ''),
                        'Qty' => (string) (($qtys[$i] ?? '') !== '' ? $qtys[$i] : '0'),
                        'Unit' => (string) ($units[$i] ?? ''),
                        'MRP' => (string) (($mrps[$i] ?? '') !== '' ? $mrps[$i] : '0'),
                        'Discount' => (string) (($discounts[$i] ?? '') !== '' ? $discounts[$i] : '0'),
                        'Price' => (string) (($prices[$i] ?? '') !== '' ? $prices[$i] : '0'),
                        'Amount' => (string) (($amounts[$i] ?? '') !== '' ? $amounts[$i] : '0'),
                        'GST' => (string) (($gsts[$i] ?? '') !== '' ? $gsts[$i] : '0'),
                        'SGST' => (string) (($sgsts[$i] ?? '') !== '' ? $sgsts[$i] : '0'),
                        'IGST' => (string) (($igsts[$i] ?? '') !== '' ? $igsts[$i] : '0'),
                        'Cess' => (string) (($cesses[$i] ?? '') !== '' ? $cesses[$i] : '0'),
                        'Total_Amount' => (string) (($tamounts[$i] ?? '') !== '' ? $tamounts[$i] : '0'),
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
            'Payment_Mode' => 'nullable|string|max:100',
            'Supplier_Ref' => 'nullable|string|max:150',
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'Buyer_Address' => 'nullable|string|max:500',
            'Vendor_Address' => 'nullable|string|max:500',
            'Buyer_Order' => 'nullable|string|max:150',
            'Buyer_Order_Date' => 'nullable|date',
            'Dispatch_Trough' => 'nullable|string|max:255',
            'Delivery_Note_Date' => 'nullable|date',
            'Department' => 'nullable|integer',
            'Category' => 'nullable|string|max:255',
            'Ledger' => 'nullable|string|max:255',
            'File' => 'nullable|string|max:255',
            'LR_Number' => 'nullable|string|max:150',
            'LR_Date' => 'nullable|date',
            'Cartoon_Number' => 'nullable|string|max:150',
            'Consignee_Name' => 'nullable|string|max:255',
            'Sub_Total' => 'nullable|numeric',
            'Total' => 'nullable|numeric',
            'Total_Discount' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'TCS' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Bill_No.required' => 'Invoice No is required.',
            'Bill_Date.required' => 'Invoice Date is required.',
            'From.required' => 'Vendor is required.',
            'To.required' => 'Buyer is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            
            $fromId = (int) $request->input('From', 0);
            $fromName = $fromId ? DB::table('master_firm')->where('firm_id', $fromId)->value('firm_name') : '';
            
            $toId = (int) $request->input('To', 0);
            $toName = $toId ? DB::table('master_firm')->where('firm_id', $toId)->value('firm_name') : '';
            
            $deptId = (int) $request->input('Department', 0);
            $deptName = $deptId ? DB::table('departments')->where('id', $deptId)->value('department_name') : '';

            $data = [
                'Scan_Id' => $scanId,
                'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '',
                'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'ReferenceNo' => $request->input('Supplier_Ref', ''),
                'From_ID' => $fromId,
                'FromName' => $fromName,
                'To_ID' => $toId,
                'ToName' => $toName,
                'Loc_Add' => $request->input('Buyer_Address', ''),
                'AgencyAddress' => $request->input('Vendor_Address', ''),
                'ServiceNo' => $request->input('Buyer_Order', ''),
                'BookingDate' => $request->input('Buyer_Order_Date'),
                'Particular' => $request->input('Dispatch_Trough', ''),
                'DueDate' => $request->input('Delivery_Note_Date'),
                'Department' => $deptName,
                'DepartmentID' => $deptId,
                'Category' => $request->input('Category', ''),
                'Ledger' => $request->input('Ledger', ''),
                'FileName' => $request->input('File', ''),
                'FDRNo' => $request->input('LR_Number', ''),
                'File_Date' => $request->input('LR_Date'),
                'RegNo' => $request->input('Cartoon_Number', ''),
                'AgentName' => $request->input('Consignee_Name', ''),
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Total_Amount' => (float) $request->input('Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
                'TCS' => (float) $request->input('TCS', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                // Update existing record
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update([
                    'Amount' => '-' . $data['Grand_Total'],
                    'Comment' => $data['Remark']
                ]);
                // Delete existing invoice details
                DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
                // Update scan_file to clear rejection
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N'
                ]);
            } else {
                // Insert new record
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert([
                    'FileID' => $fileID,
                    'Amount' => '-' . $data['Grand_Total'],
                    'Comment' => $data['Remark']
                ]);
            }

            // Insert invoice details (line items)
            $particulars = $request->input('Particular', []);
            if (is_array($particulars) && !empty($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    // Skip empty rows
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
                
                // Insert in batches of 100
                if (!empty($items)) {
                    foreach (array_chunk($items, 100) as $chunk) {
                        DB::table('invoice_detail')->insert($chunk);
                    }
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y',
                    'Punch_By' => Auth::id(),
                    'Punch_Date' => now(),
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $isFinal ? 'Sale Bill submitted successfully.' : 'Draft saved.',
                'redirect' => $isFinal ? route('workflow.punching.index') : null
            ]);
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
            'CreditNo' => 'nullable|string|max:150',
            'CreditDate' => 'nullable|date',
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'Location' => 'nullable|string|max:255',
            'Sub_Total' => 'nullable|numeric',
            'Total' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
            'Particular' => 'nullable|array',
            'Particular.*' => 'nullable|string|max:255',
        ];
        if ($isFinal) {
            $rules['CreditNo'] = 'required|string|max:150';
            $rules['CreditDate'] = 'required|date';
            $rules['Bill_Date'] = 'required|date';
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Location'] = 'required|string|max:255';
            $rules['Grand_Total'] = 'required|numeric|min:0';
            $rules['Remark'] = 'required|string|max:5000';
            $rules['Particular'] = 'required|array|min:1';
            $rules['Particular.*'] = 'required|string|max:255';
        }
        $request->validate($rules, [
            'CreditNo.required' => 'Credit Note No is required.',
            'CreditDate.required' => 'Credit Note Date is required.',
            'Bill_Date.required' => 'Invoice Date is required.',
            'From.required' => 'Buyer is required.',
            'To.required' => 'Vendor is required.',
            'Location.required' => 'Location is required.',
            'Grand_Total.required' => 'Grand Total is required.',
            'Remark.required' => 'Remark is required.',
            'Particular.required' => 'At least one line item is required.',
            'Particular.*.required' => 'Particular is required in all line items.',
        ]);

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
                'CreditNo' => $request->input('CreditNo', ''),
                'CreditDate' => $request->input('CreditDate'),
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
                'Particular' => $request->input('Dispatch_Trough', ''),
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
                'SubTotal' => (float) $request->input('Sub_Total', 0),
                'Total_Amount' => (float) $request->input('Total', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
                'Round_Off_Type' => $request->input('Round_Off_Type', 'none'),
                'Round_Off_Value' => (float) $request->input('Round_Off_Value', 0),
                'TCS' => (float) $request->input('TCS', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                // Update existing record
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update([
                    'Amount' => '-' . $data['Grand_Total'],
                    'Comment' => $data['Remark']
                ]);
                // Update scan_file to clear rejection status
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N'
                ]);
            } else {
                // Insert new record
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert([
                    'FileID' => $fileID,
                    'Amount' => '-' . $data['Grand_Total'],
                    'Comment' => $data['Remark']
                ]);
            }

            // Delete and re-insert invoice details
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            if (is_array($particulars) && !empty($particulars)) {
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
                if (!empty($items)) {
                    foreach (array_chunk($items, 100) as $chunk) {
                        DB::table('invoice_detail')->insert($chunk);
                    }
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y',
                    'Punch_By' => Auth::id(),
                    'Punch_Date' => now(),
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $isFinal ? 'Credit Note submitted successfully.' : 'Draft saved.',
                'redirect' => $isFinal ? route('workflow.punching.index') : null
            ]);
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
            'InvoiceNo' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Vendor_Name' => 'nullable|integer',
            'Billing_To' => 'nullable|integer',
            'VehicleRegNo' => 'nullable|string|max:50',
            'Work_Location' => 'nullable|string|max:255',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['InvoiceNo'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Vendor_Name'] = 'required|integer|min:1';
            $rules['Billing_To'] = 'required|integer|min:1';
            $rules['Grand_Total'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'InvoiceNo.required' => 'Invoice No is required.',
            'Bill_Date.required' => 'Invoice Date is required.',
            'Vendor_Name.required' => 'Vendor Name is required.',
            'Billing_To.required' => 'Billing To is required.',
            'Grand_Total.required' => 'Grand Total is required.',
            'Grand_Total.gt' => 'Grand Total must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $vendorId = (int) $request->input('Vendor_Name', 0);
            $vendorName = $vendorId ? DB::table('master_firm')->where('firm_id', $vendorId)->value('firm_name') : '';
            $billingId = (int) $request->input('Billing_To', 0);
            $billingName = $billingId ? DB::table('master_firm')->where('firm_id', $billingId)->value('firm_name') : '';
            $grandTotal = (float) $request->input('Grand_Total', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'From_ID' => $vendorId, 'FromName' => $vendorName,
                'To_ID' => $billingId, 'ToName' => $billingName,
                'Company' => $billingName, 'CompanyID' => $billingId,
                'File_No' => $request->input('InvoiceNo', ''),
                'BillDate' => $request->input('Bill_Date'),
                'Loc_Name' => $request->input('Work_Location', ''),
                'VehicleRegNo' => $request->input('VehicleRegNo', ''),
                'SubTotal' => $request->input('Sub_Total', '') !== '' ? (float) $request->input('Sub_Total') : 0,
                'Total_Amount' => $request->input('Total', '') !== '' ? (float) $request->input('Total') : 0,
                'Grand_Total' => $grandTotal,
                'Total_Discount' => $request->input('Total_Discount', '') !== '' ? (float) $request->input('Total_Discount') : 0,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $grandTotal, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $grandTotal, 'Comment' => $data['Remark']]);
            }

            // Line items
            DB::table('invoice_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            $hsns = $request->input('HSN', []);
            $qtys = $request->input('Qty', []);
            $units = $request->input('Unit', []);
            $mrps = $request->input('MRP', []);
            $discounts = $request->input('Discount', []);
            $prices = $request->input('Price', []);
            $amounts = $request->input('Amount', []);
            $gsts = $request->input('GST', []);
            $sgsts = $request->input('SGST', []);
            $igsts = $request->input('IGST', []);
            $tamounts = $request->input('TAmount', []);

            if (is_array($particulars) && !empty($particulars)) {
                $items = [];
                foreach ($particulars as $i => $particular) {
                    if (empty(trim((string) ($particular ?? '')))) continue;
                    $items[] = [
                        'Scan_Id' => $scanId,
                        'Particular' => (string) $particular,
                        'HSN' => (string) ($hsns[$i] ?? ''),
                        'Qty' => (string) (($qtys[$i] ?? '') !== '' ? $qtys[$i] : '0'),
                        'Unit' => (string) ($units[$i] ?? ''),
                        'MRP' => (string) (($mrps[$i] ?? '') !== '' ? $mrps[$i] : '0'),
                        'Discount' => (string) (($discounts[$i] ?? '') !== '' ? $discounts[$i] : '0'),
                        'Price' => (string) (($prices[$i] ?? '') !== '' ? $prices[$i] : '0'),
                        'Amount' => (string) (($amounts[$i] ?? '') !== '' ? $amounts[$i] : '0'),
                        'GST' => (string) (($gsts[$i] ?? '') !== '' ? $gsts[$i] : '0'),
                        'SGST' => (string) (($sgsts[$i] ?? '') !== '' ? $sgsts[$i] : '0'),
                        'IGST' => (string) (($igsts[$i] ?? '') !== '' ? $igsts[$i] : '0'),
                        'Cess' => '0',
                        'Total_Amount' => (string) (($tamounts[$i] ?? '') !== '' ? $tamounts[$i] : '0'),
                    ];
                }
                if (!empty($items)) {
                    foreach (array_chunk($items, 100) as $chunk) {
                        DB::table('invoice_detail')->insert($chunk);
                    }
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
            'Voucher_No' => 'nullable|string|max:150',
            'Voucher_Date' => 'nullable|date',
            'Payee' => 'nullable|string|max:255',
            'Payer' => 'nullable|string|max:255',
            'Particular' => 'nullable|string|max:500',
            'Location' => 'nullable|string|max:255',
            'Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Voucher_No'] = 'required|string|max:150';
            $rules['Voucher_Date'] = 'required|date';
            $rules['Payee'] = 'required|string|max:255';
            $rules['Payer'] = 'required|string|max:255';
            $rules['Particular'] = 'required|string|max:500';
            $rules['Location'] = 'required|string|max:255';
            $rules['Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Voucher_No.required' => 'Voucher No is required.',
            'Voucher_Date.required' => 'Voucher Date is required.',
            'Payee.required' => 'Payee is required.',
            'Payer.required' => 'Payer is required.',
            'Particular.required' => 'Particular is required.',
            'Location.required' => 'Location is required.',
            'Amount.required' => 'Amount is required.',
            'Amount.gt' => 'Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $amount = (float) $request->input('Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_No' => $request->input('Voucher_No', ''),
                'BillDate' => $request->input('Voucher_Date'),
                'Related_Person' => $request->input('Payee', ''),
                'AgentName' => $request->input('Payer', ''),
                'FileName' => $request->input('Particular', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => $amount,
                'finance_total_Amount' => $amount,
                'Grand_Total' => $amount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
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
            'Type' => 'nullable|string|max:100',
            'Date' => 'nullable|date',
            'Bank_Name' => 'nullable|string|max:255',
            'Branch' => 'nullable|string|max:255',
            'Account_No' => 'nullable|string|max:100',
            'Beneficiary_Name' => 'nullable|string|max:255',
            'Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Type'] = 'required|string|max:100';
            $rules['Date'] = 'required|date';
            $rules['Bank_Name'] = 'required|string|max:255';
            $rules['Branch'] = 'required|string|max:255';
            $rules['Account_No'] = 'required|string|max:100';
            $rules['Beneficiary_Name'] = 'required|string|max:255';
            $rules['Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Type.required' => 'Type is required.',
            'Date.required' => 'Date is required.',
            'Bank_Name.required' => 'Bank Name is required.',
            'Branch.required' => 'Branch is required.',
            'Account_No.required' => 'Account No is required.',
            'Beneficiary_Name.required' => 'Beneficiary Name is required.',
            'Amount.required' => 'Amount is required.',
            'Amount.gt' => 'Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_Type' => $request->input('Type', ''),
                'BillDate' => $request->input('Date'),
                'BankName' => $request->input('Bank_Name', ''),
                'BankAddress' => $request->input('Branch', ''),
                'BankAccountNo' => $request->input('Account_No', ''),
                'Related_Person' => $request->input('Beneficiary_Name', ''),
                'Total_Amount' => (float) $request->input('Amount', 0),
                'Grand_Total' => (float) $request->input('Amount', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Total_Amount'], 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $data['Total_Amount'], 'Comment' => $data['Remark']]);
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
            'Receipt_No' => 'nullable|string|max:150',
            'Receipt_Date' => 'nullable|date',
            'CompanyID' => 'nullable|integer',
            'Receiver' => 'nullable|string|max:255',
            'ReceivedFrom' => 'nullable|string|max:255',
            'Particular' => 'nullable|string|max:500',
            'Location' => 'nullable|string|max:255',
            'Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Receipt_No'] = 'required|string|max:150';
            $rules['Receipt_Date'] = 'required|date';
            $rules['CompanyID'] = 'required|integer|min:1';
            $rules['Receiver'] = 'required|string|max:255';
            $rules['ReceivedFrom'] = 'required|string|max:255';
            $rules['Amount'] = 'required|numeric|gt:0';
            $rules['Location'] = 'required|string|max:255';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Receipt_No.required' => 'Receipt No is required.',
            'Receipt_Date.required' => 'Receipt Date is required.',
            'CompanyID.required' => 'Company is required.',
            'Receiver.required' => 'Receiver is required.',
            'ReceivedFrom.required' => 'Received From is required.',
            'Amount.required' => 'Amount is required.',
            'Amount.gt' => 'Amount must be greater than 0.',
            'Location.required' => 'Location is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $companyId = (int) $request->input('CompanyID', 0);
            $companyName = $companyId ? DB::table('master_firm')->where('firm_id', $companyId)->value('firm_name') : '';
            $amount = (float) $request->input('Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_No' => $request->input('Receipt_No', ''),
                'BillDate' => $request->input('Receipt_Date'),
                'CompanyID' => $companyId,
                'Company' => $companyName,
                'Related_Person' => $request->input('Receiver', ''),
                'FromName' => $request->input('ReceivedFrom', ''),
                'FileName' => $request->input('Particular', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => $amount,
                'Grand_Total' => $amount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
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
            'Biller_Name' => 'nullable|string|max:255',
            'BP_No' => 'nullable|string|max:100',
            'Period' => 'nullable|string|max:50',
            'Meter_No' => 'nullable|string|max:100',
            'Bill_Date' => 'nullable|date',
            'Bill_No' => 'nullable|string|max:150',
            'Previous_Reading' => 'nullable|string|max:50',
            'Current_Reading' => 'nullable|string|max:50',
            'Unit_Consumed' => 'nullable|string|max:50',
            'Last_Date' => 'nullable|date',
            'Payment_Mode' => 'nullable|string|max:100',
            'Bill_Amount' => 'nullable|numeric',
            'Payment_Amount' => 'nullable|numeric',
            'Location' => 'nullable|string|max:255',
            'PaymentDate' => 'nullable|date',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Location'] = 'required|string|max:255';
            $rules['PaymentDate'] = 'required|date';
            $rules['Biller_Name'] = 'required|string|max:255';
            $rules['BP_No'] = 'required|string|max:100';
            $rules['Period'] = 'required|string|max:50';
            $rules['Meter_No'] = 'required|string|max:100';
            $rules['Bill_Date'] = 'required|date';
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Previous_Reading'] = 'required|string|max:50';
            $rules['Current_Reading'] = 'required|string|max:50';
            $rules['Unit_Consumed'] = 'required|string|max:50';
            $rules['Bill_Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Location.required' => 'Location is required.',
            'PaymentDate.required' => 'Payment Date is required.',
            'Biller_Name.required' => 'Biller Name is required.',
            'BP_No.required' => 'BP No is required.',
            'Period.required' => 'Bill Period is required.',
            'Meter_No.required' => 'Meter Number is required.',
            'Bill_Date.required' => 'Bill Date is required.',
            'Bill_No.required' => 'Bill No is required.',
            'Previous_Reading.required' => 'Previous Reading is required.',
            'Current_Reading.required' => 'Current Reading is required.',
            'Unit_Consumed.required' => 'Unit Consumed is required.',
            'Bill_Amount.required' => 'Bill Amount is required.',
            'Bill_Amount.gt' => 'Bill Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $amount = (float) $request->input('Bill_Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'Related_Person' => $request->input('Biller_Name', ''),
                'ReferenceNo' => $request->input('BP_No', ''),
                'Period' => $request->input('Period', ''),
                'MeterNumber' => $request->input('Meter_No', ''),
                'BillDate' => $request->input('Bill_Date'),
                'File_No' => $request->input('Bill_No', ''),
                'LastDateOfPayment' => $request->input('Last_Date'),
                'PreviousReading' => $request->input('Previous_Reading', ''),
                'CurrentReading' => $request->input('Current_Reading', ''),
                'UnitsConsumed' => $request->input('Unit_Consumed', ''),
                'NatureOfPayment' => $request->input('Payment_Mode', ''),
                'Total_Amount' => $amount,
                'Payment_Amount' => (float) $request->input('Payment_Amount', 0),
                'Loc_Name' => $request->input('Location', ''),
                'PremiumDate' => $request->input('PaymentDate'),
                'Grand_Total' => $amount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
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
            $rules['Biller_Name'] = 'required|string|max:255';
            $rules['Phone_No'] = 'required|string|max:50';
            $rules['Amount_Outstanding'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Invoice_No.required' => 'Invoice No is required.',
            'Bill_Date.required' => 'Bill Date is required.',
            'Biller_Name.required' => 'Biller Name is required.',
            'Phone_No.required' => 'Phone No is required.',
            'Amount_Outstanding.required' => 'Total Amount Outstanding is required.',
            'Amount_Outstanding.gt' => 'Total Amount Outstanding must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

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
                'SubTotal' => $request->input('Taxable_Value', '') !== '' ? (float) $request->input('Taxable_Value') : 0,
                'CGST_Amount' => $request->input('CGST', '') !== '' ? (float) $request->input('CGST') : 0,
                'SGST_Amount' => $request->input('SGST', '') !== '' ? (float) $request->input('SGST') : 0,
                'GST_IGST_Amount' => $request->input('IGST', '') !== '' ? (float) $request->input('IGST') : 0,
                'Total_Amount' => $request->input('Amount_Due', '') !== '' ? (float) $request->input('Amount_Due') : 0,
                'Grand_Total' => $request->input('Amount_Outstanding', '') !== '' ? (float) $request->input('Amount_Outstanding') : 0,
                'DueDate' => $request->input('Last_Payment_Date'),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $data['Grand_Total'], 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
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
            $rules['Deposit_Date'] = 'required|date';
            $rules['GSTIN'] = 'required|string|max:50';
            $rules['Total_Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'CPIN.required' => 'CPIN is required.',
            'Deposit_Date.required' => 'Deposit Date is required.',
            'GSTIN.required' => 'GSTIN is required.',
            'Total_Amount.required' => 'Total Challan Amount is required.',
            'Total_Amount.gt' => 'Total Challan Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $amount = (float) $request->input('Total_Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'CPIN' => $request->input('CPIN', ''),
                'File_Date' => $request->input('Deposit_Date'),
                'CIN' => $request->input('CIN', ''),
                'BankName' => $request->input('Bank_Name', ''),
                'BankBSRCode' => $request->input('BRN', ''),
                'GSTIN' => $request->input('GSTIN', '') ?? '',
                'Email' => $request->input('Email', '') ?? '',
                'MobileNo' => $request->input('Mobile', '') ?? '',
                'Company' => $request->input('Company', '') ?? '',
                'Related_Address' => $request->input('Address', '') ?? '',
                'Total_Amount' => $amount,
                'Grand_Total' => $amount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
            }

            // GST Challan detail items
            DB::table('gst_challan_detail')->where('Scan_Id', $scanId)->delete();
            $particulars = $request->input('Particular', []);
            $taxes = $request->input('Tax', []);
            $interests = $request->input('Interest', []);
            $penalties = $request->input('Penalty', []);
            $fees = $request->input('Fees', []);
            $others = $request->input('Other', []);
            $totals = $request->input('Total', []);

            if (is_array($particulars) && !empty($particulars)) {
                $details = [];
                foreach ($particulars as $i => $particular) {
                    $details[] = [
                        'Scan_Id' => $scanId,
                        'Particular' => (string) ($particular ?? ''),
                        'Tax' => ($taxes[$i] ?? '') !== '' ? (float) $taxes[$i] : 0,
                        'Interest' => ($interests[$i] ?? '') !== '' ? (float) $interests[$i] : 0,
                        'Penalty' => ($penalties[$i] ?? '') !== '' ? (float) $penalties[$i] : 0,
                        'Fees' => ($fees[$i] ?? '') !== '' ? (float) $fees[$i] : 0,
                        'Other' => ($others[$i] ?? '') !== '' ? (float) $others[$i] : 0,
                        'Total' => ($totals[$i] ?? '') !== '' ? (float) $totals[$i] : 0,
                    ];
                }
                if (!empty($details)) {
                    foreach (array_chunk($details, 100) as $chunk) {
                        DB::table('gst_challan_detail')->insert($chunk);
                    }
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
            'Employee' => 'nullable|integer',
            'Bill_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Vehicle_Type' => 'nullable|string|max:50',
            'Location' => 'nullable|string|max:255',
            'Rate_Per_KM' => 'nullable|numeric',
            'Total_KM' => 'nullable|numeric',
            'Total_Amount' => 'nullable|numeric',
            'Total_Discount' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
            'Dist_Opening' => 'nullable|array',
            'Dist_Closing' => 'nullable|array',
        ];
        if ($isFinal) {
            $rules['Employee'] = 'required|integer|min:1';
            $rules['Bill_Date'] = 'required|date';
            $rules['Vehicle_No'] = 'required|string|max:50';
            $rules['Location'] = 'required|string|max:255';
            $rules['Rate_Per_KM'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
            $rules['Dist_Opening'] = 'required|array|min:1';
            $rules['Dist_Closing'] = 'required|array|min:1';
        }
        $request->validate($rules, [
            'Employee.required' => 'Employee / Payee is required.',
            'Bill_Date.required' => 'Bill Date is required.',
            'Vehicle_No.required' => 'Vehicle No is required.',
            'Location.required' => 'Location is required.',
            'Rate_Per_KM.required' => 'Rs/KM is required.',
            'Dist_Opening.required' => 'At least one KM Detail row is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            // Fetch employee details from master_employee
            $employeeId = (int) $request->input('Employee', 0);
            $employee = $employeeId ? DB::table('master_employee')->where('id', $employeeId)->first() : null;
            $employeeName = $employee->emp_name ?? '';
            $employeeCode = $request->input('Emp_Code', $employee->emp_code ?? '');

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('Bill_Date'),
                'EmployeeID' => $employeeId,
                'EmployeeCode' => $employeeCode,
                'Employee_Name' => $employeeName,
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'Vehicle_Type' => $request->input('Vehicle_Type', ''),
                'VehicleRs_PerKM' => $request->input('Rate_Per_KM', ''),
                'Loc_Name' => $request->input('Location', ''),
                'TotalRunKM' => $request->input('Total_KM', ''),
                'Total_Amount' => $request->input('Total_Amount', 0),
                'Total_Discount' => $request->input('Total_Discount', 0),
                'Round_Off_Type' => $request->input('Round_Off_Type', 'none'),
                'Grand_Total' => $request->input('Grand_Total', 0),
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

            // KM-based line items — store in vehicle_traveling table
            DB::table('vehicle_traveling')->where('Scan_Id', $scanId)->delete();
            $openings = $request->input('Dist_Opening', []);
            $closings = $request->input('Dist_Closing', []);
            $kms = $request->input('Km', []);
            $amounts = $request->input('Amount', []);

            if (is_array($openings)) {
                $items = [];
                foreach ($openings as $i => $opening) {
                    // Skip completely empty rows
                    $op = trim($opening ?? '');
                    $cl = trim($closings[$i] ?? '');
                    if ($op === '' && $cl === '') continue;

                    $km = (float)($kms[$i] ?? 0);
                    if ($km <= 0) continue;

                    $items[] = [
                        'Scan_Id' => $scanId,
                        'DistTraOpen' => $op,
                        'DistTraClose' => $cl,
                        'Totalkm' => $kms[$i] ?? '',
                        'FilledTAmt' => $amounts[$i] ?? '',
                    ];
                }
                if (!empty($items)) {
                    foreach (array_chunk($items, 100) as $chunk) {
                        DB::table('vehicle_traveling')->insert($chunk);
                    }
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
            'Agency_Name' => 'nullable|integer',
            'Billing_Name' => 'nullable|integer',
            'Employee' => 'nullable|integer',
            'Emp_Code' => 'nullable|string|max:20',
            'Vehicle_No' => 'nullable|string|max:50',
            'Invoice_No' => 'nullable|string|max:150',
            'Invoice_Date' => 'nullable|date',
            'Per_KM_Rate' => 'nullable|numeric',
            'Journey_Start' => 'nullable|date',
            'Journey_End' => 'nullable|date',
            'Opening_Reading' => 'nullable|numeric',
            'Closing_Reading' => 'nullable|numeric',
            'Total_KM' => 'nullable|numeric',
            'Other_Charge' => 'nullable|numeric',
            'Total_Amount' => 'nullable|numeric',
            'Location' => 'nullable|string|max:255',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Agency_Name'] = 'required|integer|min:1';
            $rules['Billing_Name'] = 'required|integer|min:1';
            $rules['Vehicle_No'] = 'required|string|max:50';
            $rules['Invoice_No'] = 'required|string|max:150';
            $rules['Invoice_Date'] = 'required|date';
            $rules['Per_KM_Rate'] = 'required|numeric|gt:0';
            $rules['Journey_Start'] = 'required|date';
            $rules['Journey_End'] = 'required|date';
            $rules['Opening_Reading'] = 'required|numeric';
            $rules['Closing_Reading'] = 'required|numeric';
            $rules['Location'] = 'required|string|max:255';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Agency_Name.required' => 'Agency Name is required.',
            'Billing_Name.required' => 'Billing Name is required.',
            'Vehicle_No.required' => 'Vehicle No is required.',
            'Invoice_No.required' => 'Invoice No is required.',
            'Invoice_Date.required' => 'Invoice Date is required.',
            'Per_KM_Rate.required' => 'Per KM Rate is required.',
            'Per_KM_Rate.gt' => 'Per KM Rate must be greater than 0.',
            'Journey_Start.required' => 'Booking Date is required.',
            'Journey_End.required' => 'End Date is required.',
            'Opening_Reading.required' => 'Start Reading is required.',
            'Closing_Reading.required' => 'Closing Reading is required.',
            'Location.required' => 'Location is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $agencyId = (int) $request->input('Agency_Name', 0);
            $agencyName = $agencyId ? DB::table('master_firm')->where('firm_id', $agencyId)->value('firm_name') : '';
            $billingId = (int) $request->input('Billing_Name', 0);
            $billingName = $billingId ? DB::table('master_firm')->where('firm_id', $billingId)->value('firm_name') : '';

            $employeeId = (int) $request->input('Employee', 0);
            $employee = $employeeId ? DB::table('master_employee')->where('id', $employeeId)->first() : null;
            $employeeName = $employee->emp_name ?? '';
            $employeeCode = $request->input('Emp_Code', $employee->emp_code ?? '');

            $totalAmount = (float) $request->input('Total_Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'From_ID' => $agencyId,
                'FromName' => $agencyName,
                'AgencyAddress' => $request->input('Agency_Address', ''),
                'To_ID' => $billingId,
                'ToName' => $billingName,
                'Related_Address' => $request->input('Billing_Address', ''),
                'EmployeeID' => $employeeId,
                'EmployeeCode' => $employeeCode,
                'Employee_Name' => $employeeName,
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'File_No' => $request->input('Invoice_No', ''),
                'File_Date' => $request->input('Invoice_Date'),
                'VehicleRs_PerKM' => $request->input('Per_KM_Rate', ''),
                'FromDateTime' => $request->input('Journey_Start') ? $request->input('Journey_Start') . ' 00:00:00' : null,
                'ToDateTime' => $request->input('Journey_End') ? $request->input('Journey_End') . ' 00:00:00' : null,
                'OpeningKm' => $request->input('Opening_Reading', ''),
                'ClosingKm' => $request->input('Closing_Reading', ''),
                'TotalRunKM' => $request->input('Total_KM', ''),
                'OthCharge_Amount' => $request->input('Other_Charge', ''),
                'Total_Amount' => $totalAmount,
                'Grand_Total' => $totalAmount,
                'Loc_Name' => $request->input('Location', ''),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
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
            'Travel_Mode' => 'nullable|string|max:100',
            'Employee' => 'nullable|integer',
            'Emp_Code' => 'nullable|string|max:20',
            'Vehicle_No' => 'nullable|string|max:50',
            'Month' => 'nullable|string|max:5',
            'cal_by' => 'nullable|string|max:20',
            'Rate_Per_KM' => 'nullable|numeric',
            'Total_KM' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Location' => 'nullable|string|max:255',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Travel_Mode'] = 'required|string|max:100';
            $rules['Employee'] = 'required|integer|min:1';
            $rules['Vehicle_No'] = 'required|string|max:50';
            $rules['Month'] = 'required|string|max:5';
            $rules['Rate_Per_KM'] = 'required|numeric|gt:0';
            $rules['Location'] = 'required|string|max:255';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Travel_Mode.required' => 'Mode is required.',
            'Employee.required' => 'Employee is required.',
            'Vehicle_No.required' => 'Vehicle No is required.',
            'Month.required' => 'Month is required.',
            'Rate_Per_KM.required' => 'Per KM Rate is required.',
            'Rate_Per_KM.gt' => 'Per KM Rate must be greater than 0.',
            'Location.required' => 'Location is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $employeeId = (int) $request->input('Employee', 0);
            $employee = $employeeId ? DB::table('master_employee')->where('id', $employeeId)->first() : null;
            $employeeName = $employee->emp_name ?? '';
            $employeeCode = $request->input('Emp_Code', $employee->emp_code ?? '');

            $month = $request->input('Month', '');
            $monthNames = ['1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'];
            $monthName = $monthNames[$month] ?? '';

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'TravelMode' => $request->input('Travel_Mode', ''),
                'EmployeeID' => $employeeId,
                'EmployeeCode' => $employeeCode,
                'Employee_Name' => $employeeName,
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'Month' => $month,
                'MonthName' => $monthName,
                'Cal_By' => $request->input('cal_by', ''),
                'VehicleRs_PerKM' => $request->input('Rate_Per_KM', ''),
                'HiredVehicle_Amount' => $request->input('cal_by') === 'Fixed' ? (float) $request->input('Rate_Per_KM', 0) : 0,
                'TotalRunKM' => $request->input('Total_KM', ''),
                'Total_Amount' => (float) $request->input('Total_Amount', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
                'Round_Off_Type' => $request->input('Round_Off_Type', 'none'),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Loc_Name' => $request->input('Location', ''),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $totalAmount = (float) $request->input('Grand_Total', 0);
            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
            }

            // Trip details — store in vehicle_traveling table
            DB::table('vehicle_traveling')->where('Scan_Id', $scanId)->delete();
            $dates = $request->input('Date', []);
            $openings = $request->input('Dist_Opening', []);
            $closings = $request->input('Dist_Closing', []);
            $kms = $request->input('Km', []);
            $amounts = $request->input('Amount', []);

            if (is_array($dates)) {
                $items = [];
                foreach ($dates as $i => $date) {
                    $op = trim($openings[$i] ?? '');
                    $cl = trim($closings[$i] ?? '');
                    if ($op === '' && $cl === '' && empty(trim($date ?? ''))) continue;

                    $items[] = [
                        'Scan_Id' => $scanId,
                        'JourneyStartDt' => !empty($date) ? $date . ' 00:00:00' : null,
                        'DistTraOpen' => $op,
                        'DistTraClose' => $cl,
                        'Totalkm' => $kms[$i] ?? '',
                        'FilledTAmt' => $amounts[$i] ?? '',
                    ];
                }
                if (!empty($items)) {
                    foreach (array_chunk($items, 100) as $chunk) {
                        DB::table('vehicle_traveling')->insert($chunk);
                    }
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
            'From' => 'nullable|integer',
            'To' => 'nullable|integer',
            'Bill_No' => 'nullable|string|max:150',
            'Bill_Date' => 'nullable|date',
            'Due_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Dealer_Code' => 'nullable|string|max:100',
            'Description' => 'nullable|string|max:500',
            'Liters' => 'nullable|numeric',
            'Rate' => 'nullable|numeric',
            'Total_Discount' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Location' => 'nullable|string|max:255',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['From'] = 'required|integer|min:1';
            $rules['To'] = 'required|integer|min:1';
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Vehicle_No'] = 'required|string|max:50';
            $rules['Liters'] = 'required|numeric|gt:0';
            $rules['Grand_Total'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'From.required' => 'Vendor Name is required.',
            'To.required' => 'Billing To is required.',
            'Bill_No.required' => 'Invoice No is required.',
            'Bill_Date.required' => 'Invoice Date is required.',
            'Vehicle_No.required' => 'Vehicle No is required.',
            'Liters.required' => 'Liter is required.',
            'Liters.gt' => 'Liter must be greater than 0.',
            'Grand_Total.required' => 'Grand Total is required.',
            'Grand_Total.gt' => 'Grand Total must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $vendorId = (int) $request->input('From', 0);
            $vendorName = $vendorId ? DB::table('master_firm')->where('firm_id', $vendorId)->value('firm_name') : '';
            $billingId = (int) $request->input('To', 0);
            $billingName = $billingId ? DB::table('master_firm')->where('firm_id', $billingId)->value('firm_name') : '';
            $grandTotal = (float) $request->input('Grand_Total', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'FileName' => $request->input('Description', ''),
                'From_ID' => $vendorId,
                'FromName' => $vendorName,
                'To_ID' => $billingId,
                'ToName' => $billingName,
                'CompanyID' => $billingId,
                'Company' => $billingName,
                'BSRCode' => $request->input('Dealer_Code', ''),
                'File_No' => $request->input('Bill_No', ''),
                'BillDate' => $request->input('Bill_Date'),
                'DueDate' => $request->input('Due_Date'),
                'Loc_Name' => $request->input('Location', ''),
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'MeterNumber' => $request->input('Liters', ''),
                'TariffPlan' => $request->input('Rate', ''),
                'Total_Amount' => $request->input('Amount', '') !== '' ? (float) $request->input('Amount') : 0,
                'Grand_Total' => $grandTotal,
                'Total_Discount' => $request->input('Total_Discount', '') !== '' ? (float) $request->input('Total_Discount') : 0,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $grandTotal, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $grandTotal, 'Comment' => $data['Remark']]);
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
            'Voucher_No' => 'nullable|string|max:150',
            'Payment_Date' => 'nullable|date',
            'Payee' => 'nullable|string|max:255',
            'Location' => 'nullable|string|max:255',
            'Particular_Text' => 'nullable|string|max:500',
            'Total_Amount' => 'nullable|numeric',
            'From_Date' => 'nullable|date',
            'To_Date' => 'nullable|date',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Voucher_No'] = 'required|string|max:150';
            $rules['Payment_Date'] = 'required|date';
            $rules['Payee'] = 'required|string|max:255';
            $rules['Location'] = 'required|string|max:255';
            $rules['Total_Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
            $rules['Head'] = 'required|array|min:1';
        }
        $request->validate($rules, [
            'Voucher_No.required' => 'Voucher No is required.',
            'Payment_Date.required' => 'Payment Date is required.',
            'Payee.required' => 'Payee is required.',
            'Location.required' => 'Location is required.',
            'Total_Amount.required' => 'Total Amount is required.',
            'Total_Amount.gt' => 'Total Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
            'Head.required' => 'At least one Payment Head is required.',
            'Head.min' => 'At least one Payment Head is required.',
        ]);

        // Custom validation: if head filled, amount required and vice versa
        if ($isFinal) {
            $heads = $request->input('Head', []);
            $amounts = $request->input('Amount', []);
            $hasValidRow = false;
            foreach ($heads as $i => $head) {
                $h = trim($head ?? '');
                $a = trim($amounts[$i] ?? '');
                if ($h && $a) $hasValidRow = true;
                if ($h && !$a) {
                    return response()->json(['success' => false, 'message' => 'Amount is required for Head: ' . $h], 422);
                }
                if (!$h && $a) {
                    return response()->json(['success' => false, 'message' => 'Head is required when Amount is filled (row ' . ($i + 1) . ')'], 422);
                }
            }
            if (!$hasValidRow) {
                return response()->json(['success' => false, 'message' => 'At least one Payment Head with Amount is required.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $totalAmount = (float) $request->input('Total_Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_No' => $request->input('Voucher_No', ''),
                'BillDate' => $request->input('Payment_Date'),
                'Related_Person' => $request->input('Payee', ''),
                'Loc_Name' => $request->input('Location', ''),
                'FileName' => $request->input('Particular_Text', ''),
                'Total_Amount' => $totalAmount,
                'Grand_Total' => $totalAmount,
                'FromDateTime' => $request->input('From_Date') ? $request->input('From_Date') . ' 00:00:00' : null,
                'ToDateTime' => $request->input('To_Date') ? $request->input('To_Date') . ' 00:00:00' : null,
                'SubTotal' => $request->input('Sub_Total', '') !== '' ? (float) $request->input('Sub_Total') : 0,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
            }

            // Line items (labour payment details)
            DB::table('labour_payment_detail')->where('Scan_Id', $scanId)->delete();
            $heads = $request->input('Head', []);
            $amounts = $request->input('Amount', []);
            if (is_array($heads) && !empty($heads)) {
                $details = [];
                foreach ($heads as $i => $head) {
                    if (empty(trim((string) ($head ?? '')))) continue;
                    $details[] = [
                        'Scan_Id' => $scanId,
                        'Head' => (string) $head,
                        'Amount' => ($amounts[$i] ?? '') !== '' ? (float) $amounts[$i] : 0,
                    ];
                }
                if (!empty($details)) {
                    foreach (array_chunk($details, 100) as $chunk) {
                        DB::table('labour_payment_detail')->insert($chunk);
                    }
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
            'CompanyID' => 'nullable|integer',
            'To_ID' => 'nullable|integer',
            'VehicleRegNo' => 'nullable|string|max:50',
            'Vehicle_Type' => 'nullable|string|max:50',
            'location_id' => 'nullable|string|max:255',
            'Invoice_Date' => 'nullable|date',
            'Particular' => 'nullable|string|max:500',
            'Hour' => 'nullable|string|max:50',
            'Trip' => 'nullable|numeric',
            'Rate' => 'nullable|numeric',
            'Total_Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['CompanyID'] = 'required|integer|min:1';
            $rules['To_ID'] = 'required|integer|min:1';
            $rules['VehicleRegNo'] = 'required|string|max:50';
            $rules['Vehicle_Type'] = 'required|string|max:50';
            $rules['location_id'] = 'required|string|max:255';
            $rules['Invoice_Date'] = 'required|date';
            $rules['Trip'] = 'required|numeric|gt:0';
            $rules['Rate'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'CompanyID.required' => 'Company is required.',
            'To_ID.required' => 'Vendor is required.',
            'VehicleRegNo.required' => 'Vehicle No is required.',
            'Vehicle_Type.required' => 'Vehicle Type is required.',
            'location_id.required' => 'Location is required.',
            'Invoice_Date.required' => 'Invoice Date is required.',
            'Trip.required' => 'Trips is required.',
            'Trip.gt' => 'Trips must be greater than 0.',
            'Rate.required' => 'Rate per Trip is required.',
            'Rate.gt' => 'Rate must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $companyId = (int) $request->input('CompanyID', 0);
            $companyName = $companyId ? DB::table('master_firm')->where('firm_id', $companyId)->value('firm_name') : '';
            $vendorId = (int) $request->input('To_ID', 0);
            $vendorName = $vendorId ? DB::table('master_firm')->where('firm_id', $vendorId)->value('firm_name') : '';
            $totalAmount = (float) $request->input('Total_Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'Company' => $companyName,
                'CompanyID' => $companyId,
                'Related_Address' => $request->input('Related_Address', '') ?? '',
                'To_ID' => $vendorId,
                'ToName' => $vendorName,
                'AgencyAddress' => $request->input('AgencyAddress', '') ?? '',
                'VehicleRegNo' => $request->input('VehicleRegNo', ''),
                'Vehicle_Type' => $request->input('Vehicle_Type', ''),
                'Loc_Name' => $request->input('location_id', ''),
                'BillDate' => $request->input('Invoice_Date'),
                'Particular' => $request->input('Particular', ''),
                'Period' => $request->input('Hour', ''),
                'TotalRunKM' => $request->input('Trip', ''),
                'RateOfInterest' => $request->input('Rate', ''),
                'Total_Amount' => $totalAmount,
                'Grand_Total' => $totalAmount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $totalAmount, 'Comment' => $data['Remark']]);
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
            'Section' => 'nullable|string|max:100',
            'Company' => 'nullable|integer',
            'Payment_Nature' => 'nullable|string|max:255',
            'Assessment_Year' => 'nullable|string|max:20',
            'Bank_Name' => 'nullable|string|max:255',
            'BSR_Code' => 'nullable|string|max:50',
            'Challan_No' => 'nullable|string|max:150',
            'Challan_Date' => 'nullable|date',
            'Ref_No' => 'nullable|string|max:100',
            'Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Section'] = 'required|string|max:100';
            $rules['Company'] = 'required|integer|min:1';
            $rules['Payment_Nature'] = 'required|string|max:255';
            $rules['Assessment_Year'] = 'required|string|max:20';
            $rules['Bank_Name'] = 'required|string|max:255';
            $rules['BSR_Code'] = 'required|string|max:50';
            $rules['Challan_No'] = 'required|string|max:150';
            $rules['Challan_Date'] = 'required|date';
            $rules['Ref_No'] = 'required|string|max:100';
            $rules['Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Section.required' => 'Section is required.',
            'Company.required' => 'Company is required.',
            'Payment_Nature.required' => 'Nature of Payment is required.',
            'Assessment_Year.required' => 'Assessment Year is required.',
            'Bank_Name.required' => 'Bank Name is required.',
            'BSR_Code.required' => 'BSR Code is required.',
            'Challan_No.required' => 'Challan No is required.',
            'Challan_Date.required' => 'Challan Date is required.',
            'Ref_No.required' => 'Bank Reference No is required.',
            'Amount.required' => 'Amount is required.',
            'Amount.gt' => 'Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $companyId = (int) $request->input('Company', 0);
            $companyName = $companyId ? DB::table('master_firm')->where('firm_id', $companyId)->value('firm_name') : '';
            $amount = (float) $request->input('Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'Company' => $companyName,
                'CompanyID' => $companyId,
                'Financial_Year' => $request->input('Assessment_Year', ''),
                'Section' => $request->input('Section', ''),
                'BSRCode' => $request->input('BSR_Code', ''),
                'NatureOfPayment' => $request->input('Payment_Nature', ''),
                'File_No' => $request->input('Challan_No', ''),
                'File_Date' => $request->input('Challan_Date'),
                'ReferenceNo' => $request->input('Ref_No', ''),
                'BankName' => $request->input('Bank_Name', ''),
                'Total_Amount' => $amount,
                'Grand_Total' => $amount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
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
            'Insurance_Type' => 'nullable|string|max:100',
            'Insurance_Company' => 'nullable|string|max:255',
            'Policy_Number' => 'nullable|string|max:100',
            'Policy_Date' => 'nullable|date',
            'From_Date' => 'nullable|date',
            'To_Date' => 'nullable|date',
            'Vehicle_No' => 'nullable|string|max:50',
            'Location' => 'nullable|string|max:255',
            'Premium_Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Insurance_Type'] = 'required|string|max:100';
            $rules['Insurance_Company'] = 'required|string|max:255';
            $rules['Policy_Number'] = 'required|string|max:100';
            $rules['Policy_Date'] = 'required|date';
            $rules['From_Date'] = 'required|date';
            $rules['To_Date'] = 'required|date';
            $rules['Location'] = 'required|string|max:255';
            $rules['Premium_Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Insurance_Type.required' => 'Insurance Type is required.',
            'Insurance_Company.required' => 'Insurance Company is required.',
            'Policy_Number.required' => 'Policy Number is required.',
            'Policy_Date.required' => 'Policy Date is required.',
            'From_Date.required' => 'From Date is required.',
            'To_Date.required' => 'To Date is required.',
            'Location.required' => 'Location is required.',
            'Premium_Amount.required' => 'Premium Amount is required.',
            'Premium_Amount.gt' => 'Premium Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $amount = (float) $request->input('Premium_Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_Type' => $request->input('Insurance_Type', ''),
                'AgentName' => $request->input('Insurance_Company', ''),
                'File_No' => $request->input('Policy_Number', ''),
                'File_Date' => $request->input('Policy_Date'),
                'FromDateTime' => $request->input('From_Date') ? $request->input('From_Date') . ' 00:00:00' : null,
                'ToDateTime' => $request->input('To_Date') ? $request->input('To_Date') . ' 00:00:00' : null,
                'VehicleRegNo' => $request->input('Vehicle_No', ''),
                'Loc_Name' => $request->input('Location', ''),
                'Total_Amount' => $amount,
                'Grand_Total' => $amount,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
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
            'Billing_Name' => 'nullable|integer',
            'Hotel' => 'nullable|integer',
            'Arrival_Date' => 'nullable|date',
            'Departure_Date' => 'nullable|date',
            'No_Room' => 'nullable|string|max:20',
            'Room_Rate' => 'nullable|numeric',
            'Other_Charge' => 'nullable|numeric',
            'Discount' => 'nullable|numeric',
            'Gst' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Location' => 'nullable|string|max:255',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Bill_No'] = 'required|string|max:150';
            $rules['Bill_Date'] = 'required|date';
            $rules['Billing_Name'] = 'required|integer|min:1';
            $rules['Hotel'] = 'required|integer|min:1';
            $rules['Arrival_Date'] = 'required|date';
            $rules['Departure_Date'] = 'required|date';
            $rules['No_Room'] = 'required|string|max:20';
            $rules['Room_Rate'] = 'required|numeric|gt:0';
            $rules['Location'] = 'required|string|max:255';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Bill_No.required' => 'Bill No is required.',
            'Bill_Date.required' => 'Bill Date is required.',
            'Billing_Name.required' => 'Billing Name is required.',
            'Hotel.required' => 'Hotel Name is required.',
            'Arrival_Date.required' => 'Arrival Date is required.',
            'Departure_Date.required' => 'Departure Date is required.',
            'No_Room.required' => 'No. of Rooms is required.',
            'Room_Rate.required' => 'Room Rate is required.',
            'Room_Rate.gt' => 'Room Rate must be greater than 0.',
            'Location.required' => 'Location is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $billingId = (int) $request->input('Billing_Name', 0);
            $billingName = $billingId ? DB::table('master_firm')->where('firm_id', $billingId)->value('firm_name') : '';
            $grandTotal = (float) $request->input('Grand_Total', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_No' => $request->input('Bill_No', ''),
                'BillDate' => $request->input('Bill_Date'),
                'CompanyID' => $billingId,
                'Company' => $billingName,
                'Related_Address' => $request->input('Billing_Address', ''),
                'Hotel' => $request->input('Hotel') ? (int) $request->input('Hotel') : null,
                'Hotel_Name' => $request->input('Hotel') && is_numeric($request->input('Hotel')) ? (DB::table('master_hotel')->where('hotel_id', $request->input('Hotel'))->value('hotel_name') ?? '') : ($request->input('Hotel', '') ?: ''),
                'Hotel_Address' => $request->input('Hotel_Address', ''),
                'Particular' => $request->input('Billing_Instruction', ''),
                'RegNo' => $request->input('Booking_Id', ''),
                'FromDateTime' => $request->input('Arrival_Date') ? $request->input('Arrival_Date') . ' 00:00:00' : null,
                'ToDateTime' => $request->input('Departure_Date') ? $request->input('Departure_Date') . ' 00:00:00' : null,
                'Period' => $request->input('Duration', ''),
                'ReferenceNo' => $request->input('No_Room', ''),
                'TravelClass' => $request->input('Room_Type', ''),
                'TariffPlan' => $request->input('Room_Rate', ''),
                'Loc_Name' => $request->input('Location', ''),
                'SubTotal' => $request->input('Amount', 0) !== '' ? (float) $request->input('Amount', 0) : 0,
                'OthCharge_Amount' => $request->input('Other_Charge', 0) !== '' ? (float) $request->input('Other_Charge', 0) : 0,
                'Total_Discount' => $request->input('Discount', 0) !== '' ? (float) $request->input('Discount', 0) : 0,
                'GSTIN' => (string) ($request->input('Gst') ?? ''),
                'Round_Off_Type' => $request->input('Round_Off_Type', 'none'),
                'Grand_Total' => $grandTotal,
                'Total_Amount' => $grandTotal,
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $grandTotal, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $grandTotal, 'Comment' => $data['Remark']]);
            }

            // Employee list — store in lodging_employee table
            DB::table('lodging_employee')->where('Scan_Id', $scanId)->delete();
            $employees = $request->input('Employee', []);
            $empCodes = $request->input('EmpCode', []);
            if (is_array($employees)) {
                $items = [];
                foreach ($employees as $i => $empId) {
                    if (empty($empId)) continue;
                    $empName = DB::table('master_employee')->where('id', $empId)->value('emp_name') ?? '';
                    $items[] = [
                        'Scan_Id' => $scanId,
                        'emp_id' => (int) $empId,
                        'emp_code' => $empCodes[$i] ?? '',
                        'emp_name' => $empName,
                    ];
                }
                if (!empty($items)) {
                    DB::table('lodging_employee')->insert($items);
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
            'Employee' => 'nullable|integer',
            'Date' => 'nullable|date',
            'InvoiceNo' => 'nullable|string|max:150',
            'Hotel' => 'nullable|string|max:255',
            'Detail' => 'nullable|string|max:500',
            'Amount' => 'nullable|numeric',
            'Location' => 'nullable|string|max:255',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['Employee'] = 'required|integer|min:1';
            $rules['Date'] = 'required|date';
            $rules['Hotel'] = 'required|string|max:255';
            $rules['Amount'] = 'required|numeric|gt:0';
            $rules['Location'] = 'required|string|max:255';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Employee.required' => 'Employee is required.',
            'Date.required' => 'Bill Date is required.',
            'Hotel.required' => 'Hotel / Restaurant is required.',
            'Amount.required' => 'Amount is required.',
            'Amount.gt' => 'Amount must be greater than 0.',
            'Location.required' => 'Location is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $employeeId = (int) $request->input('Employee', 0);
            $employee = $employeeId ? DB::table('master_employee')->where('id', $employeeId)->first() : null;
            $employeeName = $employee->emp_name ?? '';
            $employeeCode = $request->input('Emp_Code', $employee->emp_code ?? '');

            // Hotel name - could be ID from select2 or text
            $hotelInput = $request->input('Hotel', '');
            $hotelName = $hotelInput;
            if (is_numeric($hotelInput)) {
                $hotelName = DB::table('master_hotel')->where('hotel_id', $hotelInput)->value('hotel_name') ?? $hotelInput;
            }

            $amount = (float) $request->input('Amount', 0);

            $data = [
                'Scan_Id' => $scanId, 'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '', 'DocTypeId' => $scanRecord->DocType_Id,
                'File_No' => $request->input('InvoiceNo', ''),
                'FileName' => $request->input('Detail', ''),
                'EmployeeID' => $employeeId,
                'EmployeeCode' => $employeeCode,
                'Employee_Name' => $employeeName,
                'BillDate' => $request->input('Date'),
                'Hotel_Name' => $hotelName,
                'Hotel_Address' => $request->input('Hotel_Address', '') ?? '',
                'Total_Amount' => $amount,
                'Grand_Total' => $amount,
                'Loc_Name' => $request->input('Location', ''),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(), 'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                DB::table('sub_punchfile')->where('FileID', $existing->FileID)->update(['Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert(['FileID' => $fileID, 'Amount' => '-' . $amount, 'Comment' => $data['Remark']]);
            }

            // Employee list — store in lodging_employee table
            DB::table('lodging_employee')->where('Scan_Id', $scanId)->delete();
            $employees = $request->input('Employee', []);
            $empCodes = $request->input('EmpCode', []);
            if (is_array($employees)) {
                $items = [];
                foreach ($employees as $i => $empId) {
                    if (empty($empId)) continue;
                    $empName = DB::table('master_employee')->where('id', $empId)->value('emp_name') ?? '';
                    $items[] = [
                        'Scan_Id' => $scanId,
                        'emp_id' => (int) $empId,
                        'emp_code' => $empCodes[$i] ?? '',
                        'emp_name' => $empName,
                    ];
                }
                if (!empty($items)) {
                    DB::table('lodging_employee')->insert($items);
                }
            }

            // Store first employee in punchfile for backward compatibility
            if (!empty($employees[0])) {
                $firstEmp = DB::table('master_employee')->where('id', $employees[0])->first();
                DB::table('punchfile')->where('Scan_Id', $scanId)->update([
                    'EmployeeID' => (int) $employees[0],
                    'EmployeeCode' => $empCodes[0] ?? ($firstEmp->emp_code ?? ''),
                    'Employee_Name' => $firstEmp->emp_name ?? '',
                ]);
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
            'Agent_Name' => 'nullable|string|max:255',
            'PNR_Number' => 'nullable|string|max:150',
            'Booking_Date' => 'nullable|date',
            'Journey_Date' => 'nullable|date',
            'Airline' => 'nullable|string|max:255',
            'Ticket_Number' => 'nullable|string|max:150',
            'Journey_From' => 'nullable|string|max:150',
            'Journey_To' => 'nullable|string|max:150',
            'Travel_Class' => 'nullable|string|max:50',
            'Passenger_Details' => 'nullable|string|max:1000',
            'Base_Fare' => 'nullable|numeric',
            'GST' => 'nullable|numeric',
            'Surcharge' => 'nullable|numeric',
            'Cute_Charge' => 'nullable|numeric',
            'Extra_Luggage' => 'nullable|numeric',
            'Other' => 'nullable|numeric',
            'Total_Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
            'location_id' => 'nullable|string|max:255',
        ];
        if ($isFinal) {
            $rules['Agent_Name'] = 'required|string|max:255';
            $rules['PNR_Number'] = 'required|string|max:150';
            $rules['Booking_Date'] = 'required|date';
            $rules['Journey_Date'] = 'required|date';
            $rules['Airline'] = 'required|string|max:255';
            $rules['Ticket_Number'] = 'required|string|max:150';
            $rules['Journey_From'] = 'required|string|max:150';
            $rules['Journey_To'] = 'required|string|max:150';
            $rules['Travel_Class'] = 'required|string|max:50';
            $rules['location_id'] = 'required|string|max:255';
            $rules['Total_Amount'] = 'required|numeric|min:0';
            $rules['Employee'] = 'required|array|min:1';
            $rules['Employee.*'] = 'required|integer|min:1';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Agent_Name.required' => 'Agent Name is required.',
            'PNR_Number.required' => 'PNR Number is required.',
            'Booking_Date.required' => 'Booking Date is required.',
            'Journey_Date.required' => 'Journey Date is required.',
            'Airline.required' => 'Airline is required.',
            'Ticket_Number.required' => 'Ticket Number is required.',
            'Journey_From.required' => 'Journey From is required.',
            'Journey_To.required' => 'Journey To is required.',
            'Travel_Class.required' => 'Travel Class is required.',
            'location_id.required' => 'Location is required.',
            'Total_Amount.required' => 'Total Amount is required.',
            'Employee.required' => 'At least one Employee is required.',
            'Employee.min' => 'At least one Employee is required.',
            'Employee.*.required' => 'Employee is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId,
                'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '',
                'DocTypeId' => $scanRecord->DocType_Id,
                'TravelMode' => 'Air',
                'AgentName' => $request->input('Agent_Name', ''),
                'ServiceNo' => $request->input('PNR_Number', ''),
                'BookingDate' => $request->input('Booking_Date'),
                'FromDateTime' => $request->input('Journey_Date'),
                'Airline' => $request->input('Airline', ''),
                'File_No' => $request->input('Ticket_Number', ''),
                'TripStarted' => $request->input('Journey_From', ''),
                'TripEnded' => $request->input('Journey_To', ''),
                'TravelClass' => $request->input('Travel_Class', ''),
                'PassengerDetail' => $request->input('Passenger_Details', ''),
                'Base_Fare' => (float) $request->input('Base_Fare', 0),
                'GSTIN' => (float) $request->input('GST', 0),
                'Surcharge' => (float) $request->input('Surcharge', 0),
                'Cute_Charge' => (float) $request->input('Cute_Charge', 0),
                'Extra_Luggage' => (float) $request->input('Extra_Luggage', 0),
                'OthCharge_Amount' => (float) $request->input('Other', 0),
                'Total_Amount' => (float) $request->input('Total_Amount', 0),
                'Grand_Total' => (float) $request->input('Total_Amount', 0),
                'Remark' => $request->input('Remark', ''),
                'Loc_Name' => $request->input('location_id', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update([
                    'Amount' => '-' . $data['Total_Amount'],
                    'Comment' => $data['Remark']
                ]);
                // Clear existing employees
                DB::table('lodging_employee')->where('scan_id', $scanId)->delete();
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N'
                ]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert([
                    'FileID' => $fileID,
                    'Amount' => '-' . $data['Total_Amount'],
                    'Comment' => $data['Remark']
                ]);
            }

            // Save employee associations
            $employees = $request->input('Employee', []);
            $empCodes = $request->input('EmpCode', []);
            if (is_array($employees) && !empty($employees)) {
                $empData = [];
                foreach ($employees as $i => $empId) {
                    if (empty($empId)) continue;
                    $empName = DB::table('master_employee')->where('id', $empId)->value('emp_name') ?? '';
                    $empData[] = [
                        'scan_id' => $scanId,
                        'emp_id' => $empId,
                        'emp_code' => $empCodes[$i] ?? '',
                        'emp_name' => $empName,
                    ];
                }
                if (!empty($empData)) {
                    DB::table('lodging_employee')->insert($empData);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y',
                    'Punch_By' => Auth::id(),
                    'Punch_Date' => now(),
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $isFinal ? 'Air Fare submitted successfully.' : 'Draft saved.',
                'redirect' => $isFinal ? route('workflow.punching.index') : null
            ]);
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
            'Train_Number' => 'nullable|string|max:150',
            'Agent_Name' => 'nullable|string|max:255',
            'PNR_Number' => 'nullable|string|max:150',
            'Booking_Date' => 'nullable|date',
            'Journey_Date' => 'nullable|date',
            'Booking_Id' => 'nullable|string|max:150',
            'Transaction_Id' => 'nullable|string|max:150',
            'Journey_From' => 'nullable|string|max:150',
            'Journey_To' => 'nullable|string|max:150',
            'Travel_Class' => 'nullable|string|max:50',
            'Travel_Quota' => 'nullable|string|max:50',
            'Passenger_Details' => 'nullable|string|max:1000',
            'Base_Fare' => 'nullable|numeric',
            'GST' => 'nullable|numeric',
            'Surcharge' => 'nullable|numeric',
            'Other' => 'nullable|numeric',
            'Total_Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
            'location_id' => 'nullable|string|max:255',
        ];
        if ($isFinal) {
            $rules['Agent_Name'] = 'required|string|max:255';
            $rules['Train_Number'] = 'required|string|max:150';
            $rules['PNR_Number'] = 'required|string|max:150';
            $rules['Booking_Date'] = 'required|date';
            $rules['Journey_Date'] = 'required|date';
            $rules['Booking_Id'] = 'required|string|max:150';
            $rules['Journey_From'] = 'required|string|max:150';
            $rules['Journey_To'] = 'required|string|max:150';
            $rules['Travel_Class'] = 'required|string|max:50';
            $rules['location_id'] = 'required|string|max:255';
            $rules['Total_Amount'] = 'required|numeric|min:0';
            $rules['Employee'] = 'required|array|min:1';
            $rules['Employee.*'] = 'required|integer|min:1';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'Agent_Name.required' => 'Agent Name is required.',
            'Train_Number.required' => 'Train Number is required.',
            'PNR_Number.required' => 'PNR Number is required.',
            'Booking_Date.required' => 'Booking Date is required.',
            'Journey_Date.required' => 'Journey Date is required.',
            'Booking_Id.required' => 'Booking ID is required.',
            'Journey_From.required' => 'Journey From is required.',
            'Journey_To.required' => 'Journey To is required.',
            'Travel_Class.required' => 'Travel Class is required.',
            'location_id.required' => 'Location is required.',
            'Total_Amount.required' => 'Total Amount is required.',
            'Employee.required' => 'At least one Employee is required.',
            'Employee.min' => 'At least one Employee is required.',
            'Employee.*.required' => 'Employee is required.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId,
                'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '',
                'DocTypeId' => $scanRecord->DocType_Id,
                'TravelMode' => 'Rail',
                'File_No' => $request->input('Train_Number', ''),
                'AgentName' => $request->input('Agent_Name', ''),
                'ServiceNo' => $request->input('PNR_Number', ''),
                'BookingDate' => $request->input('Booking_Date'),
                'FromDateTime' => $request->input('Journey_Date'),
                'FDRNo' => $request->input('Booking_Id', ''),
                'RegNo' => $request->input('Transaction_Id', ''),
                'TripStarted' => $request->input('Journey_From', ''),
                'TripEnded' => $request->input('Journey_To', ''),
                'TravelClass' => $request->input('Travel_Class', ''),
                'TravelQuota' => $request->input('Travel_Quota', ''),
                'PassengerDetail' => $request->input('Passenger_Details', ''),
                'Base_Fare' => (float) $request->input('Base_Fare', 0),
                'GSTIN' => (float) $request->input('GST', 0),
                'Surcharge' => (float) $request->input('Surcharge', 0),
                'OthCharge_Amount' => (float) $request->input('Other', 0),
                'Total_Amount' => (float) $request->input('Total_Amount', 0),
                'Grand_Total' => (float) $request->input('Total_Amount', 0),
                'Remark' => $request->input('Remark', ''),
                'Loc_Name' => $request->input('location_id', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update([
                    'Amount' => '-' . $data['Total_Amount'],
                    'Comment' => $data['Remark']
                ]);
                // Clear existing employees
                DB::table('lodging_employee')->where('scan_id', $scanId)->delete();
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N'
                ]);
            } else {
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert([
                    'FileID' => $fileID,
                    'Amount' => '-' . $data['Total_Amount'],
                    'Comment' => $data['Remark']
                ]);
            }

            // Save employee associations
            $employees = $request->input('Employee', []);
            $empCodes = $request->input('EmpCode', []);
            if (is_array($employees) && !empty($employees)) {
                $empData = [];
                foreach ($employees as $i => $empId) {
                    if (empty($empId)) continue;
                    $empName = DB::table('master_employee')->where('id', $empId)->value('emp_name') ?? '';
                    $empData[] = [
                        'scan_id' => $scanId,
                        'emp_id' => $empId,
                        'emp_code' => $empCodes[$i] ?? '',
                        'emp_name' => $empName,
                    ];
                }
                if (!empty($empData)) {
                    DB::table('lodging_employee')->insert($empData);
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y',
                    'Punch_By' => Auth::id(),
                    'Punch_Date' => now(),
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $isFinal ? 'Rail Fare submitted successfully.' : 'Draft saved.',
                'redirect' => $isFinal ? route('workflow.punching.index') : null
            ]);
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
            'SubTotal' => 'nullable|numeric',
            'OthCharge_Amount' => 'nullable|numeric',
            'Total_Discount' => 'nullable|numeric',
            'Grand_Total' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
            'Employee' => 'nullable|array',
            'Employee.*' => 'nullable|integer',
            'PNR' => 'nullable|array',
            'PNR.*' => 'nullable|string|max:100',
            'Amount' => 'nullable|array',
            'Amount.*' => 'nullable|numeric',
        ];
        if ($isFinal) {
            $rules['BillDate'] = 'required|date';
            $rules['AgentName'] = 'required|string|max:255';
            $rules['File_Date'] = 'required|date';
            $rules['SubTotal'] = 'required|numeric|min:0';
            $rules['Grand_Total'] = 'required|numeric|min:0';
            $rules['Remark'] = 'required|string|max:5000';
            $rules['Employee'] = 'required|array|min:1';
            $rules['Employee.*'] = 'required|integer';
            $rules['PNR'] = 'required|array|min:1';
            $rules['PNR.*'] = 'required|string|max:100';
            $rules['Amount'] = 'required|array|min:1';
            $rules['Amount.*'] = 'required|numeric|min:0';
        }
        $request->validate($rules, [
            'AgentName.required' => 'Agent Name is required.',
            'BillDate.required' => 'Date is required.',
            'File_Date.required' => 'Cancelled Date is required.',
            'SubTotal.required' => 'Sub Total is required.',
            'Grand_Total.required' => 'Grand Total is required.',
            'Remark.required' => 'Remark is required.',
            'Employee.required' => 'At least one employee is required.',
            'Employee.*.required' => 'Employee is required in all rows.',
            'PNR.required' => 'PNR Number is required in all rows.',
            'PNR.*.required' => 'PNR Number is required in all rows.',
            'Amount.required' => 'Amount is required in all rows.',
            'Amount.*.required' => 'Amount is required in all rows.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');

            $data = [
                'Scan_Id' => $scanId,
                'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '',
                'DocTypeId' => $scanRecord->DocType_Id,
                'BillDate' => $request->input('BillDate'),
                'AgentName' => $request->input('AgentName', ''),
                'BookingDate' => $request->input('BookingDate'),
                'File_Date' => $request->input('File_Date'),
                'SubTotal' => (float) $request->input('SubTotal', 0),
                'OthCharge_Amount' => (float) $request->input('OthCharge_Amount', 0),
                'Total_Discount' => (float) $request->input('Total_Discount', 0),
                'Grand_Total' => (float) $request->input('Grand_Total', 0),
                'Total_Amount' => (float) $request->input('Grand_Total', 0),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
            ];

            $existing = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                // Update existing record
                DB::table('punchfile')->where('Scan_Id', $scanId)->update($data);
                $fileID = $existing->FileID;
                DB::table('sub_punchfile')->where('FileID', $fileID)->update([
                    'Amount' => '-' . $data['Grand_Total'],
                    'Comment' => $data['Remark']
                ]);
                // Delete existing ticket cancellation details
                DB::table('ticket_cancellation')->where('Scan_Id', $scanId)->delete();
                // Update scan_file to clear rejection status
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N'
                ]);
            } else {
                // Insert new record
                $fileID = DB::table('punchfile')->insertGetId($data);
                DB::table('sub_punchfile')->insert([
                    'FileID' => $fileID,
                    'Amount' => '-' . $data['Grand_Total'],
                    'Comment' => $data['Remark']
                ]);
            }

            // Insert ticket cancellation detail items (employee-wise)
            $employees = $request->input('Employee', []);
            if (is_array($employees) && !empty($employees)) {
                $details = [];
                foreach ($employees as $i => $empId) {
                    if (empty($empId)) continue;
                    
                    // Get employee name from database
                    $empName = DB::table('master_employee')->where('id', $empId)->value('emp_name') ?? '';
                    
                    $details[] = [
                        'Scan_Id' => $scanId,
                        'Emp_Id' => $empId,
                        'PNR' => (string) ($request->input('PNR')[$i] ?? ''),
                        'Amount' => (float) ($request->input('Amount')[$i] ?? 0),
                        'Emp_Name' => $empName,
                    ];
                }
                
                // Insert in batches of 100
                if (!empty($details)) {
                    foreach (array_chunk($details, 100) as $chunk) {
                        DB::table('ticket_cancellation')->insert($chunk);
                    }
                }
            }

            if ($isFinal) {
                DB::table('scan_file')->where('Scan_Id', $scanId)->update([
                    'File_Punched' => 'Y',
                    'Punch_By' => Auth::id(),
                    'Punch_Date' => now(),
                    'Is_Rejected' => 'N',
                    'Reject_Date' => null,
                    'Edit_Permission' => 'N',
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $isFinal ? 'Ticket Cancellation submitted successfully.' : 'Draft saved.',
                'redirect' => $isFinal ? route('workflow.punching.index') : null
            ]);
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
            'VoucherNo' => 'nullable|string|max:150',
            'Voucher_Date' => 'nullable|date',
            'File_Date' => 'nullable|date',
            'Company' => 'nullable|integer',
            'Vendor' => 'nullable|integer',
            'Location' => 'nullable|string|max:255',
            'Particular' => 'nullable|string|max:1000',
            'Amount' => 'nullable|numeric',
            'Remark' => 'nullable|string|max:5000',
        ];
        if ($isFinal) {
            $rules['VoucherNo'] = 'required|string|max:150';
            $rules['Voucher_Date'] = 'required|date';
            $rules['File_Date'] = 'required|date';
            $rules['Company'] = 'required|integer|min:1';
            $rules['Vendor'] = 'required|integer|min:1';
            $rules['Location'] = 'required|string|max:255';
            $rules['Particular'] = 'required|string|max:1000';
            $rules['Amount'] = 'required|numeric|gt:0';
            $rules['Remark'] = 'required|string|min:1|max:5000';
        }
        $request->validate($rules, [
            'VoucherNo.required' => 'Voucher No is required.',
            'Voucher_Date.required' => 'Voucher Date is required.',
            'File_Date.required' => 'Date is required.',
            'Company.required' => 'Company (From) is required.',
            'Vendor.required' => 'Vendor (To) is required.',
            'Location.required' => 'Location is required.',
            'Particular.required' => 'Particular / Description is required.',
            'Amount.required' => 'Amount is required.',
            'Amount.gt' => 'Amount must be greater than 0.',
            'Remark.required' => 'Remark is required.',
        ]);

        DB::beginTransaction();
        try {
            $docType = DB::table('document_types')->where('id', $scanRecord->DocType_Id)->value('key');
            $companyId = (int) $request->input('Company', 0);
            $companyName = $companyId ? DB::table('master_firm')->where('firm_id', $companyId)->value('firm_name') : '';
            $vendorId = (int) $request->input('Vendor', 0);
            $vendorName = $vendorId ? DB::table('master_firm')->where('firm_id', $vendorId)->value('firm_name') : '';

            $data = [
                'Scan_Id' => $scanId,
                'Group_Id' => $scanRecord->Group_Id,
                'DocType' => $docType ?? '',
                'DocTypeId' => $scanRecord->DocType_Id,
                'File_Date' => $request->input('File_Date'),
                'File_No' => $request->input('VoucherNo', ''),
                'RegPurDate' => $request->input('Voucher_Date'),
                'TotalAmount' => (float) $request->input('Amount', 0),
                'Additional_Exposure' => $request->input('Particular', ''),
                'Company' => $companyName,
                'CompanyID' => $companyId,
                'Vendor' => $vendorName,
                'VendorID' => $vendorId,
                'Location' => $request->input('Location', ''),
                'Remark' => $request->input('Remark', ''),
                'Created_By' => Auth::id(),
                'Created_Date' => now()->toDateTimeString(),
                'Registered' => '',
                'Department' => '',
                'DepartmentID' => 0,
                'Stamp_Duty' => '',
                'Diversion_Paper' => '',
                'Map_Approval' => '',
                'Rating' => '',
            ];

            $existing = DB::table('punchfile2')->where('Scan_Id', $scanId)->first();
            if ($existing) {
                DB::table('punchfile2')->where('Scan_Id', $scanId)->update($data);
                DB::table('scan_file')->where('Scan_Id', $scanId)->update(['Is_Rejected' => 'N', 'Reject_Date' => null, 'Edit_Permission' => 'N']);
            } else {
                DB::table('punchfile2')->insert($data);
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

        return response()->json(['results' => $query->groupBy('file_name')->get(['file_name as id', 'file_name as text']), 'pagination' => ['more' => false]]);
    }

    public function locationsSelect(Request $request)
    {
        $q = $request->query('q', '');
        $query = \App\Models\Location::active()->orderBy('location_name');
        if ($q !== '') $query->where('location_name', 'like', "%{$q}%");

        return response()->json(['results' => $query->get(['location_name as id', 'location_name as text']), 'pagination' => ['more' => false]]);
    }

    public function employeesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_employee')
            ->where('status', 'A')
            ->where('is_deleted', 'N')
            ->orderBy('emp_name');

        if ($q !== '') {
            $query->where(function ($qb) use ($q) {
                $qb->where('emp_name', 'like', "%{$q}%")
                   ->orWhere('emp_code', 'like', "%{$q}%");
            });
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['id', 'emp_name as text', 'emp_code']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function hotelsSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('master_hotel')
            ->where('status', 'A')
            ->where('is_deleted', 'N')
            ->orderBy('hotel_name');

        if ($q !== '') {
            $query->where('hotel_name', 'like', "%{$q}%");
        }

        $total   = $query->count();
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['hotel_id as id', 'hotel_name as text', 'address', 'city_name']);

        return response()->json(['results' => $results, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function agentNamesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $docTypeId = $request->query('doc_type_id', 51); // Default to Air (51)
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('punchfile')
            ->leftJoin('scan_file', 'scan_file.Scan_Id', '=', 'punchfile.Scan_Id')
            ->where('scan_file.DocType_Id', $docTypeId)
            ->whereNotNull('punchfile.AgentName')
            ->where('punchfile.AgentName', '!=', '')
            ->groupBy('punchfile.AgentName')
            ->orderBy('punchfile.AgentName');

        if ($q !== '') {
            $query->where('punchfile.AgentName', 'like', "%{$q}%");
        }

        $total   = $query->count(DB::raw('DISTINCT punchfile.AgentName'));
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['punchfile.AgentName as id', 'punchfile.AgentName as text']);

        // Convert to array and add "Other" option at the beginning if search query is empty
        $resultsArray = $results->toArray();
        if ($page === 1 && $q === '') {
            array_unshift($resultsArray, (object)['id' => '__other__', 'text' => 'Other (Type manually)']);
        }

        return response()->json(['results' => $resultsArray, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function airlinesSelect(Request $request)
    {
        $q    = $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));
        $per  = 20;

        $query = DB::table('punchfile')
            ->leftJoin('scan_file', 'scan_file.Scan_Id', '=', 'punchfile.Scan_Id')
            ->where('scan_file.DocType_Id', 51) // Air travel only
            ->whereNotNull('punchfile.Airline')
            ->where('punchfile.Airline', '!=', '')
            ->groupBy('punchfile.Airline')
            ->orderBy('punchfile.Airline');

        if ($q !== '') {
            $query->where('punchfile.Airline', 'like', "%{$q}%");
        }

        $total   = $query->count(DB::raw('DISTINCT punchfile.Airline'));
        $results = $query->offset(($page - 1) * $per)->limit($per)
                         ->get(['punchfile.Airline as id', 'punchfile.Airline as text']);

        // Convert to array and add "Other" option at the beginning if search query is empty
        $resultsArray = $results->toArray();
        if ($page === 1 && $q === '') {
            array_unshift($resultsArray, (object)['id' => '__other__', 'text' => 'Other (Type manually)']);
        }

        return response()->json(['results' => $resultsArray, 'pagination' => ['more' => ($page * $per) < $total]]);
    }

    public function lastReading(Request $request)
    {
        $bpNo = $request->query('bp_no', '');
        if (!$bpNo) {
            return response()->json(['reading' => '']);
        }

        // Search by ReferenceNo (BP No) or MeterNumber
        $last = DB::table('punchfile')
            ->where(function($q) use ($bpNo) {
                $q->where('ReferenceNo', $bpNo)
                  ->orWhere('MeterNumber', $bpNo);
            })
            ->whereNotNull('CurrentReading')
            ->where('CurrentReading', '!=', '')
            ->where('CurrentReading', '!=', '0')
            ->orderBy('BillDate', 'desc')
            ->orderBy('FileID', 'desc')
            ->value('CurrentReading');

        return response()->json(['reading' => $last ?? '']);
    }
}
