<?php

namespace App\Console\Commands;

use App\Models\SaleInvoice;
use App\Models\Receipt;
use App\Models\CreditNote;
use App\Services\LedgerService;
use Illuminate\Console\Command;

class SyncLedgerEntriesCommand extends Command
{
    protected $signature = 'ledger:sync {--fresh : Clear all existing ledger entries before syncing}';
    protected $description = 'Sync ledger entries from existing transactions (invoices, receipts, credit notes)';

    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        parent::__construct();
        $this->ledgerService = $ledgerService;
    }

    public function handle()
    {
        $this->info('Starting ledger synchronization...');

        if ($this->option('fresh')) {
            $this->warn('Clearing all existing ledger entries...');
            \App\Models\LedgerEntry::truncate();
            $this->info('Ledger cleared.');
        }

        // Sync Sales Invoices
        $this->info('Syncing Sales Invoices...');
        $invoices = SaleInvoice::where('status', 'approved')->with('party')->get();
        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();

        foreach ($invoices as $invoice) {
            $this->ledgerService->postSalesInvoice($invoice);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Synced {$invoices->count()} sales invoices.");

        // Sync Receipts
        $this->info('Syncing Receipts...');
        $receipts = Receipt::where('status', 'approved')->with('party')->get();
        $bar = $this->output->createProgressBar($receipts->count());
        $bar->start();

        foreach ($receipts as $receipt) {
            $this->ledgerService->postReceipt($receipt);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Synced {$receipts->count()} receipts.");

        // Sync Credit Notes
        $this->info('Syncing Credit Notes...');
        $creditNotes = CreditNote::where('status', 'approved')->with('party')->get();
        $bar = $this->output->createProgressBar($creditNotes->count());
        $bar->start();

        foreach ($creditNotes as $creditNote) {
            $this->ledgerService->postCreditNote($creditNote);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Synced {$creditNotes->count()} credit notes.");

        $this->newLine();
        $this->info('✓ Ledger synchronization completed successfully!');
        
        return 0;
    }
}
