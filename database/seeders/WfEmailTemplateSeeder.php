<?php

namespace Database\Seeders;

use App\Models\WfEmailTemplate;
use Illuminate\Database\Seeder;

class WfEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        WfEmailTemplate::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $templates = [
            [
                'name' => 'Action Required - Standard',
                'slug' => 'action-required-standard',
                'subject' => 'Action Required: {{action_name}} on {{document_name}}',
                'category' => 'notification',
                'is_default' => true,
                'variables' => ['action_name', 'stage_name', 'document_name', 'user_name', 'workflow_name', 'due_date', 'app_url'],
                'body_html' => $this->standardTemplate(),
            ],
            [
                'name' => 'Approval Request',
                'slug' => 'approval-request',
                'subject' => 'Approval Needed: {{document_name}} - {{stage_name}}',
                'category' => 'approval',
                'variables' => ['action_name', 'stage_name', 'document_name', 'user_name', 'workflow_name', 'submitted_by', 'due_date', 'app_url'],
                'body_html' => $this->approvalTemplate(),
            ],
            [
                'name' => 'Escalation Alert',
                'slug' => 'escalation-alert',
                'subject' => '⚠ Escalation: {{document_name}} pending at {{stage_name}}',
                'category' => 'escalation',
                'variables' => ['action_name', 'stage_name', 'document_name', 'user_name', 'workflow_name', 'pending_since', 'escalation_hours', 'app_url'],
                'body_html' => $this->escalationTemplate(),
            ],
            [
                'name' => 'Daily Reminder',
                'slug' => 'daily-reminder',
                'subject' => 'Reminder: {{pending_count}} items pending your action',
                'category' => 'reminder',
                'variables' => ['user_name', 'pending_count', 'pending_items', 'app_url'],
                'body_html' => $this->reminderTemplate(),
            ],
            [
                'name' => 'Stage Completed',
                'slug' => 'stage-completed',
                'subject' => '✓ {{stage_name}} completed for {{document_name}}',
                'category' => 'notification',
                'variables' => ['action_name', 'stage_name', 'document_name', 'completed_by', 'next_stage', 'workflow_name', 'app_url'],
                'body_html' => $this->stageCompletedTemplate(),
            ],
            [
                'name' => 'Rejection Notice',
                'slug' => 'rejection-notice',
                'subject' => '✗ Rejected: {{document_name}} at {{stage_name}}',
                'category' => 'approval',
                'variables' => ['action_name', 'stage_name', 'document_name', 'rejected_by', 'rejection_reason', 'workflow_name', 'app_url'],
                'body_html' => $this->rejectionTemplate(),
            ],
        ];

        foreach ($templates as $t) {
            WfEmailTemplate::create(array_merge(['is_active' => true, 'is_default' => false], $t));
        }

        $this->command->info('✓ Email templates seeded: ' . WfEmailTemplate::count() . ' templates.');
    }

    private function baseWrapper(string $content): string
    {
        return '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e7e5e4;border-radius:12px;overflow:hidden;">'
            . '<div style="background:#7f1d1d;padding:24px 32px;">'
            . '<h1 style="margin:0;color:#ffffff;font-size:18px;font-weight:700;">{{workflow_name}}</h1>'
            . '</div>'
            . '<div style="padding:32px;">' . $content . '</div>'
            . '<div style="background:#fafaf9;padding:16px 32px;border-top:1px solid #e7e5e4;text-align:center;">'
            . '<p style="margin:0;font-size:12px;color:#a8a29e;">This is an automated notification from WolfBooks Workflow System.</p>'
            . '</div></div>';
    }

    private function standardTemplate(): string
    {
        return $this->baseWrapper(
            '<p style="margin:0 0 16px;font-size:15px;color:#292524;">Hi <strong>{{user_name}}</strong>,</p>'
            . '<p style="margin:0 0 24px;font-size:14px;color:#57534e;">An action requires your attention:</p>'
            . '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin:0 0 24px;">'
            . '<table style="width:100%;font-size:13px;color:#44403c;">'
            . '<tr><td style="padding:4px 0;font-weight:600;width:120px;">Action:</td><td>{{action_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Document:</td><td>{{document_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Stage:</td><td>{{stage_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Due:</td><td>{{due_date}}</td></tr>'
            . '</table></div>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#7f1d1d;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">Take Action →</a>'
        );
    }

    private function approvalTemplate(): string
    {
        return $this->baseWrapper(
            '<p style="margin:0 0 16px;font-size:15px;color:#292524;">Hi <strong>{{user_name}}</strong>,</p>'
            . '<p style="margin:0 0 24px;font-size:14px;color:#57534e;">A document has been submitted for your approval:</p>'
            . '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:0 0 24px;">'
            . '<table style="width:100%;font-size:13px;color:#44403c;">'
            . '<tr><td style="padding:4px 0;font-weight:600;width:120px;">Document:</td><td>{{document_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Stage:</td><td>{{stage_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Submitted by:</td><td>{{submitted_by}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Due by:</td><td>{{due_date}}</td></tr>'
            . '</table></div>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#15803d;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;margin-right:8px;">Approve</a>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#b91c1c;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">Reject</a>'
        );
    }

    private function escalationTemplate(): string
    {
        return $this->baseWrapper(
            '<p style="margin:0 0 16px;font-size:15px;color:#292524;">Hi <strong>{{user_name}}</strong>,</p>'
            . '<p style="margin:0 0 24px;font-size:14px;color:#b91c1c;font-weight:600;">⚠ This item has been escalated due to inaction.</p>'
            . '<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:16px;margin:0 0 24px;">'
            . '<table style="width:100%;font-size:13px;color:#44403c;">'
            . '<tr><td style="padding:4px 0;font-weight:600;width:130px;">Document:</td><td>{{document_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Stage:</td><td>{{stage_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Pending since:</td><td>{{pending_since}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Escalation after:</td><td>{{escalation_hours}} hours</td></tr>'
            . '</table></div>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#b45309;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">Review Now →</a>'
        );
    }

    private function reminderTemplate(): string
    {
        return $this->baseWrapper(
            '<p style="margin:0 0 16px;font-size:15px;color:#292524;">Hi <strong>{{user_name}}</strong>,</p>'
            . '<p style="margin:0 0 24px;font-size:14px;color:#57534e;">You have <strong>{{pending_count}}</strong> item(s) pending your action:</p>'
            . '<div style="margin:0 0 24px;">{{pending_items}}</div>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#7f1d1d;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">View Dashboard →</a>'
        );
    }

    private function stageCompletedTemplate(): string
    {
        return $this->baseWrapper(
            '<p style="margin:0 0 16px;font-size:15px;color:#292524;">Hi <strong>{{user_name}}</strong>,</p>'
            . '<p style="margin:0 0 24px;font-size:14px;color:#57534e;">A stage has been completed and the document is moving forward:</p>'
            . '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:0 0 24px;">'
            . '<table style="width:100%;font-size:13px;color:#44403c;">'
            . '<tr><td style="padding:4px 0;font-weight:600;width:130px;">Document:</td><td>{{document_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Completed stage:</td><td>{{stage_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Completed by:</td><td>{{completed_by}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Next stage:</td><td>{{next_stage}}</td></tr>'
            . '</table></div>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#7f1d1d;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">View Document →</a>'
        );
    }

    private function rejectionTemplate(): string
    {
        return $this->baseWrapper(
            '<p style="margin:0 0 16px;font-size:15px;color:#292524;">Hi <strong>{{user_name}}</strong>,</p>'
            . '<p style="margin:0 0 24px;font-size:14px;color:#b91c1c;">Your document has been rejected:</p>'
            . '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:16px;margin:0 0 24px;">'
            . '<table style="width:100%;font-size:13px;color:#44403c;">'
            . '<tr><td style="padding:4px 0;font-weight:600;width:130px;">Document:</td><td>{{document_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Stage:</td><td>{{stage_name}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Rejected by:</td><td>{{rejected_by}}</td></tr>'
            . '<tr><td style="padding:4px 0;font-weight:600;">Reason:</td><td>{{rejection_reason}}</td></tr>'
            . '</table></div>'
            . '<a href="{{app_url}}" style="display:inline-block;background:#7f1d1d;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">Review & Resubmit →</a>'
        );
    }
}
