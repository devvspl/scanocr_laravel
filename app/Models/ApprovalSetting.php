<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalSetting extends Model
{
    protected $fillable = [
        'company_id',
        'document_type',
        'is_enabled',
        'approval_mode',
        'levels_count',
        'levels',
    ];

    protected $casts = [
        'levels'       => 'array',
        'levels_count' => 'integer',
        'is_enabled'   => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the approval setting for a given document type and company.
     */
    public static function getFor(string $documentType, int $companyId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->first();
    }

    /**
     * Check if digital approval is enabled for this document type.
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Check if approval is required.
     */
    public function isRequired(): bool
    {
        return $this->is_enabled && $this->approval_mode === 'required';
    }

    public function isAutoApproved(): bool
    {
        return $this->is_enabled && $this->approval_mode === 'auto_approved';
    }

    public function isNoApproval(): bool
    {
        return !$this->is_enabled || $this->approval_mode === 'no_approval';
    }

    /**
     * Get level config by level number (1-indexed).
     */
    public function getLevel(int $level): ?array
    {
        $levels = $this->levels ?? [];
        return $levels[$level - 1] ?? null;
    }

    /**
     * Get approver IDs for a specific level.
     */
    public function getApproverIds(int $level): array
    {
        $levelConfig = $this->getLevel($level);
        return $levelConfig['approver_ids'] ?? [];
    }

    /**
     * Check if a user can approve at a specific level.
     */
    public function canUserApproveAtLevel(int $userId, int $level): bool
    {
        return in_array($userId, $this->getApproverIds($level));
    }
}
