<?php

namespace Database\Seeders;

use App\Models\WfActionDefinition;
use Illuminate\Database\Seeder;

class WfActionDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        WfActionDefinition::truncate();
        \App\Models\WfStageActionMap::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $actions = [

            // ═══════════════════════════════════════════════════════════════
            // ENTRY LEVEL
            // ═══════════════════════════════════════════════════════════════
            [
                'group' => 'Entry Level',
                'action_key' => 'create_entry',
                'display_label' => 'Create Entry',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                'button_style' => 'primary',
                'button_color' => '#b91c1c',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'draft'],
                'is_system' => true,
            ],
            [
                'group' => 'Entry Level',
                'action_key' => 'save_draft',
                'display_label' => 'Save Draft',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>',
                'button_style' => 'info',
                'button_color' => '#0284c7',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'draft'],
            ],
            [
                'group' => 'Entry Level',
                'action_key' => 'edit_entry',
                'display_label' => 'Edit Entry',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
                'button_style' => 'primary',
                'button_color' => '#b91c1c',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'in_progress'],
            ],
            [
                'group' => 'Entry Level',
                'action_key' => 'delete_entry',
                'display_label' => 'Delete',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
                'button_style' => 'danger',
                'button_color' => '#dc2626',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'deleted'],
                'requires_confirmation' => true,
                'confirm_message' => 'Are you sure you want to delete this entry?',
            ],

            // ═══════════════════════════════════════════════════════════════
            // STAGE TRANSITION
            // ═══════════════════════════════════════════════════════════════
            [
                'group' => 'Stage Transition',
                'action_key' => 'send_to_next',
                'display_label' => 'Send to Next Stage',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>',
                'button_style' => 'success',
                'button_color' => '#16a34a',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'in_progress', 'move_to' => 'next'],
                'is_system' => true,
            ],
            [
                'group' => 'Stage Transition',
                'action_key' => 'send_back',
                'display_label' => 'Send Back',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>',
                'button_style' => 'warning',
                'button_color' => '#d97706',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'returned', 'move_to' => 'previous'],
                'requires_remark' => true,
            ],

            // ═══════════════════════════════════════════════════════════════
            // APPROVAL
            // ═══════════════════════════════════════════════════════════════
            [
                'group' => 'Approval',
                'action_key' => 'send_for_approval',
                'display_label' => 'Send for Approval',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>',
                'button_style' => 'primary',
                'button_color' => '#7c3aed',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'pending_approval', 'move_to' => 'next'],
                'is_system' => true,
            ],
            [
                'group' => 'Approval',
                'action_key' => 'approve',
                'display_label' => 'Approve',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'button_style' => 'success',
                'button_color' => '#16a34a',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'approved', 'move_to' => 'next'],
                'is_system' => true,
            ],
            [
                'group' => 'Approval',
                'action_key' => 'reject',
                'display_label' => 'Reject',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'button_style' => 'danger',
                'button_color' => '#dc2626',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'rejected', 'move_to' => 'previous'],
                'requires_remark' => true,
                'is_system' => true,
            ],
            [
                'group' => 'Approval',
                'action_key' => 'hold',
                'display_label' => 'Hold',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'button_style' => 'warning',
                'button_color' => '#d97706',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'on_hold'],
                'requires_remark' => true,
            ],

            // ═══════════════════════════════════════════════════════════════
            // VERIFICATION
            // ═══════════════════════════════════════════════════════════════
            [
                'group' => 'Verification',
                'action_key' => 'verify_entry',
                'display_label' => 'Verify',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
                'button_style' => 'success',
                'button_color' => '#0d9488',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'verified', 'move_to' => 'next'],
            ],
            [
                'group' => 'Verification',
                'action_key' => 'mark_punched',
                'display_label' => 'Mark as Punched',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                'button_style' => 'success',
                'button_color' => '#16a34a',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'punched', 'move_to' => 'next'],
            ],

            // ═══════════════════════════════════════════════════════════════
            // STATUS
            // ═══════════════════════════════════════════════════════════════
            [
                'group' => 'Status',
                'action_key' => 'mark_completed',
                'display_label' => 'Mark Completed',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                'button_style' => 'success',
                'button_color' => '#16a34a',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'completed'],
            ],
            [
                'group' => 'Status',
                'action_key' => 'mark_cancelled',
                'display_label' => 'Cancel',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
                'button_style' => 'danger',
                'button_color' => '#dc2626',
                'logic_type' => 'status_change',
                'logic_config' => ['set_status' => 'cancelled'],
                'requires_confirmation' => true,
                'confirm_message' => 'Are you sure you want to cancel?',
            ],
        ];

        $sortOrder = 0;
        foreach ($actions as $action) {
            WfActionDefinition::create(array_merge([
                'requires_remark' => false,
                'requires_confirmation' => false,
                'confirm_message' => null,
                'is_system' => false,
                'is_active' => true,
                'sort_order' => $sortOrder++,
            ], $action));
        }

        $this->command->info('✓ Workflow Action Definitions seeded: ' . WfActionDefinition::count() . ' actions.');
    }
}
