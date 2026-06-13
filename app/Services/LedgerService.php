<?php

namespace App\Services;

use App\Models\LedgerEntry;
use App\Models\SaleInvoice;
use App\Models\Receipt;
use App\Models\CreditNote;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post Sales Invoice to Ledger
     * 
     * Accounting Entry:
     * Dr. Customer Account (Party)     XXX
     *     Cr. Sales Account                XXX
     *     Cr. CGST/SGST/IGST Account       XXX
     */
    public function postSalesInvoice(SaleInvoice $invoice): void
    {
        // Only post approved invoices
        if ($invoice->status !== 'approved') {
            return;
        }

        // Delete existing entries for this invoice (for re-posting)
        $this->deleteEntriesForDocument('App\Models\SaleInvoice', $invoice->id);

        $entries = [];
        $date = $invoice->invoice_date->format('Y-m-d');

        // 1. Debit Customer Account (Total Amount Receivable)
        $entries[] = [
            'company_id'        => $invoice->company_id,
            'financial_year_id' => $invoice->financial_year_id,
            'entry_date'        => $date,
            'voucher_type'      => 'sales_invoice',
            'voucher_number'    => $invoice->invoice_number,
            'document_id'       => $invoice->id,
            'document_type'     => 'App\Models\SaleInvoice',
            'account_id'        => null,
            'party_id'          => $invoice->party_id,
            'account_name'      => $invoice->party->display_name ?? $invoice->party->name,
            'debit'             => $invoice->grand_total,
            'credit'            => 0,
            'narration'         => "Sales Invoice - {$invoice->invoice_number}",
            'description'       => $invoice->notes,
            'created_by'        => $invoice->created_by,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        // 2. Credit Sales Account (Taxable Amount)
        $salesAccount = $this->getSalesAccount($invoice->company_id);
        if ($salesAccount) {
            $entries[] = [
                'company_id'        => $invoice->company_id,
                'financial_year_id' => $invoice->financial_year_id,
                'entry_date'        => $date,
                'voucher_type'      => 'sales_invoice',
                'voucher_number'    => $invoice->invoice_number,
                'document_id'       => $invoice->id,
                'document_type'     => 'App\Models\SaleInvoice',
                'account_id'        => $salesAccount->id,
                'party_id'          => null,
                'account_name'      => $salesAccount->name,
                'debit'             => 0,
                'credit'            => $invoice->taxable_amount,
                'narration'         => "Sales Invoice - {$invoice->invoice_number}",
                'description'       => $invoice->notes,
                'created_by'        => $invoice->created_by,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        // 3. Credit Tax Accounts
        if ($invoice->is_igst && $invoice->igst_amount > 0) {
            $igstAccount = $this->getIGSTAccount($invoice->company_id);
            if ($igstAccount) {
                $entries[] = [
                    'company_id'        => $invoice->company_id,
                    'financial_year_id' => $invoice->financial_year_id,
                    'entry_date'        => $date,
                    'voucher_type'      => 'sales_invoice',
                    'voucher_number'    => $invoice->invoice_number,
                    'document_id'       => $invoice->id,
                    'document_type'     => 'App\Models\SaleInvoice',
                    'account_id'        => $igstAccount->id,
                    'party_id'          => null,
                    'account_name'      => $igstAccount->name,
                    'debit'             => 0,
                    'credit'            => $invoice->igst_amount,
                    'narration'         => "IGST on Sales - {$invoice->invoice_number}",
                    'description'       => null,
                    'created_by'        => $invoice->created_by,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }
        } else {
            if ($invoice->cgst_amount > 0) {
                $cgstAccount = $this->getCGSTAccount($invoice->company_id);
                if ($cgstAccount) {
                    $entries[] = [
                        'company_id'        => $invoice->company_id,
                        'financial_year_id' => $invoice->financial_year_id,
                        'entry_date'        => $date,
                        'voucher_type'      => 'sales_invoice',
                        'voucher_number'    => $invoice->invoice_number,
                        'document_id'       => $invoice->id,
                        'document_type'     => 'App\Models\SaleInvoice',
                        'account_id'        => $cgstAccount->id,
                        'party_id'          => null,
                        'account_name'      => $cgstAccount->name,
                        'debit'             => 0,
                        'credit'            => $invoice->cgst_amount,
                        'narration'         => "CGST on Sales - {$invoice->invoice_number}",
                        'description'       => null,
                        'created_by'        => $invoice->created_by,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }
            }

            if ($invoice->sgst_amount > 0) {
                $sgstAccount = $this->getSGSTAccount($invoice->company_id);
                if ($sgstAccount) {
                    $entries[] = [
                        'company_id'        => $invoice->company_id,
                        'financial_year_id' => $invoice->financial_year_id,
                        'entry_date'        => $date,
                        'voucher_type'      => 'sales_invoice',
                        'voucher_number'    => $invoice->invoice_number,
                        'document_id'       => $invoice->id,
                        'document_type'     => 'App\Models\SaleInvoice',
                        'account_id'        => $sgstAccount->id,
                        'party_id'          => null,
                        'account_name'      => $sgstAccount->name,
                        'debit'             => 0,
                        'credit'            => $invoice->sgst_amount,
                        'narration'         => "SGST on Sales - {$invoice->invoice_number}",
                        'description'       => null,
                        'created_by'        => $invoice->created_by,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }
            }
        }

        // Insert all entries
        if (!empty($entries)) {
            DB::table('ledger_entries')->insert($entries);
        }
    }

    /**
     * Post Receipt to Ledger
     * 
     * Accounting Entry:
     * Dr. Bank/Cash Account            XXX
     *     Cr. Customer Account (Party)     XXX
     */
    public function postReceipt(Receipt $receipt): void
    {
        // Only post approved receipts
        if ($receipt->status !== 'approved') {
            return;
        }

        // Delete existing entries for this receipt (for re-posting)
        $this->deleteEntriesForDocument('App\Models\Receipt', $receipt->id);

        $entries = [];
        $date = $receipt->receipt_date->format('Y-m-d');

        // 1. Debit Bank/Cash Account
        $bankAccount = $this->getBankCashAccount($receipt->company_id, $receipt->payment_method);
        if ($bankAccount) {
            $entries[] = [
                'company_id'        => $receipt->company_id,
                'financial_year_id' => $receipt->financial_year_id,
                'entry_date'        => $date,
                'voucher_type'      => 'receipt',
                'voucher_number'    => $receipt->receipt_number,
                'document_id'       => $receipt->id,
                'document_type'     => 'App\Models\Receipt',
                'account_id'        => $bankAccount->id,
                'party_id'          => null,
                'account_name'      => $bankAccount->name,
                'debit'             => $receipt->amount,
                'credit'            => 0,
                'narration'         => "Receipt - {$receipt->receipt_number}",
                'description'       => $receipt->description,
                'created_by'        => $receipt->created_by,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        // 2. Credit Customer Account
        $entries[] = [
            'company_id'        => $receipt->company_id,
            'financial_year_id' => $receipt->financial_year_id,
            'entry_date'        => $date,
            'voucher_type'      => 'receipt',
            'voucher_number'    => $receipt->receipt_number,
            'document_id'       => $receipt->id,
            'document_type'     => 'App\Models\Receipt',
            'account_id'        => null,
            'party_id'          => $receipt->party_id,
            'account_name'      => $receipt->party->display_name ?? $receipt->party->name,
            'debit'             => 0,
            'credit'            => $receipt->amount,
            'narration'         => "Receipt - {$receipt->receipt_number}",
            'description'       => $receipt->description,
            'created_by'        => $receipt->created_by,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        // Insert all entries
        if (!empty($entries)) {
            DB::table('ledger_entries')->insert($entries);
        }
    }

    /**
     * Post Credit Note to Ledger
     * 
     * Accounting Entry:
     * Dr. Sales Return Account         XXX
     * Dr. Tax Accounts                 XXX
     *     Cr. Customer Account (Party)     XXX
     */
    public function postCreditNote(CreditNote $creditNote): void
    {
        // Only post approved credit notes
        if ($creditNote->status !== 'approved') {
            return;
        }

        // Delete existing entries for this credit note (for re-posting)
        $this->deleteEntriesForDocument('App\Models\CreditNote', $creditNote->id);

        $entries = [];
        $date = $creditNote->credit_note_date->format('Y-m-d');

        // 1. Debit Sales Return Account
        $salesReturnAccount = $this->getSalesReturnAccount($creditNote->company_id);
        if ($salesReturnAccount) {
            $entries[] = [
                'company_id'        => $creditNote->company_id,
                'financial_year_id' => $creditNote->financial_year_id,
                'entry_date'        => $date,
                'voucher_type'      => 'credit_note',
                'voucher_number'    => $creditNote->credit_note_number,
                'document_id'       => $creditNote->id,
                'document_type'     => 'App\Models\CreditNote',
                'account_id'        => $salesReturnAccount->id,
                'party_id'          => null,
                'account_name'      => $salesReturnAccount->name,
                'debit'             => $creditNote->taxable_amount,
                'credit'            => 0,
                'narration'         => "Credit Note - {$creditNote->credit_note_number}",
                'description'       => $creditNote->notes,
                'created_by'        => $creditNote->created_by,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        // 2. Debit Tax Accounts
        if ($creditNote->is_igst && $creditNote->igst_amount > 0) {
            $igstAccount = $this->getIGSTAccount($creditNote->company_id);
            if ($igstAccount) {
                $entries[] = [
                    'company_id'        => $creditNote->company_id,
                    'financial_year_id' => $creditNote->financial_year_id,
                    'entry_date'        => $date,
                    'voucher_type'      => 'credit_note',
                    'voucher_number'    => $creditNote->credit_note_number,
                    'document_id'       => $creditNote->id,
                    'document_type'     => 'App\Models\CreditNote',
                    'account_id'        => $igstAccount->id,
                    'party_id'          => null,
                    'account_name'      => $igstAccount->name,
                    'debit'             => $creditNote->igst_amount,
                    'credit'            => 0,
                    'narration'         => "IGST on Credit Note - {$creditNote->credit_note_number}",
                    'description'       => null,
                    'created_by'        => $creditNote->created_by,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }
        } else {
            if ($creditNote->cgst_amount > 0) {
                $cgstAccount = $this->getCGSTAccount($creditNote->company_id);
                if ($cgstAccount) {
                    $entries[] = [
                        'company_id'        => $creditNote->company_id,
                        'financial_year_id' => $creditNote->financial_year_id,
                        'entry_date'        => $date,
                        'voucher_type'      => 'credit_note',
                        'voucher_number'    => $creditNote->credit_note_number,
                        'document_id'       => $creditNote->id,
                        'document_type'     => 'App\Models\CreditNote',
                        'account_id'        => $cgstAccount->id,
                        'party_id'          => null,
                        'account_name'      => $cgstAccount->name,
                        'debit'             => $creditNote->cgst_amount,
                        'credit'            => 0,
                        'narration'         => "CGST on Credit Note - {$creditNote->credit_note_number}",
                        'description'       => null,
                        'created_by'        => $creditNote->created_by,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }
            }

            if ($creditNote->sgst_amount > 0) {
                $sgstAccount = $this->getSGSTAccount($creditNote->company_id);
                if ($sgstAccount) {
                    $entries[] = [
                        'company_id'        => $creditNote->company_id,
                        'financial_year_id' => $creditNote->financial_year_id,
                        'entry_date'        => $date,
                        'voucher_type'      => 'credit_note',
                        'voucher_number'    => $creditNote->credit_note_number,
                        'document_id'       => $creditNote->id,
                        'document_type'     => 'App\Models\CreditNote',
                        'account_id'        => $sgstAccount->id,
                        'party_id'          => null,
                        'account_name'      => $sgstAccount->name,
                        'debit'             => $creditNote->sgst_amount,
                        'credit'            => 0,
                        'narration'         => "SGST on Credit Note - {$creditNote->credit_note_number}",
                        'description'       => null,
                        'created_by'        => $creditNote->created_by,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }
            }
        }

        // 3. Credit Customer Account
        $entries[] = [
            'company_id'        => $creditNote->company_id,
            'financial_year_id' => $creditNote->financial_year_id,
            'entry_date'        => $date,
            'voucher_type'      => 'credit_note',
            'voucher_number'    => $creditNote->credit_note_number,
            'document_id'       => $creditNote->id,
            'document_type'     => 'App\Models\CreditNote',
            'account_id'        => null,
            'party_id'          => $creditNote->party_id,
            'account_name'      => $creditNote->party->display_name ?? $creditNote->party->name,
            'debit'             => 0,
            'credit'            => $creditNote->grand_total,
            'narration'         => "Credit Note - {$creditNote->credit_note_number}",
            'description'       => $creditNote->notes,
            'created_by'        => $creditNote->created_by,
            'created_at'        => now(),
            'updated_at'        => now(),
        ];

        // Insert all entries
        if (!empty($entries)) {
            DB::table('ledger_entries')->insert($entries);
        }
    }

    /**
     * Delete ledger entries for a specific document
     */
    public function deleteEntriesForDocument(string $documentType, int $documentId): void
    {
        LedgerEntry::where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->delete();
    }

    /**
     * Get Ledger Balance for an Account/Party
     */
    public function getBalance(int $companyId, ?int $accountId = null, ?int $partyId = null, $asOfDate = null): array
    {
        $query = LedgerEntry::where('company_id', $companyId);

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        if ($partyId) {
            $query->where('party_id', $partyId);
        }

        if ($asOfDate) {
            $query->where('entry_date', '<=', $asOfDate);
        }

        $result = $query->selectRaw('
            SUM(debit) as total_debit,
            SUM(credit) as total_credit
        ')->first();

        $totalDebit = (float)($result->total_debit ?? 0);
        $totalCredit = (float)($result->total_credit ?? 0);
        $balance = $totalDebit - $totalCredit;

        return [
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'balance'      => abs($balance),
            'balance_type' => $balance >= 0 ? 'Dr' : 'Cr',
        ];
    }

    // ── Helper Methods to Get System Accounts ────────────────────────────────

    private function getSalesAccount(int $companyId): ?Account
    {
        return Account::where('code', 'SALES')->orWhere('name', 'LIKE', '%Sales%')->first();
    }

    private function getSalesReturnAccount(int $companyId): ?Account
    {
        return Account::where('code', 'SALES_RETURN')->orWhere('name', 'LIKE', '%Sales Return%')->first();
    }

    private function getCGSTAccount(int $companyId): ?Account
    {
        return Account::where('code', 'CGST')->orWhere('name', 'LIKE', '%CGST%')->first();
    }

    private function getSGSTAccount(int $companyId): ?Account
    {
        return Account::where('code', 'SGST')->orWhere('name', 'LIKE', '%SGST%')->first();
    }

    private function getIGSTAccount(int $companyId): ?Account
    {
        return Account::where('code', 'IGST')->orWhere('name', 'LIKE', '%IGST%')->first();
    }

    private function getBankCashAccount(int $companyId, string $paymentMethod): ?Account
    {
        $code = match($paymentMethod) {
            'cash' => 'CASH',
            'bank_transfer', 'cheque', 'upi', 'card' => 'BANK',
            default => 'CASH',
        };

        return Account::where('code', $code)
            ->orWhere('name', 'LIKE', "%{$code}%")
            ->first();
    }
}
