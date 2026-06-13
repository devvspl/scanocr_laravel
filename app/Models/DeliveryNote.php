<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $fillable = [
        'company_id', 'financial_year_id', 'delivery_number', 'dispatch_date',
        'party_id', 'receiver_name', 'receiver_phone', 'delivery_address',
        'order_number', 'sale_invoice_id', 'proforma_invoice_id',
        'transport_mode', 'transporter_name', 'vehicle_number',
        'driver_name', 'driver_phone', 'tracking_number',
        'total_packages', 'total_weight',
        'status', 'current_approval_level', 'max_approval_level',
        'submitted_by', 'submitted_at',
        'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'cancelled_by', 'cancelled_at', 'cancel_reason',
        'received_by', 'received_at', 'receiver_remarks',
        'notes', 'narration', 'created_by',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'cancelled_at'  => 'datetime',
        'received_at'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function financialYear(): BelongsTo { return $this->belongsTo(FinancialYear::class); }
    public function party(): BelongsTo { return $this->belongsTo(Party::class); }
    public function items(): HasMany { return $this->hasMany(DeliveryNoteItem::class)->orderBy('sort_order'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function saleInvoice(): BelongsTo { return $this->belongsTo(SaleInvoice::class); }
    public function proformaInvoice(): BelongsTo { return $this->belongsTo(ProformaInvoice::class); }

    public function approvalLogs()
    {
        return $this->hasMany(ApprovalLog::class, 'document_id')
            ->where('document_type', 'delivery_note')
            ->with('user')->orderBy('level')->orderBy('created_at');
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function isDraft(): bool      { return $this->status === 'draft'; }
    public function isSubmitted(): bool  { return $this->status === 'submitted'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isDelivered(): bool  { return $this->status === 'delivered'; }
    public function canEdit(): bool      { return in_array($this->status, ['draft', 'rejected']); }
    public function canSubmit(): bool    { return $this->status === 'draft'; }
    public function canApprove(): bool   { return $this->status === 'submitted'; }
    public function canReject(): bool    { return $this->status === 'submitted'; }
    public function canCancel(): bool    { return in_array($this->status, ['draft', 'submitted', 'approved']); }
    public function canMarkDelivered(): bool { return $this->status === 'approved'; }

    public function statusBadge(): array
    {
        return match ($this->status) {
            'draft'     => ['label' => 'Draft',     'class' => 'bg-stone-100 text-stone-600'],
            'submitted' => ['label' => 'Submitted', 'class' => 'bg-blue-50 text-blue-700'],
            'approved'  => ['label' => 'Dispatched','class' => 'bg-green-50 text-green-700'],
            'rejected'  => ['label' => 'Rejected',  'class' => 'bg-red-50 text-red-700'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-amber-50 text-amber-700'],
            'delivered' => ['label' => 'Delivered', 'class' => 'bg-emerald-50 text-emerald-700'],
            default     => ['label' => ucfirst($this->status), 'class' => 'bg-stone-100 text-stone-600'],
        };
    }

    // ── Number generation ─────────────────────────────────────────────────────

    public static function generateNumber(int $companyId): string
    {
        $setting = NumberingSetting::where('company_id', $companyId)
            ->where('document_type', 'delivery_note')
            ->lockForUpdate()
            ->first();

        if (!$setting) {
            return 'DN/' . now()->format('Y') . '/' . str_pad(1, 4, '0', STR_PAD_LEFT);
        }

        $number = $setting->buildPreview();
        $setting->increment('next_number');
        return $number;
    }

    // ── Computed: total items qty ─────────────────────────────────────────────

    public function getTotalQtyAttribute(): float
    {
        return $this->items->sum('qty');
    }
}
