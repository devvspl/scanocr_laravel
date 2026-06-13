<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WfStageAction extends Model
{
    protected $table = 'wf_stage_actions';

    // All valid action_keys grouped by stage system_key
    const ACTIONS_BY_STAGE = [
        'scanner'           => ['upload_file', 'upload_supporting', 'final_submit', 'edit_rejected', 'delete_scan'],
        'extraction'        => ['trigger_extraction'],
        'doc_classifier'    => ['reject_scan', 'classify_document', 'fill_received_date', 'reclassify_document'],
        'dms_punching'      => ['reject_classify', 'punch_document', 'skip_bill_approval', 'repunch_document'],
        'punching_approval' => ['approve_punch', 'reject_punch', 'bypass_approval'],
        'bill_approver'     => ['approve_bill', 'reject_bill'],
        'finance_punching'  => ['additional_punch', 'reject_to_bill', 'repunch_finance'],
        'punch_approver'    => ['approve_final', 'reject_final'],
        'focus_export'      => ['export_csv'],
    ];

    // Default display labels for each action_key
    const DEFAULT_LABELS = [
        'upload_file'         => 'Upload File',
        'upload_supporting'   => 'Upload Supporting File',
        'final_submit'        => 'Final Submit',
        'edit_rejected'       => 'Edit Rejected Scan',
        'delete_scan'         => 'Delete Scan',
        'trigger_extraction'  => 'Trigger Extraction',
        'reject_scan'         => 'Reject Scan',
        'classify_document'   => 'Classify Document',
        'fill_received_date'  => 'Fill Document Received Date',
        'reclassify_document' => 'Reclassify Document',
        'reject_classify'     => 'Reject Classification',
        'punch_document'      => 'Punch Document',
        'skip_bill_approval'  => 'Skip Bill Approval',
        'repunch_document'    => 'Repunch Document',
        'approve_punch'       => 'Approve',
        'reject_punch'        => 'Reject',
        'bypass_approval'     => 'Bypass Approval',
        'approve_bill'        => 'Approve Bill',
        'reject_bill'         => 'Reject Bill',
        'additional_punch'    => 'Additional Punch',
        'reject_to_bill'      => 'Reject to Bill Approver',
        'repunch_finance'     => 'Repunch Document',
        'approve_final'       => 'Approve',
        'reject_final'        => 'Reject',
        'export_csv'          => 'Export CSV',
    ];

    protected $fillable = [
        'stage_id',
        'action_key',
        'display_label',
        'is_active',
        'requires_remark',
        'remark_label',
        'confirm_before_action',
        'confirm_message',
        'position',
        'icon',
        'button_style',
        'next_stage_key',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'requires_remark'       => 'boolean',
        'confirm_before_action' => 'boolean',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WfStage::class, 'stage_id');
    }
}
