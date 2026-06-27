<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AgrisoftController
 *
 * Handles syncing approved punch entries to the secondary (Agrisoft) database.
 * Configure secondary DB credentials in .env:
 *   DB_SECONDARY_HOST, DB_SECONDARY_DATABASE, DB_SECONDARY_USERNAME, DB_SECONDARY_PASSWORD
 */
class AgrisoftController extends Controller
{
    /**
     * Document types whose detail rows live in the `punchfile` table.
     */
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
     * Send a punched & approved entry to the secondary database.
     * Called automatically after punch approval.
     *
     * @param  int  $scanId
     * @return bool
     */
    public function sendForAccounting(int $scanId): bool
    {
        // Guard: skip if secondary DB is not configured
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

            // ── 1. Sync scan_file row ─────────────────────────────────────────
            $scanData = [
                'Scan_Id'          => $scanId,
                'Group_Id'         => $scanDetail->Group_Id,
                'Doc_Type'         => $scanDetail->Doc_Type     ?? null,
                'DocType_Id'       => $scanDetail->DocType_Id   ?? null,
                'Document_Name'    => $scanDetail->Document_name ?? null,
                'File'             => $scanDetail->File         ?? null,
                'File_Ext'         => $scanDetail->File_Ext     ?? null,
                'File_Location'    => $scanDetail->File_Location ?? null,
                'Punch_Date'       => $scanDetail->Punch_Date   ?? null,
                'Scan_Date'        => !empty($scanDetail->Temp_Scan_Date)
                    ? date('Y-m-d', strtotime($scanDetail->Temp_Scan_Date))
                    : (!empty($scanDetail->Scan_Date) ? date('Y-m-d', strtotime($scanDetail->Scan_Date)) : null),
                'Approve_Date'     => !empty($scanDetail->Approve_Date)
                    ? date('Y-m-d', strtotime($scanDetail->Approve_Date)) : null,
                'Bill_Approver_Date' => !empty($scanDetail->Bill_Approver_Date)
                    ? date('Y-m-d', strtotime($scanDetail->Bill_Approver_Date)) : null,
            ];

            // Insert or update (upsert on Scan_Id)
            $exists = $secondary->table('scan_file')->where('Scan_Id', $scanId)->exists();
            if ($exists) {
                $secondary->table('scan_file')->where('Scan_Id', $scanId)->update($scanData);
            } else {
                $secondary->table('scan_file')->insert($scanData);
            }

            // ── 2. Sync punchfile / punchfile2 ───────────────────────────────
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
                    Log::debug("AgrisoftController: punchfile synced for Scan_Id={$scanId}");
                } else {
                    Log::debug("AgrisoftController: no punchfile found for Scan_Id={$scanId}");
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

            // ── 3. Sync related detail tables ────────────────────────────────
            $this->syncDetailTable($secondary, $scanId, $docType);

            Log::info("AgrisoftController: sync complete for Scan_Id={$scanId}");
            return true;

        } catch (\Throwable $e) {
            Log::error("AgrisoftController: sync failed for Scan_Id={$scanId} — {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Sync document-type-specific detail rows to the secondary DB.
     */
    private function syncDetailTable($secondary, int $scanId, string $docType): void
    {
        // Invoice / sale bill / fixed asset / vehicle maintenance → invoice_detail
        if (in_array($docType, ['invoice', 'sale_bill', 'fixed_asset', 'vehicle_maintenance', 'credit_note'])) {
            $this->upsertBatch($secondary, 'invoice_detail', 'Scan_Id', $scanId,
                ['Scan_Id', 'Particular', 'HSN', 'Qty', 'Unit', 'MRP',
                 'Discount', 'Price', 'Amount', 'GST', 'SGST', 'IGST', 'Cess', 'Total_Amount']);
        }
        // Two/four wheeler & local conveyance → vehicle_traveling
        elseif (in_array($docType, ['two_four_wheeler', 'local_conveyance'])) {
            $this->upsertBatch($secondary, 'vehicle_traveling', 'Scan_Id', $scanId,
                ['Scan_Id', 'VehicleReg', 'JourneyStartDt', 'JourneyEndDt',
                 'DistTraOpen', 'DistTraClose', 'Totalkm', 'FilledTAmt']);
        }
        // Lodging / air / rail → lodging_employee
        elseif (in_array($docType, ['lodging', 'air', 'rail'])) {
            $this->upsertBatch($secondary, 'lodging_employee', 'Scan_Id', $scanId,
                ['Scan_Id', 'emp_id', 'emp_name', 'emp_code']);
        }
        // GST challan → gst_challan_detail
        elseif ($docType === 'gst_challan') {
            $this->upsertBatch($secondary, 'gst_challan_detail', 'Scan_Id', $scanId,
                ['Scan_Id', 'Particular', 'Tax', 'Interest', 'Penalty', 'Fees', 'Other', 'Total']);
        }
        // Labour payment → labour_payment_detail
        elseif ($docType === 'labour_payment') {
            $this->upsertBatch($secondary, 'labour_payment_detail', 'Scan_Id', $scanId,
                ['Scan_Id', 'Head', 'Amount']);
        }
        // Ticket cancellation → ticket_cancellation
        elseif ($docType === 'ticket_cancellation') {
            $this->upsertBatch($secondary, 'ticket_cancellation', 'Scan_Id', $scanId,
                ['Scan_Id', 'Emp_Id', 'Emp_Name', 'Amount', 'PNR']);
        }
    }

    /**
     * Delete + re-insert detail rows for a given scan in the secondary DB.
     */
    private function upsertBatch($secondary, string $table, string $fk, int $scanId, array $columns): void
    {
        $rows = DB::table($table)->where($fk, $scanId)->get($columns)->toArray();

        if (empty($rows)) return;

        $secondary->table($table)->where($fk, $scanId)->delete();
        $secondary->table($table)->insert(array_map(fn($r) => (array) $r, $rows));

        Log::debug("AgrisoftController: {$table} synced (" . count($rows) . " rows) for Scan_Id={$scanId}");
    }
}
