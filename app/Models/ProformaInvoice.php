<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProformaInvoice extends Model
{
    protected $fillable = [
        'company_id', 'financial_year_id', 'proforma_number', 'proforma_date', 'due_date',
        'party_id', 'billing_address', 'shipping_address',
        'reference_number', 'place_of_supply',
        'subtotal', 'discount_amount', 'taxable_amount',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'cess_amount',
        'total_tax', 'grand_total', 'amount_paid', 'amount_due',
        'is_igst', 'status', 'current_approval_level', 'max_approval_level',
        'submitted_by', 'submitted_at',
        'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'cancelled_by', 'cancelled_at', 'cancel_reason',
        'is_converted', 'converted_to_invoice_id',
        'notes', 'terms', 'narration', 'created_by',
    ];

    protected $casts = [
        'proforma_date'  => 'date',
        'due_date'       => 'date',
        'submitted_at'   => 'datetime',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'cancelled_at'   => 'datetime',
        'is_igst'        => 'boolean',
        'is_converted'   => 'boolean',
        'subtotal'       => 'decimal:2',
        'discount_amount'=> 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount'    => 'decimal:2',
        'sgst_amount'    => 'decimal:2',
        'igst_amount'    => 'decimal:2',
        'cess_amount'    => 'decimal:2',
        'total_tax'      => 'decimal:2',
        'grand_total'    => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'amount_due'     => 'decimal:2',
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

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class, 'converted_to_invoice_id');
    }

    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class, 'document_id')
            ->where('document_type', 'proforma')
            ->with('user')
            ->orderBy('level')
            ->orderBy('created_at');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isSubmitted(): bool { return $this->status === 'submitted'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canSubmit(): bool
    {
        return $this->status === 'draft';
    }

    public function canApprove(): bool
    {
        return $this->status === 'submitted';
    }

    public function canReject(): bool
    {
        return $this->status === 'submitted';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'submitted', 'approved']);
    }

    public function canConvert(): bool
    {
        return $this->status === 'approved' && !$this->is_converted;
    }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'draft'     => ['label' => 'Draft',     'class' => 'bg-stone-100 text-stone-600'],
            'submitted' => ['label' => 'Submitted', 'class' => 'bg-blue-50 text-blue-700'],
            'approved'  => ['label' => 'Approved',  'class' => 'bg-green-50 text-green-700'],
            'rejected'  => ['label' => 'Rejected',  'class' => 'bg-red-50 text-red-700'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-amber-50 text-amber-700'],
            default     => ['label' => ucfirst($this->status), 'class' => 'bg-stone-100 text-stone-600'],
        };
    }

    // ── Number generation ────────────────────────────────────────────────────

    public static function generateNumber(int $companyId): string
    {
        $setting = NumberingSetting::where('company_id', $companyId)
            ->where('document_type', 'proforma')
            ->lockForUpdate()
            ->first();

        if (! $setting) {
            return 'PF/' . now()->format('Y') . '/' . str_pad(1, 4, '0', STR_PAD_LEFT);
        }

        $number = $setting->buildPreview();
        $setting->increment('next_number');

        return $number;
    }
}
