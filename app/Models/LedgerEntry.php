<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    protected $fillable = [
        'company_id', 'financial_year_id', 'entry_date',
        'voucher_type', 'voucher_number',
        'document_id', 'document_type',
        'account_id', 'party_id', 'account_name',
        'debit', 'credit',
        'narration', 'description',
        'is_reconciled', 'reconciled_date',
        'created_by',
    ];

    protected $casts = [
        'entry_date'       => 'date',
        'reconciled_date'  => 'date',
        'debit'            => 'decimal:2',
        'credit'           => 'decimal:2',
        'is_reconciled'    => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Helper Methods ───────────────────────────────────────────────────────

    public function getBalanceEffect(): float
    {
        return (float)$this->debit - (float)$this->credit;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    public function scopeByVoucherType($query, string $voucherType)
    {
        return $query->where('voucher_type', $voucherType);
    }
}
