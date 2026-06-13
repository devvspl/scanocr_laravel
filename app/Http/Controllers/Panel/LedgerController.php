<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class LedgerController extends Controller
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Display Account Ledger
     */
    public function accountLedger(Request $request, Account $account)
    {
        $company = Company::getDefault();
        $fy = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        $startDate = $request->get('start_date', $fy?->start_date?->format('Y-m-d') ?? now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', $fy?->end_date?->format('Y-m-d') ?? now()->endOfYear()->format('Y-m-d'));

        // Get opening balance (before start date)
        $openingBalance = $this->ledgerService->getBalance(
            $company->id,
            $account->id,
            null,
            date('Y-m-d', strtotime($startDate . ' -1 day'))
        );

        // Get ledger entries
        $entries = LedgerEntry::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->with(['party', 'creator'])
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance['balance_type'] === 'Dr'
            ? $openingBalance['balance']
            : -$openingBalance['balance'];

        foreach ($entries as $entry) {
            $runningBalance += (float)$entry->debit - (float)$entry->credit;
            $entry->running_balance = abs($runningBalance);
            $entry->balance_type = $runningBalance >= 0 ? 'Dr' : 'Cr';
        }

        $closingBalance = [
            'balance'      => abs($runningBalance),
            'balance_type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
        ];

        return view('panel.ledgers.account-ledger', compact(
            'account',
            'company',
            'fy',
            'entries',
            'openingBalance',
            'closingBalance',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Display Party Ledger (Customer/Vendor)
     */
    public function partyLedger(Request $request, Party $party)
    {
        $company = Company::getDefault();
        $fy = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        $startDate = $request->get('start_date', $fy?->start_date?->format('Y-m-d') ?? now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', $fy?->end_date?->format('Y-m-d') ?? now()->endOfYear()->format('Y-m-d'));

        // Get opening balance (before start date)
        $openingBalance = $this->ledgerService->getBalance(
            $company->id,
            null,
            $party->id,
            date('Y-m-d', strtotime($startDate . ' -1 day'))
        );

        // Get ledger entries
        $entries = LedgerEntry::where('company_id', $company->id)
            ->where('party_id', $party->id)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->with(['account', 'creator'])
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance['balance_type'] === 'Dr'
            ? $openingBalance['balance']
            : -$openingBalance['balance'];

        foreach ($entries as $entry) {
            $runningBalance += (float)$entry->debit - (float)$entry->credit;
            $entry->running_balance = abs($runningBalance);
            $entry->balance_type = $runningBalance >= 0 ? 'Dr' : 'Cr';
        }

        $closingBalance = [
            'balance'      => abs($runningBalance),
            'balance_type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
        ];

        return view('panel.ledgers.party-ledger', compact(
            'party',
            'company',
            'fy',
            'entries',
            'openingBalance',
            'closingBalance',
            'startDate',
            'endDate'
        ));
    }

    /**
     * List all accounts with balances
     */
    public function accountsList(Request $request)
    {
        $company = Company::getDefault();
        $fy = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        $accounts = Account::where('is_active', true)
            ->with('group')
            ->orderBy('name')
            ->get();

        // Calculate balance for each account
        foreach ($accounts as $account) {
            $balance = $this->ledgerService->getBalance($company->id, $account->id);
            $account->balance = $balance['balance'];
            $account->balance_type = $balance['balance_type'];
        }

        return view('panel.ledgers.accounts-list', compact('accounts', 'company', 'fy'));
    }

    /**
     * List all parties with balances
     */
    public function partiesList(Request $request)
    {
        $company = Company::getDefault();
        $fy = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        $type = $request->get('type', 'customer'); // customer or vendor

        $parties = Party::where('is_active', true)
            ->where('type', $type)
            ->orderBy('name')
            ->get();

        // Calculate balance for each party
        foreach ($parties as $party) {
            $balance = $this->ledgerService->getBalance($company->id, null, $party->id);
            $party->balance = $balance['balance'];
            $party->balance_type = $balance['balance_type'];
        }

        return view('panel.ledgers.parties-list', compact('parties', 'company', 'fy', 'type'));
    }

    /**
     * Export Account Ledger to PDF
     */
    public function exportAccountLedgerPdf(Request $request, Account $account)
    {
        $company = Company::getDefault();
        $fy = $company
            ? FinancialYear::where('company_id', $company->id)->where('is_current', true)->first()
            : null;

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get opening balance
        $openingBalance = $this->ledgerService->getBalance(
            $company->id,
            $account->id,
            null,
            date('Y-m-d', strtotime($startDate . ' -1 day'))
        );

        // Get ledger entries
        $entries = LedgerEntry::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->with(['party'])
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance['balance_type'] === 'Dr'
            ? $openingBalance['balance']
            : -$openingBalance['balance'];

        foreach ($entries as $entry) {
            $runningBalance += (float)$entry->debit - (float)$entry->credit;
            $entry->running_balance = abs($runningBalance);
            $entry->balance_type = $runningBalance >= 0 ? 'Dr' : 'Cr';
        }

        $closingBalance = [
            'balance'      => abs($runningBalance),
            'balance_type' => $runningBalance >= 0 ? 'Dr' : 'Cr',
        ];

        $pdf = Pdf::loadView('panel.ledgers.pdf.account-ledger', compact(
            'account',
            'company',
            'entries',
            'openingBalance',
            'closingBalance',
            'startDate',
            'endDate'
        ))
        ->setPaper('a4', 'portrait');

        return $pdf->download("Ledger-{$account->name}-" . date('Y-m-d') . '.pdf');
    }
}
