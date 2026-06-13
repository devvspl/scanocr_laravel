<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Required</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f4; color: #1c1917; margin: 0; padding: 40px 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">

        <!-- Header -->
        <div style="background-color: #7f1d1d; padding: 28px 24px; text-align: center;">
            <h2 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">{{ config('app.name') }}</h2>
            <p style="margin: 8px 0 0; color: #fecaca; font-size: 13px;">Approval Request</p>
        </div>

        <!-- Body -->
        <div style="padding: 36px 32px;">
            <p style="margin: 0 0 8px; font-size: 15px; color: #44403c;">
                Hi <strong>{{ $approver->name }}</strong>,
            </p>
            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #44403c;">
                A sales invoice requires your approval at <strong>{{ $levelName }}</strong>.
            </p>

            <!-- Invoice details card -->
            <div style="background-color: #fafaf9; border: 1px solid #e7e5e4; border-radius: 10px; padding: 20px; margin-bottom: 28px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Invoice #</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1c1917; font-weight: 700; text-align: right; font-family: monospace;">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Customer</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1c1917; font-weight: 600; text-align: right;">{{ $invoice->party->display_name ?? $invoice->party->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Invoice Date</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1c1917; text-align: right;">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Amount</td>
                        <td style="padding: 6px 0; font-size: 18px; color: #7f1d1d; font-weight: 800; text-align: right;">₹ {{ number_format((float)$invoice->grand_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Submitted By</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1c1917; text-align: right;">{{ $invoice->submitter->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Approval Level</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1c1917; text-align: right;">{{ $levelName }}</td>
                    </tr>
                </table>
            </div>

            <!-- Action buttons -->
            <div style="text-align: center; margin-bottom: 28px;">
                <p style="margin: 0 0 16px; font-size: 13px; color: #78716c;">Take action directly from this email:</p>

                @if($requireSignature)
                {{-- When digital signature is required, show Sign & Approve as primary --}}
                <div style="margin-bottom: 16px;">
                    <a href="{{ $signUrl }}" style="display: inline-block; background-color: #7f1d1d; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 14px 36px; border-radius: 8px; letter-spacing: 0.3px;">
                        ✍️ Sign & Approve Digitally
                    </a>
                    <p style="margin: 8px 0 0; font-size: 11px; color: #a8a29e;">Digital signature is required for this approval level</p>
                </div>
                <table style="margin: 0 auto;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <a href="{{ $rejectUrl }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 10px 24px; border-radius: 8px; letter-spacing: 0.3px;">
                                ✗ Reject
                            </a>
                        </td>
                    </tr>
                </table>
                @else
                {{-- Normal approval buttons + optional sign link --}}
                <table style="margin: 0 auto;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding-right: 12px;">
                            <a href="{{ $approveUrl }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 8px; letter-spacing: 0.3px;">
                                ✓ Approve
                            </a>
                        </td>
                        <td>
                            <a href="{{ $rejectUrl }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 8px; letter-spacing: 0.3px;">
                                ✗ Reject
                            </a>
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 12px;">
                    <a href="{{ $signUrl }}" style="font-size: 12px; color: #78716c; text-decoration: underline;">
                        Or sign digitally →
                    </a>
                </div>
                @endif
            </div>

            <!-- View link -->
            <div style="text-align: center; margin-bottom: 24px;">
                <a href="{{ $viewUrl }}" style="display: inline-block; background-color: #f5f5f4; color: #44403c; text-decoration: none; font-size: 13px; font-weight: 600; padding: 10px 24px; border-radius: 8px; border: 1px solid #e7e5e4;">
                    View Full Invoice →
                </a>
            </div>

            <div style="border-top: 1px solid #e7e5e4; padding-top: 16px;">
                <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #a8a29e;">
                    This approval link is unique to you and expires once used. If you did not expect this request, please contact your administrator.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div style="background-color: #f5f5f4; padding: 20px 24px; text-align: center; border-top: 1px solid #e7e5e4;">
            <p style="margin: 0; font-size: 11px; color: #a8a29e;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>

    </div>
</body>
</html>
