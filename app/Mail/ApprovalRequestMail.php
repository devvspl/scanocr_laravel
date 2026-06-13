<?php

namespace App\Mail;

use App\Models\ApprovalLog;
use App\Models\SaleInvoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public SaleInvoice $invoice;
    public User $approver;
    public ApprovalLog $log;
    public string $approveUrl;
    public string $rejectUrl;
    public string $signUrl;
    public string $viewUrl;
    public string $levelName;
    public bool $requireSignature;

    public function __construct(SaleInvoice $invoice, User $approver, ApprovalLog $log, bool $requireSignature = false)
    {
        $this->invoice  = $invoice;
        $this->approver = $approver;
        $this->log      = $log;
        $this->levelName = $log->level_name ?? "Level {$log->level}";
        $this->requireSignature = $requireSignature;

        $baseUrl = config('app.url');
        $token   = $log->token;

        $this->approveUrl = "{$baseUrl}/approval/action/{$token}/approve";
        $this->rejectUrl  = "{$baseUrl}/approval/action/{$token}/reject";
        $this->signUrl    = "{$baseUrl}/approval/sign/{$token}";
        $this->viewUrl    = "{$baseUrl}/sales/invoices/{$invoice->id}";
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Approval Required: Invoice {$this->invoice->invoice_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approval-request',
        );
    }
}
