<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $fillable = [
        'company_id', 'financial_year_id', 'receipt_number', 'receipt_date',
        'party_id', 'amount', 'payment_method', 'payment_reference', 'payment_date',
        'bank_name', 'bank_account', 'sale_invoice_id',
        'description', 'narration',
        'status', 'current_approval_level', 'max_approval_level',
        'submitted_by', 'submitted_at',
        'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'cancelled_by', 'cancelled_at', 'cancel_reason',
        'created_by',
    ];

    protected $casts = [
        'receipt_date'  => 'date',
        'payment_date'  => 'date',
        'amount'        => 'decimal:2',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'cancelled_at'  => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function financialYear(): BelongsTo { return $this->belongsTo(FinancialYear::class); }
    public function party(): BelongsTo { return $this->belongsTo(Party::class); }
    public function saleInvoice(): BelongsTo { return $this->belongsTo(SaleInvoice::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }

    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class, 'document_id')
            ->where('document_type', 'receipt')
            ->with('user')->orderBy('level')->orderBy('created_at');
    }

    public function isDraft(): bool     { return $this->status === 'draft'; }
    public function isSubmitted(): bool { return $this->status === 'submitted'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function canEdit(): bool     { return in_array($this->status, ['draft', 'rejected']); }
    public function canSubmit(): bool   { return $this->status === 'draft'; }
    public function canApprove(): bool  { return $this->status === 'submitted'; }
    public function canReject(): bool   { return $this->status === 'submitted'; }
    public function canCancel(): bool   { return in_array($this->status, ['draft', 'submitted', 'approved']); }

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

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'upi'           => 'UPI',
            'card'          => 'Card',
            'other'         => 'Other',
            default         => ucfirst($this->payment_method),
        };
    }

    public static function generateNumber(int $companyId): string
    {
        $setting = NumberingSetting::where('company_id', $companyId)
            ->where('document_type', 'receipt')
            ->lockForUpdate()
            ->first();

        if (!$setting) {
            return 'RCP/' . now()->format('Y') . '/' . str_pad(1, 4, '0', STR_PAD_LEFT);
        }

        $number = $setting->buildPreview();
        $setting->increment('next_number');
        return $number;
    }
}
