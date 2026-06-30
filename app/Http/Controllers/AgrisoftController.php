<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgrisoftController extends Controller
{
    private const ACCOUNTING_TYPES = [
        'invoice', 'two_four_wheeler', 'air_rail_bus', 'bank_loan_paper',
        'cash_deposit_withdrawals', 'vehicle_maintenance', 'vehicle_fuel',
        'telephone_bill', 'subsidy', 'rtgs_neft', 'rst_ofd', 'postage_courier',
        'phone_fax', 'meals', 'lodging', 'local_conveyance', 'lease_rent',
        'jeep_campaign', 'it_return', 'insurance_policy', 'insurance_document',
        'income_taxt_tds', 'hired_vehicle', 'challan', 'fixed_deposit_receipt',
        'fd_fv', 'electricity_bill', 'dealer_meeting', 'cheque', 'cash_voucher',
        'gst_challan', 'labour_payment', 'cash_receipt', 'fixed_asset',
        'machine_operation', 'air', 'rail', 'bus', 'ticket_cancellation', 'sale_bill',
    ];


    /**
     * GET /api/Agrisoft_ctrl/scan_detail
     */
    public function scan_detail()
    {
        $secondary = DB::connection('secondary');
        $list = $secondary->table('scan_file')
            ->select('Scan_Id', 'Group_Id', 'Doc_Type', 'DocType_Id', 'Document_Name', 'File_Location', 'Punch_Date', 'Scan_Date', 'Approve_Date', 'Bill_Approver_Date', 'Missing_Data')
            ->where('Import_Flag', '0')
            ->get()
            ->toArray();

        return response()->json(['scan_files' => $list]);
    }

    /**
     * GET /api/Agrisoft_ctrl/punch_detail
     */
    public function punch_detail()
    {
        $secondary = DB::connection('secondary');
        $scan_ids = $secondary->table('scan_file')->where('import_flag', 0)->pluck('Scan_Id')->toArray();

        if (empty($scan_ids)) {
            return response()->json(['punchfile' => [], 'punchfile2' => []]);
        }

        $punchfile  = $secondary->table('punchfile')->whereIn('scan_id', $scan_ids)->get()->toArray();
        $punchfile2 = $secondary->table('punchfile2')->whereIn('scan_id', $scan_ids)->get()->toArray();

        return response()->json(['punchfile' => $punchfile, 'punchfile2' => $punchfile2]);
    }

    /**
     * GET /api/Agrisoft_ctrl/invoice_detail
     */
    public function invoice_detail()
    {
        $secondary = DB::connection('secondary');
        $scan_ids = $secondary->table('scan_file')->where('import_flag', 0)->pluck('Scan_Id')->toArray();

        if (empty($scan_ids)) {
            return response()->json(['invoice_detail' => []]);
        }

        $data = $secondary->table('invoice_detail')->whereIn('scan_id', $scan_ids)->get()->toArray();
        return response()->json(['invoice_detail' => $data]);
    }

    /**
     * GET /api/Agrisoft_ctrl/vehicle_traveling_detail
     */
    public function vehicle_traveling_detail()
    {
        $secondary = DB::connection('secondary');
        $scan_ids = $secondary->table('scan_file')->where('import_flag', 0)->pluck('Scan_Id')->toArray();

        if (empty($scan_ids)) {
            return response()->json(['vehicle_traveling_detail' => []]);
        }

        $data = $secondary->table('vehicle_traveling')->whereIn('scan_id', $scan_ids)->get()->toArray();
        return response()->json(['vehicle_traveling_detail' => $data]);
    }

    /**
     * GET /api/Agrisoft_ctrl/labour_payment_detail_detail
     */
    public function labour_payment_detail_detail()
    {
        $secondary = DB::connection('secondary');
        $scan_ids = $secondary->table('scan_file')->where('import_flag', 0)->pluck('Scan_Id')->toArray();

        if (empty($scan_ids)) {
            return response()->json(['labour_payment_detail_detail' => []]);
        }

        $data = $secondary->table('labour_payment_detail')->whereIn('scan_id', $scan_ids)->get()->toArray();
        return response()->json(['labour_payment_detail_detail' => $data]);
    }

    /**
     * GET /api/Agrisoft_ctrl/lodging_employee_detail
     */
    public function lodging_employee_detail()
    {
        $secondary = DB::connection('secondary');
        $scan_ids = $secondary->table('scan_file')->where('import_flag', 0)->pluck('Scan_Id')->toArray();

        if (empty($scan_ids)) {
            return response()->json(['lodging_employee_detail' => []]);
        }

        $data = $secondary->table('lodging_employee')->whereIn('scan_id', $scan_ids)->get()->toArray();
        return response()->json(['lodging_employee_detail' => $data]);
    }

    /**
     * GET /api/Agrisoft_ctrl/ticket_cancellation_detail
     */
    public function ticket_cancellation_detail()
    {
        $secondary = DB::connection('secondary');
        $scan_ids = $secondary->table('scan_file')->where('import_flag', 0)->pluck('Scan_Id')->toArray();

        if (empty($scan_ids)) {
            return response()->json(['ticket_cancellation_detail' => []]);
        }

        $data = $secondary->table('ticket_cancellation')->whereIn('Scan_Id', $scan_ids)->get()->toArray();
        return response()->json(['ticket_cancellation_detail' => $data]);
    }

    /**
     * GET /api/Agrisoft_ctrl/master_detail
     */
    public function master_detail()
    {
        return response()->json([
            'master_category'      => DB::table('master_category')->select('category_id', 'category_name', 'category_code')->get(),
            'master_country'       => DB::table('master_country')->select('country_id', 'country_name', 'country_code')->get(),
            'master_department'    => DB::table('master_department')->select('department_id', 'company_id', 'department_name', 'department_code')->get(),
            'master_employee'      => DB::table('master_employee')->select('id', 'emp_vspl', 'emp_code', 'emp_name', 'company_id', 'company_code', 'status')->get(),
            'master_doctype'       => DB::table('master_doctype')->select('type_id', 'file_type', 'alias')->get(),
            'master_file'          => DB::table('master_file')->select('file_id', 'file_name', 'file_code', 'company_id')->get(),
            'master_group'         => DB::table('master_group')->select('group_id', 'group_name')->get(),
            'master_hotel'         => DB::table('master_hotel')->select('hotel_id', 'hotel_name', 'state_id', 'address', 'city_name')->get(),
            'master_ledger'        => DB::table('master_ledger')->select('ledger_id', 'ledger_name', 'ledger_code', 'ledger_head')->get(),
            'master_report_type'   => DB::table('master_report_type')->select('report_id', 'report_name', 'report_alias')->get(),
            'master_state'         => DB::table('master_state')->select('state_id', 'country_id', 'state_name', 'state_code')->get(),
            'master_unit'          => DB::table('master_unit')->select('unit_id', 'unit_name', 'unit_code')->get(),
            'master_work_location' => DB::table('master_work_location')->select('location_id', 'location_name')->get(),
        ]);
    }

    /**
     * GET /api/Agrisoft_ctrl/master_firm_detail
     */
    public function master_firm_detail()
    {
        $data = DB::table('master_firm')
            ->select('firm_id', 'firm_type', 'firm_name', 'firm_code', 'country_id', 'state_id', 'city_name', 'pin_code', 'address', 'gst')
            ->where('Import_Flag', '0')
            ->get();

        return response()->json(['master_firm_detail' => $data]);
    }

    /**
     * GET /api/Agrisoft_ctrl/master_item_detail
     */
    public function master_item_detail()
    {
        $data = DB::table('master_item')
            ->select('item_id', 'item_name', 'item_code')
            ->where('Import_Flag', '0')
            ->get();

        return response()->json(['master_item_detail' => $data]);
    }

    /**
     * POST /api/Agrisoft_ctrl/transfer_result
     */
    public function transfer_result(Request $request)
    {
        $data = $request->all();
        $secondary = DB::connection('secondary');

        // Update scan_file Import_Flag on secondary DB
        if (!empty($data['scan_files'])) {
            foreach ($data['scan_files'] as $scanFile) {
                $secondary->table('scan_file')->where('Scan_Id', $scanFile['Scan_Id'])->update(['Import_Flag' => 1]);
            }
        }

        // Update master_firm Import_Flag on primary DB
        if (!empty($data['firm_files'])) {
            foreach ($data['firm_files'] as $row) {
                DB::table('master_firm')->where('firm_id', $row['firm_id'])->update(['Import_Flag' => 1]);
            }
        }

        // Update master_item Import_Flag on primary DB
        if (!empty($data['item_files'])) {
            foreach ($data['item_files'] as $row) {
                DB::table('master_item')->where('item_id', $row['item_id'])->update(['Import_Flag' => 1]);
            }
        }

        return response()->json(['status' => 200, 'message' => 'Successfully Updated']);
    }

    /**
     * GET /api/Agrisoft_ctrl/get_punch_date
     */
    public function get_punch_date()
    {
        $data = DB::table('scan_file')
            ->select('Scan_Id', 'Punch_Date')
            ->where('File_Approved', 'Y')
            ->get();

        return response()->json(['scan_detail' => $data]);
    }

    /**
     * GET /agrisoft_data_set  (same as CI3 route)
     * Equivalent of $this->customlib->set_missing_data()
     */
    public function set_data()
    {
        if (empty(config('database.connections.secondary.database'))) {
            return response()->json(['status' => 500, 'message' => 'Secondary DB not configured']);
        }

        $secondary = DB::connection('secondary');

        // Find scan_ids in secondary scan_file that have no punchfile or punchfile2
        $scan_ids = $secondary->select("SELECT Scan_Id FROM scan_file WHERE Scan_Id NOT IN (SELECT Scan_Id FROM punchfile) AND Scan_Id NOT IN (SELECT Scan_Id FROM punchfile2)");

        if (empty($scan_ids)) {
            return response()->json(['status' => 200, 'message' => 'No missing data found']);
        }

        try {
            $secondary->beginTransaction();

            foreach ($scan_ids as $row) {
                $scanId = $row->Scan_Id;
                $scanDetail = DB::table('scan_file')->where('Scan_Id', $scanId)->first();

                if (!$scanDetail) continue;

                $docType = $scanDetail->Doc_Type ?? '';

                // Insert punchfile or punchfile2
                if (in_array($docType, self::ACCOUNTING_TYPES)) {
                    $punchfile = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
                    if ($punchfile) {
                        $secondary->table('punchfile')->insert((array) $punchfile);
                    }
                } else {
                    $punchfile2 = DB::table('punchfile2')->where('Scan_Id', $scanId)->first();
                    if ($punchfile2) {
                        $secondary->table('punchfile2')->insert((array) $punchfile2);
                    }
                }

                // Insert detail tables
                if (in_array($docType, ['invoice', 'fixed_asset', 'vehicle_maintenance'])) {
                    $rows = DB::table('invoice_detail')->where('Scan_Id', $scanId)->get()->toArray();
                    foreach ($rows as $r) { $secondary->table('invoice_detail')->insert((array) $r); }
                } elseif (in_array($docType, ['two_four_wheeler', 'local_conveyance'])) {
                    $rows = DB::table('vehicle_traveling')->where('Scan_Id', $scanId)->get()->toArray();
                    foreach ($rows as $r) { $secondary->table('vehicle_traveling')->insert((array) $r); }
                } elseif (in_array($docType, ['lodging', 'air', 'rail'])) {
                    $rows = DB::table('lodging_employee')->where('Scan_Id', $scanId)->get()->toArray();
                    foreach ($rows as $r) { $secondary->table('lodging_employee')->insert((array) $r); }
                } elseif ($docType === 'gst_challan') {
                    $rows = DB::table('gst_challan_detail')->where('Scan_Id', $scanId)->get()->toArray();
                    foreach ($rows as $r) { $secondary->table('gst_challan_detail')->insert((array) $r); }
                } elseif ($docType === 'labour_payment') {
                    $rows = DB::table('labour_payment_detail')->where('Scan_Id', $scanId)->get()->toArray();
                    foreach ($rows as $r) { $secondary->table('labour_payment_detail')->insert((array) $r); }
                } elseif ($docType === 'ticket_cancellation') {
                    $rows = DB::table('ticket_cancellation')->where('Scan_Id', $scanId)->get()->toArray();
                    foreach ($rows as $r) { $secondary->table('ticket_cancellation')->insert((array) $r); }
                }
                // Mark Missing_Data = 0
                $secondary->table('scan_file')->where('Scan_Id', $scanId)->update(['Missing_Data' => '0']);
            }

            $secondary->commit();
            return response()->json(['status' => 200, 'message' => 'Success']);
        } catch (\Exception $e) {
            $secondary->rollBack();
            Log::error("AgrisoftController set_data error: " . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Something went wrong']);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // INTERNAL: sendForAccounting (called by PunchApprovalController)
    // ══════════════════════════════════════════════════════════════════════════

    public function sendForAccounting(int $scanId): bool
    {
        if (empty(config('database.connections.secondary.database'))) {
            Log::warning("AgrisoftController: secondary DB not configured — skipping sync for Scan_Id={$scanId}");
            return false;
        }

        try {
            $secondary = DB::connection('secondary');
            $scanDetail = DB::table('scan_file')->where('Scan_Id', $scanId)->first();

            if (!$scanDetail) {
                Log::error("AgrisoftController: scan_file not found for Scan_Id={$scanId}");
                return false;
            }

            // 1. Sync scan_file
            $scanData = [
                'Scan_Id'            => $scanId,
                'Group_Id'           => $scanDetail->Group_Id,
                'Doc_Type'           => $scanDetail->Doc_Type ?? null,
                'DocType_Id'         => $scanDetail->DocType_Id ?? null,
                'Document_Name'      => $scanDetail->Document_name ?? null,
                'File'               => $scanDetail->File ?? null,
                'File_Ext'           => $scanDetail->File_Ext ?? null,
                'File_Location'      => $scanDetail->File_Location ?? null,
                'Punch_Date'         => $scanDetail->Punch_Date ?? null,
                'Scan_Date'          => !empty($scanDetail->Temp_Scan_Date) ? date('Y-m-d', strtotime($scanDetail->Temp_Scan_Date)) : (!empty($scanDetail->Scan_Date) ? date('Y-m-d', strtotime($scanDetail->Scan_Date)) : null),
                'Approve_Date'       => !empty($scanDetail->Approve_Date) ? date('Y-m-d', strtotime($scanDetail->Approve_Date)) : null,
                'Bill_Approver_Date' => !empty($scanDetail->Bill_Approver_Date) ? date('Y-m-d', strtotime($scanDetail->Bill_Approver_Date)) : null,
            ];

            $exists = $secondary->table('scan_file')->where('Scan_Id', $scanId)->exists();
            if ($exists) {
                $secondary->table('scan_file')->where('Scan_Id', $scanId)->update($scanData);
            } else {
                $secondary->table('scan_file')->insert($scanData);
            }

            // 2. Sync punchfile / punchfile2
            $docType = $scanDetail->Doc_Type ?? '';

            if (in_array($docType, self::ACCOUNTING_TYPES)) {
                $punchfile = DB::table('punchfile')->where('Scan_Id', $scanId)->first();
                if ($punchfile) {
                    $punchData = (array) $punchfile;
                    $punchExists = $secondary->table('punchfile')->where('Scan_Id', $scanId)->exists();
                    if ($punchExists) {
                        $secondary->table('punchfile')->where('Scan_Id', $scanId)->update($punchData);
                    } else {
                        $secondary->table('punchfile')->insert($punchData);
                    }
                }
            } else {
                $punchfile2 = DB::table('punchfile2')->where('Scan_Id', $scanId)->first();
                if ($punchfile2) {
                    $p2Exists = $secondary->table('punchfile2')->where('Scan_Id', $scanId)->exists();
                    if ($p2Exists) {
                        $secondary->table('punchfile2')->where('Scan_Id', $scanId)->update((array) $punchfile2);
                    } else {
                        $secondary->table('punchfile2')->insert((array) $punchfile2);
                    }
                }
            }

            // 3. Sync detail tables
            $this->syncDetailTable($secondary, $scanId, $docType);

            Log::info("AgrisoftController: sync complete for Scan_Id={$scanId}");
            return true;

        } catch (\Throwable $e) {
            Log::error("AgrisoftController: sync failed for Scan_Id={$scanId} — {$e->getMessage()}");
            return false;
        }
    }

    private function syncDetailTable($secondary, int $scanId, string $docType): void
    {
        if (in_array($docType, ['invoice', 'sale_bill', 'fixed_asset', 'vehicle_maintenance', 'credit_note'])) {
            $this->upsertBatch($secondary, 'invoice_detail', 'Scan_Id', $scanId);
        } elseif (in_array($docType, ['two_four_wheeler', 'local_conveyance'])) {
            $this->upsertBatch($secondary, 'vehicle_traveling', 'Scan_Id', $scanId);
        } elseif (in_array($docType, ['lodging', 'air', 'rail'])) {
            $this->upsertBatch($secondary, 'lodging_employee', 'Scan_Id', $scanId);
        } elseif ($docType === 'gst_challan') {
            $this->upsertBatch($secondary, 'gst_challan_detail', 'Scan_Id', $scanId);
        } elseif ($docType === 'labour_payment') {
            $this->upsertBatch($secondary, 'labour_payment_detail', 'Scan_Id', $scanId);
        } elseif ($docType === 'ticket_cancellation') {
            $this->upsertBatch($secondary, 'ticket_cancellation', 'Scan_Id', $scanId);
        }
    }

    private function upsertBatch($secondary, string $table, string $fk, int $scanId): void
    {
        $rows = DB::table($table)->where($fk, $scanId)->get()->toArray();
        if (empty($rows)) return;

        $secondary->table($table)->where($fk, $scanId)->delete();
        foreach ($rows as $r) {
            $secondary->table($table)->insert((array) $r);
        }
    }
}
